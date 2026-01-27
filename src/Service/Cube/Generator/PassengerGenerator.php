<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use App\Repository\CompanyRepository;
use App\Service\GameRulesEngine;
use Random\Randomizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PassengerGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly CompanyRepository $companyRepo,
        private readonly GameRulesEngine $rules
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'PASSENGERS';
    }

    public function getType(): string
    {
        return 'PASSENGERS';
    }

    public function generate(array $context, int $maxDist, Randomizer $randomizer): CubeOpportunityData
    {
        $dist = $context['distance'];

        // Load Rules
        $minPax = $this->rules->get('passenger.pax_min', 2);
        $maxPax = $this->rules->get('passenger.pax_max', 12);

        $paxCount = $randomizer->getInt($minPax, $maxPax);

        // Determina la classe usando regole dinamiche
        $rollHigh = $this->rules->get('passenger.class_chance.high', 10);
        $rollMiddle = $this->rules->get('passenger.class_chance.middle', 40);
        $rollBasic = $this->rules->get('passenger.class_chance.basic', 80);

        $classRoll = $randomizer->getInt(1, 100);
        if ($classRoll <= $rollHigh) $class = 'high';
        elseif ($classRoll <= $rollMiddle) $class = 'middle';
        elseif ($classRoll <= $rollBasic) $class = 'basic';
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
