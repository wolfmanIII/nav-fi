<?php

namespace App\EventSubscriber;

use App\Entity\Crew;
use App\Entity\Cost;
use App\Entity\Mortgage;
use App\Entity\MortgageInstallment;
use App\Entity\Ship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: 'prePersist')]
class AssignUserSubscriber
{
    public function __construct(private readonly Security $security)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $entity = $args->getObject();

        if (
            $entity instanceof Crew
            || $entity instanceof Ship
            || $entity instanceof Mortgage
            || $entity instanceof MortgageInstallment
            || $entity instanceof Cost
        ) {
            if (method_exists($entity, 'getUser') && method_exists($entity, 'setUser') && $entity->getUser() === null) {
                $entity->setUser($user);
            }
        }
    }
}
