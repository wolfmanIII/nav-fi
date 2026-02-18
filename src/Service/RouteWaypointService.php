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
        private readonly TravellerMapDataService $dataService,
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

        // Estrai punto di partenza
        $startWp = $waypoints[0];
        $startPoint = [
            'sector' => $startWp->getSector(),
            'hex' => $startWp->getHex()
        ];

        // Estrai destinazioni
        $destinations = [];
        foreach (array_slice($waypoints, 1) as $wp) {
            $destinations[] = [
                'sector' => $wp->getSector(),
                'hex' => $wp->getHex()
            ];
        }

        $jumpRating = $this->routeMath->resolveJumpRating($route) ?? 1;

        $settings = [
            'avoidRedZones' => $route->isAvoidRedZones(),
            'preferHighPort' => $route->isPreferHighPort(),
        ];

        // Calcola path ottimizzato
        $result = $this->optimizer->optimizeMultiStopRoute($startPoint, $destinations, $jumpRating, $settings);
        $optimizedPath = $result['path'];

        // Salva lo stato del waypoint attivo (se presente)
        $activeWpData = null;
        if ($route->isActive()) {
            foreach ($waypoints as $wp) {
                if ($wp->isActive()) {
                    $activeWpData = [
                        'sector' => $wp->getSector(),
                        'hex' => $wp->getHex()
                    ];
                    break;
                }
            }
        }

        // Rimuovi vecchi waypoint
        foreach ($waypoints as $wp) {
            $route->getWaypoints()->removeElement($wp);
            $this->em->remove($wp);
        }

        // Crea nuovi waypoint dal path ottimizzato
        $position = 1;
        $previousWp = null;
        $activeRestored = false;

        foreach ($optimizedPath as $point) {
            $waypoint = new RouteWaypoint();
            $waypoint->setHex($point['hex']);
            $waypoint->setSector($point['sector']);
            $waypoint->setPosition($position++);
            $route->addWaypoint($waypoint);

            // Ripristina stato attivo se corrisponde
            if ($activeWpData && !$activeRestored) {
                if ($point['hex'] === $activeWpData['hex'] && $point['sector'] === $activeWpData['sector']) {
                    $waypoint->setActive(true);
                    $activeRestored = true;
                }
            }

            // Gestione caso "Ships off course": se la nave era attiva su un waypoint rimosso 
            // e non abbiamo trovato una corrispondenza esatta nel nuovo percorso, reset al punto iniziale.
            if ($route->isActive() && !$activeRestored) {
                $first = $route->getWaypoints()->first();
                if ($first) {
                    $first->setActive(true);
                }
            }

            $this->enrichWaypointFromTravellerMap($waypoint);

            // Calcola distanza dal precedente usando le coordinate dei settori
            if ($previousWp !== null) {
                $dist = $this->routeMath->distance(
                    $previousWp->getHex(),
                    $waypoint->getHex(),
                    $this->dataService->getSectorCoordinates($previousWp->getSector()),
                    $this->dataService->getSectorCoordinates($waypoint->getSector())
                );
                $waypoint->setJumpDistance($dist);
            }

            $this->em->persist($waypoint);
            $previousWp = $waypoint;
        }

        // Aggiorna route endpoints
        $lastWp = $optimizedPath[array_key_last($optimizedPath)];
        $route->setStartHex($optimizedPath[0]['hex']);
        $route->setStartSector($optimizedPath[0]['sector']);
        $route->setDestHex($lastWp['hex']);

        // Ricalcola fuel estimate
        $totalDistances = $this->getRouteSegmentDistances($route);
        $calculatedFuel = $this->routeMath->estimateJumpFuel($route, $totalDistances);
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
        usort($waypoints, fn($a, $b) => $a->getPosition() <=> $b->getPosition());

        $previous = null;
        foreach ($waypoints as $wp) {
            if ($previous === null) {
                $wp->setJumpDistance(null);
            } else {
                $dist = $this->routeMath->distance(
                    $previous->getHex(),
                    $wp->getHex(),
                    $this->dataService->getSectorCoordinates($previous->getSector()),
                    $this->dataService->getSectorCoordinates($wp->getSector())
                );
                $wp->setJumpDistance($dist);
            }
            $previous = $wp;
        }
    }

    private function recalculateDistancesAfterRemoval(Route $route): void
    {
        $this->recalculateDistances($route, new RouteWaypoint()); // Trigger full re-calc
    }

    private function updateRouteEndpoints(Route $route, RouteWaypoint $newWaypoint): void
    {
        $firstWp = $route->getWaypoints()->first() ?: null;
        $route->setStartHex($firstWp?->getHex() ?? $newWaypoint->getHex());
        $route->setStartSector($firstWp?->getSector() ?? $newWaypoint->getSector());
        $route->setDestHex($newWaypoint->getHex());
    }

    private function updateRouteEndpointsAfterRemoval(Route $route): void
    {
        $firstWp = $route->getWaypoints()->first() ?: null;
        $lastWp = $route->getWaypoints()->last() ?: null;

        $route->setStartHex($firstWp?->getHex());
        $route->setStartSector($firstWp?->getSector());
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
        $distances = $this->getRouteSegmentDistances($route);
        $calculatedFuel = $this->routeMath->estimateJumpFuel($route, $distances);

        if ($calculatedFuel !== null) {
            $route->setFuelEstimate($calculatedFuel);
        }
    }

    /**
     * @return array<int, int|null>
     */
    private function getRouteSegmentDistances(Route $route): array
    {
        $distances = [];
        $waypoints = $route->getWaypoints()->toArray();
        usort($waypoints, fn($a, $b) => $a->getPosition() <=> $b->getPosition());

        $previous = null;
        foreach ($waypoints as $wp) {
            if ($previous === null) {
                $distances[] = null;
            } else {
                $distances[] = $this->routeMath->distance(
                    $previous->getHex(),
                    $wp->getHex(),
                    $this->dataService->getSectorCoordinates($previous->getSector()),
                    $this->dataService->getSectorCoordinates($wp->getSector())
                );
            }
            $previous = $wp;
        }

        return $distances;
    }
}
