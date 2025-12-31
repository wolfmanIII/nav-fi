<?php

namespace App\Repository;

use App\Entity\MortgageInstallment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MortgageInstallment>
 */
class MortgageInstallmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MortgageInstallment::class);
    }

    /**
     * @return MortgageInstallment[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('mi')
            ->andWhere('mi.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
