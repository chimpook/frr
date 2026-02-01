<?php

namespace App\Repository;

use App\Entity\Finding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Finding>
 */
class FindingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Finding::class);
    }

    public function save(Finding $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Finding $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPaginated(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNextSequenceNumber(): int
    {
        // Use native SQL to extract and find max numeric ID
        $connection = $this->getEntityManager()->getConnection();
        $sql = "SELECT MAX(CAST(SUBSTRING(id, 3) AS UNSIGNED)) as max_num FROM findings WHERE id LIKE 'SF%'";
        $result = $connection->executeQuery($sql)->fetchAssociative();

        if ($result && $result['max_num'] !== null) {
            return (int) $result['max_num'] + 1;
        }

        return 1;
    }
}
