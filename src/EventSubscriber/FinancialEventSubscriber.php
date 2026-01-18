<?php

namespace App\EventSubscriber;

use App\Entity\Cost;
use App\Entity\Income;
use App\Entity\MortgageInstallment;
use App\Service\LedgerService;
use App\Service\ImperialDateHelper;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::onFlush, priority: 500, connection: 'default')]
class FinancialEventSubscriber
{
    public function __construct(
        private LedgerService $ledgerService,
        private ImperialDateHelper $dateHelper
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->handleEvent($args->getObject(), false, true);
    }

    public function onFlush(\Doctrine\ORM\Event\OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Income || $entity instanceof Cost || $entity instanceof MortgageInstallment) {
                // Reverse old transactions
                $type = match (true) {
                    $entity instanceof Income => 'Income',
                    $entity instanceof Cost => 'Cost',
                    $entity instanceof MortgageInstallment => 'MortgageInstallment',
                };

                // REVERSAL STRATEGY:
                // The old architecture relied on reversing everything and adding new.
                // WE MUST NOT FLUSH.
                $reversals = $this->ledgerService->reverseTransactions($type, $entity->getId(), false);
                $metadata = $em->getClassMetadata(Transaction::class);
                foreach ($reversals as $rtx) {
                    $uow->computeChangeSet($metadata, $rtx);
                }

                // 2. Add New
                $this->handleEvent($entity, true, false, $em);
            }
        }
    }

    private function handleEvent(object $entity, bool $isUpdate, bool $autoFlush = true, ?\Doctrine\ORM\EntityManagerInterface $emForCompute = null): void
    {
        if ($entity instanceof Income) {
            $this->processIncome($entity, $autoFlush, $emForCompute);
        } elseif ($entity instanceof Cost) {
            $this->processCost($entity, $autoFlush, $emForCompute);
        } elseif ($entity instanceof MortgageInstallment) {
            $this->processMortgageInstallment($entity, $autoFlush, $emForCompute);
        }
    }

    private function processIncome(Income $income, bool $autoFlush, ?\Doctrine\ORM\EntityManagerInterface $emForCompute): void
    {
        $asset = $income->getAsset();
        if (!$asset) return;

        // 1. Calculate Deposit (Advance) from Contract and Charter
        $deposit = '0.00';
        if ($income->getContractDetails()) {
            $deposit = bcadd($deposit, $income->getContractDetails()->getDeposit() ?? '0.00', 2);
        }
        if ($income->getCharterDetails()) {
            $deposit = bcadd($deposit, $income->getCharterDetails()->getDeposit() ?? '0.00', 2);
        }

        // 2. Calculate Bonus (Added to Final Payment) from Contract
        $bonus = '0.00';
        if ($income->getContractDetails()) {
            $bonus = bcadd($bonus, $income->getContractDetails()->getBonus() ?? '0.00', 2);
        }

        // Helper to persist safely
        $persist = function (Transaction $tx) use ($emForCompute) {
            if ($emForCompute) {
                $uow = $emForCompute->getUnitOfWork();
                $uow->computeChangeSet($emForCompute->getClassMetadata(Transaction::class), $tx);
            }
        };

        // SCENARIO 1: Deposit Exists (> 0)
        if (bccomp($deposit, '0.00', 2) > 0) {
            $signingDay = $income->getSigningDay();
            $signingYear = $income->getSigningYear();

            if ($signingDay !== null && $signingYear !== null) {
                $tx = $this->ledgerService->deposit(
                    $asset,
                    $deposit,
                    "Income Deposit: " . $income->getTitle() . " (" . $income->getCode() . ")",
                    $signingDay,
                    $signingYear,
                    'Income',
                    $income->getId(),
                    $this->isVoid($income, $signingDay, $signingYear) ? Transaction::STATUS_VOID : null,
                    $autoFlush
                );
                if (!$autoFlush) $persist($tx);
            }

            $day = $income->getPaymentDay();
            $year = $income->getPaymentYear();

            if ($day !== null && $year !== null) {
                $baseAmount = $income->getAmount() ?? '0.00';
                $totalDue = bcadd($baseAmount, $bonus, 2);
                $balance = bcsub($totalDue, $deposit, 2);

                if (bccomp($balance, '0.00', 2) > 0) {
                    $desc = "Income Balance";
                    if (bccomp($bonus, '0.00', 2) > 0) $desc .= " (+Bonus)";

                    $tx = $this->ledgerService->deposit(
                        $asset,
                        $balance,
                        $desc . ": " . $income->getTitle() . " (" . $income->getCode() . ")",
                        $day,
                        $year,
                        'Income',
                        $income->getId(),
                        $this->isVoid($income, $day, $year) ? Transaction::STATUS_VOID : null,
                        $autoFlush
                    );
                    if (!$autoFlush) $persist($tx);
                }
            }
            return;
        }

        // SCENARIO 2: No Deposit (Standard)
        $day = $income->getPaymentDay();
        $year = $income->getPaymentYear();
        if ($day === null || $year === null) return;

        $amount = $income->getAmount();
        if ($amount === null) return; // Should allow 0?

        $totalAmount = bcadd($amount, $bonus, 2);

        $desc = "Income";
        if (bccomp($bonus, '0.00', 2) > 0) $desc .= " (+Bonus)";

        $tx = $this->ledgerService->deposit(
            $asset,
            $totalAmount,
            $desc . ": " . $income->getTitle() . " (" . $income->getCode() . ")",
            $day,
            $year,
            'Income',
            $income->getId(),
            $this->isVoid($income, $day, $year) ? Transaction::STATUS_VOID : null,
            $autoFlush
        );
        if (!$autoFlush) $persist($tx);
    }

    private function isVoid(Income $income, int $day, int $year): bool
    {
        if (!$income->isCancelled()) {
            return false;
        }

        $txKey = $this->dateHelper->toKey($day, $year);
        $cancelKey = $this->dateHelper->toKey($income->getCancelDay(), $income->getCancelYear());

        return $txKey !== null && $cancelKey !== null && $txKey > $cancelKey;
    }

    private function processCost(Cost $cost, bool $autoFlush, ?\Doctrine\ORM\EntityManagerInterface $emForCompute): void
    {
        $asset = $cost->getAsset();
        if (!$asset) return;

        $day = $cost->getPaymentDay();
        $year = $cost->getPaymentYear();
        if ($day === null || $year === null) return;

        $amount = $cost->getAmount();
        if ($amount === null) return;

        $tx = $this->ledgerService->withdraw(
            $asset,
            $amount,
            "Cost: " . $cost->getTitle() . " (" . $cost->getCode() . ")",
            $day,
            $year,
            'Cost',
            $cost->getId(),
            null,
            $autoFlush
        );
        if (!$autoFlush && $emForCompute) {
            $uow = $emForCompute->getUnitOfWork();
            $uow->computeChangeSet($emForCompute->getClassMetadata(Transaction::class), $tx);
        }
    }

    private function processMortgageInstallment(MortgageInstallment $installment, bool $autoFlush, ?\Doctrine\ORM\EntityManagerInterface $emForCompute): void
    {
        $mortgage = $installment->getMortgage();
        if (!$mortgage) return;

        $asset = $mortgage->getAsset();
        if (!$asset) return;

        $day = $installment->getPaymentDay();
        $year = $installment->getPaymentYear();
        if ($day === null || $year === null) return;

        $amount = $installment->getPayment();
        if ($amount === null) return;

        $tx = $this->ledgerService->withdraw(
            $asset,
            $amount,
            sprintf("Mortgage Installment (Date: %03d-%s)", $day, $year),
            $day,
            $year,
            'MortgageInstallment',
            $installment->getId(),
            null,
            $autoFlush
        );
        if (!$autoFlush && $emForCompute) {
            $uow = $emForCompute->getUnitOfWork();
            $uow->computeChangeSet($emForCompute->getClassMetadata(Transaction::class), $tx);
        }
    }
}
