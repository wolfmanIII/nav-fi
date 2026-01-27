<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use App\Repository\CompanyRepository;
use App\Service\GameRulesEngine;
use Random\Randomizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MailGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly CompanyRepository $companyRepo,
        private readonly GameRulesEngine $rules
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'MAIL';
    }

    public function getType(): string
    {
        return 'MAIL';
    }

    public function generate(array $context, int $maxDist, Randomizer $randomizer): CubeOpportunityData
    {
        $dist = $context['distance'];

        // Numero Contenitori (di solito 1-3)
        $minContainers = $this->rules->get('mail.containers.min', 1);
        $maxContainers = $this->rules->get('mail.containers.max', 3);
        $containers = $randomizer->getInt($minContainers, $maxContainers);

        $rate = $this->economyConfig['mail']['flat_rate'];
        $total = $containers * $rate;

        // La posta è tipicamente ufficiale
        $patron = 'Imperial Interstellar Scout Service (IISS)';

        // Probabilità Contratto Privato (es. 20%)
        $privateChance = $this->rules->get('mail.private_courier.chance', 20);

        if ($randomizer->getInt(1, 100) <= $privateChance) {
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
