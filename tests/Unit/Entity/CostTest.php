<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Cost;
use PHPUnit\Framework\TestCase;

class CostTest extends TestCase
{
    public function testInitialState(): void
    {
        $cost = new Cost();
        self::assertNotNull($cost->getCode());
        self::assertNotEmpty($cost->getCode());
        self::assertNull($cost->getAmount());
    }

    public function testSettersAndGetters(): void
    {
        $cost = new Cost();

        $cost->setTitle('Fuel Restock');
        self::assertSame('Fuel Restock', $cost->getTitle());

        $cost->setAmount('500.00');
        self::assertSame('500.00', $cost->getAmount());

        $cost->setPaymentDay(105);
        $cost->setPaymentYear(1105);
        self::assertSame(105, $cost->getPaymentDay());
        self::assertSame(1105, $cost->getPaymentYear());

        $cost->setNote('Urgent');
        self::assertSame('Urgent', $cost->getNote());
    }
}
