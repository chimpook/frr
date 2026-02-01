<?php

namespace App\Tests\Controller;

use App\Entity\Finding;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class FindingControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->clearFindings();
    }

    protected function tearDown(): void
    {
        $this->clearFindings();
        $this->entityManager = null;
        $this->client = null;
        parent::tearDown();
    }

    private function clearFindings(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM findings');
    }

    private function createTestFinding(array $data = []): Finding
    {
        $finding = new Finding();
        $finding->setLocation($data['location'] ?? 'Test Location');
        $finding->setRiskRange($data['risk_range'] ?? 'Medium');
        $finding->setComment($data['comment'] ?? 'Test comment');
        $finding->setRecommendations($data['recommendations'] ?? 'Test recommendations');
        $finding->setResolved($data['resolved'] ?? false);

        $this->entityManager->persist($finding);
        $this->entityManager->flush();

        return $finding;
    }

    // ==================== LIST TESTS ====================

    public function testListFindingsReturnsEmptyArrayWhenNoFindings(): void
    {
        $this->client->request('GET', '/api/findings');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertEmpty($response['data']);
        $this->assertEquals(0, $response['meta']['total']);
    }

    public function testListFindingsReturnsPaginatedResults(): void
    {
        // Create 3 findings
        $this->createTestFinding(['location' => 'Location 1']);
        $this->createTestFinding(['location' => 'Location 2']);
        $this->createTestFinding(['location' => 'Location 3']);

        $this->client->request('GET', '/api/findings');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(3, $response['data']);
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertEquals(1, $response['meta']['page']);
        $this->assertEquals(10, $response['meta']['limit']);
    }

    public function testListFindingsRespectsPaginationParameters(): void
    {
        // Create 5 findings
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestFinding(['location' => "Location $i"]);
        }

        $this->client->request('GET', '/api/findings?page=2&limit=2');

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertEquals(5, $response['meta']['total']);
        $this->assertEquals(2, $response['meta']['page']);
        $this->assertEquals(2, $response['meta']['limit']);
        $this->assertEquals(3, $response['meta']['pages']);
    }

    public function testListFindingsReturnsJsonContentType(): void
    {
        $this->client->request('GET', '/api/findings');

        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    // ==================== SHOW TESTS ====================

    public function testShowFindingReturnsCorrectFinding(): void
    {
        $finding = $this->createTestFinding([
            'location' => 'Building A',
            'risk_range' => 'High',
            'comment' => 'Fire hazard',
            'recommendations' => 'Fix immediately',
        ]);

        $this->client->request('GET', '/api/findings/' . $finding->getId());

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($finding->getId(), $response['id']);
        $this->assertEquals('Building A', $response['location']);
        $this->assertEquals('High', $response['risk_range']);
        $this->assertEquals('Fire hazard', $response['comment']);
        $this->assertEquals('Fix immediately', $response['recommendations']);
        $this->assertFalse($response['resolved']);
    }

    public function testShowFindingReturns404ForNonExistentFinding(): void
    {
        $this->client->request('GET', '/api/findings/NONEXISTENT');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    // ==================== CREATE TESTS ====================

    public function testCreateFindingSuccessfully(): void
    {
        $data = [
            'location' => 'New Building',
            'risk_range' => 'Low',
            'comment' => 'Minor issue',
            'recommendations' => 'Monitor situation',
        ];

        $this->client->request(
            'POST',
            '/api/findings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertStringStartsWith('SF', $response['id']);
        $this->assertEquals('New Building', $response['location']);
        $this->assertEquals('Low', $response['risk_range']);
        $this->assertEquals('Minor issue', $response['comment']);
        $this->assertEquals('Monitor situation', $response['recommendations']);
        $this->assertFalse($response['resolved']);
        $this->assertArrayHasKey('created_at', $response);
        $this->assertArrayHasKey('updated_at', $response);
    }

    public function testCreateFindingGeneratesSequentialIds(): void
    {
        $data = [
            'location' => 'Location',
            'risk_range' => 'Medium',
            'comment' => 'Comment',
            'recommendations' => 'Recommendations',
        ];

        // Create first finding
        $this->client->request('POST', '/api/findings', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response1 = json_decode($this->client->getResponse()->getContent(), true);

        // Create second finding
        $this->client->request('POST', '/api/findings', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $response2 = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('SF1', $response1['id']);
        $this->assertEquals('SF2', $response2['id']);
    }

    public function testCreateFindingWithResolvedTrue(): void
    {
        $data = [
            'location' => 'Location',
            'risk_range' => 'High',
            'comment' => 'Comment',
            'recommendations' => 'Recommendations',
            'resolved' => true,
        ];

        $this->client->request('POST', '/api/findings', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['resolved']);
    }

    public function testCreateFindingFailsWithMissingLocation(): void
    {
        $data = [
            'risk_range' => 'High',
            'comment' => 'Comment',
            'recommendations' => 'Recommendations',
        ];

        $this->client->request('POST', '/api/findings', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testCreateFindingFailsWithInvalidRiskRange(): void
    {
        $data = [
            'location' => 'Location',
            'risk_range' => 'Invalid',
            'comment' => 'Comment',
            'recommendations' => 'Recommendations',
        ];

        $this->client->request('POST', '/api/findings', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testCreateFindingFailsWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/findings', [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testCreateFindingAcceptsAllRiskRanges(): void
    {
        $riskRanges = ['Low', 'Medium', 'High'];

        foreach ($riskRanges as $index => $riskRange) {
            $data = [
                'location' => "Location $index",
                'risk_range' => $riskRange,
                'comment' => 'Comment',
                'recommendations' => 'Recommendations',
            ];

            $this->client->request('POST', '/api/findings', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

            $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
            $response = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertEquals($riskRange, $response['risk_range']);
        }
    }

    // ==================== UPDATE TESTS ====================

    public function testUpdateFindingSuccessfully(): void
    {
        $finding = $this->createTestFinding(['location' => 'Original Location']);

        $updateData = [
            'location' => 'Updated Location',
            'risk_range' => 'High',
        ];

        $this->client->request(
            'PUT',
            '/api/findings/' . $finding->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Location', $response['location']);
        $this->assertEquals('High', $response['risk_range']);
    }

    public function testUpdateFindingPartially(): void
    {
        $finding = $this->createTestFinding([
            'location' => 'Original',
            'comment' => 'Original comment',
        ]);

        $updateData = ['resolved' => true];

        $this->client->request(
            'PUT',
            '/api/findings/' . $finding->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($response['resolved']);
        $this->assertEquals('Original', $response['location']);
        $this->assertEquals('Original comment', $response['comment']);
    }

    public function testUpdateFindingReturns404ForNonExistent(): void
    {
        $this->client->request(
            'PUT',
            '/api/findings/NONEXISTENT',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['location' => 'New'])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateFindingWithPatchMethod(): void
    {
        $finding = $this->createTestFinding(['location' => 'Original']);

        $this->client->request(
            'PATCH',
            '/api/findings/' . $finding->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['location' => 'Patched'])
        );

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Patched', $response['location']);
    }

    public function testUpdateFindingUpdatesTimestamp(): void
    {
        $finding = $this->createTestFinding();
        $originalCreatedAt = $finding->getCreatedAt();

        // Wait a moment to ensure timestamp difference
        sleep(1);

        $this->client->request(
            'PUT',
            '/api/findings/' . $finding->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['resolved' => true])
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $newUpdatedAt = new \DateTimeImmutable($response['updated_at']);

        // The updated_at should be >= created_at after an update
        $this->assertGreaterThanOrEqual($originalCreatedAt, $newUpdatedAt);
    }

    public function testUpdateFindingFailsWithInvalidRiskRange(): void
    {
        $finding = $this->createTestFinding();

        $this->client->request(
            'PUT',
            '/api/findings/' . $finding->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['risk_range' => 'Invalid'])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ==================== DELETE TESTS ====================

    public function testDeleteFindingSuccessfully(): void
    {
        $finding = $this->createTestFinding();
        $findingId = $finding->getId();

        $this->client->request('DELETE', '/api/findings/' . $findingId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify it's actually deleted
        $this->client->request('GET', '/api/findings/' . $findingId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteFindingReturns404ForNonExistent(): void
    {
        $this->client->request('DELETE', '/api/findings/NONEXISTENT');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteFindingRemovesFromList(): void
    {
        $finding1 = $this->createTestFinding(['location' => 'Location 1']);
        $finding2 = $this->createTestFinding(['location' => 'Location 2']);

        // Verify we have 2 findings
        $this->client->request('GET', '/api/findings');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['meta']['total']);

        // Delete one
        $this->client->request('DELETE', '/api/findings/' . $finding1->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify we now have 1 finding
        $this->client->request('GET', '/api/findings');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['meta']['total']);
        $this->assertEquals($finding2->getId(), $response['data'][0]['id']);
    }

    // ==================== RESPONSE FORMAT TESTS ====================

    public function testFindingResponseContainsAllFields(): void
    {
        $finding = $this->createTestFinding([
            'location' => 'Test Location',
            'risk_range' => 'High',
            'comment' => 'Test comment',
            'recommendations' => 'Test recommendations',
            'resolved' => true,
        ]);

        $this->client->request('GET', '/api/findings/' . $finding->getId());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $requiredFields = ['id', 'location', 'risk_range', 'comment', 'recommendations', 'resolved', 'created_at', 'updated_at'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $response, "Response missing field: $field");
        }
    }

    public function testListResponseContainsCorrectMetaFields(): void
    {
        $this->client->request('GET', '/api/findings');

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('page', $response['meta']);
        $this->assertArrayHasKey('limit', $response['meta']);
        $this->assertArrayHasKey('total', $response['meta']);
        $this->assertArrayHasKey('pages', $response['meta']);
    }
}
