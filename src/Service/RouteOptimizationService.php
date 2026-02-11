<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class RouteOptimizationService
{
    /**
     * @var array<string, array{x: int, y: int}>
     */
    /**
     * @var array<string, array{x: int, y: int}>
     */
    private array $sectorCoordsMap = [];

    /**
     * @var array<string, array<string>>
     */
    private array $grid = [];

    /**
     * @var array<string, array<string, array{path: string[], jumps: int}>>
     */
    private array $pathCache = [];

    private const GRID_SIZE = 10;

    public function __construct(
        private readonly TravellerMapSectorLookup $sectorLookup,
        private readonly TravellerMapDataService $dataService,
        private readonly RouteMathHelper $mathHelper,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Restituisce le coordinate di un settore caricato durante l'ottimizzazione.
     */
    public function findSectorCoords(string $sectorName): array
    {
        return $this->sectorCoordsMap[$sectorName] ?? ['x' => 0, 'y' => 0];
    }

    /**
     * Trova la rotta più breve che visita tutte le destinazioni partendo da startPoint.
     */
    public function optimizeMultiStopRoute(array $startPoint, array $destinations, int $jumpRating): array
    {
        // 1. Carica i dati di tutti i settori coinvolti
        $involvedSectors = array_unique(array_merge(
            [$startPoint['sector']],
            array_column($destinations, 'sector')
        ));

        $systemMap = []; 
        $this->sectorCoordsMap = [];
        $this->grid = [];
        $this->pathCache = [];

        foreach ($involvedSectors as $sectorName) {
            $systems = $this->sectorLookup->parseSector($sectorName);
            $coords = $this->dataService->getSectorCoordinates($sectorName) ?? ['x' => 0, 'y' => 0];
            $this->sectorCoordsMap[$sectorName] = $coords;

            foreach ($systems as $sys) {
                $key = $sectorName . ':' . $sys['hex'];
                
                // Pre-calcola coordinate cubiche globali
                $cube = $this->mathHelper->getGlobalCubeCoordinates($sys['hex'], $coords);
                
                $sys['sector'] = $sectorName;
                $sys['cube'] = $cube;
                $systemMap[$key] = $sys;

                // Spatial Indexing
                $gx = (int) floor($cube[0] / self::GRID_SIZE);
                $gy = (int) floor($cube[1] / self::GRID_SIZE);
                $gKey = "$gx:$gy";
                $this->grid[$gKey][] = $key;
            }
        }

        $startKey = $startPoint['sector'] . ':' . $startPoint['hex'];
        $destKeys = array_map(fn($d) => $d['sector'] . ':' . $d['hex'], $destinations);

        // Valida esistenza
        if (!isset($systemMap[$startKey])) {
            throw new \InvalidArgumentException("Partenza {$startPoint['hex']} non trovata");
        }
        foreach ($destKeys as $key) {
            if (!isset($systemMap[$key])) {
                throw new \InvalidArgumentException("Destinazione $key non trovata");
            }
        }

        // 2. Pre-calcola Matrice delle Distanze (A* Cache)
        $allWaypoints = array_unique(array_merge([$startKey], $destKeys));
        foreach ($allWaypoints as $from) {
            foreach ($allWaypoints as $to) {
                if ($from === $to) continue;
                if (isset($this->pathCache[$from][$to])) continue;

                $path = $this->findShortestPath($systemMap, $from, $to, $jumpRating);
                if ($path !== null) {
                    $this->pathCache[$from][$to] = [
                        'path' => $path,
                        'jumps' => count($path) - 1
                    ];
                }
            }
        }

        // 3. Risolvi TSP usando la cache
        if (count($destinations) <= 6) {
            $bestRouteKeys = $this->solveTspBruteForce($startKey, $destKeys);
        } else {
            $bestRouteKeys = $this->solveTspNearestNeighbor($startKey, $destKeys);
        }

        // Converti i key di ritorno in dati strutturati
        $finalPath = array_map(function($key) use ($systemMap) {
            return [
                'sector' => $systemMap[$key]['sector'],
                'hex' => $systemMap[$key]['hex']
            ];
        }, $bestRouteKeys['path']);

        return [
            'path' => $finalPath,
            'total_jumps' => $bestRouteKeys['total_jumps']
        ];
    }

    private function solveTspBruteForce(string $startKey, array $destKeys): array
    {
        $permutations = $this->getPermutations($destKeys);

        $bestRoute = null;
        $minTotalJumps = PHP_INT_MAX;

        foreach ($permutations as $perm) {
            $currentKey = $startKey;
            $fullPath = [$startKey];
            $totalJumps = 0;
            $possible = true;

            foreach ($perm as $destKey) {
                if (!isset($this->pathCache[$currentKey][$destKey])) {
                    $possible = false;
                    break;
                }

                $cached = $this->pathCache[$currentKey][$destKey];
                $totalJumps += $cached['jumps'];

                for ($i = 1; $i < count($cached['path']); $i++) {
                    $fullPath[] = $cached['path'][$i];
                }

                $currentKey = $destKey;
            }

            if ($possible && $totalJumps < $minTotalJumps) {
                $minTotalJumps = $totalJumps;
                $bestRoute = $fullPath;
            }
        }

        if ($bestRoute === null) {
            throw new \RuntimeException("Nessuna rotta valida trovata");
        }

        return ['path' => $bestRoute, 'total_jumps' => $minTotalJumps];
    }

    private function solveTspNearestNeighbor(string $startKey, array $destKeys): array
    {
        $unvisited = $destKeys;
        $currentKey = $startKey;
        $fullPath = [$startKey];
        $totalJumps = 0;

        while (!empty($unvisited)) {
            $nearestKey = null;
            $maxJumps = PHP_INT_MAX;
            $bestIdx = null;

            foreach ($unvisited as $idx => $candKey) {
                if (!isset($this->pathCache[$currentKey][$candKey])) continue;
                
                $jumps = $this->pathCache[$currentKey][$candKey]['jumps'];
                if ($jumps < $maxJumps) {
                    $maxJumps = $jumps;
                    $nearestKey = $candKey;
                    $bestIdx = $idx;
                }
            }

            if ($nearestKey === null) {
                throw new \RuntimeException("Impossibile raggiungere le destinazioni rimanenti");
            }

            $cached = $this->pathCache[$currentKey][$nearestKey];
            $totalJumps += $cached['jumps'];
            for ($i = 1; $i < count($cached['path']); $i++) {
                $fullPath[] = $cached['path'][$i];
            }

            $currentKey = $nearestKey;
            unset($unvisited[$bestIdx]);
        }

        return ['path' => $fullPath, 'total_jumps' => $totalJumps];
    }

    public function findShortestPath(array $systemMap, string $startKey, string $endKey, int $jumpRating): ?array
    {
        if ($startKey === $endKey) return [$startKey];

        $openSet = [$startKey];
        $cameFrom = [];
        $gScore = [$startKey => 0];
        $fScore = [$startKey => $this->heuristic($systemMap[$startKey]['cube'], $systemMap[$endKey]['cube'], $jumpRating)];

        while (!empty($openSet)) {
            $current = null;
            $lowestF = PHP_INT_MAX;
            $currentIndex = -1;

            foreach ($openSet as $idx => $key) {
                $score = $fScore[$key] ?? PHP_INT_MAX;
                if ($score < $lowestF) {
                    $lowestF = $score;
                    $current = $key;
                    $currentIndex = $idx;
                }
            }

            if ($current === $endKey) return $this->reconstructPath($cameFrom, $current);

            array_splice($openSet, $currentIndex, 1);

            $neighbors = $this->findReachableNeighbors($systemMap, $current, $jumpRating);

            foreach ($neighbors as $neighbor) {
                $tentativeG = $gScore[$current] + 1;

                if ($tentativeG < ($gScore[$neighbor] ?? PHP_INT_MAX)) {
                    $cameFrom[$neighbor] = $current;
                    $gScore[$neighbor] = $tentativeG;
                    $fScore[$neighbor] = $tentativeG + $this->heuristic($systemMap[$neighbor]['cube'], $systemMap[$endKey]['cube'], $jumpRating);

                    if (!in_array($neighbor, $openSet)) {
                        $openSet[] = $neighbor;
                    }
                }
            }
        }

        return null;
    }

    private function findReachableNeighbors(array $systemMap, string $centerKey, int $jumpRating): array
    {
        $neighbors = [];
        $centerCube = $systemMap[$centerKey]['cube'];

        $gx = (int) floor($centerCube[0] / self::GRID_SIZE);
        $gy = (int) floor($centerCube[1] / self::GRID_SIZE);

        for ($dx = -1; $dx <= 1; $dx++) {
            for ($dy = -1; $dy <= 1; $dy++) {
                $gKey = ($gx + $dx) . ":" . ($gy + $dy);
                if (!isset($this->grid[$gKey])) continue;

                foreach ($this->grid[$gKey] as $key) {
                    if ($key === $centerKey) continue;

                    $dist = $this->cubeDistance($centerCube, $systemMap[$key]['cube']);
                    if ($dist <= $jumpRating) {
                        $neighbors[] = $key;
                    }
                }
            }
        }
        return $neighbors;
    }

    private function cubeDistance(array $c1, array $c2): int
    {
        return (int) max(abs($c1[0] - $c2[0]), abs($c1[1] - $c2[1]), abs($c1[2] - $c2[2]));
    }

    private function heuristic(array $c1, array $c2, int $jumpRating): float
    {
        return $this->cubeDistance($c1, $c2) / $jumpRating;
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
        if (count($elements) <= 1) return [$elements];

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
