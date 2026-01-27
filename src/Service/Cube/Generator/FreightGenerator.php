<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FreightGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly \App\Repository\CompanyRepository $companyRepo
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'FREIGHT';
    }

    public function getType(): string
    {
        return 'FREIGHT';
    }

    public function generate(array $context, int $maxDist, \Random\Randomizer $randomizer): CubeOpportunityData
    {
        $dist = $context['distance'];
        $tons = $randomizer->getInt(1, 6) * 10; // 10-60 tonnellate

        $baseRate = $this->economyConfig['freight_pricing'][$dist] ?? 1000;
        $total = $tons * $baseRate;

        // Select Shipper
        $companies = $this->companyRepo->findAll();
        $shipper = null;
        if (!empty($companies)) {
            $shipper = $companies[$randomizer->getInt(0, count($companies) - 1)];
        }

        $summary = "Freight: $tons dt to {$context['destination']}";
        if ($shipper) {
            $summary .= " ({$shipper->getName()})";
        }

        return new CubeOpportunityData(
            signature: '',
            type: 'FREIGHT',
            summary: $summary,
            distance: $dist,
            amount: (float)$total,
            details: [
                'origin' => $context['origin'],
                'destination' => $context['destination'],
                'dest_hex' => $context['dest_hex'] ?? '????',
                'tons' => $tons,
                'cargo_type' => 'General Goods',
                'dest_dist' => $dist,
                'company_id' => $shipper?->getId(),
                'patron' => $shipper?->getName() ?? 'Independent Broker',
                'start_day' => $context['session_day'],
                'start_year' => $context['session_year']
            ]
        );
    }
}
