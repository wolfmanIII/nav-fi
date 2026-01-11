<?php

namespace App\Service;

use App\Entity\Route;
use App\Entity\Ship;

class RouteMathHelper
{
    public function parseHex(string $hex): ?array
    {
        $normalized = strtoupper(trim($hex));
        if (!preg_match('/^[0-9A-F]{4}$/', $normalized)) {
            return null;
        }

        $col = hexdec(substr($normalized, 0, 2));
        $row = hexdec(substr($normalized, 2, 2));

        return [$col, $row];
    }

    public function distance(string $fromHex, string $toHex): ?int
    {
        $from = $this->parseHex($fromHex);
        $to = $this->parseHex($toHex);
        if ($from === null || $to === null) {
            return null;
        }

        return abs($from[0] - $to[0]) + abs($from[1] - $to[1]);
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
        $hullTons = $this->getShipHullTonnage($route->getShip());
        if ($hullTons === null) {
            return null;
        }

        $fuel = 0.0;
        foreach ($distances as $distance) {
            if ($distance === null || $distance <= 0) {
                continue;
            }
            $effectiveJump = max(1, $distance);
            $fuel += 0.1 * $hullTons * $effectiveJump;
        }

        if ($fuel <= 0) {
            return null;
        }

        return sprintf('%.2f', $fuel);
    }

    public function resolveJumpRating(Route $route): ?int
    {
        if ($route->getJumpRating() !== null) {
            return $route->getJumpRating();
        }

        return $this->getShipJumpRating($route->getShip());
    }

    public function resolveFuelEstimate(Route $route): ?string
    {
        if ($route->getFuelEstimate() !== null) {
            return $route->getFuelEstimate();
        }

        return null;
    }

    public function getShipJumpRating(?Ship $ship): ?int
    {
        $details = $ship?->getShipDetails() ?? [];
        $jump = $details['jDrive']['jump'] ?? null;
        if (is_numeric($jump)) {
            return (int) $jump;
        }

        return null;
    }

    public function getShipFuelCapacity(?Ship $ship): ?float
    {
        $details = $ship?->getShipDetails() ?? [];
        $tons = $details['fuel']['tons'] ?? null;
        if (is_numeric($tons)) {
            return (float) $tons;
        }

        return null;
    }

    public function getShipHullTonnage(?Ship $ship): ?float
    {
        $details = $ship?->getShipDetails() ?? [];
        $tons = $details['hull']['tons'] ?? null;
        if (is_numeric($tons)) {
            return (float) $tons;
        }

        return null;
    }
}
