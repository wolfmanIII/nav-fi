<?php

namespace App\Command;

use App\Service\TravellerMapDataService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:travellermap:refresh-sectors',
    description: 'Refreshes the local cache of TravellerMap sectors metadata.',
)]
class TravellerMapRefreshCommand extends Command
{
    public function __construct(
        private readonly TravellerMapDataService $dataService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Refreshing TravellerMap Sectors Metadata');

        try {
            $io->info('Fetching data from TravellerMap...');
            $data = $this->dataService->getSectorsMetadata(true);
            
            $io->success(sprintf('Successfully refreshed metadata. Found %d sectors.', count($data['Sectors'] ?? [])));
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to refresh metadata: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
