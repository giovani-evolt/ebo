<?php

namespace App\Service;

use App\Entity\Amazon\Settlement;
use App\Entity\Amazon\Settlement\TransactionTotal;
use App\Entity\Amazon\Settlement\UnitsSold;
use App\Exceptions\IngestException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Saturio\DuckDB\DuckDB;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class IngestService
{
    protected $tmpFilePath;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $doctrine,
    )
    {
    }

    public function setTmpFilePath($path)
    {
        $this->tmpFilePath = $path;

        return $this;
    }

    protected function getTransactionType($row): mixed{
        switch($row['amount-type']){
            case 'ItemPrice':
                switch($row['amount-description']){
                    case 'Principal':
                    case 'Shipping':
                        return TransactionTotal::TOTAL_GROSS_SALES;
                    case 'Tax':
                    case 'ShippingTax':
                        return TransactionTotal::TOTAL_TAXES;
                    default:
                        return false;    
                }
            case 'ItemFees':
                switch($row['amount-description']){
                    case 'Commision':
                        return TransactionTotal::TOTAL_COMMISIONS_FEES;
                    case 'FBAPerUnitFulfillmentFees':
                    case 'ShippingChargeback':
                        return TransactionTotal::TOTAL_FREIGHT_SHIPPING_COSTS;
                    default:
                        return false;
                }
            case 'Promotion':
                return TransactionTotal::TOTAL_DISCOUNTS;
            case 'Returns':
                return TransactionTotal::TOTAL_RETURNS;
       }

       return false;
    }

    protected function getUnitsSoldQuery($settlementId): string{
        $query = <<<END
            SELECT
                "settlement-id",
                "sku",
                extract("year" from "posted-date") AS year,
                extract("month" from "posted-date") AS month,
                SUM(CAST(amount AS DOUBLE)) AS total_amount,
                SUM(CAST("quantity-purchased" AS INT)) AS total_qty_purchased
            FROM "{$this->tmpFilePath}"
            WHERE 
                "marketplace-name" = 'Amazon.com'
                AND "transaction-type" = 'Order'
                AND "amount-type" = 'ItemPrice'
                AND "amount-description" = 'Principal'
                AND "settlement-id" = '{$settlementId}'
            GROUP BY 1, 2, 3, 4;
        END;

        return $query;
    }

    protected function getTotalsBySettlementQuery(): string{
        $query = <<<END
            SELECT
                "settlement-id",
                SUM(CAST(amount AS DOUBLE)) AS total_amount
            FROM "{$this->tmpFilePath}"
            GROUP BY 
                "settlement-id";
        END;

        return $query;
    }

    protected function getSettlementTotalsQuery($settlementId): string{
        $query = <<<END
            SELECT
                "settlement-id",
                "transaction-type",
                "amount-type",
                "amount-description",
                extract("year" from "posted-date") AS year,
                extract("month" from "posted-date") AS month,
                SUM(CAST(amount AS DOUBLE)) AS total_amount
            FROM "{$this->tmpFilePath}"
            WHERE 
                ("marketplace-name" = 'Amazon.com' OR "marketplace-name" = '')
                AND "transaction-type" = 'Order'
                AND "settlement-id" = '{$settlementId}'
            GROUP BY 
                year,
                month,
                "settlement-id",
                "transaction-type",
                "amount-type",
                "amount-description"
            ORDER BY 
                year,
                month,
                "transaction-type",
                "amount-type",
                "amount-description";
        END;

        return $query;
    }

    protected function getSettlementByQuery($settlementId): string{
        $query = <<<END
            SELECT
                "settlement-id",
                "settlement-start-date",
                "settlement-end-date",
                "deposit-date",
                "total-amount",
                "currency"
            FROM "{$this->tmpFilePath}"
            WHERE 
                "total-amount" IS NOT NULL
                AND "settlement-id" = '{$settlementId}'
        END;

        return $query;
    }

    public function ingestSettlement(): array
    {
        $messages = [];
        $settlements = [];
        $settlementsTotals = [];
        foreach (DuckDB::sql($this->getTotalsBySettlementQuery())->rows(true) as $row) {
            $settlementsTotals[$row['settlement-id']] = $row['total_amount'];

            try{
                $settlement = new Settlement();
                foreach (DuckDB::sql($this->getSettlementByQuery($row['settlement-id']))->rows(true) as $row) {
                    $settlement
                        ->setSettlementId($row['settlement-id'])
                        ->setStartDate(new \DateTime($row['settlement-start-date']))
                        ->setEndDate(new \DateTime($row['settlement-end-date']))
                        ->setDepositDate(new \DateTime($row['deposit-date']))
                        ->setTotalAmount($row['total-amount'])
                        ->setCurrency('USD');

                    if(abs($settlement->getTotalAmount() - $settlementsTotals[$row['settlement-id']]) > 0.01){
                        throw new IngestException(sprintf(
                            "Settlement %s has inconsistent total amount: expected %s but got %s, diff: %f", 
                            $settlement->getSettlementId(),
                            $settlement->getTotalAmount(),
                            $settlement->getSettlementId(),
                            abs($settlement->getTotalAmount() - $settlementsTotals[$row['settlement-id']])
                        ));   
                    }

                    $settlements[$row['settlement-id']] = $settlement;
                    $this->entityManager->persist($settlement);
                }
                
                foreach (DuckDB::sql($this->getSettlementTotalsQuery($settlement->getSettlementId()))->rows(true) as $row) {
                    if($type = $this->getTransactionType($row)){
                        $transactionTotal = new TransactionTotal();
                        $transactionTotal
                            ->setTotalType($type)
                            ->setYear($row['year'])
                            ->setMonth($row['month'])
                            ->setTransactionType($row['transaction-type'])
                            ->setAmountType($row['amount-type'])
                            ->setAmountDescription($row['amount-description'])
                            ->setTotalAmount($row['total_amount'])
                            ->setSettlement($settlement);
                        $settlement->addTransactionTotal($transactionTotal);
                        $this->entityManager->persist($transactionTotal);
                    }
                }

                // UNITS SOLD /////////////////////////////////////////////////////////////////////////////////
                foreach (DuckDB::sql($this->getUnitsSoldQuery($settlement->getSettlementId()))->rows(true) as $row) {
                    $unitsSold = new UnitsSold();
                    $unitsSold
                        ->setSettlement($settlement)
                        ->setYear($row['year'])
                        ->setMonth($row['month'])
                        ->setSku($row['sku'])
                        ->setQuantityPurchased((int)(string)$row['total_qty_purchased'])
                        ->setTotalAmount($row['total_amount']);
                    $this->entityManager->persist($unitsSold);
                }

                $this->entityManager->flush();
            } catch(UniqueConstraintViolationException $e){
                $messages[] = $this->getUniqueIdErrorMessage($e);
                $this->doctrine->resetManager();
            } catch (\Doctrine\DBAL\Exception $e) {
                $messages[] = 'Database error: ' . $e->getMessage();
                $this->doctrine->resetManager();
                //Log Error
            } catch (\Exception $e){
                $messages[] = $e->getMessage();
            }

            $this->entityManager->clear();
        }

        return [
            'messages' => $messages,
            'settlements' => $settlements
        ];
    }

    protected function getUniqueIdErrorMessage($e){
        // Extraer el valor duplicado si aparece en el detalle
        preg_match('/Key \(([^)]+)\)=\(([^)]+)\)/', $e->getMessage(), $matches);
        $field = $matches[1] ?? 'unknown_field';
        $value = $matches[2] ?? 'unknown_value';

        return sprintf(
            'El registro con %s "%s" ya existe y no puede duplicarse.',
            $field,
            $value
        );
    }
}