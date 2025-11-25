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

class TokenTest extends SellerTest
{
  
  #[Depends('testLogin')]    
  public function testCreateAccount2(): void{
      $response = static::createClient()->request('POST', self::API_USER_URL, [
          'json' => [
              'name' => 'Wuffes',
              'lastName' => 'Shop',
              'email' => 'gio@wuffes.shop',
              'plainPassword' => 'giopass'
          ],
          'headers' => [
              'Content-Type' => 'application/ld+json',
          ],
      ]);

      self::$data['user2'] = $response->toArray();

      $this->assertResponseStatusCodeSame(201);
      $this->assertJsonContains([
          '@context' => '/api/contexts/User',
          '@type' => 'User',
      ]);

      $this->assertEquals('gio@wuffes.shop', self::$data['user2']['email']);
    }

    #[Depends('testCreateAccount2')]  
    public function testLogin2(): void{
      $response = static::createClient()->request('POST', self::API_USER_LOGIN_URL, [
          'json' => [
              'username' => 'gio@wuffes.shop',
              'password' => 'giopass',
          ],
          'headers' => [
              'Content-Type' => 'application/ld+json',
          ],
      ]);

      $this->assertNotEquals(self::$token, $response->toArray()['token']);
    }
}
