<?php

namespace App\Entity\Seller;

use ApiPlatform\Metadata\ApiResource;
use App\Controller\UploadCsvController;
use App\Entity\Seller;
use App\Repository\Seller\CsvRepository;
use App\State\CsvStateProcessor;
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

#[ORM\Entity(repositoryClass: CsvRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read']], 
    outputFormats: ['jsonld' => ['application/ld+json']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            outputFormats: ['jsonld' => ['application/ld+json']],
            inputFormats: ['multipart' => ['multipart/form-data']],
            processor: CsvStateProcessor::class,
        )
    ]
)]
#[Vich\Uploadable]
class Csv
{

    CONST STATUS_PENDING    = 1000;
    CONST STATUS_WIP        = 2000;
    CONST STATUS_DONE       = 3000;

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

    #[ORM\Column(nullable: false)]
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

    #[ORM\ManyToOne(inversedBy: 'csvs')]
    #[Groups(['write'])]
    private ?Seller $seller = null;

    #[ORM\Column]
    #[Groups(['read'])]
    private ?int $type = null;

    public function __construct()
    {
        $this->code = Uuid::v4();
        $this->created_at = new \DateTimeImmutable();
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

    public function setFilename(string $filename): static
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
}
