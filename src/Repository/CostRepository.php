<?php

namespace App\Repository;

use App\Entity\Cost;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cost>
 */
class CostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cost::class);
    }

    /**
     * @return Cost[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.paymentYear', 'DESC')
            ->addOrderBy('c.paymentDay', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneForUser(int $id, User $user): ?Cost
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
