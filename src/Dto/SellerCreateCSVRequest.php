<?php

namespace App\Dto;

use EasyRdf\Literal\Integer;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/csvs',
            input: self::class,
            output: false
        )
    ]
)]
class SellerCreateCSVRequest
{
    #[ApiProperty(
        description: 'Seller IRI',
        required: true,
        openapiContext: [
            'type' => 'string',
            'format' => 'iri-reference'
        ]
    )]
    #[Assert\NotBlank]
    public string $seller = '';

    #[ApiProperty(
        description: 'File',
        required: true
    )]
    #[Assert\NotNull]
    #[Assert\File(
        maxSize: '100M',
        mimeTypes: ['text/csv', 'text/plain']
    )]
    public ?UploadedFile $file = null;

    #[ApiProperty(
        description: 'Type',
        required: true
    )]
    #[Assert\NotBlank]
    public int $type = 0;
}