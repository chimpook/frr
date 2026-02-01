<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AuthenticatedTestCase extends WebTestCase
{
    protected ?KernelBrowser $client = null;
    protected ?EntityManagerInterface $entityManager = null;
    protected ?User $adminUser = null;
    protected ?User $regularUser = null;
    protected ?string $adminToken = null;
    protected ?string $userToken = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->clearUsers();
        $this->createTestUsers();
    }

    protected function tearDown(): void
    {
        $this->clearUsers();
        $this->entityManager = null;
        $this->client = null;
        $this->adminUser = null;
        $this->regularUser = null;
        $this->adminToken = null;
        $this->userToken = null;
        parent::tearDown();
    }

    protected function clearUsers(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM users');
    }

    protected function createTestUsers(): void
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Create admin user
        $this->adminUser = new User();
        $this->adminUser->setEmail('admin@test.local');
        $this->adminUser->setName('Test Admin');
        $this->adminUser->setRoles(['ROLE_ADMIN']);
        $this->adminUser->setActive(true);
        $this->adminUser->setPassword($passwordHasher->hashPassword($this->adminUser, 'admin123'));

        $this->entityManager->persist($this->adminUser);

        // Create regular user
        $this->regularUser = new User();
        $this->regularUser->setEmail('user@test.local');
        $this->regularUser->setName('Test User');
        $this->regularUser->setRoles([]);
        $this->regularUser->setActive(true);
        $this->regularUser->setPassword($passwordHasher->hashPassword($this->regularUser, 'user123'));

        $this->entityManager->persist($this->regularUser);

        $this->entityManager->flush();

        // Generate tokens
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $this->adminToken = $jwtManager->create($this->adminUser);
        $this->userToken = $jwtManager->create($this->regularUser);
    }

    protected function requestWithAdminAuth(string $method, string $uri, array $data = []): void
    {
        $headers = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->adminToken,
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            $headers,
            $data ? json_encode($data) : null
        );
    }

    protected function requestWithUserAuth(string $method, string $uri, array $data = []): void
    {
        $headers = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken,
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request(
            $method,
            $uri,
            [],
            [],
            $headers,
            $data ? json_encode($data) : null
        );
    }

    protected function requestWithoutAuth(string $method, string $uri, array $data = []): void
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $data ? json_encode($data) : null
        );
    }
}
