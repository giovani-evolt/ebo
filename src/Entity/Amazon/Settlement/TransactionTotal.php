<?php

namespace App\Entity\Amazon\Settlement;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Amazon\Settlement;
use App\Repository\Amazon\Settlement\TransactionTotalRepository;
use App\State\TransactionTotalsProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\TransactionTotalsProvider;
use ApiPlatform\OpenApi\Model;

#[ORM\Entity(repositoryClass: TransactionTotalRepository::class)]
#[ORM\Table(uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_settlement_type_description_year_month', columns: ['settlement_id', 'total_type', 'amount_description', 'year', 'month'])
])]
#[ApiResource(
    normalizationContext: ['groups' => ['settlement:read']],
    denormalizationContext: ['groups' => ['settlement:write']],
    operations: [
        new GetCollection(provider: TransactionTotalsProvider::class),
    ],
    security: "is_granted('ROLE_USER')",
)]
#[ApiFilter(SearchFilter::class, properties: [
    'totalType' => 'exact',
    'year' => 'exact',
    'month' => 'exact'
])]
class TransactionTotal
{
    const TOTAL_GROSS_SALES             = 'GRSS';
    const TOTAL_TAXES                   = 'TXS';
    const TOTAL_DISCOUNTS               = 'DSCN';
    const TOTAL_RETURNS                 = 'RTRN';
    const TOTAL_COMMISIONS_FEES         = 'COMM';
    const TOTAL_FREIGHT_SHIPPING_COSTS  = 'FRSH';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactionTotal')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Settlement $settlement = null;

    #[ORM\Column(length: 255)]
    #[Groups(['settlement:read'])]
    private ?string $transactionType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['settlement:read'])]
    private ?string $amountType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['settlement:read'])]
    private ?string $amountDescription = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['settlement:read'])]
    private ?string $totalAmount = null;

    #[ORM\Column]
    #[Groups(['settlement:read'])]
    private ?int $year = null;

    #[ORM\Column]
    #[Groups(['settlement:read'])]
    private ?int $month = null;

    #[ORM\Column(length: 4)]
    #[Groups(['settlement:read'])]
    private ?string $totalType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettlement(): ?Settlement
    {
        return $this->settlement;
    }

    public function setSettlement(?Settlement $settlement): static
    {
        $this->settlement = $settlement;

        return $this;
    }

    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    public function setTransactionType(string $transactionType): static
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    public function getAmountType(): ?string
    {
        return $this->amountType;
    }

    public function setAmountType(string $amountType): static
    {
        $this->amountType = $amountType;

        return $this;
    }

    public function getAmountDescription(): ?string
    {
        return $this->amountDescription;
    }

    public function setAmountDescription(string $amountDescription): static
    {
        $this->amountDescription = $amountDescription;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getTotalType(): ?string
    {
        return $this->totalType;
    }

    public function setTotalType(string $totalType): static
    {
        $this->totalType = $totalType;

        return $this;
    }
}
