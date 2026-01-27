<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;

interface OpportunityGeneratorInterface
{
    /**
     * Determines if this generator supports the given type or chance roll.
     */
    public function supports(string $type): bool;

    /**
     * Generates an opportunity.
     */
    public function generate(array $context, int $maxDist, \Random\Randomizer $randomizer): CubeOpportunityData;

    /**
     * Returns the type identifier for this generator (e.g., 'FREIGHT', 'PASSENGER').
     */
    public function getType(): string;
}
