<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PassengerGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly \App\Repository\CompanyRepository $companyRepo
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'PASSENGERS';
    }

    public function getType(): string
    {
        return 'PASSENGERS';
    }

    public function generate(array $context, int $maxDist, \Random\Randomizer $randomizer): CubeOpportunityData
    {
        $dist = $context['distance'];
        $paxCount = $randomizer->getInt(2, 12);

        // Determina la classe
        $classRoll = $randomizer->getInt(1, 100);
        if ($classRoll <= 10) $class = 'high';
        elseif ($classRoll <= 40) $class = 'middle';
        elseif ($classRoll <= 80) $class = 'basic';
        else $class = 'low';

        $ticketPrice = $this->economyConfig['passengers'][$class][$dist] ?? 500;
        $total = $paxCount * $ticketPrice;

        // Select Booking Agent
        $companies = $this->companyRepo->findAll();
        $agent = null;
        if (!empty($companies)) {
            $agent = $companies[$randomizer->getInt(0, count($companies) - 1)];
        }

        return new CubeOpportunityData(
            signature: '',
            type: 'PASSENGERS',
            summary: "$paxCount x $class Passage to {$context['destination']}",
            distance: $dist,
            amount: (float)$total,
            details: [
                'origin' => $context['origin'],
                'destination' => $context['destination'],
                'dest_hex' => $context['dest_hex'] ?? '????',
                'pax' => $paxCount,
                'class' => $class,
                'dest_dist' => $dist,
                'company_id' => $agent?->getId(),
                'patron' => $agent?->getName() ?? 'Starport Authority',
                'start_day' => $context['session_day'],
                'start_year' => $context['session_year']
            ]
        );
    }
}
