<?php

namespace App\Service\Cube;

use App\Entity\BrokerSession;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TheCubeEngine
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly \App\Service\RouteMathHelper $routeMath
    ) {}

    /**
     * Genera un batch di candidati basato sul seed della sessione.
     * Questo metodo è DETERMINISTICO: stessa sessione + stesso seed = stessi risultati.
     * @return \App\Dto\Cube\CubeOpportunityData[]
     */
    public function generateBatch(BrokerSession $session, array $originSystemData, array $allSystems = [], int $count = 5): array
    {
        // 1. Inizializza PRNG
        $seedString = sprintf('%s_%s_%s', $session->getSeed(), $session->getSector(), $session->getOriginHex());
        $numericSeed = crc32($seedString);
        mt_srand($numericSeed);

        $results = [];

        // Raggio di salto dalla sessione
        $maxDist = $session->getJumpRange() ?: 2;
        $originHex = $session->getOriginHex();
        $originName = $originSystemData['name'] ?? 'Unknown';

        // Filtra destinazioni valide da allSystems se fornito
        $destinations = [];
        if (!empty($allSystems)) {
            foreach ($allSystems as $sys) {
                // Salta se stesso
                if ($sys['hex'] === $originHex) continue;

                $dist = $this->routeMath->distance($originHex, $sys['hex']);
                if ($dist !== null && $dist <= $maxDist && $dist > 0) {
                    $sys['distance'] = $dist;
                    $destinations[] = $sys;
                }
            }
        }

        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->generateSingle($originName, $destinations, $maxDist, $i);
        }

        return $results;
    }

    private function generateSingle(string $originName, array $destinations, int $maxDist, int $index): \App\Dto\Cube\CubeOpportunityData
    {
        // Seleziona destinazione
        $destination = null;
        $dist = mt_rand(1, $maxDist); // Distanza di fallback
        $destName = 'Unknown';
        $destHex = '????';

        if (!empty($destinations)) {
            // Scegli un valore casuale dalla lista filtrata
            // Nota: dato che mt_srand è seedato, è deterministico per la stessa lista
            $randIndex = mt_rand(0, count($destinations) - 1);
            $destination = $destinations[$randIndex];
            $dist = $destination['distance'];
            $destName = $destination['name'];
            $destHex = $destination['hex'];
        }

        // Contesto per il payload
        $context = [
            'origin' => $originName,
            'destination' => $destName,
            'dest_hex' => $destHex,
            'distance' => $dist
        ];

        // Genera firma deterministica
        // Usiamo la stessa logica di seed usata per mt_rand, più l'indice
        // Per sicurezza potremmo usare solo le proprietà, ma l'indice è migliore per l'unicità dello "slot".
        $signature = sprintf('OPP-%s-%d', crc32(serialize($context) . $index), $index);

        // Seleziona il tipo in base a un tiro
        $roll = mt_rand(1, 100);

        $opp = null;
        if ($roll <= 40) $opp = $this->generateFreight($context, $maxDist);
        elseif ($roll <= 65) $opp = $this->generatePassenger($context, $maxDist);
        elseif ($roll <= 75) $opp = $this->generateMail($context, $maxDist);
        elseif ($roll <= 85) $opp = $this->generateContract($context);
        else $opp = $this->generateOpportunity($context);

        // Il DTO viene già creato nei metodi specifici, ma sovrascriviamo la firma
        // Nota: I metodi specifici ora ritornano CubeOpportunityData
        // Per semplicità, possiamo passare la signature ai metodi o settarla qui se il DTO fosse mutabile.
        // Essendo PHP 8.2 readonly/promoted properties spesso sono immutabili se usiamo `readonly class`.
        // Tuttavia qui abbiamo definito una classe normale.

        // Ricostruiamo il DTO con la firma corretta
        return new \App\Dto\Cube\CubeOpportunityData(
            signature: $signature,
            type: $opp->type,
            summary: $opp->summary,
            amount: $opp->amount,
            distance: $opp->distance,
            details: $opp->details
        );
    }

    private function generateFreight(array $ctx, int $maxDist): \App\Dto\Cube\CubeOpportunityData
    {
        $dist = $ctx['distance'];
        $tons = mt_rand(1, 6) * 10; // 10-60 tonnellate

        $baseRate = $this->economyConfig['freight_pricing'][$dist] ?? 1000;
        $total = $tons * $baseRate;

        return new \App\Dto\Cube\CubeOpportunityData(
            signature: '', // Sarà impostata dal chiamante
            type: 'FREIGHT',
            summary: "Freight: $tons dt to {$ctx['destination']}",
            distance: $dist,
            amount: (float)$total,
            details: [
                'origin' => $ctx['origin'],
                'destination' => $ctx['destination'],
                'tons' => $tons,
                'cargo_type' => 'General Goods',
                'dest_dist' => $dist
            ]
        );
    }

    private function generatePassenger(array $ctx, int $maxDist): \App\Dto\Cube\CubeOpportunityData
    {
        $dist = $ctx['distance'];
        $paxCount = mt_rand(2, 12);

        // Determina la classe
        $classRoll = mt_rand(1, 100);
        if ($classRoll <= 10) $class = 'high';
        elseif ($classRoll <= 40) $class = 'middle';
        elseif ($classRoll <= 80) $class = 'basic';
        else $class = 'low';

        $ticketPrice = $this->economyConfig['passengers'][$class][$dist] ?? 500;
        $total = $paxCount * $ticketPrice;

        return new \App\Dto\Cube\CubeOpportunityData(
            signature: '',
            type: 'PASSENGER',
            summary: "$paxCount x $class Passage to {$ctx['destination']}",
            distance: $dist,
            amount: (float)$total,
            details: [
                'origin' => $ctx['origin'],
                'destination' => $ctx['destination'],
                'pax' => $paxCount,
                'class' => $class,
                'dest_dist' => $dist
            ]
        );
    }

    private function generateMail(array $ctx, int $maxDist): \App\Dto\Cube\CubeOpportunityData
    {
        $dist = $ctx['distance'];
        $containers = mt_rand(1, 3);
        $rate = $this->economyConfig['mail']['flat_rate'];
        $total = $containers * $rate;

        return new \App\Dto\Cube\CubeOpportunityData(
            signature: '',
            type: 'MAIL',
            summary: "Xboat Mail ($containers cont.) to {$ctx['destination']}",
            distance: $dist,
            amount: (float)$total,
            details: [
                'origin' => $ctx['origin'],
                'destination' => $ctx['destination'],
                'containers' => $containers,
                'tons' => $containers * 5,
                'priority' => 'High'
            ]
        );
    }

    private function generateContract(array $ctx): \App\Dto\Cube\CubeOpportunityData
    {
        $base = mt_rand(
            $this->economyConfig['contract']['base_reward_min'],
            $this->economyConfig['contract']['base_reward_max']
        );

        $hasBonus = (mt_rand(1, 100) <= ($this->economyConfig['contract']['bonus_chance'] * 100));
        if ($hasBonus) {
            $base += ($base * $this->economyConfig['contract']['bonus_multiplier']);
        }

        return new \App\Dto\Cube\CubeOpportunityData(
            signature: '',
            type: 'CONTRACT',
            summary: "Patron Mission (Local)",
            distance: 0,
            amount: (float)$base,
            details: [
                'origin' => $ctx['origin'],
                'destination' => 'Local/System',
                'patron' => 'Local Corp',
                'difficulty' => 'Standard'
            ]
        );
    }

    private function generateOpportunity(array $ctx): \App\Dto\Cube\CubeOpportunityData
    {
        // Segnaposto per commercio speculativo
        // Per MVP, generiamo una semplice opportunità "compra basso, vendi alto"
        $resources = ['Radioactives', 'Metals', 'Crystals', 'Luxuries', 'Electronics', 'Pharmaceuticals'];
        $resource = $resources[mt_rand(0, count($resources) - 1)];

        $buyPrice = mt_rand(1000, 5000);
        $markup = mt_rand(120, 180) / 100; // 1.2x to 1.8x
        $estimatedProfit = (int)($buyPrice * ($markup - 1));

        return new \App\Dto\Cube\CubeOpportunityData(
            signature: '',
            type: 'TRADE',
            summary: "Speculative Trade: $resource",
            distance: 0,
            amount: (float)$estimatedProfit,
            details: [
                'origin' => $ctx['origin'],
                'destination' => 'Market',
                'resource' => $resource,
                'buy_price' => $buyPrice,
                'markup_estimate' => $markup,
                'risk_level' => 'Medium'
            ]
        );
    }
}
