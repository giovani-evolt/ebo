<?php

namespace App\Entity\Amazon;

use App\Entity\Amazon\Settlement\TransactionTotal;
use App\Entity\Amazon\Settlement\UnitsSold;
use App\Entity\Seller;
use App\Repository\Amazon\SettlementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettlementRepository::class)]
class Settlement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $settlement_id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $end_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $deposit_date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $total_amount = null;

    #[ORM\ManyToOne(inversedBy: 'settlements')]
    private ?Seller $seller = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    /**
     * @var Collection<int, TransactionTotal>
     */
    #[ORM\OneToMany(targetEntity: TransactionTotal::class, mappedBy: 'settlement')]
    private Collection $transactionTotal;

    /**
     * @var Collection<int, UnitsSold>
     */
    #[ORM\OneToMany(targetEntity: UnitsSold::class, mappedBy: 'settlement')]
    private Collection $unitsSolds;

    public function __construct()
    {
        $this->transactionTotal = new ArrayCollection();
        $this->unitsSolds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettlementId(): ?string
    {
        return $this->settlement_id;
    }

    public function setSettlementId(string $settlement_id): static
    {
        $this->settlement_id = $settlement_id;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTime $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->end_date;
    }

    public function setEndDate(\DateTime $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getDepositDate(): ?\DateTime
    {
        return $this->deposit_date;
    }

    public function setDepositDate(\DateTime $deposit_date): static
    {
        $this->deposit_date = $deposit_date;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->total_amount;
    }

    public function setTotalAmount(string $total_amount): static
    {
        $this->total_amount = $total_amount;

        return $this;
    }

    public function getSeller(): ?Seller
    {
        return $this->seller;
    }

    public function setSeller(?Seller $seller): static
    {
        $this->seller = $seller;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Collection<int, TransactionTotal>
     */
    public function getTransactionTotal(): Collection
    {
        return $this->transactionTotal;
    }

    public function addTransactionTotal(TransactionTotal $transactionTotal): static
    {
        if (!$this->transactionTotal->contains($transactionTotal)) {
            $this->transactionTotal->add($transactionTotal);
            $transactionTotal->setSettlement($this);
        }

        return $this;
    }

    public function removeTransactionTotal(TransactionTotal $transactionTotal): static
    {
        if ($this->transactionTotal->removeElement($transactionTotal)) {
            // set the owning side to null (unless already changed)
            if ($transactionTotal->getSettlement() === $this) {
                $transactionTotal->setSettlement(null);
            }
        }

        return $this;
    }

    public function validateTotals(): bool {
        return bccomp($this->getTransactionsTotal(), $this->getTotalAmount(), 2) === 0;
    }

    public function getTransactionsTotal(): float {
        $total = 0;
        foreach($this->getTransactionTotal() as $item){
            $total += $item->getTotalAmount();
        }
        return $total;
    }

    /**
     * @return Collection<int, UnitsSold>
     */
    public function getUnitsSolds(): Collection
    {
        return $this->unitsSolds;
    }

    public function addUnitsSold(UnitsSold $unitsSold): static
    {
        if (!$this->unitsSolds->contains($unitsSold)) {
            $this->unitsSolds->add($unitsSold);
            $unitsSold->setSettlement($this);
        }

        return $this;
    }

    public function removeUnitsSold(UnitsSold $unitsSold): static
    {
        if ($this->unitsSolds->removeElement($unitsSold)) {
            // set the owning side to null (unless already changed)
            if ($unitsSold->getSettlement() === $this) {
                $unitsSold->setSettlement(null);
            }
        }

        return $this;
    }
}
