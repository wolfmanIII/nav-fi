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

        // Arrotonda a cifre "pulite" (es. multipli di 500)
        $amount = round($amount / 500) * 500;

        // 2. Generazione Narrativa
        $patron = $this->narrative->generatePatron();
        $twist = $this->narrative->generateTwist();
        $risk = $tierConfig['risk'] ?? 'Standard';

        // Seleziona un tipo di missione esemplificativo
        $examples = $tierConfig['examples'] ?? ['Mission'];
        $missionType = $examples[mt_rand(0, count($examples) - 1)];

        return new CubeOpportunityData(
            signature: '',
            type: 'CONTRACT',
            summary: "[$risk] $missionType for $patron",
            distance: 0,
            amount: (float)$amount,
            details: [
                'origin' => $context['origin'],
                'destination' => 'Local/System',
                'patron' => $patron,
                'difficulty' => $risk,
                'mission_type' => $missionType,
                'twist' => $twist,
                'tier' => $tierKey
            ]
        );
    }
}
