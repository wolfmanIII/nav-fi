<?php

namespace App\EventSubscriber;

use App\Entity\Company;
use App\Service\NormalizationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Subscriber per mantenere aggiornato il campo canonical_name dell'entità Company.
 * Interviene durante le fasi di persistenza e aggiornamento di Doctrine.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class CompanyCanonicalNameSubscriber
{
    public function __construct(
        private readonly NormalizationService $normalizationService
    ) {}

    /**
     * Chiamato prima dell'inserimento nel database.
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->updateCanonicalName($args);
    }

    /**
     * Chiamato prima dell'aggiornamento nel database.
     */
    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->updateCanonicalName($args);
    }

    /**
     * Calcola e imposta il nome canonico basandosi sul nome attuale della società.
     */
    private function updateCanonicalName(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Company) {
            return;
        }

        if ($entity->getName()) {
            // Utilizza il NormalizationService per generare la stringa pulita (senza titoli, spazi, etc.)
            $entity->setCanonicalName(
                $this->normalizationService->normalize($entity->getName())
            );
        }
    }
}
