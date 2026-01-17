<?php

namespace App\Repository;

use App\Entity\AssetAmendment;
use App\Entity\Asset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssetAmendment>
 */
class AssetAmendmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssetAmendment::class);
    }

    /**
     * @return AssetAmendment[]
     */
    public function findForAsset(Asset $asset): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.asset = :asset')
            ->setParameter('asset', $asset)
            ->orderBy('a.effectiveYear', 'DESC')
            ->addOrderBy('a.effectiveDay', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
