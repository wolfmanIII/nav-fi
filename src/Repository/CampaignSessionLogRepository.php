<?php

namespace App\Repository;

use App\Entity\CampaignSessionLog;
use App\Entity\Campaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CampaignSessionLog>
 */
class CampaignSessionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampaignSessionLog::class);
    }
    public function findForCampaign(Campaign $campaign, int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('l.createdAt', 'DESC');

        $query = $qb->getQuery();

        $paginator = new Paginator($query);
        $total = count($paginator);

        $paginator->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $total,
        ];
    }
}
