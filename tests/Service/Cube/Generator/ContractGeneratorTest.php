<?php

namespace App\Tests\Service\Cube\Generator;

use App\Service\Cube\Generator\ContractGenerator;
use PHPUnit\Framework\TestCase;

class ContractGeneratorTest extends TestCase
{
    public function testGenerateContractCalculatesCorrectly(): void
    {
        // Mock NarrativeService
        $narrative = $this->createMock(\App\Service\Cube\NarrativeService::class);

        $narrative->method('resolveTiers')->willReturn([
            'min' => 1000,
            'max' => 2000,
            'risk' => 'Test Risk',
            'examples' => ['Test Mission']
        ]);

        // Mock result object (StoryData or similar structure)
        $storyMock = new \App\Model\Cube\Narrative\Story(
            summary: 'Test Summary',
            patronName: 'Test Patron',
            archetypeCode: 'Escort',
            briefing: 'Test Briefing',
            twist: 'Test Twist',
            variables: []
        );

        $narrative->method('generateStory')->willReturn($storyMock);

        $rules = $this->createMock(\App\Service\GameRulesEngine::class);
        $rules->method('get')->willReturnCallback(fn($key, $default) => $default);

        $generator = new ContractGenerator($narrative, $rules);
        $this->assertTrue($generator->supports('CONTRACT'));

        $context = [
            'origin' => 'A',
            'destination' => 'B',
            'distance' => 0,
            'sector' => 'Test Sector',
            'session_day' => 100,
            'session_year' => 1105
        ];

        $engine = new \Random\Engine\Xoshiro256StarStar(hash('sha256', 'TEST', true));
        $randomizer = new \Random\Randomizer($engine);

        $opp = $generator->generate($context, 2, $randomizer);

        $this->assertEquals('CONTRACT', $opp->type);
        $this->assertGreaterThanOrEqual(1000, $opp->amount);
        $this->assertEquals('Test Patron', $opp->details['patron']);
        $this->assertEquals('Test Twist', $opp->details['twist']);
        $this->assertEquals('Test Briefing', $opp->details['briefing']);
    }
}
