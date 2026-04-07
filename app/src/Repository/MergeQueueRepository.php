<?php

namespace App\Repository;

use App\Entity\MergeQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MergeQueue>
 */
class MergeQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MergeQueue::class);
    }

    /**
     * Find pending merge requests
     * @return MergeQueue[]
     */
    public function findPending(int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.status = :status')
            ->setParameter('status', MergeQueue::STATUS_PENDING)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
