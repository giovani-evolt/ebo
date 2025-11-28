<?php

namespace App\Controller;

use App\Entity\Seller;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UploadCsvController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request, Seller $seller): Response
    {
        $file = $request->files->get('file');

        $content = base64_decode($request->getContent());

        if (!$file) {
            throw new BadRequestHttpException('No se proporcionó ningún archivo.');
        }

        if ($file->getClientOriginalExtension() !== 'csv') {
            throw new BadRequestHttpException('El archivo debe ser un CSV.');
        }

        $uploadDir = __DIR__ . '/../../../csv';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = sprintf('%s_%s', $seller->getId(), $file->getClientOriginalName());

        try {
            $file->move($uploadDir, $filename);
        } catch (FileException $e) {
            throw new BadRequestHttpException('Error al subir el archivo: ' . $e->getMessage());
        }

        // Aquí puedes procesar el archivo CSV si es necesario.

        return new Response('Archivo subido exitosamente.', Response::HTTP_OK);
    }
}