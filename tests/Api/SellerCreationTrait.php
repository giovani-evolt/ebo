<?php

namespace Api\Tests\Api;

trait SellerCreationTrait{

    /**
     * Summary of testUserCreation
     * @test
     * @return void
     */
    public function testUserCreation(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', self::API_USER_URL, [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json'
            ],
            'json' => [
                'email' => 'test@example.com',
                'plainPassword' => 'password',
                'name' => 'Test',
                'lastName' => 'User',
                'roles' => ['ROLE_OWNER']
            ]
        ]);

        self::$userData = $response->toArray();

        $this->assertResponseIsSuccessful();

        $client = static::createClient();
        $response = $client->request('POST', self::API_USER_LOGIN_URL, [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json'
            ],
            'json' => [
                'email' => 'test@example.com',
                'password' => 'password',
            ]
        ]);

        $data = $response->toArray();
        // Asegurarse de que tenemos el token
        $this->assertArrayHasKey('token', $data);
        self::$token = $data['token'];
    }
}