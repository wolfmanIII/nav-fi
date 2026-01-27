<?php

namespace App\Repository;

use App\Entity\GameRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameRule>
 */
class GameRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameRule::class);
    }

    /**
     * Trova una regola per chiave.
     */
    public function findOneByKey(string $key): ?GameRule
    {
        return $this->findOneBy(['ruleKey' => $key]);
    }
}
