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

    public function distance(string $fromHex, string $toHex): ?int
    {
        $from = $this->parseHex($fromHex);
        $to = $this->parseHex($toHex);
        if ($from === null || $to === null) {
            return null;
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

        // We only care about the fuel for the LATEST jump segment added to the route,
        // as per user requirement: "quanto serve per la prossimo waypoint dall l'ultimo waypoint inserito"
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

        // Formula: 0.1 * Hull * Last Segment Distance
        // (10% of hull tonnage per Jump Number/Distance)
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
        $details = $asset?->getAssetDetails() ?? [];
        $jump = $details['jDrive']['jump'] ?? null;
        if (is_numeric($jump)) {
            return (int) $jump;
        }

        return null;
    }

    public function getAssetFuelCapacity(?Asset $asset): ?float
    {
        $details = $asset?->getAssetDetails() ?? [];
        $tons = $details['fuel']['tons'] ?? null;
        if (is_numeric($tons)) {
            return (float) $tons;
        }

        return null;
    }

    public function getAssetHullTonnage(?Asset $asset): ?float
    {
        $details = $asset?->getAssetDetails() ?? [];
        $tons = $details['hull']['tons'] ?? null;
        if (is_numeric($tons)) {
            return (float) $tons;
        }

        return null;
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
