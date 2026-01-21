<?php

namespace App\Repository;

use App\Entity\BrokerSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BrokerSession>
 */
class BrokerSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BrokerSession::class);
    }

    public function findByCampaign(int $campaignId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.campaign = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
