<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Command\ProcessCsvsCommand;
use App\Entity\Amazon\Settlement\TransactionTotal;
use App\Entity\Seller\Csv;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\IngestService;
use Symfony\Component\Console\Tester\CommandTester;

class SellerTest extends AbstractTest
{
  
    public function testCreateAccount(): void{
      $response = static::createClient()->request('POST', self::API_USER_URL, [
          'json' => [
              'name' => 'Wuffes',
              'lastName' => 'Shop',
              'email' => 'finanzas@wuffes.shop',
              'plainPassword' => 'wuffespass'
          ],
          'headers' => [
              'Content-Type' => 'application/ld+json',
          ],
      ]);

      self::$data['user'] = $response->toArray();

      $this->assertResponseStatusCodeSame(201);
      $this->assertJsonContains([
          '@context' => '/contexts/User',
          '@type' => 'User',
      ]);

      $this->assertEquals('finanzas@wuffes.shop', self::$data['user']['email']);
    }

    #[Depends('testCreateAccount')]  
    public function testLogin(): void{
      $response = static::createClient()->request('POST', self::API_USER_LOGIN_URL, [
          'json' => [
              'username' => 'finanzas@wuffes.shop',
              'password' => 'wuffespass',
          ],
          'headers' => [
              'Content-Type' => 'application/ld+json',
          ],
      ]);

      self::$token = $response->toArray()['token'];

      $this->assertResponseStatusCodeSame(200);
    }

    #[Depends('testLogin')]  
    public function testUserMe(): void{
      $response = static::createClient()->request('GET', self::API_USER_ME_URL, [
          'headers' => $this->getHeaders(),
      ]);

      $this->assertResponseStatusCodeSame(200);
      $this->assertEquals('finanzas@wuffes.shop', $response->toArray()['email']);
    }

    #[Depends('testUserMe')]  
    public function testCreateSeller(): void{
        $response = static::createClient()->request('POST', self::API_SELLER_URL, [
            'json' => [
                'name' => 'Wuffes Shop',
            ],
            'headers' => $this->getHeaders(),
        ]);

        self::$data['seller'] = $response->toArray(false);

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

          $response = $client->request('POST', self::API_CSV_URL, [
            'headers' => array_merge(
              $this->getHeaders(),
              ['Content-Type' => 'multipart/form-data']
            ),
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

      $container = self::getContainer();
      $command = $container->get(ProcessCsvsCommand::class);
      $tester = new CommandTester($command);
  
      $tester->execute([]);
  
      // Validar la salida del comando
      $this->assertStringContainsString('Processed CSV', $tester->getDisplay());

      $client = self::createClient();
      $response = $client->request('GET', self::API_TRANSACTION_TOTALS_URL, [
        'headers' => $this->getHeaders(),
        'query' => [
          'year' => 2025,
          'month' => 7,
        ],
      ]);

      $results = $response->toArray()['member'];

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
        'headers' => $this->getHeaders(),
      ]);

      $results = $response->toArray(false)['member'];

      $totals = [];
      foreach($results as $result){
        $totals[$result['totalType']][$result['month']] = $result['totalAmount'];
      }

      $this->assertEquals(148066.71, $totals['GRSS'][8]);
    }

}
