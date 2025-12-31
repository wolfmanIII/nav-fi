<?php

namespace App\Repository;

use App\Entity\Income;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Income>
 */
class IncomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Income::class);
    }

    /**
     * @return Income[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.signingYear', 'DESC')
            ->addOrderBy('i.signingDay', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneForUser(int $id, User $user): ?Income
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id = :id')
            ->andWhere('i.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
