<?php

namespace App\Service;

use App\Entity\Campaign;
use App\Entity\Mortgage;
use App\Entity\MortgageInstallment;
use App\Entity\Asset;
use App\Entity\Transaction;
use App\Entity\Salary;
use App\Entity\SalaryPayment;
use Doctrine\ORM\EntityManagerInterface;

class FinancialAutomationService
{
    private const IMPERIAL_YEAR_DAYS = 365;
    private const MORTGAGE_PERIOD_DAYS = 28; // Periodo standard di 4 settimane
    private const SALARY_PERIOD_DAYS = 28; // Periodo standard di 4 settimane

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LedgerService $ledgerService
    ) {}

    public function processAutomatedFinancials(Campaign $campaign): void
    {
        $currentDay = $campaign->getSessionDay() ?? 0;
        $currentYear = $campaign->getSessionYear() ?? 0;

        foreach ($campaign->getAssets() as $asset) {
            $this->processMortgage($asset, $currentDay, $currentYear);
        }

        $this->processSalaries($campaign, $currentDay, $currentYear);
    }

    private function processSalaries(Campaign $campaign, int $currentDay, int $currentYear): void
    {
        foreach ($campaign->getAssets() as $asset) {
            foreach ($asset->getCrews() as $crew) {
                foreach ($crew->getSalaries() as $salary) {
                    if ($salary->getStatus() === Salary::STATUS_ACTIVE) {
                        $this->processSalary($salary, $currentDay, $currentYear);
                    }
                }
            }
        }
    }

    private function processSalary(Salary $salary, int $currentDay, int $currentYear): void
    {
        $payments = $salary->getPayments()->toArray();
        usort($payments, fn($a, $b) => ($a->getPaymentYear() * 1000 + $a->getPaymentDay()) <=> ($b->getPaymentYear() * 1000 + $b->getPaymentDay()));

        $lastPayment = end($payments);
        $crew = $salary->getCrew();
        $asset = $crew?->getAsset();

        if (!$asset) return;

        if ($lastPayment) {
            [$nextDueDay, $nextDueYear] = $this->addDays($lastPayment->getPaymentDay(), $lastPayment->getPaymentYear(), self::SALARY_PERIOD_DAYS);
        } else {
            $nextDueDay = $salary->getFirstPaymentDay();
            $nextDueYear = $salary->getFirstPaymentYear();
        }

        while ($this->isDateBeforeOrEqual($nextDueDay, $nextDueYear, $currentDay, $currentYear)) {
            $payment = new SalaryPayment();
            $payment->setSalary($salary);
            $payment->setPaymentDay($nextDueDay);
            $payment->setPaymentYear($nextDueYear);

            // Calcola importo (pro-rata per il primo pagamento)
            if (!$lastPayment && $crew->getActiveDay() !== null) {
                $totalDays = ($nextDueYear * self::IMPERIAL_YEAR_DAYS + $nextDueDay) - ($crew->getActiveYear() * self::IMPERIAL_YEAR_DAYS + $crew->getActiveDay());
                $amount = bcmul(bcdiv($salary->getAmount(), (string) self::SALARY_PERIOD_DAYS, 10), (string)$totalDays, 2);
            } else {
                $amount = $salary->getAmount();
            }

            $payment->setAmount($amount);
            $this->entityManager->persist($payment);
            $this->entityManager->flush(); // Serve l'ID per FinancialEventSubscriber

            // Il subscriber gestirà la creazione della transazione

            $lastPayment = $payment;
            [$nextDueDay, $nextDueYear] = $this->addDays($nextDueDay, $nextDueYear, self::SALARY_PERIOD_DAYS);
        }
    }

    private function processMortgage(Asset $asset, int $currentDay, int $currentYear): void
    {
        $mortgage = $asset->getMortgage();
        if (!$mortgage || !$mortgage->isSigned()) {
            return;
        }

        $startDay = $mortgage->getStartDay();
        $startYear = $mortgage->getStartYear();

        if ($startDay === null || $startYear === null) {
            return;
        }

        // Determina la data dell'ultimo pagamento
        $installments = $mortgage->getMortgageInstallments()->toArray();
        // Ordina per ottenere l'ultimo in ordine cronologico
        usort($installments, function ($a, $b) {
            if ($a->getPaymentYear() !== $b->getPaymentYear()) {
                return $a->getPaymentYear() <=> $b->getPaymentYear();
            }
            return $a->getPaymentDay() <=> $b->getPaymentDay();
        });

        $lastInstallment = end($installments);

        if ($lastInstallment) {
            $lastDay = $lastInstallment->getPaymentDay();
            $lastYear = $lastInstallment->getPaymentYear();

            // Prossima scadenza = ultimo pagamento + periodo
            [$nextDueDay, $nextDueYear] = $this->addDays($lastDay, $lastYear, self::MORTGAGE_PERIOD_DAYS);
        } else {
            // Nessun pagamento ancora.
            // Se "Start Date" è "First Payment Date", allora la prima scadenza = Start Date.
            $nextDueDay = $startDay;
            $nextDueYear = $startYear;
        }

        // Calcola l'importo del pagamento mensile
        $summary = $mortgage->calculate();
        $paymentAmount = $summary['total_monthly_payment'] ?? '0.00';

        // Ciclo per generare tutte le rate mancanti fino alla data corrente
        while ($this->isDateBeforeOrEqual($nextDueDay, $nextDueYear, $currentDay, $currentYear)) {

            // Crea record rata
            $installment = new MortgageInstallment();
            $installment->setMortgage($mortgage); // Imposta il lato proprietario
            $mortgage->addMortgageInstallment($installment); // Aggiorna il lato inverso (in memoria)

            $installment->setPaymentDay($nextDueDay);
            $installment->setPaymentYear($nextDueYear);
            $installment->setPayment($paymentAmount);
            $installment->setUser($asset->getUser()); // Tracciamento opzionale

            $this->entityManager->persist($installment);
            // Serve fare flush per ottenere l'ID per il collegamento
            $this->entityManager->flush();

            // Crea prelievo nel libro mastro
            $this->ledgerService->withdraw(
                $asset,
                $paymentAmount,
                "Mortgage Installment (Date: $nextDueYear-$nextDueDay)",
                $nextDueDay,
                $nextDueYear,
                'MortgageInstallment',
                $installment->getId()
            );

            // Avanza al periodo successivo
            [$nextDueDay, $nextDueYear] = $this->addDays($nextDueDay, $nextDueYear, self::MORTGAGE_PERIOD_DAYS);
        }
    }

    private function addDays(int $day, int $year, int $daysToAdd): array
    {
        $day += $daysToAdd;
        while ($day > self::IMPERIAL_YEAR_DAYS) {
            $day -= self::IMPERIAL_YEAR_DAYS;
            $year++;
        }
        return [$day, $year];
    }

    private function isDateBeforeOrEqual(int $day1, int $year1, int $day2, int $year2): bool
    {
        if ($year1 < $year2) return true;
        if ($year1 > $year2) return false;
        return $day1 <= $day2;
    }
}
