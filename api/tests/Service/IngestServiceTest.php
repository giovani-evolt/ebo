<?php

namespace App\Tests\Service;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Service\IngestService;
use App\Tests\Api\AbstractTest;
use Doctrine\ORM\Tools\SchemaTool;

class IngestServiceTest extends AbstractTest
{
    public function testIngestNoMatchJulWithDuckDB(): void
    {
        $ingestService = static::getContainer()->get(IngestService::class);

        $result = $ingestService
            ->setTmpFilePath('/app/tests/files/jul-no-match.csv')
            ->ingestSettlement();

        $this->assertEquals(1, count($result['messages']));
    }

    public function testIngestJunJulWithDuckDB(): void
    {
        $ingestService = static::getContainer()->get(IngestService::class);

        $result = $ingestService
            ->setTmpFilePath('/app/tests/files/jun.csv')
            ->ingestSettlement();

        $result = $ingestService
            ->setTmpFilePath('/app/tests/files/jul.csv')
            ->ingestSettlement();
        
        $this->assertArrayHasKey('24022078181', $result['settlements']);
        $this->assertEquals(1, count($result['messages']));
    }
}