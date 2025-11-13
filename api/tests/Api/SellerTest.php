<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Amazon\Settlement\TransactionTotal;
use App\Entity\Seller\Csv;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\IngestService;

class SellerTest extends AbstractTest
{
    public function testCreateSeller(): void
    {
        $response = static::createClient()->request('POST', self::API_SELLER_URL, [
            'json' => [
                'name' => 'Wuffes Shop',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        self::$data['seller'] = $response->toArray();

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/Seller',
            '@type' => 'Seller',
        ]);

        $this->assertEquals('Wuffes Shop', self::$data['seller']['name']);
    }

    static public function getFiles(): array{
      return ['jul.csv', 'jun.csv'];
    } 

    #[Depends('testCreateSeller')]
    public function testUploadCSV(): void
    {
        // ini_set('memory_limit', '2048M');

        $files = self::getFiles();

        foreach($files as $idx => $file){
          $uploadedFile = $this->createFileToUpload($file);

          $client = self::createClient();

          $response = $client->request('POST', '/csvs', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
              // If you have additional fields in your MediaObject entity, use the parameters.
              'parameters' => [
                  'seller' => self::$data['seller']['@id'],
                  'type' => 1000,
              ],
              'files' => [
                'file' => $uploadedFile,
              ],
            ]
          ]);

          self::$data['csv'][$file] = $response->toArray();

          $this->assertEquals(Csv::STATUS_PENDING, $response->toArray()['status']);
        }
        
    }

    #[Depends('testUploadCSV')]
    public function testIngest(): void
    {
      $ingestService = static::getContainer()->get(IngestService::class);

      foreach(self::$data['csv'] as $idx => $csv){
        $ingestService
          ->setTmpFilePath('/app/csv/'.$csv['filename'])
          ->ingestSettlement();
      }

      $client = self::createClient();
      $response = $client->request('GET', self::API_TRANSACTION_TOTALS_URL, [
        'headers' => [
            'Content-Type' => 'application/ld+json',
        ],
        'query' => [
          'year' => 2025,
          'month' => 7,
        ],
      ]);

      $results = $response->toArray(false)['member'];

      $totals = [];
      foreach($results as $result){
        $totals[$result['totalType']] = $result['totalAmount'];
      }

      $this->assertEquals(3306974.70, $totals['GRSS']);
      $this->assertEquals(210634.85, $totals['TXS']);
      $this->assertEquals(-373.69, $totals['FRSH']);
      $this->assertEquals(-350485.14, $totals['DSCN']);
    }

    #[Depends('testIngest')]
    public function testGetResumeByLatestMonth(){
      $client = self::createClient();
      $response = $client->request('GET', self::API_TRANSACTION_TOTALS_URL, [
        'headers' => [
            'Content-Type' => 'application/ld+json',
        ],
      ]);

      $results = $response->toArray(false)['member'];

      $totals = [];
      foreach($results as $result){
        $totals[$result['totalType']][$result['month']] = $result['totalAmount'];
      }

      dd($results);

      $this->assertEquals(148066.71, $totals['GRSS'][8]);
    }

}
