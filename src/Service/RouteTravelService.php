<?php

namespace App\Service;

use App\Entity\Route;
use App\Entity\RouteWaypoint;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ImperialDateHelper;
use LogicException;

/**
 * Service architettonico per la gestione dei flussi di astrogazione.
 * Coordina la transizione di stato della rotta e la progressione del tempo imperiale (Jump-Sync).
 * 
 * @author Nav-Fi Authority
 */
class RouteTravelService
{
    /**
     * Inizializza il servizio con le dipendenze necessarie.
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ImperialDateHelper $dateHelper
    ) {}

    /**
     * Attiva la sequenza di navigazione per una rotta specifica.
     * Verifica la saturazione dei canali di navigazione dell'Asset (max 1 rotta attiva).
     * 
     * @param Route $route La rotta da inizializzare.
     * @throws LogicException Se viene rilevata un'altra rotta attiva per l'asset specificato.
     */
    public function activate(Route $route): void
    {
        $asset = $route->getAsset();

        // Controllo collisione protocolli: un asset non può gestire due rotte attive simultaneamente.
        foreach ($asset->getRoutes() as $existingRoute) {
            if ($existingRoute->isActive() && $existingRoute->getId() !== $route->getId()) {
                throw new LogicException("NAV-LINK CONFLICT: Active navigation session detected for " . $asset->getName());
            }
        }

        $route->setActive(true);

        // Protocollo Smart Resume: se esiste già un 'bookmark' operativo, lo preserva.
        $activeWp = $this->getActiveWaypoint($route);
        if (!$activeWp) {
            // Inizializzazione al Waypoint primario se non ci sono segnalibri esistenti.
            foreach ($route->getWaypoints() as $wp) {
                $wp->setActive($wp->getPosition() === 1);
            }
        }

        $this->em->flush();
    }

    /**
     * Sospende il collegamento di navigazione.
     * Mantiene lo stato dei waypoint attivo per garantire il ripristino della sessione.
     */
    public function close(Route $route): void
    {
        $route->setActive(false);
        $this->em->flush();
    }

    /**
     * Esegue la transizione tra coordinate spaziali.
     * Applica una dilatazione temporale di 7 giorni standard per ogni salto (Jump).
     * 
     * @param Route $route La rotta in corso.
     * @param string $direction Direzione del transito: 'forward' o 'backward'.
     * @return RouteWaypoint Il nuovo waypoint attivo dopo il transito.
     * @throws LogicException Se i sistemi di navigazione sono offline o le coordinate sono fuori raggio.
     */
    public function travel(Route $route, string $direction): RouteWaypoint
    {
        if (!$route->isActive()) {
            throw new LogicException("NAV-LINK ERROR: Systems offline. Execute engagement sequence.");
        }

        $activeWp = $this->getActiveWaypoint($route);
        if (!$activeWp) {
            throw new LogicException("NAV-SYNC ERROR: Waypoint synchronization failure.");
        }

        $targetPosition = $direction === 'forward'
            ? $activeWp->getPosition() + 1
            : $activeWp->getPosition() - 1;

        $targetWp = $this->getWaypointByPosition($route, $targetPosition);

        if (!$targetWp) {
            throw new LogicException("ASTROGATION ERROR: Target coordinates out of range or destination reached.");
        }

        // Transito dati al nuovo waypoint
        $activeWp->setActive(false);
        $targetWp->setActive(true);

        // Sincronizzazione con l'Orologio Imperiale del settore
        $campaign = $route->getCampaign();
        if ($campaign && $campaign->getSessionDay() !== null && $campaign->getSessionYear() !== null) {
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
