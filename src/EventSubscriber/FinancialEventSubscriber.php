<?php

namespace App\EventSubscriber;

use App\Entity\Cost;
use App\Entity\Income;
use App\Service\LedgerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
class FinancialEventSubscriber
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->handleEvent($args->getObject(), false);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        // For updates, we first reverse previous transactions, then apply new ones.
        // This effectively "updates" the ledger history.
        // NOTE: This assumes the "Previous" state of the entity was the basis for the existing Txs.
        // But here we are postUpdate, so the Entity is already New.
        // We rely on relatedEntityType/Id to find old Txs.

        $entity = $args->getObject();
        if ($entity instanceof Income || $entity instanceof Cost) {
            // Reverse old
            $type = $entity instanceof Income ? 'Income' : 'Cost';
            $this->ledgerService->reverseTransactions($type, $entity->getId());

            // Apply new
            $this->handleEvent($entity, true);
        }
    }

    private function handleEvent(object $entity, bool $isUpdate): void
    {
        if ($entity instanceof Income) {
            $this->processIncome($entity);
        } elseif ($entity instanceof Cost) {
            $this->processCost($entity);
        }
    }

    private function processIncome(Income $income): void
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

        // SCENARIO 1: Deposit Exists (> 0)
        if (bccomp($deposit, '0.00', 2) > 0) {
            // A. Deposit Transaction (At Signing Date)
            // If we have a signing date, we book the deposit.
            $signingDay = $income->getSigningDay();
            $signingYear = $income->getSigningYear();

            if ($signingDay !== null && $signingYear !== null) {
                $this->ledgerService->deposit(
                    $asset,
                    $deposit,
                    "Income Deposit: " . $income->getTitle() . " (" . $income->getCode() . ")",
                    $signingDay,
                    $signingYear,
                    'Income',
                    $income->getId()
                );
            }

            // B. Balance Transaction (At Payment Date)
            // Only if payment date is set (strict rule).
            $day = $income->getPaymentDay();
            $year = $income->getPaymentYear();

            if ($day !== null && $year !== null) {
                $baseAmount = $income->getAmount() ?? '0.00';

                // Total Due = Base + Bonus
                $totalDue = bcadd($baseAmount, $bonus, 2);

                // Balance = Total Due - Deposit
                $balance = bcsub($totalDue, $deposit, 2);

                // If balance > 0, create transaction
                if (bccomp($balance, '0.00', 2) > 0) {
                    $desc = "Income Balance";
                    if (bccomp($bonus, '0.00', 2) > 0) {
                        $desc .= " (+Bonus)";
                    }

                    $this->ledgerService->deposit(
                        $asset,
                        $balance,
                        $desc . ": " . $income->getTitle() . " (" . $income->getCode() . ")",
                        $day,
                        $year,
                        'Income',
                        $income->getId()
                    );
                }
            }

            return; // Done handling deposit scenario
        }

        // SCENARIO 2: No Deposit (Standard)
        // Require Payment Date explicitly.
        $day = $income->getPaymentDay();
        $year = $income->getPaymentYear();

        // If no payment date, no transaction.
        if ($day === null || $year === null) return;

        // Amount = Base + Bonus
        $amount = $income->getAmount();
        if ($amount === null) return;

        $totalAmount = bcadd($amount, $bonus, 2);

        $desc = "Income";
        if (bccomp($bonus, '0.00', 2) > 0) {
            $desc .= " (+Bonus)";
        }

        $this->ledgerService->deposit(
            $asset,
            $totalAmount,
            $desc . ": " . $income->getTitle() . " (" . $income->getCode() . ")",
            $day,
            $year,
            'Income',
            $income->getId()
        );
    }

    private function processCost(Cost $cost): void
    {
        $asset = $cost->getAsset();
        if (!$asset) return;

        $day = $cost->getPaymentDay() ?? null;
        $year = $cost->getPaymentYear() ?? null;

        // If no payment date, no transaction
        if ($day === null || $year === null) return;

        $amount = $cost->getAmount();
        if ($amount === null) return;

        $this->ledgerService->withdraw(
            $asset,
            $amount,
            "Cost: " . $cost->getTitle() . " (" . $cost->getCode() . ")",
            $day,
            $year,
            'Cost',
            $cost->getId()
        );
    }
}
