<?php

namespace App\Service;

use App\Entity\Campaign;
use App\Entity\Mortgage;
use App\Entity\MortgageInstallment;
use App\Entity\Ship;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;

class FinancialAutomationService
{
    private const IMPERIAL_YEAR_DAYS = 365;
    private const MORTGAGE_PERIOD_DAYS = 28; // Standard 4-week period

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LedgerService $ledgerService
    ) {}

    public function processAutomatedFinancials(Campaign $campaign): void
    {
        $currentDay = $campaign->getSessionDay() ?? 0;
        $currentYear = $campaign->getSessionYear() ?? 0;

        foreach ($campaign->getShips() as $ship) {
            $this->processMortgage($ship, $currentDay, $currentYear);
        }
    }

    private function processMortgage(Ship $ship, int $currentDay, int $currentYear): void
    {
        $mortgage = $ship->getMortgage();
        if (!$mortgage || !$mortgage->isSigned()) {
            return;
        }

        $startDay = $mortgage->getStartDay();
        $startYear = $mortgage->getStartYear();

        if ($startDay === null || $startYear === null) {
            return;
        }

        // Determine Last Payment Date
        // Determine Last Payment Date
        $installments = $mortgage->getMortgageInstallments()->toArray();
        // Sort to ensure we get the chronological last
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

            // Next Due = Last Payment + Period
            [$nextDueDay, $nextDueYear] = $this->addDays($lastDay, $lastYear, self::MORTGAGE_PERIOD_DAYS);
        } else {
            // No payments yet. 
            // If "Start Date" is "First Payment Date", then First Due = Start Date.
            $nextDueDay = $startDay;
            $nextDueYear = $startYear;
        }

        // Calculate Monthly Payment Amount
        $summary = $mortgage->calculate();
        $paymentAmount = $summary['total_monthly_payment'] ?? '0.00';

        // Loop to generate all missing installments up to current date
        while ($this->isDateBeforeOrEqual($nextDueDay, $nextDueYear, $currentDay, $currentYear)) {

            // Create Installment Record
            $installment = new MortgageInstallment();
            $installment->setMortgage($mortgage); // Sets owning side
            $mortgage->addMortgageInstallment($installment); // Updates inverse side (in memory)

            $installment->setPaymentDay($nextDueDay);
            $installment->setPaymentYear($nextDueYear);
            $installment->setPayment($paymentAmount);
            $installment->setUser($ship->getUser()); // Optional tracking

            $this->entityManager->persist($installment);
            // We need to flush to get ID for linking
            $this->entityManager->flush();

            // Create Ledger Withdrawal
            $this->ledgerService->withdraw(
                $ship,
                $paymentAmount,
                "Mortgage Installment (Date: $nextDueYear-$nextDueDay)",
                $nextDueDay,
                $nextDueYear,
                'MortgageInstallment',
                $installment->getId()
            );

            // Advance to next period
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
