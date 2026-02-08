<?php

namespace App\Twig;

use App\Service\TravellerMapSectorLookup;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension per lookup dati TravellerMap.
 */
class TravellerMapExtension extends AbstractExtension
{
    public function __construct(
        private readonly TravellerMapSectorLookup $lookup
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('world_zone', [$this, 'getWorldZone']),
        ];
    }

    /**
     * Restituisce la zona di un sistema: 'A' (Amber), 'R' (Red), o null (Green).
     */
    public function getWorldZone(?string $sector, ?string $hex): ?string
    {
        if (empty($sector) || empty($hex)) {
            return null;
        }

        try {
            $data = $this->lookup->lookupWorld($sector, $hex);
            return $data['zone'] ?? null;
        } catch (\Exception) {
            return null;
        }
    }
}
