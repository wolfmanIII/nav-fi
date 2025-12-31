<?php

namespace App\Repository;

use App\Entity\AnnualBudget;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnnualBudget>
 */
class AnnualBudgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnnualBudget::class);
    }

    /**
     * @return AnnualBudget[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.startYear', 'DESC')
            ->addOrderBy('b.startDay', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?AnnualBudget
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.id = :id')
            ->andWhere('b.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
