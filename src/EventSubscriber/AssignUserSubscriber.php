<?php

namespace App\EventSubscriber;

use App\Entity\Crew;
use App\Entity\Cost;
use App\Entity\Income;
use App\Entity\Mortgage;
use App\Entity\MortgageInstallment;
use App\Entity\AnnualBudget;
use App\Entity\Asset;
use App\Entity\Company;
use App\Entity\User;
use App\Entity\Campaign;
use App\Entity\AssetAmendment;
use App\Entity\FinancialAccount;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: 'prePersist')]
class AssignUserSubscriber
{
    public function __construct(private readonly Security $security) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $entity = $args->getObject();

        if (
            $entity instanceof Crew
            || $entity instanceof Asset
            || $entity instanceof Mortgage
            || $entity instanceof MortgageInstallment
            || $entity instanceof Cost
            || $entity instanceof Income
            || $entity instanceof AnnualBudget
            || $entity instanceof Company
            || $entity instanceof Campaign
            || $entity instanceof AssetAmendment
            || $entity instanceof FinancialAccount
        ) {
            if (method_exists($entity, 'getUser') && method_exists($entity, 'setUser') && $entity->getUser() === null) {
                $entity->setUser($user);
            }
        }
    }
}
