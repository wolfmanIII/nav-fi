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
            $crew->setStatus(Crew::STATUS_ACTIVE);
            $crew->setActiveDay($sessionDay);
            $crew->setActiveYear($sessionYear);
        } else {
            // Asset has no active mission -> Pending
            $crew->setStatus(Crew::STATUS_PENDING);
            $crew->setActiveDay(null);
            $crew->setActiveYear(null);
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

    public function activatePendingCrew(Asset $asset): void
    {
        $campaign = $asset->getCampaign();
        if (!$campaign) {
            return;
        }

        $sessionDay = $campaign->getSessionDay();
        $sessionYear = $campaign->getSessionYear();

        if ($sessionDay === null || $sessionYear === null) {
            return;
        }

        foreach ($asset->getCrews() as $crew) {
            if ($crew->getStatus() === Crew::STATUS_PENDING) {
                $crew->setStatus(Crew::STATUS_ACTIVE);
                $crew->setActiveDay($sessionDay);
                $crew->setActiveYear($sessionYear);
            }
        }
    }

    public function deactivateCrew(Asset $asset): void
    {
        foreach ($asset->getCrews() as $crew) {
            $status = $crew->getStatus();
            if (in_array($status, [Crew::STATUS_ACTIVE, Crew::STATUS_ON_LEAVE, Crew::STATUS_RETIRED], true)) {
                $crew->setStatus(Crew::STATUS_PENDING);
                $crew->setActiveDay(null);
                $crew->setActiveYear(null);
                $crew->setOnLeaveDay(null);
                $crew->setOnLeaveYear(null);
                $crew->setRetiredDay(null);
                $crew->setRetiredYear(null);
            }
        }
    }
}
