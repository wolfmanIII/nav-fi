<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Service\LedgerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LedgerServiceTest extends TestCase
{
    private LedgerService $ledgerService;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|TransactionRepository $transactionRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);

        $this->ledgerService = new LedgerService(
            $this->entityManager,
            $this->transactionRepository
        );
    }

    public function testDepositCreatesPostedTransactionIfEffective(): void
    {
        $asset = new Asset();
        $asset->setCredits('1000.00');

        $campaign = new Campaign();
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $asset->setCampaign($campaign);

        $amount = '500.00';
        $desc = 'Test Deposit';
        $day = 100; // Stesso giorno della sessione
        $year = 1105;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Transaction $tx) use ($amount, $desc, $day, $year) {
                return $tx->getAmount() === $amount &&
                    $tx->getDescription() === $desc &&
                    $tx->getSessionDay() === $day &&
                    $tx->getSessionYear() === $year &&
                    $tx->getStatus() === Transaction::STATUS_POSTED;
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $tx = $this->ledgerService->deposit($asset, $amount, $desc, $day, $year);

        // Verifica saldo aggiornato
        $this->assertEquals('1500.00', $asset->getCredits());
    }

    public function testDepositCreatesPendingTransactionIfFuture(): void
    {
        $asset = new Asset();
        $asset->setCredits('1000.00');

        $campaign = new Campaign();
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $asset->setCampaign($campaign);

        // Data futura
        $day = 101;
        $year = 1105;
        $amount = '500.00';

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Transaction $tx) {
                return $tx->getStatus() === Transaction::STATUS_PENDING;
            }));

        $this->ledgerService->deposit($asset, $amount, 'Future Deposit', $day, $year);

        // Verifica saldo NON aggiornato
        $this->assertEquals('1000.00', $asset->getCredits());
    }

    public function testVoidDepositDoesNotAffectBalanceEvenIfEffective(): void
    {
        $asset = new Asset();
        $asset->setCredits('1000.00');

        $campaign = new Campaign();
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $asset->setCampaign($campaign);

        // Data effettiva, MA status è VOID
        $day = 99;
        $year = 1105;
        $amount = '500.00';

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Transaction $tx) {
                return $tx->getStatus() === Transaction::STATUS_VOID;
            }));

        $this->ledgerService->deposit(
            $asset,
            $amount,
            'Void Transaction',
            $day,
            $year,
            null,
            null,
            Transaction::STATUS_VOID
        );

        // Verifica saldo NON aggiornato
        $this->assertEquals('1000.00', $asset->getCredits());
    }

    public function testWithdrawCreatesNegativeTransaction(): void
    {
        $asset = new Asset();
        $asset->setCredits('1000.00');

        $campaign = new Campaign();
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $asset->setCampaign($campaign);

        $amount = '200.00'; // La logica lo rende negativo
        $day = 100;
        $year = 1105;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Transaction $tx) {
                return $tx->getAmount() === '-200.00' &&
                    $tx->getStatus() === Transaction::STATUS_POSTED;
            }));

        $this->ledgerService->withdraw($asset, $amount, 'Test Withdraw', $day, $year);

        // Verifica saldo aggiornato (1000 - 200 = 800)
        $this->assertEquals('800.00', $asset->getCredits());
    }
}
