<?php

namespace App\Tests\Unit\Service\Cube;

use App\Service\Cube\NarrativeService;
use PHPUnit\Framework\TestCase;

class NarrativeEngineTest extends TestCase
{
    private NarrativeService $narrative;

    protected function setUp(): void
    {
        $economyConfig = [
            'contract' => [
                'tiers' => [
                    'routine' => ['min' => 50000, 'max' => 200000, 'risk' => 'Routine']
                ],
                'narrative' => [
                    'patrons' => [
                        ['name' => 'Noble', 'type' => 'Noble', 'archetypes' => ['HEIST']]
                    ],
                    'archetypes' => [
                        'HEIST' => [
                            'summary' => 'Steal %target% from %site%',
                            'variables' => [
                                'target' => ['Diamond'],
                                'site' => ['Vault']
                            ]
                        ]
                    ],
                    'locations' => ['Bar'],
                    'time_constraints' => ['Soon'],
                    'opposition' => ['Guards'],
                    'twists' => ['None']
                ]
            ]
        ];

        $companyRepo = $this->createMock(\App\Repository\CompanyRepository::class);
        $nameGenerator = $this->createMock(\App\Service\Cube\NameGeneratorService::class);
        $this->narrative = new NarrativeService($economyConfig, $companyRepo, $nameGenerator);
    }

    public function testStoryGenerationIsDeterministic(): void
    {
        $seed = 12345;
        $engine1 = new \Random\Engine\Mt19937($seed);
        $randomizer1 = new \Random\Randomizer($engine1);

        // Prima generazione
        $story1 = $this->narrative->generateStory('Core', $randomizer1);

        // Seconda generazione con lo stesso seed (nuova istanza engine)
        $engine2 = new \Random\Engine\Mt19937($seed);
        $randomizer2 = new \Random\Randomizer($engine2);
        $story2 = $this->narrative->generateStory('Core', $randomizer2);

        $this->assertEquals($story1->summary, $story2->summary);
        $this->assertEquals($story1->patronName, $story2->patronName);
        $this->assertEquals($story1->briefing, $story2->briefing);
    }
}
