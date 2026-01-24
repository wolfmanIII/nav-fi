<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MailGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'MAIL';
    }

    public function getType(): string
    {
        return 'MAIL';
    }

    public function generate(array $context, int $maxDist): CubeOpportunityData
    {
        $dist = $context['distance'];
        $containers = mt_rand(1, 3);
        $rate = $this->economyConfig['mail']['flat_rate'];
        $total = $containers * $rate;

        return new CubeOpportunityData(
            signature: '',
            type: 'MAIL',
            summary: "Xboat Mail ($containers cont.) to {$context['destination']}",
            distance: $dist,
            amount: (float)$total,
            details: [
                'origin' => $context['origin'],
                'destination' => $context['destination'],
                'containers' => $containers,
                'tons' => $containers * 5,
                'priority' => 'High'
            ]
        );
    }
}
