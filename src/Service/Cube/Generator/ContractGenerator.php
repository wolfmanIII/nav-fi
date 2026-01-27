<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;

use App\Service\Cube\NarrativeService;
use App\Service\GameRulesEngine;
use Random\Randomizer;

class ContractGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        private readonly NarrativeService $narrative,
        private readonly GameRulesEngine $rules
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'CONTRACT';
    }

    public function getType(): string
    {
        return 'CONTRACT';
    }

    public function generate(array $context, int $maxDist, Randomizer $randomizer): CubeOpportunityData
    {
        // 1. Determina il livello (Tier)
        $tierRoll = $randomizer->getInt(1, 100);

        // Soglie di rischio configurabili
        $thresholdRoutine = $this->rules->get('contract.tier_chance.routine', 60);
        $thresholdHazardous = $this->rules->get('contract.tier_chance.hazardous', 90);

        $tierKey = ($tierRoll <= $thresholdRoutine) ? 'routine' : (($tierRoll <= $thresholdHazardous) ? 'hazardous' : 'black_ops');

        $tierConfig = $this->narrative->resolveTiers($tierKey);

        // Calcolo ricompensa base
        $minReward = $tierConfig['min'] ?? 1000;
        $maxReward = $tierConfig['max'] ?? 5000;

        $amount = $randomizer->getInt($minReward, $maxReward);
        $amount = round($amount / 500) * 500;

        // 2. Generazione Narrativa tramite il nuovo Motore
        $sector = $context['sector'] ?? 'Unknown';
        $story = $this->narrative->generateStory($sector, $randomizer);

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
