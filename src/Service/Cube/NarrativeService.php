<?php

namespace App\Service\Cube;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NarrativeService
{
    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig
    ) {}

    public function generatePatron(): string
    {
        $patrons = $this->economyConfig['contract']['narrative']['patrons'] ?? ['Unknown Patron'];
        return $patrons[mt_rand(0, count($patrons) - 1)];
    }

    public function generateTwist(): string
    {
        $twists = $this->economyConfig['contract']['narrative']['twists'] ?? ['None'];
        // Restituisce una possibile complicazione; la probabilità di applicazione è delegata al chiamante.
        return $twists[mt_rand(0, count($twists) - 1)];
    }

    public function resolveTiers(string $tierName): array
    {
        return $this->economyConfig['contract']['tiers'][$tierName] ?? [];
    }
}
