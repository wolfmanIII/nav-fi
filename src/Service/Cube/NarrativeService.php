<?php

namespace App\Service\Cube;

use App\Repository\CompanyRepository;
use App\Entity\Company;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NarrativeService
{
    private array $narrativeConfig;

    public function __construct(
        #[Autowire('%app.cube.economy%')]
        private readonly array $economyConfig,
        private readonly CompanyRepository $companyRepository
    ) {
        $this->narrativeConfig = $this->economyConfig['contract']['narrative'] ?? [];
    }

    /**
     * Generates a rich, mad-libs style briefing.
     */
    public function generateBriefing(string $type, string $patronName, ?string $target = null, array $context = []): string
    {
        $location = $this->generateLocation();
        $constraint = $this->generateConstraint();
        // $opposition = $this->generateOpposition(); // Used implicitly in some templates or logic

        // Se il target non è fornito o è generico, ne generiamo uno specifico
        if ($target === null || $target === 'the target') {
            $target = $this->generateTarget();
        }

        $templates = [
            "PATRON: %s. OBJECTIVE: %s %s. LOCATION: %s. CONDITION: %s.",
            "Client %s requires you to %s %s. Ren-dezvous at %s. Note: %s.",
            "URGENT: %s needs %s %s. Meet at %s. Constraint: %s.",
            "Contract Offer: %s. Task: %s %s. Site: %s. Warning: %s."
        ];

        // Define action verbs per type
        $action = match ($type) {
            'FREIGHT' => 'transport',
            'PASSENGER' => 'extract',
            'MAIL' => 'retrieve',
            'CONTRACT' => 'secure',
            'TRADE' => 'acquire',
            default => 'handle'
        };

        $template = $templates[array_rand($templates)];

        return sprintf($template, $patronName, $action, $target, $location, $constraint);
    }

    /**
     * Hybrid Patron Selector: DB (Sector-based) > Config > Generic
     * Returns either a Company entity or a string name.
     */
    public function selectPatron(string $sector): Company|string
    {
        // 1. Try to fetch local companies from DB (50% chance if available)
        $useLocal = (mt_rand(1, 100) <= 50);

        if ($useLocal) {
            // Assuming we implement findBySector in repository, or just findAll and filter for now (MVP)
            // Ideally: $this->companyRepository->findBy(['sector' => $sector]);
            // For now, let's fetch a limited set and filter or pick random
            $companies = $this->companyRepository->findAll(); // Optimization needed for large DB
            if (!empty($companies)) {
                $company = $companies[array_rand($companies)];
                // Filter by sector if entity has it (we just added it)
                if ($company->getSector() === $sector || $company->getSector() === null) {
                    return $company;
                }
            }
        }

        // 2. Fallback to Config/YAML
        $patrons = $this->narrativeConfig['patrons'] ?? ['Unknown Patron'];
        return $patrons[mt_rand(0, count($patrons) - 1)];
    }

    public function generateLocation(): string
    {
        $list = $this->narrativeConfig['locations'] ?? ['Standard Starport'];
        return $list[mt_rand(0, count($list) - 1)];
    }

    public function generateConstraint(): string
    {
        $list = $this->narrativeConfig['time_constraints'] ?? ['Time is of the essence'];
        return $list[mt_rand(0, count($list) - 1)];
    }

    public function generateOpposition(): string
    {
        $list = $this->narrativeConfig['opposition'] ?? ['None'];
        return $list[mt_rand(0, count($list) - 1)];
    }

    public function generateTarget(): string
    {
        $list = $this->narrativeConfig['targets'] ?? ['a package'];
        return $list[mt_rand(0, count($list) - 1)];
    }

    public function generateTwist(): string
    {
        $twists = $this->narrativeConfig['twists'] ?? ['None'];
        return $twists[mt_rand(0, count($twists) - 1)];
    }

    public function resolveTiers(string $tierName): array
    {
        return $this->economyConfig['contract']['tiers'][$tierName] ?? [];
    }
}
