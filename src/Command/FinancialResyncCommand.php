<?php

namespace App\Command;

use App\Entity\Cost;
use App\Entity\Income;
use App\Entity\Transaction;
use App\Repository\CostRepository;
use App\Repository\IncomeRepository;
use App\Repository\TransactionRepository;
use App\Service\LedgerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:financial:resync',
    description: 'Regenerates missing transactions for Income and Cost entities.',
)]
class FinancialResyncCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LedgerService $ledgerService,
        private TransactionRepository $transactionRepository,
        private IncomeRepository $incomeRepository,
        private CostRepository $costRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Financial Resync: Regenerating Missing Transactions');

        $incomes = $this->incomeRepository->findAll();
        $costs = $this->costRepository->findAll();

        $createdCount = 0;
        $skippedCount = 0;

        // Process Incomes
        foreach ($incomes as $income) {
            if ($this->hasTransaction('Income', $income->getId())) {
                $skippedCount++;
                continue;
            }

            $asset = $income->getAsset();
            if (!$asset) continue;

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

            if (bccomp($deposit, '0.00', 2) > 0) {
                // A. Deposit
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
                    $createdCount++;
                }

                // B. Balance
                $paymentDay = $income->getPaymentDay();
                $paymentYear = $income->getPaymentYear();
                if ($paymentDay !== null && $paymentYear !== null) {
                    $baseAmount = $income->getAmount() ?? '0.00';
                    $totalDue = bcadd($baseAmount, $bonus, 2);
                    $balance = bcsub($totalDue, $deposit, 2);

                    if (bccomp($balance, '0.00', 2) > 0) {
                        $desc = "Income Balance";
                        if (bccomp($bonus, '0.00', 2) > 0) {
                            $desc .= " (+Bonus)";
                        }

                        $this->ledgerService->deposit(
                            $asset,
                            $balance,
                            $desc . ": " . $income->getTitle() . " (" . $income->getCode() . ")",
                            $paymentDay,
                            $paymentYear,
                            'Income',
                            $income->getId()
                        );
                        $createdCount++;
                    }
                }
                continue; // Done with this item
            }

            // STANDARD LOGIC (No Deposit)
            $day = $income->getPaymentDay();
            $year = $income->getPaymentYear();

            if ($day === null || $year === null) continue;

            $amount = $income->getAmount();
            if ($amount === null || $amount <= 0) continue;

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
            $createdCount++;
        }

        // Process Costs
        foreach ($costs as $cost) {
            if ($this->hasTransaction('Cost', $cost->getId())) {
                $skippedCount++;
                continue;
            }

            $asset = $cost->getAsset();
            if (!$asset) continue;

            $day = $cost->getPaymentDay();
            $year = $cost->getPaymentYear();

            if ($day === null || $year === null || $cost->getAmount() === null || $cost->getAmount() <= 0) {
                // Skip invalid or zero-cost items
                continue;
            }

            $this->ledgerService->withdraw(
                $asset,
                $cost->getAmount(),
                "Cost: " . $cost->getTitle() . " (" . $cost->getCode() . ")",
                $day,
                $year,
                'Cost',
                $cost->getId()
            );
            $createdCount++;
        }

        $io->success(sprintf('Resync Complete. Created %d transactions. Skipped %d existing.', $createdCount, $skippedCount));

        if ($createdCount > 0) {
            $io->note('Transactions created. Please note that LedgerService automatically sets status (Pending/Posted) based on current Campaign date during creation.');
        }

        return Command::SUCCESS;
    }

    private function hasTransaction(string $type, int $id): bool
    {
        return (bool) $this->transactionRepository->findOneBy([
            'relatedEntityType' => $type,
            'relatedEntityId' => $id
        ]);
    }
}
