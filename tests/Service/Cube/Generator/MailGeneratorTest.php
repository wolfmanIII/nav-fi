<?php

namespace App\Tests\Service\Cube\Generator;

use App\Service\Cube\Generator\MailGenerator;
use PHPUnit\Framework\TestCase;

class MailGeneratorTest extends TestCase
{
    public function testGenerateMailCalculatesCorrectly(): void
    {
        $config = [
            'mail' => ['flat_rate' => 25000]
        ];

        $repo = $this->createMock(\App\Repository\CompanyRepository::class);
        $repo->method('findAll')->willReturn([]);

        $rules = $this->createMock(\App\Service\GameRulesEngine::class);
        $rules->method('get')->willReturnCallback(fn($key, $default) => $default);

        $generator = new MailGenerator($config, $repo, $rules);
        $this->assertTrue($generator->supports('MAIL'));
        $this->assertEquals('MAIL', $generator->getType());

        $context = [
            'origin' => 'A',
            'destination' => 'B',
            'distance' => 2,
            'session_day' => 100,
            'session_year' => 1105
        ];

        $engine = new \Random\Engine\Xoshiro256StarStar(hash('sha256', 'TEST', true));
        $randomizer = new \Random\Randomizer($engine);

        $opp = $generator->generate($context, 2, $randomizer);

        $this->assertEquals('MAIL', $opp->type);
        // Containers 1-3. Flat rate 25000.
        $this->assertGreaterThanOrEqual(25000, $opp->amount);
        $this->assertArrayHasKey('containers', $opp->details);
    }
}
