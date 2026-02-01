<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    public function __construct(
        private Connection $connection
    ) {}

    #[Route('/api/health', name: 'health_check', methods: ['GET'])]
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];
        $httpCode = Response::HTTP_OK;

        // Check database connection
        try {
            $this->connection->executeQuery('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
            $status = 'unhealthy';
            $httpCode = Response::HTTP_SERVICE_UNAVAILABLE;
        }

        return $this->json([
            'status' => $status,
            'checks' => $checks,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ], $httpCode);
    }
}
