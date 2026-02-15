<?php

namespace App\Service;

use App\Entity\Route;
use App\Entity\RouteWaypoint;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service dedicato alla gestione della logica di viaggio e navigazione.
 * Applica il principio di singola responsabilità (SRP) isolando il dominio operativo dal controller.
 * 
 * @author Nav-Fi Authority
 */
class RouteTravelService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ImperialDateHelper $dateHelper
    ) {}

    /**
     * Attiva una rotta impostando il primo waypoint come attivo.
     * 
     * @param Route $route La rotta da inizializzare.
     */
    public function activate(Route $route): void
    {
        $route->setActive(true);

        // Imposta il primo waypoint come attivo, disattiva gli altri
        foreach ($route->getWaypoints() as $wp) {
            $wp->setActive($wp->getPosition() === 1);
        }

        $this->em->flush();
    }

    /**
     * Esegue il transito tra waypoint.
     * Avanza il calendario imperiale di 7 giorni per ogni salto eseguito.
     * 
     * @param Route $route La rotta in corso.
     * @param string $direction Direzione del transito ('forward' o 'backward').
     * @return RouteWaypoint Il nuovo waypoint attivo dopo il transito.
     * @throws \LogicException Se la rotta non è attiva o il transito non è possibile.
     */
    public function travel(Route $route, string $direction): RouteWaypoint
    {
        if (!$route->isActive()) {
            throw new \LogicException("NAV-LINK ERROR: Rotta non attiva. Inizializzare sequenza di engagement.");
        }

        $activeWp = $this->getActiveWaypoint($route);
        if (!$activeWp) {
            throw new \LogicException("NAV-SYNC ERROR: Nessun waypoint attivo rilevato.");
        }

        $targetPosition = $direction === 'forward'
            ? $activeWp->getPosition() + 1
            : $activeWp->getPosition() - 1;

        $targetWp = $this->getWaypointByPosition($route, $targetPosition);

        if (!$targetWp) {
            throw new LogicException("NAVIGATION ERROR: Coordinate di destinazione fuori raggio o fine rotta raggiunta.");
        }

        // Transito waypoint
        $activeWp->setActive(false);
        $targetWp->setActive(true);

        // Sincronizzazione Temporale (Imperial Calendar Advance)
        $campaign = $route->getCampaign();
        if ($campaign && $campaign->getSessionDay() !== null && $campaign->getSessionYear() !== null) {
            // Ogni transito (avanti o indietro nel log) consuma tempo reale (7 giorni standard per jump)
            $newDate = $this->dateHelper->addDays(
                $campaign->getSessionDay(),
                $campaign->getSessionYear(),
                7
            );
            $campaign->setSessionDay($newDate['day']);
            $campaign->setSessionYear($newDate['year']);
        }

        $this->em->flush();

        return $targetWp;
    }

    /**
     * Recupera il waypoint attualmente attivo nella rotta.
     */
    private function getActiveWaypoint(Route $route): ?RouteWaypoint
    {
        foreach ($route->getWaypoints() as $wp) {
            if ($wp->isActive()) {
                return $wp;
            }
        }
        return null;
    }

    /**
     * Recupera un waypoint specifico per posizione.
     */
    private function getWaypointByPosition(Route $route, int $position): ?RouteWaypoint
    {
        foreach ($route->getWaypoints() as $wp) {
            if ($wp->getPosition() === $position) {
                return $wp;
            }
        }
        return null;
    }
}
