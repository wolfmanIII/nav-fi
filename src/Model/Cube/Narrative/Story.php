<?php

namespace App\Model\Cube\Narrative;

/**
 * Rappresenta la storia generata per un contratto.
 */
readonly class Story
{
    public function __construct(
        public string $patronName,
        public string $archetypeCode,
        public string $summary,
        public string $briefing,
        public string $twist,
        public array $variables = []
    ) {}
}
