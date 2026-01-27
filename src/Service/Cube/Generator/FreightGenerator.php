<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use App\Repository\CompanyRepository;
use App\Service\GameRulesEngine;
use Random\Randomizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FreightGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly CompanyRepository $companyRepo,
        private readonly GameRulesEngine $rules
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'FREIGHT';
    }

    public function getType(): string
    {
        return 'FREIGHT';
    }

    public function generate(array $context, int $maxDist, Randomizer $randomizer): CubeOpportunityData
    {
        $dist = $context['distance'];

        // Calcolo Tonnellaggio tramite regole
        $tonsMinFactor = $this->rules->get('freight.tons_factor.min', 1);
        $tonsMaxFactor = $this->rules->get('freight.tons_factor.max', 6);
        $tonsMultiplier = $this->rules->get('freight.tons_multiplier', 10);

        $tons = $randomizer->getInt($tonsMinFactor, $tonsMaxFactor) * $tonsMultiplier;

        $baseRate = $this->economyConfig['freight_pricing'][$dist] ?? 1000;
        $total = $tons * $baseRate;

        // Selezione Spedizioniere
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
