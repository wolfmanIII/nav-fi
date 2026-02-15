<?php

namespace App\Service\Cube;

use App\Entity\BrokerSession;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use App\Service\Cube\Generator\OpportunityGeneratorInterface;
use App\Service\RouteMathHelper;
use App\Dto\Cube\CubeOpportunityData;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use App\Entity\User;

class TheCubeEngine
{
    /**
     * @param iterable<OpportunityGeneratorInterface> $generators
     */
    public function __construct(
        #[AutowireIterator('app.cube.generator')]
        private readonly iterable $generators,
        private readonly RouteMathHelper $routeMath
    ) {}

    /**
     * Genera un batch di candidati basato sul seed della sessione.
     * Questo metodo è DETERMINISTICO: stessa sessione + stesso seed = stessi risultati.
     * Utilizza Random\Engine\Xoshiro256StarStar per garantire isolamento dallo stato globale (mt_srand).
     *
     * @return CubeOpportunityData[]
     */
    public function generateBatch(BrokerSession $session, array $originSystemData, array $allSystems = [], ?int $count = null): array
    {
        // 1. Inizializza PRNG Isolato (PHP 8.2+)
        $seedString = sprintf('%s_%s_%s', $session->getSeed(), $session->getSector(), $session->getOriginHex());
        // Calcola l'hash del seed per ottenere una stringa binaria robusta per Xoshiro
        $seedHash = hash('sha256', $seedString, true);

        $engine = new Xoshiro256StarStar($seedHash);
        $randomizer = new Randomizer($engine);

        // Determina il numero in modo deterministico basandosi sul seed, se non fornito
        if ($count === null) {
            $count = $randomizer->getInt(10, 20);
        }

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

        $campaign = $session->getCampaign();
        $sessionDay = $campaign?->getSessionDay() ?? 1;
        $sessionYear = $campaign?->getSessionYear() ?? 1105;

        $sector = $session->getSector();
        $user = $campaign?->getUser();

        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->generateSingle($randomizer, $originName, $originHex, $destinations, $maxDist, $i, $sessionDay, $sessionYear, $sector, $user);
        }

        return $results;
    }

    private function generateSingle(Randomizer $randomizer, string $originName, string $originHex, array $destinations, int $maxDist, int $index, int $sessionDay, int $sessionYear, string $sector, ?User $user): CubeOpportunityData
    {
        // Seleziona destinazione
        $destination = null;
        $dist = $randomizer->getInt(1, $maxDist); // Distanza di fallback
        $destName = 'Unknown';
        $destHex = '????';

        if (!empty($destinations)) {
            // Scegli un valore casuale dalla lista filtrata
            // Nota: dato che l'engine è seedato, è deterministico per la stessa lista
            $randIndex = $randomizer->getInt(0, count($destinations) - 1);
            $destination = $destinations[$randIndex];
            $dist = $destination['distance'];
            $destName = $destination['name'];
            $destHex = $destination['hex'];
        }

        // Contesto per il payload
        $context = [
            'origin' => $originName,
            'origin_hex' => $originHex,
            'destination' => $destName,
            'dest_hex' => $destHex,
            'distance' => $dist,
            'session_day' => $sessionDay,
            'session_year' => $sessionYear,
            'sector' => $sector,
            'user' => $user
        ];

        // Genera firma deterministica (rimane basata su CRC32 per compatibilità o semplicità, 
        // ma la logica di gioco usa il randomizer)
        // Nota: Qui usiamo serialize. Per il determinismo puro, il context deve essere ordinato.
        // Ma va bene così per ora.
        $signature = sprintf('OPP-%s-%d', crc32(serialize($context) . $index), $index);

        // Seleziona il tipo in base a un tiro
        $roll = $randomizer->getInt(1, 100);

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
            // Fallback su TRADE se non trovato (non dovrebbe accadere se configurato correttamente)
            foreach ($this->generators as $generator) {
                if ($generator->supports('TRADE')) {
                    $selectedGenerator = $generator;
                    break;
                }
            }
        }

        if (!$selectedGenerator) {
            throw new RuntimeException("No suitable generator found for type: $type");
        }

        $opp = $selectedGenerator->generate($context, $maxDist, $randomizer);

        // Ricostruiamo il DTO con la firma corretta
        return new CubeOpportunityData(
            signature: $signature,
            type: $opp->type,
            summary: $opp->summary,
            amount: $opp->amount,
            distance: $opp->distance,
            details: $opp->details
        );
    }
}
