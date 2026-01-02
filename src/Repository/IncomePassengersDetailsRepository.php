<?php

namespace App\Repository;

use App\Entity\IncomePassengersDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IncomePassengersDetails>
 */
class IncomePassengersDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncomePassengersDetails::class);
    }
}
