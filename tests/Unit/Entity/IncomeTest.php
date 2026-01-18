<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Income;
use PHPUnit\Framework\TestCase;

class IncomeTest extends TestCase
{
    public function testIsCancelledReturnsFalseWhenNoDateSet(): void
    {
        $income = new Income();
        self::assertFalse($income->isCancelled());
    }

    public function testIsCancelledReturnsFalseWhenOnlyDaySet(): void
    {
        $income = new Income();
        $income->setCancelDay(1);
        self::assertFalse($income->isCancelled());
    }

    public function testIsCancelledReturnsFalseWhenOnlyYearSet(): void
    {
        $income = new Income();
        $income->setCancelYear(1105);
        self::assertFalse($income->isCancelled());
    }

    public function testIsCancelledReturnsTrueWhenBothSet(): void
    {
        $income = new Income();
        $income->setCancelDay(1);
        $income->setCancelYear(1105);
        self::assertTrue($income->isCancelled());
    }

    public function testIsCancelledReturnsFalseWhenCleared(): void
    {
        $income = new Income();
        $income->setCancelDay(1);
        $income->setCancelYear(1105);
        self::assertTrue($income->isCancelled());

        $income->setCancelDay(null);
        self::assertFalse($income->isCancelled());
    }
}
