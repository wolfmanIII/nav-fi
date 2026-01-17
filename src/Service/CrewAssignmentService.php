<?php

namespace App\Service;

use App\Entity\Crew;
use App\Entity\Ship;

final class CrewAssignmentService
{
    public function assignToShip(Ship $ship, Crew $crew): void
    {
        $ship->addCrew($crew);
        $crew->setStatus('Active');

        $sessionDay = $ship->getCampaign()?->getSessionDay();
        $sessionYear = $ship->getCampaign()?->getSessionYear();
        if ($sessionDay !== null && $sessionYear !== null) {
            $crew->setActiveDay($sessionDay);
            $crew->setActiveYear($sessionYear);
        }
    }

    public function removeFromShip(Ship $ship, Crew $crew): void
    {
        $this->clearAfterDetach($crew);
        $ship->removeCrew($crew);
    }

    public function clearAfterDetach(Crew $crew): void
    {
        $status = $crew->getStatus();
        if (!in_array($status, ['Missing (MIA)', 'Deceased'], true)) {
            $crew->setStatus(null);
        }

        $crew->setActiveDay(null);
        $crew->setActiveYear(null);
        $crew->setOnLeaveDay(null);
        $crew->setOnLeaveYear(null);
        $crew->setRetiredDay(null);
        $crew->setRetiredYear(null);
    }
}
