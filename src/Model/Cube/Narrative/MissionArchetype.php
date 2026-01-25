<?php

namespace App\Model\Cube\Narrative;

/**
 * Rappresenta un archetipo di missione con i suoi template e variabili.
 */
readonly class MissionArchetype
{
    public function __construct(
        public string $code,
        public string $summaryTemplate,
        public array $variables = []
    ) {}

    public static function fromArray(string $code, array $config): self
    {
        return new self(
            code: $code,
            summaryTemplate: $config['summary'] ?? 'Mission: %target%',
            variables: $config['variables'] ?? []
        );
    }
}
