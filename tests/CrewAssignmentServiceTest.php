<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Campaign;
use App\Entity\Crew;
use App\Entity\Asset;
use App\Entity\User;
use App\Service\CrewAssignmentService;
use PHPUnit\Framework\TestCase;

class CrewAssignmentServiceTest extends TestCase
{
    public function testAssignToAssetUsesCampaignSessionDate(): void
    {
        $service = new CrewAssignmentService();
        $campaign = (new Campaign())->setTitle('Op Drift')->setStartingYear(1105)->setSessionDay(120)->setSessionYear(1105);
        $asset = $this->makeAsset($this->makeUser(), 'ISS Relay', 'Courier', 'B-2', '1800000.00')
            ->setCampaign($campaign);
        $crew = $this->makeCrew($this->makeUser(), 'Ari', 'Voss');

        $service->assignToAsset($asset, $crew);

        self::assertSame($asset, $crew->getAsset());
        self::assertSame('Active', $crew->getStatus());
        self::assertSame(120, $crew->getActiveDay());
        self::assertSame(1105, $crew->getActiveYear());
    }


    public function testRemoveFromAssetClearsStatusAndDates(): void
    {
        $service = new CrewAssignmentService();
        $asset = $this->makeAsset($this->makeUser(), 'ISS Orion', 'Trader', 'A-1', '2000000.00');
        $crew = $this->makeCrew($this->makeUser(), 'Lira', 'Vance')
            ->setStatus('On Leave')
            ->setActiveDay(12)
            ->setActiveYear(1105)
            ->setOnLeaveDay(80)
            ->setOnLeaveYear(1105)
            ->setRetiredDay(0)
            ->setRetiredYear(0);
        $asset->addCrew($crew);

        $service->removeFromAsset($asset, $crew);

        self::assertNull($crew->getAsset());
        self::assertFalse($asset->getCrews()->contains($crew));
        self::assertNull($crew->getStatus());
        self::assertNull($crew->getActiveDay());
        self::assertNull($crew->getActiveYear());
        self::assertNull($crew->getOnLeaveDay());
        self::assertNull($crew->getOnLeaveYear());
        self::assertNull($crew->getRetiredDay());
        self::assertNull($crew->getRetiredYear());
    }

    public function testRemoveFromAssetKeepsMiaAndDeceasedStatus(): void
    {
        $service = new CrewAssignmentService();
        $asset = $this->makeAsset($this->makeUser(), 'ISS Aegis', 'Lancer', 'C-2', '4100000.00');
        $crew = $this->makeCrew($this->makeUser(), 'Mara', 'Keen')
            ->setStatus('Missing (MIA)')
            ->setMiaDay(200)
            ->setMiaYear(1105)
            ->setActiveDay(10)
            ->setActiveYear(1105);
        $asset->addCrew($crew);

        $service->removeFromAsset($asset, $crew);

        self::assertSame('Missing (MIA)', $crew->getStatus());
        self::assertSame(200, $crew->getMiaDay());
        self::assertSame(1105, $crew->getMiaYear());
        self::assertNull($crew->getActiveDay());
        self::assertNull($crew->getActiveYear());
    }

    private function makeUser(): User
    {
        return (new User())
            ->setEmail(uniqid('crew@log.test', true))
            ->setPassword('hash');
    }

    private function makeAsset(User $user, string $name, string $type, string $class, string $price): Asset
    {
        return (new Asset())
            ->setName($name)
            ->setType($type)
            ->setClass($class)
            ->setPrice($price)
            ->setUser($user);
    }

    private function makeCrew(User $user, string $name, string $surname): Crew
    {
        return (new Crew())
            ->setName($name)
            ->setSurname($surname)
            ->setUser($user);
    }
}
