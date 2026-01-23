<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class RouteOptimizationService
{
    public function __construct(
        private readonly TravellerMapSectorLookup $sectorLookup,
        private readonly RouteMathHelper $mathHelper,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Trova la rotta più breve che visita tutte le destinazioni partendo da startHex.
     * 
     * @param string $sectorName Il nome del settore
     * @param string $startHex Hex di partenza (es. 0101)
     * @param string[] $destinations Lista degli Hex di destinazione
     * @param int $jumpRating Rating di Salto della nave
     * @return array{path: string[], total_jumps: int}
     */
    public function optimizeMultiStopRoute(string $sectorName, string $startHex, array $destinations, int $jumpRating): array
    {
        // 1. Carica i Dati del Settore e indicizzali per Hex per lookup veloce
        $systems = $this->sectorLookup->parseSector($sectorName);
        $systemMap = []; // Hex -> Data
        foreach ($systems as $sys) {
            $systemMap[$sys['hex']] = $sys;
        }

        // Valida esistenza di partenza e destinazioni
        if (!isset($systemMap[$startHex])) {
            throw new \InvalidArgumentException("Hex di partenza $startHex non trovato nel settore $sectorName");
        }
        foreach ($destinations as $dest) {
            if (!isset($systemMap[$dest])) {
                throw new \InvalidArgumentException("Hex di destinazione $dest non trovato nel settore $sectorName");
            }
        }

        // 2. Risolvi il TSP (Brute Force per N piccolo, solitamente N <= 5 destinazioni)
        // Dato che la lista destinazioni è piccola (Nav-Fi gestisce di solito < 5 waypoint), le permutazioni vanno bene.
        $permutations = $this->getPermutations($destinations);
        
        $bestRoute = null;
        $minTotalJumps = PHP_INT_MAX;

        foreach ($permutations as $perm) {
            $currentHex = $startHex;
            $fullPath = [$startHex];
            $totalJumps = 0;
            $possible = true;

            foreach ($perm as $destHex) {
                // Trova percorso da Corrente a Prossima Destinazione
                $path = $this->findShortestPath($systemMap, $currentHex, $destHex, $jumpRating);
                
                if ($path === null) {
                    $possible = false;
                    break;
                }

                // Aggiungi percorso (escludendo il nodo iniziale che è già in fullPath)
                $segmentJumps = count($path) - 1;
                $totalJumps += $segmentJumps;
                
                // Aggiungi tappe intermedie e destinazione
                for ($i = 1; $i < count($path); $i++) {
                    $fullPath[] = $path[$i];
                }
                
                $currentHex = $destHex;
            }

            if ($possible && $totalJumps < $minTotalJumps) {
                $minTotalJumps = $totalJumps;
                $bestRoute = $fullPath;
            }
        }

        if ($bestRoute === null) {
            throw new \RuntimeException("Nessuna rotta valida trovata per collegare tutte le destinazioni con Jump-$jumpRating");
        }

        return [
            'path' => $bestRoute,
            'total_jumps' => $minTotalJumps
        ];
    }

    /**
     * Algoritmo di Pathfinding A* (A-Star)
     * Restituisce array di hex [Start, ..., End] o null se irraggiungibile.
     */
    public function findShortestPath(array $systemMap, string $startHex, string $endHex, int $jumpRating): ?array
    {
        if ($startHex === $endHex) {
            return [$startHex];
        }

        // Coda di Priorità: [hex, f_score]
        $openSet = [$startHex];
        $cameFrom = []; // hex -> hex_genitore
        
        $gScore = [$startHex => 0]; // Costo dalla partenza
        $fScore = [$startHex => $this->heuristic($startHex, $endHex, $jumpRating)]; // Costo totale stimato

        while (!empty($openSet)) {
            // Trova nodo in openSet con fScore più basso
            $current = null;
            $lowestF = PHP_INT_MAX;
            $currentIndex = -1;

            foreach ($openSet as $idx => $hex) {
                $score = $fScore[$hex] ?? PHP_INT_MAX;
                if ($score < $lowestF) {
                    $lowestF = $score;
                    $current = $hex;
                    $currentIndex = $idx;
                }
            }

            if ($current === $endHex) {
                return $this->reconstructPath($cameFrom, $current);
            }

            // Rimuovi current da openSet
            array_splice($openSet, $currentIndex, 1);

            // Trova Vicini (Sistemi raggiungibili entro il Rating di Salto)
            // Ottimizzazione: Invece di iterare 1000 sistemi, calcoliamo range valido? 
            // Per ora, iteriamo ma ottimizziamo il check distanza.
            $neighbors = $this->findReachableNeighbors($systemMap, $current, $jumpRating);

            foreach ($neighbors as $neighbor) {
                // Il costo è sempre 1 salto
                $tentativeG = $gScore[$current] + 1;

                if ($tentativeG < ($gScore[$neighbor] ?? PHP_INT_MAX)) {
                    $cameFrom[$neighbor] = $current;
                    $gScore[$neighbor] = $tentativeG;
                    $fScore[$neighbor] = $tentativeG + $this->heuristic($neighbor, $endHex, $jumpRating);

                    if (!in_array($neighbor, $openSet)) {
                        $openSet[] = $neighbor;
                    }
                }
            }
        }

        return null; // Percorso non trovato
    }

    private function findReachableNeighbors(array $systemMap, string $centerHex, int $jumpRating): array
    {
        $neighbors = [];
        // Ottimizzazione geometrica:
        // Iteriamo su tutta la mappa (brute force su ~1000 item è veloce in PHP)
        // TODO: Ottimizzare se i settori diventano enormi.
        
        foreach ($systemMap as $hex => $data) {
            if ($hex === $centerHex) continue;

            $dist = $this->mathHelper->distance($centerHex, $hex);
            if ($dist !== null && $dist <= $jumpRating) {
                $neighbors[] = $hex;
            }
        }
        return $neighbors;
    }

    private function heuristic(string $from, string $to, int $jumpRating): float
    {
        // Salti minimi necessari = Distanza / JumpRating
        $dist = $this->mathHelper->distance($from, $to);
        return $dist / $jumpRating;
    }

    private function reconstructPath(array $cameFrom, string $current): array
    {
        $totalPath = [$current];
        while (isset($cameFrom[$current])) {
            $current = $cameFrom[$current];
            $totalPath[] = $current;
        }
        return array_reverse($totalPath);
    }

    private function getPermutations(array $elements): array
    {
        if (count($elements) <= 1) {
            return [$elements];
        }

        $permutations = [];
        foreach ($elements as $key => $element) {
            $remaining = $elements;
            unset($remaining[$key]);
            
            foreach ($this->getPermutations(array_values($remaining)) as $perm) {
                $permutations[] = array_merge([$element], $perm);
            }
        }
        return $permutations;
    }
}
