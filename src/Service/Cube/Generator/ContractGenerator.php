<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;

class ContractGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        private readonly \App\Service\Cube\NarrativeService $narrative
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'CONTRACT';
    }

    public function getType(): string
    {
        return 'CONTRACT';
    }

    public function generate(array $context, int $maxDist): CubeOpportunityData
    {
        // 1. Determina il livello (Tier) basato su un tiro casuale
        // Probabilità: 60% Routine, 30% Hazardous, 10% Black Ops
        $tierRoll = mt_rand(1, 100);
        if ($tierRoll <= 60) {
            $tierKey = 'routine';
        } elseif ($tierRoll <= 90) {
            $tierKey = 'hazardous';
        } else {
            $tierKey = 'black_ops';
        }

        $tierConfig = $this->narrative->resolveTiers($tierKey);
        $min = $tierConfig['min'] ?? 1000;
        $max = $tierConfig['max'] ?? 5000;

        $amount = mt_rand($min, $max);
        $amount = round($amount / 500) * 500;

        // 2. Generazione Narrativa Avanzata
        // Recupera il settore dal contesto (se disponibile) o usa un valore default
        $sector = $context['sector'] ?? 'Unknown';

        // Seleziona Patron (Entity o String)
        $patronOrCompany = $this->narrative->selectPatron($sector);

        $patronName = is_string($patronOrCompany)
            ? $patronOrCompany
            : $patronOrCompany->getName();

        // Se è una company, salva l'ID nei dettagli per uso futuro (es. link)
        $companyId = ($patronOrCompany instanceof \App\Entity\Company) ? $patronOrCompany->getId() : null;

        $risk = $tierConfig['risk'] ?? 'Standard';
        $examples = $tierConfig['examples'] ?? ['Mission'];
        $missionType = $examples[mt_rand(0, count($examples) - 1)];

        // Genera Briefing "Mad-Libs"
        $target = "the target"; // TBD: Generare anche il target dinamicamente? Per ora statico o semplice.
        $briefing = $this->narrative->generateBriefing('CONTRACT', $patronName, $target, []);
        $twist = $this->narrative->generateTwist();

        return new CubeOpportunityData(
            signature: '',
            type: 'CONTRACT',
            summary: "[$risk] $missionType for $patronName",
            distance: 0,
            amount: (float)$amount,
            details: [
                'origin' => $context['origin'],
                'destination' => 'Local/System',
                'dest_hex' => $context['origin_hex'] ?? 'LOCL',
                'patron' => $patronName,
                'company_id' => $companyId, // Nuova info
                'difficulty' => $risk,
                'mission_type' => $missionType,
                'briefing' => $briefing, // Nuovo campo ricco
                'twist' => $twist,
                'tier' => $tierKey,
                'start_day' => $context['session_day'],
                'start_year' => $context['session_year']
            ]
        );
    }
}
