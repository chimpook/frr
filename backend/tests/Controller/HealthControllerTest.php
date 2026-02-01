<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HealthControllerTest extends WebTestCase
{
    public function testHealthCheckReturnsHealthyStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('checks', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertEquals('healthy', $response['status']);
        $this->assertArrayHasKey('database', $response['checks']);
    }

    public function testHealthCheckReturnsJsonContentType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testHealthCheckTimestampIsValidFormat(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('timestamp', $response);
        $timestamp = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $response['timestamp']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $timestamp);
    }
}
