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
        $this->narrative = new NarrativeService($economyConfig, $companyRepo);
    }

    public function testStoryGenerationIsDeterministic(): void
    {
        $seed = 12345;

        // Prima generazione
        mt_srand($seed);
        $story1 = $this->narrative->generateStory('Core');

        // Seconda generazione con lo stesso seed
        mt_srand($seed);
        $story2 = $this->narrative->generateStory('Core');

        $this->assertEquals($story1->summary, $story2->summary);
        $this->assertEquals($story1->patronName, $story2->patronName);
        $this->assertEquals($story1->briefing, $story2->briefing);
    }
}
