<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Amazon\Settlement;
use App\Entity\Seller\Csv;
use App\Repository\SellerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiProperty;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: SellerRepository::class)]
#[ApiResource]
class Seller
{
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true, )]
    #[ApiProperty(identifier: true)]
    private ?Uuid $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, Settlement>
     */
    #[ORM\OneToMany(targetEntity: Settlement::class, mappedBy: 'seller')]
    private Collection $settlements;

    /**
     * @var Collection<int, Csv>
     */
    #[ORM\OneToMany(targetEntity: Csv::class, mappedBy: 'seller')]
    private Collection $csvs;

    public function __construct()
    {
        $this->settlements = new ArrayCollection();
        $this->code = Uuid::v4();
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->csvs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?Uuid
    {
        return $this->code;
    }

    public function setCode(Uuid $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection<int, Settlement>
     */
    public function getSettlements(): Collection
    {
        return $this->settlements;
    }

    public function addSettlement(Settlement $settlement): static
    {
        if (!$this->settlements->contains($settlement)) {
            $this->settlements->add($settlement);
            $settlement->setSeller($this);
        }

        return $this;
    }

    public function removeSettlement(Settlement $settlement): static
    {
        if ($this->settlements->removeElement($settlement)) {
            // set the owning side to null (unless already changed)
            if ($settlement->getSeller() === $this) {
                $settlement->setSeller(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Csv>
     */
    public function getCsvs(): Collection
    {
        return $this->csvs;
    }

    public function addCsv(Csv $csv): static
    {
        if (!$this->csvs->contains($csv)) {
            $this->csvs->add($csv);
            $csv->setSeller($this);
        }

        return $this;
    }

    public function removeCsv(Csv $csv): static
    {
        if ($this->csvs->removeElement($csv)) {
            // set the owning side to null (unless already changed)
            if ($csv->getSeller() === $this) {
                $csv->setSeller(null);
            }
        }

        return $this;
    }

    public function getFolder(){
        return sprintf('sellers/%s/', $this->getCode().'/');
    }
}
