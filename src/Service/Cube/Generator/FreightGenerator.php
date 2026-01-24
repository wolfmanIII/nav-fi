<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FreightGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'FREIGHT';
    }

    public function getType(): string
    {
        return 'FREIGHT';
    }

    public function generate(array $context, int $maxDist): CubeOpportunityData
    {
        $dist = $context['distance'];
        $tons = mt_rand(1, 6) * 10; // 10-60 tonnellate

        $baseRate = $this->economyConfig['freight_pricing'][$dist] ?? 1000;
        $total = $tons * $baseRate;

        return new CubeOpportunityData(
            signature: '', // Will be set by engine
            type: 'FREIGHT',
            summary: "Freight: $tons dt to {$context['destination']}",
            distance: $dist,
            amount: (float)$total,
            details: [
                'origin' => $context['origin'],
                'destination' => $context['destination'],
                'tons' => $tons,
                'cargo_type' => 'General Goods',
                'dest_dist' => $dist
            ]
        );
    }
}
