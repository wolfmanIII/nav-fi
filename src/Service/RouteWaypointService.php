<?php

namespace App\Service;

use App\Entity\Route;
use App\Entity\RouteWaypoint;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestisce le operazioni sui waypoints di una rotta.
 */
final class RouteWaypointService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TravellerMapSectorLookup $sectorLookup,
        private readonly RouteMathHelper $routeMath,
        private readonly RouteOptimizationService $optimizer
    ) {}

    /**
     * Sincronizza il primo waypoint della rotta con i parametri di partenza della Route.
     */
    public function syncFirstWaypoint(Route $route): void
    {
        $startSector = $route->getStartSector();
        $startHex = $route->getStartHex();

        if (!$startSector || !$startHex) {
            return;
        }

        $waypoints = $route->getWaypoints();
        $firstWaypoint = null;

        foreach ($waypoints as $wp) {
            if ($wp->getPosition() === 1) {
                $firstWaypoint = $wp;
                break;
            }
        }

        if (!$firstWaypoint) {
            $firstWaypoint = new RouteWaypoint();
            $firstWaypoint->setPosition(1);
            $firstWaypoint->setRoute($route);
            $route->addWaypoint($firstWaypoint);
            $this->em->persist($firstWaypoint);
        }

        $firstWaypoint->setSector($startSector);
        $firstWaypoint->setHex($startHex);
        $firstWaypoint->setJumpDistance(0);

        $this->enrichWaypointFromTravellerMap($firstWaypoint);

        // Se ci sono altri waypoint, ricalcola le distanze dal primo
        if ($waypoints->count() > 1) {
            $this->recalculateDistances($route, $firstWaypoint);
            $this->recalculateFuelEstimate($route);
        }
    }

    /**
     * Verifica se la rotta ha salti che superano il jump rating.
     */
    public function hasInvalidJumps(Route $route): bool
    {
        $jumpRating = $this->routeMath->resolveJumpRating($route) ?? 1;

        foreach ($route->getWaypoints() as $waypoint) {
            $distance = $waypoint->getJumpDistance();
            if ($distance !== null && $distance > $jumpRating) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ricalcola la rotta usando pathfinding A* e TSP.
     * Riordina i waypoint e aggiunge quelli intermedi necessari.
     */
    public function recalculateRoute(Route $route): void
    {
        $waypoints = $route->getWaypoints()->toArray();
        if (count($waypoints) < 2) {
            return;
        }

        // Estrai settore dal primo waypoint
        $sector = $waypoints[0]->getSector();
        if (!$sector) {
            return;
        }

        // Estrai hex di partenza e destinazioni
        $startHex = $waypoints[0]->getHex();
        $destinations = [];
        foreach (array_slice($waypoints, 1) as $wp) {
            $destinations[] = $wp->getHex();
        }

        $jumpRating = $this->routeMath->resolveJumpRating($route) ?? 1;

        // Calcola path ottimizzato
        $result = $this->optimizer->optimizeMultiStopRoute($sector, $startHex, $destinations, $jumpRating);
        $optimizedPath = $result['path'];

        // Rimuovi vecchi waypoint
        foreach ($waypoints as $wp) {
            $route->getWaypoints()->removeElement($wp);
            $this->em->remove($wp);
        }

        // Crea nuovi waypoint dal path ottimizzato
        $position = 1;
        $previousHex = null;
        foreach ($optimizedPath as $hex) {
            $waypoint = new RouteWaypoint();
            $waypoint->setHex($hex);
            $waypoint->setSector($sector);
            $waypoint->setPosition($position++);
            $waypoint->setRoute($route);

            $this->enrichWaypointFromTravellerMap($waypoint);

            // Calcola distanza dal precedente
            if ($previousHex !== null) {
                $distance = $this->routeMath->distance($previousHex, $hex);
                $waypoint->setJumpDistance($distance);
            }

            $this->em->persist($waypoint);
            $previousHex = $hex;
        }

        // Aggiorna route endpoints
        $route->setStartHex($optimizedPath[0] ?? null);
        $route->setDestHex($optimizedPath[array_key_last($optimizedPath)] ?? null);

        // Ricalcola fuel estimate
        $distances = $this->routeMath->segmentDistances($optimizedPath);
        $calculatedFuel = $this->routeMath->estimateJumpFuel($route, $distances);
        if ($calculatedFuel !== null) {
            $route->setFuelEstimate($calculatedFuel);
        }
    }

    /**
     * Aggiunge un waypoint alla rotta e ricalcola distanze/fuel.
     */
    public function addWaypoint(Route $route, RouteWaypoint $waypoint): void
    {
        $this->enrichWaypointFromTravellerMap($waypoint);
        $this->assignPosition($route, $waypoint);

        $route->addWaypoint($waypoint);
        $this->em->persist($waypoint);

        $this->recalculateDistances($route, $waypoint);
        $this->updateRouteEndpoints($route, $waypoint);
        $this->recalculateFuelEstimate($route);
    }

    /**
     * Rimuove un waypoint e ricalcola la rotta.
     */
    public function removeWaypoint(Route $route, RouteWaypoint $waypoint): void
    {
        $route->getWaypoints()->removeElement($waypoint);
        $this->em->remove($waypoint);

        $this->reindexPositions($route);
        $this->recalculateDistancesAfterRemoval($route);
        $this->updateRouteEndpointsAfterRemoval($route);
        $this->recalculateFuelEstimate($route);
    }

    private function enrichWaypointFromTravellerMap(RouteWaypoint $waypoint): void
    {
        $sector = trim((string) $waypoint->getSector());
        $hex = strtoupper(trim((string) $waypoint->getHex()));

        if ($sector === '' || $hex === '') {
            return;
        }

        $worldData = $this->sectorLookup->lookupWorld($sector, $hex);
        if ($worldData) {
            $waypoint->setWorld($worldData['world'] ?? null);
            $waypoint->setUwp($worldData['uwp'] ?? null);
        }
    }

    private function assignPosition(Route $route, RouteWaypoint $waypoint): void
    {
        $maxPosition = 0;
        foreach ($route->getWaypoints() as $wp) {
            if ($wp->getPosition() > $maxPosition) {
                $maxPosition = $wp->getPosition();
            }
        }
        $waypoint->setPosition($maxPosition + 1);
    }

    private function recalculateDistances(Route $route, RouteWaypoint $newWaypoint): void
    {
        $waypoints = $route->getWaypoints()->toArray();

        // Se newWaypoint non è già nella lista, aggiungilo
        if (!in_array($newWaypoint, $waypoints, true)) {
            $waypoints[] = $newWaypoint;
        }

        // Ordina per posizione per garantire la sequenza corretta
        usort($waypoints, fn($a, $b) => $a->getPosition() <=> $b->getPosition());

        $hexes = array_map(fn($wp) => (string) $wp->getHex(), $waypoints);
        $distances = $this->routeMath->segmentDistances($hexes);

        foreach ($waypoints as $idx => $wp) {
            $wp->setJumpDistance($distances[$idx] ?? null);
        }
    }

    private function recalculateDistancesAfterRemoval(Route $route): void
    {
        $hexes = [];
        foreach ($route->getWaypoints() as $wp) {
            $hexes[] = (string) $wp->getHex();
        }

        $distances = $this->routeMath->segmentDistances($hexes);

        $idx = 0;
        foreach ($route->getWaypoints() as $wp) {
            $wp->setJumpDistance($distances[$idx] ?? null);
            $idx++;
        }
    }

    private function updateRouteEndpoints(Route $route, RouteWaypoint $newWaypoint): void
    {
        $firstWp = $route->getWaypoints()->first() ?: null;
        $route->setStartHex($firstWp?->getHex() ?? $newWaypoint->getHex());
        $route->setDestHex($newWaypoint->getHex());
    }

    private function updateRouteEndpointsAfterRemoval(Route $route): void
    {
        $firstWp = $route->getWaypoints()->first() ?: null;
        $lastWp = $route->getWaypoints()->last() ?: null;

        $route->setStartHex($firstWp?->getHex());
        $route->setDestHex($lastWp?->getHex());
    }

    private function reindexPositions(Route $route): void
    {
        $position = 1;
        foreach ($route->getWaypoints() as $wp) {
            $wp->setPosition($position++);
        }
    }

    private function recalculateFuelEstimate(Route $route): void
    {
        $hexes = [];
        foreach ($route->getWaypoints() as $wp) {
            $hexes[] = (string) $wp->getHex();
        }

        $distances = $this->routeMath->segmentDistances($hexes);
        $calculatedFuel = $this->routeMath->estimateJumpFuel($route, $distances);

        if ($calculatedFuel !== null) {
            $route->setFuelEstimate($calculatedFuel);
        }
    }
}
