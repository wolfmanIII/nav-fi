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

        $generator = new MailGenerator($config);
        $this->assertTrue($generator->supports('MAIL'));
        $this->assertEquals('MAIL', $generator->getType());

        $context = [
            'origin' => 'A',
            'destination' => 'B',
            'distance' => 2
        ];

        $opp = $generator->generate($context, 2);

        $this->assertEquals('MAIL', $opp->type);
        // Containers 1-3. Flat rate 25000.
        $this->assertGreaterThanOrEqual(25000, $opp->amount);
        $this->assertArrayHasKey('containers', $opp->details);
    }
}
