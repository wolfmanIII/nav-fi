<?php

namespace App\Service\Cube;

use App\Entity\BrokerOpportunity;
use App\Entity\BrokerSession;
use App\Entity\Campaign;
use App\Repository\BrokerSessionRepository;
use Doctrine\ORM\EntityManagerInterface;

class BrokerService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BrokerSessionRepository $sessionRepo,
        private readonly TheCubeEngine $engine,
        private readonly OpportunityConverter $converter
    ) {}

    public function createSession(Campaign $campaign, string $sector, string $hex, int $range, ?string $seed = null): BrokerSession
    {
        $session = new BrokerSession();
        $session->setCampaign($campaign);
        $session->setSector($sector);
        $session->setOriginHex($hex);
        $session->setJumpRange($range);

        // Genera il seed automaticamente se non fornito
        $seed = $seed ?? bin2hex(random_bytes(4));
        $session->setSeed($seed);

        $this->em->persist($session);
        $this->em->flush();

        return $session;
    }

    /**
     * @return \App\Dto\Cube\CubeOpportunityData[]
     */
    public function generateOpportunities(BrokerSession $session, array $originData, array $allSystems = []): array
    {
        // Genera in memoria ma non salva finché non vengono selezionate
        return $this->engine->generateBatch($session, $originData, $allSystems);
    }

    public function saveOpportunity(BrokerSession $session, array $oppData): BrokerOpportunity
    {
        // Validate via DTO
        // This ensures the array has the correct structure before saving
        $dto = \App\Dto\Cube\CubeOpportunityData::fromArray($oppData);

        $opp = new BrokerOpportunity();
        $opp->setSession($session);
        $opp->setSummary($dto->summary);
        $opp->setAmount((string)$dto->amount);
        $opp->setData($dto->toArray()); // Store strict data structure
        $opp->setStatus(BrokerOpportunity::STATUS_SAVED);

        $this->em->persist($opp);
        $this->em->flush();

        return $opp;
    }

    public function acceptOpportunity(BrokerOpportunity $opportunity, \App\Entity\Asset $asset): \App\Entity\Income|\App\Entity\Cost
    {
        // 1. Converti in DTO per passare i dati puliti
        $dto = \App\Dto\Cube\CubeOpportunityData::fromArray($opportunity->getData());

        // 2. Chiama il converter
        $financialEntity = $this->converter->convert($dto, $asset);

        // 3. Aggiorna stato opportunità
        $opportunity->setStatus('CONVERTED'); // TODO: Add constant to entity

        // 4. Salva tutto in una transazione (gestita dall'EM flush)
        $this->em->flush();

        return $financialEntity;
    }
}
