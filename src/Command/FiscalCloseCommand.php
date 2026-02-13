<?php

namespace App\Command;

use App\Entity\Asset;
use App\Service\FiscalYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fiscal-close',
    description: 'Closes a fiscal year for an asset, archiving transactions and creating a snapshot.',
)]
class FiscalCloseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private FiscalYearService $fiscalYearService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('assetId', InputArgument::REQUIRED, 'The ID of the Asset')
            ->addArgument('year', InputArgument::REQUIRED, 'The Fiscal Year to close (e.g. 1105)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Bypass confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $assetId = $input->getArgument('assetId');
        $year = (int) $input->getArgument('year');

        $asset = $this->em->getRepository(Asset::class)->find($assetId);

        if (!$asset) {
            $io->error("Asset with ID $assetId not found.");
            return Command::FAILURE;
        }

        $io->title("Fiscal Year Closure: " . $asset->getName());
        $io->text([
            "Asset: " . $asset->getName(),
            "Year to Close: " . $year,
            "Action: Archive all transactions from $year and create a snapshot for " . ($year + 1)
        ]);

        if (!$input->getOption('force')) {
            if (!$io->confirm('This action is irreversible (archives data). Continue?', false)) {
                $io->note('Aborted.');
                return Command::SUCCESS;
            }
        }

        try {
            $this->fiscalYearService->closeFiscalYear($asset, $year);
            $io->success("Fiscal Year $year closed successfully. Snapshot created for " . ($year + 1));
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
