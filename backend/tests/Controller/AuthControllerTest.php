<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends AuthenticatedTestCase
{
    // ==================== LOGIN TESTS ====================

    public function testLoginWithValidCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'admin@test.local',
                'password' => 'admin123',
            ])
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $response);
        $this->assertNotEmpty($response['token']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'admin@test.local',
                'password' => 'wrongpassword',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'nonexistent@test.local',
                'password' => 'password',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithMissingCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        // Symfony's json_login returns 400 for empty body (treated as invalid JSON)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginReturnsJwtToken(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'user@test.local',
                'password' => 'user123',
            ])
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // JWT tokens have 3 parts separated by dots
        $tokenParts = explode('.', $response['token']);
        $this->assertCount(3, $tokenParts);
    }

    // ==================== ME ENDPOINT TESTS ====================

    public function testMeReturnsCurrentUserInfo(): void
    {
        $this->requestWithAdminAuth('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('admin@test.local', $response['email']);
        $this->assertEquals('Test Admin', $response['name']);
        $this->assertContains('ROLE_ADMIN', $response['roles']);
        $this->assertContains('ROLE_USER', $response['roles']);
        $this->assertTrue($response['active']);
    }

    public function testMeReturnsRegularUserInfo(): void
    {
        $this->requestWithUserAuth('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('user@test.local', $response['email']);
        $this->assertEquals('Test User', $response['name']);
        $this->assertContains('ROLE_USER', $response['roles']);
        $this->assertNotContains('ROLE_ADMIN', $response['roles']);
    }

    public function testMeRequiresAuthentication(): void
    {
        $this->requestWithoutAuth('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeWithInvalidToken(): void
    {
        $this->client->request(
            'GET',
            '/api/me',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== HEALTH ENDPOINT TESTS ====================

    public function testHealthEndpointIsPublic(): void
    {
        $this->requestWithoutAuth('GET', '/api/health');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $response);
    }
}
