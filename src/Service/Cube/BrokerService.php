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
        private readonly TheCubeEngine $engine
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

    public function generateOpportunities(BrokerSession $session, array $originData, array $allSystems = []): array
    {
        // Genera in memoria ma non salva finché non vengono selezionate
        return $this->engine->generateBatch($session, $originData, $allSystems);
    }

    public function saveOpportunity(BrokerSession $session, array $oppData): BrokerOpportunity
    {
        $opp = new BrokerOpportunity();
        $opp->setSession($session);
        $opp->setSummary($oppData['summary']);
        $opp->setAmount((string)$oppData['amount']);
        $opp->setData($oppData);
        $opp->setStatus(BrokerOpportunity::STATUS_SAVED);

        $this->em->persist($opp);
        $this->em->flush();

        return $opp;
    }
}
