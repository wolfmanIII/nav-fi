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
        // 1. Determina il livello (Tier)
        $tierRoll = mt_rand(1, 100);
        $tierKey = ($tierRoll <= 60) ? 'routine' : (($tierRoll <= 90) ? 'hazardous' : 'black_ops');

        $tierConfig = $this->narrative->resolveTiers($tierKey);
        $amount = mt_rand($tierConfig['min'] ?? 1000, $tierConfig['max'] ?? 5000);
        $amount = round($amount / 500) * 500;

        // 2. Generazione Narrativa tramite il nuovo Motore
        $sector = $context['sector'] ?? 'Unknown';
        $story = $this->narrative->generateStory($sector);

        $destination = $context['destination'] ?? 'Local/System';
        $distance = $context['distance'] ?? 0;

        return new CubeOpportunityData(
            signature: '',
            type: 'CONTRACT',
            summary: "[{$tierConfig['risk']}] {$story->summary} at $destination",
            distance: $distance,
            amount: (float)$amount,
            details: [
                'origin' => $context['origin'],
                'destination' => $destination,
                'dest_hex' => $context['dest_hex'] ?? 'LOCL',
                'patron' => $story->patronName,
                'difficulty' => $tierConfig['risk'],
                'mission_type' => $story->archetypeCode,
                'briefing' => $story->briefing,
                'twist' => $story->twist,
                'tier' => $tierKey,
                'variables' => $story->variables,
                'start_day' => $context['session_day'],
                'start_year' => $context['session_year']
            ]
        );
    }
}
