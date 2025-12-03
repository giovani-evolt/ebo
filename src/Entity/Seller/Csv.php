<?php

namespace App\Entity\Seller;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use App\Controller\UploadCsvController;
use App\Entity\Amazon\Settlement;
use App\Entity\Seller;
use App\Repository\Seller\CsvRepository;
use App\State\CsvStateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use function Zenstruck\Foundry\Persistence\persist;

#[ORM\Entity(repositoryClass: CsvRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read']], 
    outputFormats: ['jsonld' => ['application/ld+json']],
    security: "is_granted('ROLE_USER')",
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            outputFormats: ['jsonld' => ['application/ld+json']],
            inputFormats: ['multipart' => ['multipart/form-data']],
            processor: CsvStateProcessor::class,
        ),
        new Delete()
    ]
)]
#[Vich\Uploadable]
class Csv
{

    CONST STATUS_PENDING        = 1000;
    CONST STATUS_WIP            = 2000;

    CONST STATUS_WITH_ERRORS    = 3000;
    CONST STATUS_DONE           = 3000;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['read'])]
    private ?Uuid $code = null;

    #[ApiProperty(types: ['https://schema.org/contentUrl'], writable: false)]
    #[Groups(['read'])]
    public ?string $contentUrl = null;

    #[Vich\UploadableField(mapping: 'csv', fileNameProperty: 'filename')]
    #[Groups(['write'])]
    public ?File $file = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read'])]
    private ?string $filename = null;

    #[ORM\Column]
    #[Groups(['read'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    #[Groups(['read'])]
    private ?int $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['read'])]
    private ?string $errors = null;

    #[ORM\ManyToOne(inversedBy: 'csvs', cascade: ['persist'], fetch: 'EAGER')]
    #[Groups(['write'])]
    private ?Seller $seller = null;

    #[ORM\Column]
    #[Groups(['read'])]
    private ?int $type = null;

    #[ORM\Column(nullable: true)]
    private ?array $messages = null;

    /**
     * @var Collection<int, Settlement>
     */
    #[ORM\OneToMany(targetEntity: Settlement::class, mappedBy: 'csv', orphanRemoval: true)]
    private Collection $settlements;

    public function __construct()
    {
        $this->code = Uuid::v4();
        $this->created_at = new \DateTimeImmutable();
        $this->settlements = new ArrayCollection();
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

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getErrors(): ?string
    {
        return $this->errors;
    }

    public function setErrors(?string $errors): static
    {
        $this->errors = $errors;

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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setCsvFile(?File $file = null): void
    {
        $this->csvFile = $file;

        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getFileWithSellerPath(){
        return $this->getSeller()->getCode().'/'.$this->getFilename();
    }

    public function getMessages(): ?array
    {
        return $this->messages;
    }

    public function setMessages(?array $messages): static
    {
        $this->messages = $messages;

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
            $settlement->setCsv($this);
        }

        return $this;
    }

    public function removeSettlement(Settlement $settlement): static
    {
        if ($this->settlements->removeElement($settlement)) {
            // set the owning side to null (unless already changed)
            if ($settlement->getCsv() === $this) {
                $settlement->setCsv(null);
            }
        }

        return $this;
    }
}
