<?php

namespace App\Service\Cube;

use App\Entity\BrokerSession;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TheCubeEngine
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig
    ) {}

    /**
     * Generates a batch of candidates based on the session seed.
     * This method is DETERMINISTIC: same session + same seed = same results.
     */
    public function generateBatch(BrokerSession $session, array $originSystemData, array $allSystems = [], int $count = 5): array
    {
        // 1. Initialize PRNG
        $seedString = sprintf('%s_%s_%s', $session->getSeed(), $session->getSector(), $session->getOriginHex());
        $numericSeed = crc32($seedString);
        mt_srand($numericSeed);

        $results = [];

        // Jump range from session
        $maxDist = $session->getJumpRange() ?: 2;
        $originHex = $session->getOriginHex();
        $originName = $originSystemData['name'] ?? 'Unknown';

        // Filter valid destinations from allSystems if provided
        $destinations = [];
        if (!empty($allSystems)) {
            foreach ($allSystems as $sys) {
                // Skip self
                if ($sys['hex'] === $originHex) continue;

                $dist = $this->calculateDistance($originHex, $sys['hex']);
                if ($dist <= $maxDist && $dist > 0) {
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

    private function generateSingle(string $originName, array $destinations, int $maxDist, int $index): array
    {
        // Select Destination
        $destination = null;
        $dist = mt_rand(1, $maxDist); // Fallback distance
        $destName = 'Unknown';
        $destHex = '????';

        if (!empty($destinations)) {
            // Pick rand from filtered list
            // Note: Since mt_srand is seeded, this is deterministic for the same list
            $randIndex = mt_rand(0, count($destinations) - 1);
            $destination = $destinations[$randIndex];
            $dist = $destination['distance'];
            $destName = $destination['name'];
            $destHex = $destination['hex'];
        }

        // Context for payload
        $context = [
            'origin' => $originName,
            'destination' => $destName,
            'dest_hex' => $destHex,
            'distance' => $dist
        ];

        // Generate Deterministic Signature
        // We use the same seed logic that seeded mt_rand, plus the index
        // To be safe, we can just use the properties. But index is best for "slot" uniqueness.
        $signature = sprintf('OPP-%s-%d', crc32(serialize($context) . $index), $index);

        // Select type based on a roll
        $roll = mt_rand(1, 100);

        $opp = [];
        if ($roll <= 40) $opp = $this->generateFreight($context, $maxDist);
        elseif ($roll <= 65) $opp = $this->generatePassenger($context, $maxDist);
        elseif ($roll <= 75) $opp = $this->generateMail($context, $maxDist);
        elseif ($roll <= 85) $opp = $this->generateContract($context);
        else $opp = $this->generateOpportunity($context);

        $opp['signature'] = $signature;
        return $opp;
    }

    private function generateFreight(array $ctx, int $maxDist): array
    {
        $dist = $ctx['distance'];
        $tons = mt_rand(1, 6) * 10; // 10-60 tons

        $baseRate = $this->economyConfig['freight_pricing'][$dist] ?? 1000;
        $total = $tons * $baseRate;

        return [
            'type' => 'FREIGHT',
            'summary' => "Freight: $tons dt to {$ctx['destination']}",
            'distance' => $dist,
            'amount' => $total,
            'details' => [
                'origin' => $ctx['origin'],
                'destination' => $ctx['destination'],
                'tons' => $tons,
                'cargo_type' => 'General Goods',
                'dest_dist' => $dist
            ]
        ];
    }

    private function generatePassenger(array $ctx, int $maxDist): array
    {
        $dist = $ctx['distance'];
        $paxCount = mt_rand(2, 12);

        // Determine class
        $classRoll = mt_rand(1, 100);
        if ($classRoll <= 10) $class = 'high';
        elseif ($classRoll <= 40) $class = 'middle';
        elseif ($classRoll <= 80) $class = 'basic';
        else $class = 'low';

        $ticketPrice = $this->economyConfig['passengers'][$class][$dist] ?? 500;
        $total = $paxCount * $ticketPrice;

        return [
            'type' => 'PASSENGER',
            'summary' => "$paxCount x $class Passage to {$ctx['destination']}",
            'distance' => $dist,
            'amount' => $total,
            'details' => [
                'origin' => $ctx['origin'],
                'destination' => $ctx['destination'],
                'pax' => $paxCount,
                'class' => $class,
                'dest_dist' => $dist
            ]
        ];
    }

    private function generateMail(array $ctx, int $maxDist): array
    {
        $dist = $ctx['distance'];
        $containers = mt_rand(1, 3);
        $rate = $this->economyConfig['mail']['flat_rate'];
        $total = $containers * $rate;

        return [
            'type' => 'MAIL',
            'summary' => "Xboat Mail ($containers cont.) to {$ctx['destination']}",
            'distance' => $dist,
            'amount' => $total,
            'details' => [
                'origin' => $ctx['origin'],
                'destination' => $ctx['destination'],
                'containers' => $containers,
                'tons' => $containers * 5,
                'priority' => 'High'
            ]
        ];
    }

    private function generateContract(array $ctx): array
    {
        $base = mt_rand(
            $this->economyConfig['contract']['base_reward_min'],
            $this->economyConfig['contract']['base_reward_max']
        );

        $hasBonus = (mt_rand(1, 100) <= ($this->economyConfig['contract']['bonus_chance'] * 100));
        if ($hasBonus) {
            $base += ($base * $this->economyConfig['contract']['bonus_multiplier']);
        }

        return [
            'type' => 'CONTRACT',
            'summary' => "Patron Mission (Local)",
            'distance' => 0,
            'amount' => (int)$base,
            'details' => [
                'origin' => $ctx['origin'],
                'destination' => 'Local/System',
                'patron' => 'Local Corp',
                'difficulty' => 'Standard'
            ]
        ];
    }

    private function generateOpportunity(array $ctx): array
    {
        // Placeholder for speculative trade
        // For MVP, we generate a simple "buy low, sell high" opportunity
        $resources = ['Radioactives', 'Metals', 'Crystals', 'Luxuries', 'Electronics', 'Pharmaceuticals'];
        $resource = $resources[mt_rand(0, count($resources) - 1)];

        $buyPrice = mt_rand(1000, 5000);
        $markup = mt_rand(120, 180) / 100; // 1.2x to 1.8x
        $estimatedProfit = (int)($buyPrice * ($markup - 1));

        return [
            'type' => 'TRADE',
            'summary' => "Speculative Trade: $resource",
            'distance' => 0, // Market-based, not route-based
            'amount' => $estimatedProfit, // Estimated profit
            'details' => [
                'origin' => $ctx['origin'],
                'destination' => 'Market',
                'resource' => $resource,
                'buy_price' => $buyPrice,
                'markup_estimate' => $markup,
                'risk_level' => 'Medium'
            ]
        ];
    }

    private function calculateDistance(string $hex1, string $hex2): int
    {
        // Parse "1910" -> C=19, R=10
        $c1 = (int)substr($hex1, 0, 2);
        $r1 = (int)substr($hex1, 2, 2);

        $c2 = (int)substr($hex2, 0, 2);
        $r2 = (int)substr($hex2, 2, 2);

        // Convert to Axial (q, r)
        // Using "odd-q" vertical layout assumption for Traveller
        // q = col
        // r = row - (col - (col&1)) / 2
        $q1 = $c1;
        $r1_axial = $r1 - floor($c1 / 2); // floor for even/odd stagger? Standard maps often use floor

        $q2 = $c2;
        $r2_axial = $r2 - floor($c2 / 2);

        // Euclidean on Axial (Hex distance)
        // dist = (abs(q1 - q2) + abs(r1 - r2) + abs(q1 + r1 - q2 - r2)) / 2

        return (int) ((abs($q1 - $q2) + abs($r1_axial - $r2_axial) + abs($q1 + $r1_axial - $q2 - $r2_axial)) / 2);
    }
}
