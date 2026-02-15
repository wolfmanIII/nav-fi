<?php

namespace App\Tests\Unit\Service;

use App\Service\ImperialDateHelper;
use PHPUnit\Framework\TestCase;

class ImperialDateHelperTest extends TestCase
{
    private ImperialDateHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ImperialDateHelper();
    }

    public function testToKey(): void
    {
        $this->assertEquals(1105001, $this->helper->toKey(1, 1105));
        $this->assertEquals(1105365, $this->helper->toKey(365, 1105));
        $this->assertNull($this->helper->toKey(null, 1105));
    }

    public function testFormat(): void
    {
        $this->assertEquals('001/1105', $this->helper->format(1, 1105));
        $this->assertEquals('365/1105', $this->helper->format(365, 1105));
        $this->assertNull($this->helper->format(null, 1105));
    }

    public function testParseFilter(): void
    {
        // 001/1105 -> 1105001
        $this->assertEquals(1105001, $this->helper->parseFilter('1/1105', false));

        // Solo anno -> 1/Year (inizio) o 999/Year (fine)
        $this->assertEquals(1105001, $this->helper->parseFilter('1105', false));
        $this->assertEquals(1105999, $this->helper->parseFilter('1105', true));

        $this->assertNull($this->helper->parseFilter('abc', false));
        $this->assertNull($this->helper->parseFilter('', false));
    }

    public function testAddDays(): void
    {
        // 100/1105 + 10 = 110/1105
        $result = $this->helper->addDays(100, 1105, 10);
        $this->assertEquals(110, $result['day']);
        $this->assertEquals(1105, $result['year']);

        // 360/1105 + 10 = 5/1106 (365 giorni per anno)
        $result = $this->helper->addDays(360, 1105, 10);
        $this->assertEquals(5, $result['day']);
        $this->assertEquals(1106, $result['year']);

        // 365/1105 + 365 = 365/1106
        $result = $this->helper->addDays(365, 1105, 365);
        $this->assertEquals(365, $result['day']);
        $this->assertEquals(1106, $result['year']);
    }
}
