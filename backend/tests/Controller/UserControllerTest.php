<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AuthenticatedTestCase
{
    // ==================== LIST USERS TESTS ====================

    public function testListUsersRequiresAdminRole(): void
    {
        $this->requestWithUserAuth('GET', '/api/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListUsersAsAdmin(): void
    {
        $this->requestWithAdminAuth('GET', '/api/users');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertCount(2, $response['data']); // admin + regular user
        $this->assertEquals(2, $response['meta']['total']);
    }

    public function testListUsersWithPagination(): void
    {
        $this->requestWithAdminAuth('GET', '/api/users?page=1&limit=1');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['data']);
        $this->assertEquals(2, $response['meta']['total']);
        $this->assertEquals(2, $response['meta']['pages']);
    }

    public function testListUsersRequiresAuthentication(): void
    {
        $this->requestWithoutAuth('GET', '/api/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ==================== SHOW USER TESTS ====================

    public function testShowUserAsAdmin(): void
    {
        $this->requestWithAdminAuth('GET', '/api/users/' . $this->regularUser->getId());

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('user@test.local', $response['email']);
        $this->assertEquals('Test User', $response['name']);
    }

    public function testShowUserReturns404ForNonExistent(): void
    {
        $this->requestWithAdminAuth('GET', '/api/users/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testShowUserRequiresAdminRole(): void
    {
        $this->requestWithUserAuth('GET', '/api/users/' . $this->adminUser->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ==================== CREATE USER TESTS ====================

    public function testCreateUserAsAdmin(): void
    {
        $data = [
            'email' => 'newuser@test.local',
            'name' => 'New User',
            'password' => 'newpassword123',
            'roles' => ['ROLE_USER'],
        ];

        $this->requestWithAdminAuth('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('newuser@test.local', $response['email']);
        $this->assertEquals('New User', $response['name']);
        $this->assertContains('ROLE_USER', $response['roles']);
        $this->assertTrue($response['active']);
    }

    public function testCreateAdminUser(): void
    {
        $data = [
            'email' => 'newadmin@test.local',
            'name' => 'New Admin',
            'password' => 'adminpassword123',
            'roles' => ['ROLE_ADMIN'],
        ];

        $this->requestWithAdminAuth('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertContains('ROLE_ADMIN', $response['roles']);
    }

    public function testCreateUserRequiresAdminRole(): void
    {
        $data = [
            'email' => 'newuser@test.local',
            'name' => 'New User',
            'password' => 'password123',
        ];

        $this->requestWithUserAuth('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateUserFailsWithMissingPassword(): void
    {
        $data = [
            'email' => 'newuser@test.local',
            'name' => 'New User',
        ];

        $this->requestWithAdminAuth('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('password', $response['errors']);
    }

    public function testCreateUserFailsWithMissingEmail(): void
    {
        $data = [
            'name' => 'New User',
            'password' => 'password123',
        ];

        $this->requestWithAdminAuth('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateUserFailsWithInvalidEmail(): void
    {
        $data = [
            'email' => 'not-an-email',
            'name' => 'New User',
            'password' => 'password123',
        ];

        $this->requestWithAdminAuth('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateUserFailsWithDuplicateEmail(): void
    {
        $data = [
            'email' => 'admin@test.local', // Already exists
            'name' => 'Duplicate User',
            'password' => 'password123',
        ];

        $this->requestWithAdminAuth('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testCreateUserFailsWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->adminToken,
                'CONTENT_TYPE' => 'application/json',
            ],
            'invalid json'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ==================== UPDATE USER TESTS ====================

    public function testUpdateUserAsAdmin(): void
    {
        $data = [
            'name' => 'Updated Name',
        ];

        $this->requestWithAdminAuth('PUT', '/api/users/' . $this->regularUser->getId(), $data);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Name', $response['name']);
        $this->assertEquals('user@test.local', $response['email']); // Unchanged
    }

    public function testUpdateUserEmail(): void
    {
        $data = [
            'email' => 'updated@test.local',
        ];

        $this->requestWithAdminAuth('PUT', '/api/users/' . $this->regularUser->getId(), $data);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('updated@test.local', $response['email']);
    }

    public function testUpdateUserEmailFailsWithDuplicate(): void
    {
        $data = [
            'email' => 'admin@test.local', // Already exists
        ];

        $this->requestWithAdminAuth('PUT', '/api/users/' . $this->regularUser->getId(), $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateUserPassword(): void
    {
        $data = [
            'password' => 'newpassword123',
        ];

        $this->requestWithAdminAuth('PUT', '/api/users/' . $this->regularUser->getId(), $data);

        $this->assertResponseIsSuccessful();

        // Verify new password works
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'user@test.local',
                'password' => 'newpassword123',
            ])
        );

        $this->assertResponseIsSuccessful();
    }

    public function testUpdateUserRoles(): void
    {
        $data = [
            'roles' => ['ROLE_ADMIN'],
        ];

        $this->requestWithAdminAuth('PUT', '/api/users/' . $this->regularUser->getId(), $data);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertContains('ROLE_ADMIN', $response['roles']);
    }

    public function testUpdateUserActiveStatus(): void
    {
        $data = [
            'active' => false,
        ];

        $this->requestWithAdminAuth('PUT', '/api/users/' . $this->regularUser->getId(), $data);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($response['active']);
    }

    public function testUpdateUserRequiresAdminRole(): void
    {
        $data = ['name' => 'Hacked'];

        $this->requestWithUserAuth('PUT', '/api/users/' . $this->adminUser->getId(), $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateUserReturns404ForNonExistent(): void
    {
        $data = ['name' => 'Test'];

        $this->requestWithAdminAuth('PUT', '/api/users/99999', $data);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateUserWithPatchMethod(): void
    {
        $data = ['name' => 'Patched Name'];

        $this->requestWithAdminAuth('PATCH', '/api/users/' . $this->regularUser->getId(), $data);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Patched Name', $response['name']);
    }

    // ==================== DELETE USER TESTS ====================

    public function testDeleteUserAsAdmin(): void
    {
        $userId = $this->regularUser->getId();

        $this->requestWithAdminAuth('DELETE', '/api/users/' . $userId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify user is deleted
        $this->requestWithAdminAuth('GET', '/api/users/' . $userId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteUserCannotDeleteSelf(): void
    {
        $this->requestWithAdminAuth('DELETE', '/api/users/' . $this->adminUser->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('own account', $response['error']);
    }

    public function testDeleteUserRequiresAdminRole(): void
    {
        $this->requestWithUserAuth('DELETE', '/api/users/' . $this->adminUser->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteUserReturns404ForNonExistent(): void
    {
        $this->requestWithAdminAuth('DELETE', '/api/users/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== RESPONSE FORMAT TESTS ====================

    public function testUserResponseContainsAllFields(): void
    {
        $this->requestWithAdminAuth('GET', '/api/users/' . $this->adminUser->getId());

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $requiredFields = ['id', 'email', 'name', 'roles', 'active', 'created_at', 'updated_at'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $response, "Response missing field: $field");
        }

        // Password should NOT be in response
        $this->assertArrayNotHasKey('password', $response);
    }

    public function testListUsersResponseContainsCorrectMetaFields(): void
    {
        $this->requestWithAdminAuth('GET', '/api/users');

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('page', $response['meta']);
        $this->assertArrayHasKey('limit', $response['meta']);
        $this->assertArrayHasKey('total', $response['meta']);
        $this->assertArrayHasKey('pages', $response['meta']);
    }
}
