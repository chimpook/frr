<?php

namespace App\Controller;

use App\Entity\Finding;
use App\Event\FindingEvent;
use App\Repository\FindingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/api/findings')]
class FindingController extends AbstractController
{
    public function __construct(
        private FindingRepository $findingRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    #[Route('', name: 'findings_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));

        $findings = $this->findingRepository->findPaginated($page, $limit);
        $total = $this->findingRepository->countAll();

        $data = array_map(fn(Finding $f) => $f->toArray(), $findings);

        return $this->json([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/export', name: 'findings_export', methods: ['GET'])]
    public function export(Request $request): StreamedResponse
    {
        $resolved = $request->query->get('resolved');
        $riskRange = $request->query->get('risk_range');

        $qb = $this->findingRepository->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC');

        if ($resolved !== null) {
            $qb->andWhere('f.resolved = :resolved')
               ->setParameter('resolved', $resolved === 'true' || $resolved === '1');
        }

        if ($riskRange !== null && in_array($riskRange, ['Low', 'Medium', 'High'])) {
            $qb->andWhere('f.riskRange = :riskRange')
               ->setParameter('riskRange', $riskRange);
        }

        $findings = $qb->getQuery()->getResult();

        $response = new StreamedResponse(function () use ($findings) {
            $handle = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'ID',
                'Location',
                'Risk Level',
                'Comment',
                'Recommendations',
                'Resolved',
                'Created At',
                'Updated At',
            ]);

            // Data rows
            foreach ($findings as $finding) {
                fputcsv($handle, [
                    $finding->getId(),
                    $finding->getLocation(),
                    $finding->getRiskRange(),
                    $finding->getComment(),
                    $finding->getRecommendations(),
                    $finding->isResolved() ? 'Yes' : 'No',
                    $finding->getCreatedAt()?->format('Y-m-d H:i:s'),
                    $finding->getUpdatedAt()?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        });

        $filename = 'fire_risk_findings_' . date('Y-m-d_His') . '.csv';

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    #[Route('/{id}', name: 'findings_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $finding = $this->findingRepository->find($id);

        if (!$finding) {
            return $this->json(['error' => 'Finding not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($finding->toArray());
    }

    #[Route('', name: 'findings_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $finding = new Finding();
        $finding->setLocation($data['location'] ?? '');
        $finding->setRiskRange($data['risk_range'] ?? '');
        $finding->setComment($data['comment'] ?? '');
        $finding->setRecommendations($data['recommendations'] ?? '');
        $finding->setResolved($data['resolved'] ?? false);

        $errors = $this->validator->validate($finding);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->findingRepository->save($finding, true);

        $userId = $this->getUser()?->getId() ?? 0;
        $this->eventDispatcher->dispatch(
            FindingEvent::created($finding, $userId),
            FindingEvent::CREATED
        );

        return $this->json($finding->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'findings_update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $finding = $this->findingRepository->find($id);

        if (!$finding) {
            return $this->json(['error' => 'Finding not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['location'])) {
            $finding->setLocation($data['location']);
        }
        if (isset($data['risk_range'])) {
            $finding->setRiskRange($data['risk_range']);
        }
        if (isset($data['comment'])) {
            $finding->setComment($data['comment']);
        }
        if (isset($data['recommendations'])) {
            $finding->setRecommendations($data['recommendations']);
        }
        if (isset($data['resolved'])) {
            $finding->setResolved((bool) $data['resolved']);
        }

        $errors = $this->validator->validate($finding);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $userId = $this->getUser()?->getId() ?? 0;
        $this->eventDispatcher->dispatch(
            FindingEvent::updated($finding, $userId),
            FindingEvent::UPDATED
        );

        return $this->json($finding->toArray());
    }

    #[Route('/{id}', name: 'findings_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $finding = $this->findingRepository->find($id);

        if (!$finding) {
            return $this->json(['error' => 'Finding not found'], Response::HTTP_NOT_FOUND);
        }

        $findingData = $finding->toArray();
        $userId = $this->getUser()?->getId() ?? 0;

        $this->findingRepository->remove($finding, true);

        $this->eventDispatcher->dispatch(
            FindingEvent::deleted($findingData, $userId),
            FindingEvent::DELETED
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
