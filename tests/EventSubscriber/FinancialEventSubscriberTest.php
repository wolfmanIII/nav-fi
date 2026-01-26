<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Asset;
use App\Entity\Income;
use App\Entity\Transaction;
use App\EventSubscriber\FinancialEventSubscriber;
use App\Service\ImperialDateHelper;
use App\Service\LedgerService;
use Doctrine\ORM\Event\PostPersistEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FinancialEventSubscriberTest extends TestCase
{
    private FinancialEventSubscriber $subscriber;
    private MockObject|LedgerService $ledgerService;
    private MockObject|ImperialDateHelper $dateHelper;

    protected function setUp(): void
    {
        $this->ledgerService = $this->createMock(LedgerService::class);
        $this->dateHelper = $this->createMock(ImperialDateHelper::class);

        $this->subscriber = new FinancialEventSubscriber(
            $this->ledgerService,
            $this->dateHelper
        );
    }

    public function testPostPersistCreatesStandardDeposit(): void
    {
        $income = new Income();
        $this->setEntityId($income, 1);
        $income->setTitle('Test Job');
        $income->setCode('JOB-123');
        $income->setAmount('1000.00');
        $income->setPaymentDay(100);
        $income->setPaymentYear(1105);

        $asset = new Asset();
        $account = new \App\Entity\FinancialAccount();
        $account->setAsset($asset);
        $income->setFinancialAccount($account);

        // Non cancellato

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $args = new PostPersistEventArgs($income, $em);

        $this->ledgerService->expects($this->once())
            ->method('deposit')
            ->with(
                $asset,
                '1000.00',
                $this->stringContains('Test Job'),
                100,
                1105,
                'Income',
                1,
                null // Status null = default logic
            );

        $this->subscriber->postPersist($args);
    }

    public function testPostPersistCreatesVoidDepositIfCancelledBeforePayment(): void
    {
        $income = new Income();
        $this->setEntityId($income, 2);
        $income->setTitle('Cancelled Job');
        $income->setCode('JOB-VOID');
        $income->setAmount('5000.00');
        $income->setPaymentDay(200);
        $income->setPaymentYear(1105);
        $income->setCancelDay(150); // Cancellato PRIMA del pagamento
        $income->setCancelYear(1105);

        $asset = new Asset();
        $account = new \App\Entity\FinancialAccount();
        $account->setAsset($asset);
        $income->setFinancialAccount($account);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $args = new PostPersistEventArgs($income, $em);

        // Mock della logica Date Helper
        // Chiave data pagamento = 1105200
        // Chiave data cancellazione = 1105150
        $this->dateHelper->method('toKey')->willReturnMap([
            [200, 1105, 1105200],
            [150, 1105, 1105150],
        ]);

        $this->ledgerService->expects($this->once())
            ->method('deposit')
            ->with(
                $asset,
                '5000.00',
                $this->stringContains('Cancelled Job'),
                200,
                1105,
                'Income',
                2,
                Transaction::STATUS_VOID // Atteso status VOID
            );

        $this->subscriber->postPersist($args);
    }

    public function testPostPersistDoesNotVoidIfCancelledAfterPayment(): void
    {
        $income = new Income();
        $this->setEntityId($income, 3);
        $income->setTitle('Late Cancel Job');
        $income->setCode('JOB-VALID');
        $income->setAmount('2000.00');
        $income->setPaymentDay(50);
        $income->setPaymentYear(1105);
        $income->setCancelDay(60); // Cancellato DOPO il pagamento (raro ma possibile con dati legacy)
        $income->setCancelYear(1105);

        $asset = new Asset();
        $account = new \App\Entity\FinancialAccount();
        $account->setAsset($asset);
        $income->setFinancialAccount($account);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $args = new PostPersistEventArgs($income, $em);

        // Mock della logica Date Helper
        // Chiave data pagamento = 1105050
        // Chiave data cancellazione = 1105060
        $this->dateHelper->method('toKey')->willReturnMap([
            [50, 1105, 1105050],
            [60, 1105, 1105060],
        ]);

        $this->ledgerService->expects($this->once())
            ->method('deposit')
            ->with(
                $asset,
                '2000.00',
                $this->stringContains('Late Cancel Job'),
                50,
                1105,
                'Income',
                3,
                null // Transazione valida perché data <= data cancellazione
            );

        $this->subscriber->postPersist($args);
    }

    public function testPostPersistCreatesWithdrawalForCost(): void
    {
        $cost = new \App\Entity\Cost();
        $this->setEntityId($cost, 10);
        $cost->setTitle('Fuel Restock');
        $cost->setAmount('500.00');
        $cost->setPaymentDay(105);
        $cost->setPaymentYear(1105);

        $asset = new Asset();
        $account = new \App\Entity\FinancialAccount();
        $account->setAsset($asset);
        $cost->setFinancialAccount($account);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $args = new PostPersistEventArgs($cost, $em);

        $this->ledgerService->expects($this->once())
            ->method('withdraw')
            ->with(
                $asset,
                '500.00',
                $this->stringContains('Fuel Restock'),
                105,
                1105,
                'Cost',
                10
            );

        $this->subscriber->postPersist($args);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
