<?php

namespace App\Command;

use App\Entity\BrokerSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cube:reset',
    description: 'Purges all Cube Broker Sessions and Opportunities',
)]
class CubeResetCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('The Cube // Data Purge Protocol');

        if (!$io->confirm('WARNING: This will delete ALL Broker Sessions and saved Opportunities. Continue?', false)) {
            $io->warning('Purge aborted.');
            return Command::SUCCESS;
        }

        $repo = $this->entityManager->getRepository(BrokerSession::class);
        $sessions = $repo->findAll();

        $count = count($sessions);
        $io->progressStart($count);

        foreach ($sessions as $session) {
            $this->entityManager->remove($session);
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('Purge Complete. %d sessions and related data removed.', $count));
        $io->text('// DATALINK RESET');

        return Command::SUCCESS;
    }
}
