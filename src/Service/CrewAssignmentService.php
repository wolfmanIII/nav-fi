<?php

namespace App\Service;

use App\Entity\Crew;
use App\Entity\Asset;

final class CrewAssignmentService
{
    public function assignToAsset(Asset $asset, Crew $crew): void
    {
        $asset->addCrew($crew);

        $sessionDay = $asset->getCampaign()?->getSessionDay();
        $sessionYear = $asset->getCampaign()?->getSessionYear();
        if ($sessionDay !== null && $sessionYear !== null) {
            $crew->setActiveDay($sessionDay);
            $crew->setActiveYear($sessionYear);
        }
    }

    public function removeFromAsset(Asset $asset, Crew $crew): void
    {
        $this->clearAfterDetach($crew);
        $asset->removeCrew($crew);
    }

    public function clearAfterDetach(Crew $crew): void
    {
        $status = $crew->getStatus();
        if (!in_array($status, [Crew::STATUS_MIA, Crew::STATUS_DECEASED], true)) {
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
