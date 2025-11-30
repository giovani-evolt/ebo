<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AbstractTest extends ApiTestCase
{
    CONST API_USER_URL = '/users';

    CONST API_USER_ME_URL = '/user/me';
    CONST API_USER_LOGIN_URL = '/login_check';
    CONST API_SELLER_URL = '/sellers';
    CONST API_CSV_URL = '/csvs';
    CONST API_SETTLEMENT_URL = '/settlements';
    CONST API_TRANSACTION_TOTALS_URL = '/transaction_totals';
    CONST API_UNITS_SOLD_TOTAL_URL = '/units_sold';
    
    private ?EntityManagerInterface $entityManager;
    
    protected static $token = null;

    protected static $userData = null;

    protected static $data = [];

    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected static function createClient(array $kernelOptions = [], array $defaultOptions = []): Client
    {
        return parent::createClient(['debug' => true]);
    }

    protected function createFileToUpload($filename): UploadedFile
    {
        $file = __DIR__ . '/../files/'.$filename;
        $newfile = __DIR__ . '/../files/uploaded-'.$filename;        

        if (!copy($file, $newfile)) {
            dd('failed to copy csv file');
        }

        return new UploadedFile(
            $newfile,
            'uploaded-'.$filename,
        );
    }

    protected function getHeaders($token = null): array
    {
        if (!self::$token) {
            throw new \RuntimeException('No token available');
        }
        return [
            'Accept' => 'application/ld+json',
            'Content-Type' => 'application/ld+json',
            'Authorization' => sprintf('Bearer %s', $token ?? self::$token)
        ];
    }
}
