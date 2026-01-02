<?php

namespace App\Repository;

use App\Entity\IncomeSalvageDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IncomeSalvageDetails>
 */
class IncomeSalvageDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncomeSalvageDetails::class);
    }
}
