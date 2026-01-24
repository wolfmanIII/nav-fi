<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;

class TradeGenerator implements OpportunityGeneratorInterface
{
    public function supports(string $type): bool
    {
        return $type === 'TRADE';
    }

    public function getType(): string
    {
        return 'TRADE';
    }

    public function generate(array $context, int $maxDist): CubeOpportunityData
    {
        // Segnaposto per commercio speculativo
        // Per MVP, generiamo una semplice opportunità "compra basso, vendi alto"
        $resources = ['Radioactives', 'Metals', 'Crystals', 'Luxuries', 'Electronics', 'Pharmaceuticals'];
        $resource = $resources[mt_rand(0, count($resources) - 1)];

        $buyPrice = mt_rand(1000, 5000);
        $markup = mt_rand(120, 180) / 100; // 1.2x to 1.8x
        $estimatedProfit = (int)($buyPrice * ($markup - 1));

        return new CubeOpportunityData(
            signature: '',
            type: 'TRADE',
            summary: "Speculative Trade: $resource",
            distance: 0,
            amount: (float)$estimatedProfit,
            details: [
                'origin' => $context['origin'],
                'destination' => 'Market',
                'resource' => $resource,
                'buy_price' => $buyPrice,
                'markup_estimate' => $markup,
                'risk_level' => 'Medium'
            ]
        );
    }
}
