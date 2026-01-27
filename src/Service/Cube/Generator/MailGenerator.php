<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MailGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly \App\Repository\CompanyRepository $companyRepo
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'MAIL';
    }

    public function getType(): string
    {
        return 'MAIL';
    }

    public function generate(array $context, int $maxDist, \Random\Randomizer $randomizer): CubeOpportunityData
    {
        $dist = $context['distance'];
        $containers = $randomizer->getInt(1, 3);
        $rate = $this->economyConfig['mail']['flat_rate'];
        $total = $containers * $rate;

        // Mail is usually official
        $patron = 'Imperial Interstellar Scout Service (IISS)';
        // 20% chance of Private Courier Contract
        if ($randomizer->getInt(1, 100) <= 20) {
            $companies = $this->companyRepo->findAll();
            if (!empty($companies)) {
                $c = $companies[$randomizer->getInt(0, count($companies) - 1)];
                $patron = $c->getName();
            } else {
                $patron = 'Private Courier Network';
            }
        }

        return new CubeOpportunityData(
            signature: '',
            type: 'MAIL',
            summary: "Xboat Mail ($containers cont.) to {$context['destination']}",
            distance: $dist,
            amount: (float)$total,
            details: [
                'origin' => $context['origin'],
                'destination' => $context['destination'],
                'dest_hex' => $context['dest_hex'] ?? '????',
                'containers' => $containers,
                'tons' => $containers * 5,
                'priority' => 'High',
                'patron' => $patron,
                'start_day' => $context['session_day'],
                'start_year' => $context['session_year']
            ]
        );
    }
}
