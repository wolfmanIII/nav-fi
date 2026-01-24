<?php

namespace App\Service\Cube;

use App\Entity\BrokerSession;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class TheCubeEngine
{
    /**
     * @param iterable<\App\Service\Cube\Generator\OpportunityGeneratorInterface> $generators
     */
    public function __construct(
        #[AutowireIterator('app.cube.generator')]
        private readonly iterable $generators,
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
        $signature = sprintf('OPP-%s-%d', crc32(serialize($context) . $index), $index);

        // Seleziona il tipo in base a un tiro
        $roll = mt_rand(1, 100);

        $type = 'TRADE';
        if ($roll <= 40) $type = 'FREIGHT';
        elseif ($roll <= 65) $type = 'PASSENGER';
        elseif ($roll <= 75) $type = 'MAIL';
        elseif ($roll <= 85) $type = 'CONTRACT';

        // Trova il generatore giusto
        $selectedGenerator = null;
        foreach ($this->generators as $generator) {
            if ($generator->supports($type)) {
                $selectedGenerator = $generator;
                break;
            }
        }

        if (!$selectedGenerator) {
            // Fallback to TRADE if not found (should not happen if configured correctly)
            // or throw exception. For robustness, logic fallback:
            foreach ($this->generators as $generator) {
                if ($generator->supports('TRADE')) {
                    $selectedGenerator = $generator;
                    break;
                }
            }
        }

        if (!$selectedGenerator) {
            throw new \RuntimeException("No suitable generator found for type: $type");
        }

        $opp = $selectedGenerator->generate($context, $maxDist);

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
}
