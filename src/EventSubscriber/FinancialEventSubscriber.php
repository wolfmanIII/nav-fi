<?php

namespace App\EventSubscriber;

use App\Entity\Cost;
use App\Entity\Income;
use App\Entity\MortgageInstallment;
use App\Service\LedgerService;
use App\Service\ImperialDateHelper;
use App\Entity\Transaction;
use App\Entity\SalaryPayment;
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
            $this->processUpdates($entity, $em, $uow);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->processDeletions($entity, $em, $uow);
        }
    }

    private function processUpdates(object $entity, \Doctrine\ORM\EntityManagerInterface $em, \Doctrine\ORM\UnitOfWork $uow): void
    {
        if ($entity instanceof Income || $entity instanceof Cost || $entity instanceof MortgageInstallment || $entity instanceof SalaryPayment) {
            // Inverte vecchie transazioni
            $type = match (true) {
                $entity instanceof Income => 'Income',
                $entity instanceof Cost => 'Cost',
                $entity instanceof MortgageInstallment => 'MortgageInstallment',
                $entity instanceof SalaryPayment => 'SalaryPayment',
            };

            // STRATEGIA DI INVERSIONE:
            // La vecchia architettura si basava sull'invertire tutto e aggiungere nuove.
            // NON DOBBIAMO FARE FLUSH.
            $reversals = $this->ledgerService->reverseTransactions($type, $entity->getId(), false);
            $metadata = $em->getClassMetadata(Transaction::class);
            foreach ($reversals as $rtx) {
                $uow->computeChangeSet($metadata, $rtx);
            }

            // 2. Aggiungi nuove
            $this->handleEvent($entity, true, false, $em);
        }
    }

    private function processDeletions(object $entity, \Doctrine\ORM\EntityManagerInterface $em, \Doctrine\ORM\UnitOfWork $uow): void
    {
        if ($entity instanceof Income || $entity instanceof Cost || $entity instanceof MortgageInstallment || $entity instanceof SalaryPayment) {
            $type = match (true) {
                $entity instanceof Income => 'Income',
                $entity instanceof Cost => 'Cost',
                $entity instanceof MortgageInstallment => 'MortgageInstallment',
                $entity instanceof SalaryPayment => 'SalaryPayment',
            };

            // REVERSE ONLY (Refund/Void effect)
            $reversals = $this->ledgerService->reverseTransactions($type, $entity->getId(), false);
            $metadata = $em->getClassMetadata(Transaction::class);
            foreach ($reversals as $rtx) {
                $uow->computeChangeSet($metadata, $rtx);
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
        } elseif ($entity instanceof SalaryPayment) {
            $this->processSalaryPayment($entity, $autoFlush, $emForCompute);
        }
    }

    private function processIncome(Income $income, bool $autoFlush, ?\Doctrine\ORM\EntityManagerInterface $emForCompute): void
    {
        $asset = $income->getAsset();
        if (!$asset) return;

        // 1. Calcola deposito (se presente nelle categorie CONTRACT o CHARTER)
        $deposit = '0.00';
        $details = $income->getDetails();

        if (isset($details['deposit'])) {
            $deposit = bcadd($deposit, (string)$details['deposit'], 2);
        }

        // 2. Calcola bonus (aggiunto al pagamento finale) dal contratto
        $bonus = '0.00';
        if (isset($details['bonus'])) {
            $bonus = bcadd($bonus, (string)$details['bonus'], 2);
        }

        // Helper per persistere in sicurezza
        $persist = function (Transaction $tx) use ($emForCompute) {
            if ($emForCompute) {
                $uow = $emForCompute->getUnitOfWork();
                $uow->computeChangeSet($emForCompute->getClassMetadata(Transaction::class), $tx);
            }
        };

        // SCENARIO 1: deposito presente (> 0)
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

        // SCENARIO 2: nessun deposito (standard)
        $day = $income->getPaymentDay();
        $year = $income->getPaymentYear();
        if ($day === null || $year === null) return;

        $amount = $income->getAmount();
        if ($amount === null) return; // Dovrebbe accettare 0?

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

    private function processSalaryPayment(SalaryPayment $payment, bool $autoFlush, ?\Doctrine\ORM\EntityManagerInterface $emForCompute): void
    {
        $salary = $payment->getSalary();
        if (!$salary) return;

        $crew = $salary->getCrew();
        $asset = $crew?->getAsset();
        if (!$asset) return;

        $day = $payment->getPaymentDay();
        $year = $payment->getPaymentYear();
        if ($day === null || $year === null) return;

        $amount = $payment->getAmount();
        if ($amount === null) return;

        $tx = $this->ledgerService->withdraw(
            $asset,
            $amount,
            sprintf("Salary Payment: %s %s (Date: %03d-%d)", $crew->getName(), $crew->getSurname(), $day, $year),
            $day,
            $year,
            'SalaryPayment',
            $payment->getId(),
            null,
            $autoFlush
        );

        if ($autoFlush) {
            // Nessun collegamento diretto necessario, l'entità Transaction gestisce relatedEntityId
        }

        if (!$autoFlush && $emForCompute) {
            $uow = $emForCompute->getUnitOfWork();
            $uow->computeChangeSet($emForCompute->getClassMetadata(Transaction::class), $tx);
            // L'aggiornamento della relazione nell'unit of work è complesso per OneToOne se non pre-assegnata
        }
    }
}
