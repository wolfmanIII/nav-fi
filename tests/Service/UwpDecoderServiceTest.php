<?php

namespace App\Tests\Service;

use App\Service\UwpDecoderService;
use PHPUnit\Framework\TestCase;

class UwpDecoderServiceTest extends TestCase
{
    private UwpDecoderService $decoder;

    protected function setUp(): void
    {
        $this->decoder = new UwpDecoderService();
    }

    public function testDecodeValidUwpRegina(): void
    {
        // Regina: A788899-C
        $result = $this->decoder->decode('A788899-C');

        $this->assertArrayNotHasKey('error', $result);
        
        $this->assertEquals('A', $result['starport']['code']);
        $this->assertStringContainsString('Eccellente', $result['starport']['label']);

        $this->assertEquals(7, $result['size']['value']);
        $this->assertStringContainsString('Standard', $result['size']['label']);

        $this->assertEquals('8', $result['atmosphere']['code']);
        $this->assertStringContainsString('Densa', $result['atmosphere']['label']);

        $this->assertEquals(80, $result['hydrographics']['value']);

        $this->assertEquals(8, $result['population']['val_exponent']);

        $this->assertEquals('C', $result['tech_level']['code']);
        $this->assertEquals(12, $result['tech_level']['value']);
    }

    public function testDecodeEmptyOrShortString(): void
    {
        $result = $this->decoder->decode('A788');
        $this->assertArrayHasKey('error', $result);
    }

    public function testDecodeHexValues(): void
    {
        // Test values like C, F in numeric slots
        // C (12) Tech Level, F (15) for others
        $uwp = 'X000000-F'; 
        
        $result = $this->decoder->decode($uwp);

        $this->assertEquals('X', $result['starport']['code']); // Nessuno
        $this->assertEquals(0, $result['size']['value']); // Asteroide
        $this->assertEquals(15, $result['tech_level']['value']); // F = 15
    }

    public function testDecodeRobustnessWithDashes(): void
    {
        $result = $this->decoder->decode('A788899-C'); // With dash
        $this->assertEquals('A', $result['starport']['code']);

        $resultNoDash = $this->decoder->decode('A788899C'); // Without dash
        $this->assertEquals('A', $resultNoDash['starport']['code']);
    }
}
