<?php

namespace App\Service;

use App\Entity\Route;
use App\Entity\Asset;

class RouteMathHelper
{
    public function parseHex(string $hex): ?array
    {
        $normalized = trim($hex);
        if (!preg_match('/^\d{4}$/', $normalized)) {
            return null;
        }

        $col = (int) substr($normalized, 0, 2);
        $row = (int) substr($normalized, 2, 2);

        return [$col, $row];
    }

    public function distance(string $fromHex, string $toHex, ?array $fromSectorCoords = null, ?array $toSectorCoords = null): ?int
    {
        $from = $this->parseHex($fromHex);
        $to = $this->parseHex($toHex);
        if ($from === null || $to === null) {
            return null;
        }

        // Se abbiamo le coordinate dei settori, usiamo il sistema globale di TravellerMap
        // Un settore è 32x40 esagoni.
        if ($fromSectorCoords !== null && $toSectorCoords !== null) {
            $x1 = ($fromSectorCoords['x'] * 32) + $from[0];
            $y1 = ($fromSectorCoords['y'] * 40) + $from[1];

            $x2 = ($toSectorCoords['x'] * 32) + $to[0];
            $y2 = ($toSectorCoords['y'] * 40) + $to[1];

            $from = [$x1, $y1];
            $to = [$x2, $y2];
        }

        [$x1, $y1, $z1] = $this->offsetToCube($from[0], $from[1]);
        [$x2, $y2, $z2] = $this->offsetToCube($to[0], $to[1]);

        return max(
            abs($x1 - $x2),
            abs($y1 - $y2),
            abs($z1 - $z2)
        );
    }

    /**
     * @param string[] $hexes
     *
     * @return array<int, int|null>
     */
    public function segmentDistances(array $hexes): array
    {
        $distances = [];
        $previous = null;
        foreach ($hexes as $hex) {
            if ($previous === null) {
                $distances[] = null;
                $previous = $hex;
                continue;
            }

            $distances[] = $this->distance($previous, $hex);
            $previous = $hex;
        }

        return $distances;
    }

    /**
     * @param array<int, int|null> $distances
     */
    public function sumDistances(array $distances): int
    {
        $total = 0;
        foreach ($distances as $distance) {
            if ($distance !== null) {
                $total += $distance;
            }
        }

        return $total;
    }

    /**
     * @param array<int, int|null> $distances
     */
    public function estimateJumpFuel(Route $route, array $distances): ?string
    {
        $hullTons = $this->getAssetHullTonnage($route->getAsset());
        if ($hullTons === null) {
            return null;
        }

        // Consideriamo solo il carburante per l'ULTIMO segmento di salto aggiunto alla rotta,
        // come da richiesta utente: "quanto serve per la prossimo waypoint dall l'ultimo waypoint inserito"
        $lastDistance = null;
        foreach (array_reverse($distances) as $distance) {
            if ($distance !== null) {
                $lastDistance = $distance;
                break;
            }
        }

        if ($lastDistance === null) {
            return null;
        }

        // Formula: 0.1 * scafo * distanza ultimo segmento
        // (10% del tonnellaggio dello scafo per numero/distanza del salto)
        $fuel = 0.1 * $hullTons * $lastDistance;

        return sprintf('%.2f', $fuel);
    }

    public function totalRequiredFuel(Route $route): ?string
    {
        $hullTons = $this->getAssetHullTonnage($route->getAsset());
        if ($hullTons === null) {
            return null;
        }

        $hexes = [];
        foreach ($route->getWaypoints() as $waypoint) {
            $hexes[] = (string) $waypoint->getHex();
        }

        $distances = $this->segmentDistances($hexes);
        $totalJumpNumber = 0;
        foreach ($distances as $distance) {
            if ($distance !== null) {
                $totalJumpNumber += $distance;
            }
        }

        if ($totalJumpNumber === 0) {
            return null;
        }

        $fuel = 0.1 * $hullTons * $totalJumpNumber;

        return sprintf('%.2f', $fuel);
    }

    public function resolveJumpRating(Route $route): ?int
    {
        if ($route->getJumpRating() !== null) {
            return $route->getJumpRating();
        }

        return $this->getAssetJumpRating($route->getAsset());
    }

    public function resolveFuelEstimate(Route $route): ?string
    {
        if ($route->getFuelEstimate() !== null) {
            return $route->getFuelEstimate();
        }

        return null;
    }

    public function getAssetJumpRating(?Asset $asset): ?int
    {
        if ($asset === null) {
            return null;
        }
        $rating = $asset->getSpec()->getJDrive()->getRating();
        return $rating > 0 ? $rating : null;
    }

    public function getAssetFuelCapacity(?Asset $asset): ?float
    {
        if ($asset === null) {
            return null;
        }
        $capacity = $asset->getSpec()->getFuel()->getCapacity();
        return $capacity > 0 ? $capacity : null;
    }

    public function getAssetHullTonnage(?Asset $asset): ?float
    {
        if ($asset === null) {
            return null;
        }
        $tons = $asset->getSpec()->getHull()->getTons();
        return $tons > 0 ? (float) $tons : null;
    }

    /**
     * @return array{int, int, int}
     */
    public function getGlobalCubeCoordinates(string $hex, ?array $sectorCoords = null): array
    {
        $local = $this->parseHex($hex) ?? [0, 0];
        $x = $local[0];
        $y = $local[1];

        if ($sectorCoords !== null) {
            $x += ($sectorCoords['x'] * 32);
            $y += ($sectorCoords['y'] * 40);
        }

        return $this->offsetToCube($x, $y);
    }

    /**
     * @return array{int, int, int}
     */
    private function offsetToCube(int $col, int $row): array
    {
        $x = $col;
        $z = $row - intdiv($col + ($col & 1), 2);
        $y = -$x - $z;

        return [$x, $y, $z];
    }
}
