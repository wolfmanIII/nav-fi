<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ContractGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig
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
        $base = mt_rand(
            $this->economyConfig['contract']['base_reward_min'],
            $this->economyConfig['contract']['base_reward_max']
        );

        $hasBonus = (mt_rand(1, 100) <= ($this->economyConfig['contract']['bonus_chance'] * 100));
        if ($hasBonus) {
            $base += ($base * $this->economyConfig['contract']['bonus_multiplier']);
        }

        return new CubeOpportunityData(
            signature: '',
            type: 'CONTRACT',
            summary: "Patron Mission (Local)",
            distance: 0,
            amount: (float)$base,
            details: [
                'origin' => $context['origin'],
                'destination' => 'Local/System',
                'patron' => 'Local Corp',
                'difficulty' => 'Standard'
            ]
        );
    }
}
