<?php

namespace App\Repository;

use App\Entity\Crew;
use App\Entity\Ship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Crew>
 */
class CrewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Crew::class);
    }

    public function findOneByCaptainOnShip(Ship $ship, ?Crew $exclude = null): ?Crew
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.shipRoles', 'r')
            ->andWhere('c.ship = :ship')
            ->andWhere('r.code = :cap')
            ->setParameter('ship', $ship)
            ->setParameter('cap', 'CAP')
            ->setMaxResults(1);

        if ($exclude?->getId()) {
            $qb->andWhere('c.id != :exclude')
                ->setParameter('exclude', $exclude->getId());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    //    /**
    //     * @return Crew[] Returns an array of Crew objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Crew
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
