<?php

namespace App\Service\Cube;

use App\Repository\CompanyRepository;
use App\Entity\Company;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NarrativeService
{
    private array $archetypes;
    private array $patronConfig;

    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly CompanyRepository $companyRepository
    ) {
        $narrative = $this->economyConfig['contract']['narrative'] ?? [];
        $this->patronConfig = $narrative['patrons'] ?? [];

        $this->archetypes = [];
        foreach ($narrative['archetypes'] ?? [] as $code => $config) {
            $this->archetypes[$code] = \App\Model\Cube\Narrative\MissionArchetype::fromArray($code, $config);
        }
    }

    /**
     * Genera una storia completa basata sull'archetipo e il patrono.
     */
    public function generateStory(string $sector): \App\Model\Cube\Narrative\Story
    {
        // 1. Seleziona Patron
        $patronInfo = $this->patronConfig[mt_rand(0, count($this->patronConfig) - 1)];
        $patronName = $patronInfo['name'];
        $allowedArchetypes = $patronInfo['archetypes'] ?? array_keys($this->archetypes);

        // 2. Seleziona Archetipo tra quelli permessi dal patrono
        $archetypeCode = $allowedArchetypes[mt_rand(0, count($allowedArchetypes) - 1)];
        $archetype = $this->archetypes[$archetypeCode] ?? reset($this->archetypes);

        // 3. Risolvi variabili dell'archetipo
        $variables = [];
        foreach ($archetype->variables as $varName => $options) {
            $variables[$varName] = $options[mt_rand(0, count($options) - 1)];
        }

        // 4. Genera Summary e Briefing
        $summary = $this->resolveTemplate($archetype->summaryTemplate, $variables);

        $briefingTemplate = "PATRON: %s. %s. LOCATION: %s. CONSTRAINT: %s. OPPOSITION: %s.";
        $briefing = sprintf(
            $briefingTemplate,
            $patronName,
            $summary,
            $this->getRandom('locations'),
            $this->getRandom('time_constraints'),
            $this->getRandom('opposition')
        );

        return new \App\Model\Cube\Narrative\Story(
            patronName: $patronName,
            archetypeCode: $archetypeCode,
            summary: $summary,
            briefing: $briefing,
            twist: $this->getRandom('twists'),
            variables: $variables
        );
    }

    private function resolveTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("%$key%", $value, $template);
        }
        return $template;
    }

    private function getRandom(string $key): string
    {
        $list = $this->economyConfig['contract']['narrative'][$key] ?? ['None'];
        return $list[mt_rand(0, count($list) - 1)];
    }

    public function resolveTiers(string $tierName): array
    {
        return $this->economyConfig['contract']['tiers'][$tierName] ?? [];
    }
}
