<?php

namespace App\Command;

use App\Entity\Insurance;
use App\Entity\InterestRate;
use App\Entity\ShipRole;
use App\Repository\InsuranceRepository;
use App\Repository\InterestRateRepository;
use App\Repository\ShipRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:context:import',
    description: 'Importa i dati di Insurance, InterestRate e ShipRole da file JSON'
)]
class ImportContextCommand extends Command
{
    public function __construct(
        private readonly InsuranceRepository $insuranceRepository,
        private readonly InterestRateRepository $interestRateRepository,
        private readonly ShipRoleRepository $shipRoleRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'Percorso del file di import (relativo alla root del progetto se non assoluto)',
                'config/seed/context_seed.json'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $file = (string) $input->getOption('file');
        $sourcePath = $this->resolvePath($file);

        if (!$filesystem->exists($sourcePath)) {
            $io->error(sprintf('File di import non trovato: %s', $this->relativePath($sourcePath)));

            return Command::FAILURE;
        }

        $decoded = json_decode((string) file_get_contents($sourcePath), true);
        if (!is_array($decoded)) {
            $io->error('Il file non contiene un JSON valido.');

            return Command::FAILURE;
        }

        $this->truncateTables();

        [$insCreated, $insUpdated] = $this->importInsurance($decoded['insurance'] ?? []);
        [$rateCreated, $rateUpdated] = $this->importInterestRates($decoded['interest_rates'] ?? []);
        [$roleCreated, $roleUpdated] = $this->importShipRoles($decoded['ship_roles'] ?? []);

        $this->entityManager->flush();

        $io->success(sprintf(
            'Import completato. Insurance: %d nuovi / %d aggiornati; InterestRate: %d nuovi / %d aggiornati; ShipRole: %d nuovi / %d aggiornati.',
            $insCreated,
            $insUpdated,
            $rateCreated,
            $rateUpdated,
            $roleCreated,
            $roleUpdated
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array{int, int} [creati, aggiornati]
     */
    private function importInsurance(array $rows): array
    {
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            if (!isset($row['name'])) {
                continue;
            }

            $entity = (new Insurance())
                ->setName((string) $row['name'])
                ->setAnnualCost(isset($row['annual_cost']) ? (string) $row['annual_cost'] : '0.00')
                ->setCoverage(isset($row['coverage']) && is_array($row['coverage']) ? $row['coverage'] : [])
            ;

            $this->entityManager->persist($entity);
            $created++;
        }

        return [$created, $updated];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array{int, int} [creati, aggiornati]
     */
    private function importInterestRates(array $rows): array
    {
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            if (!isset($row['duration'], $row['price_multiplier'], $row['price_divider'])) {
                continue;
            }

            $entity = (new InterestRate())
                ->setDuration((int) $row['duration'])
                ->setPriceMultiplier((string) $row['price_multiplier'])
                ->setPriceDivider((int) $row['price_divider'])
                ->setAnnualInterestRate(isset($row['annual_interest_rate']) ? (string) $row['annual_interest_rate'] : '0.00')
            ;

            $this->entityManager->persist($entity);
            $created++;
        }

        return [$created, $updated];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array{int, int} [creati, aggiornati]
     */
    private function importShipRoles(array $rows): array
    {
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            if (!isset($row['code'])) {
                continue;
            }

            $entity = (new ShipRole())
                ->setCode((string) $row['code'])
                ->setName(isset($row['name']) ? (string) $row['name'] : '')
                ->setDescription(isset($row['description']) ? (string) $row['description'] : '')
            ;

            $this->entityManager->persist($entity);
            $created++;
        }

        return [$created, $updated];
    }

    private function truncateTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform()->getName();

        if ($platform === 'mysql') {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            $connection->executeStatement('TRUNCATE TABLE ship_role');
            $connection->executeStatement('TRUNCATE TABLE insurance');
            $connection->executeStatement('TRUNCATE TABLE interest_rate');
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

            return;
        }

        if ($platform === 'postgresql' || $platform === 'postgres') {
            $connection->executeStatement('TRUNCATE TABLE ship_role, insurance, interest_rate RESTART IDENTITY CASCADE');

            return;
        }

        // fallback per SQLite o altri: delete e reset della sequence
        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        $connection->executeStatement('DELETE FROM ship_role');
        $connection->executeStatement('DELETE FROM insurance');
        $connection->executeStatement('DELETE FROM interest_rate');
        $connection->executeStatement("DELETE FROM sqlite_sequence WHERE name IN ('ship_role','insurance','interest_rate')");
        $connection->executeStatement('PRAGMA foreign_keys = ON');
    }

    private function resolvePath(string $file): string
    {
        if (str_starts_with($file, '/')) {
            return $file;
        }

        return $this->projectDir.'/'.ltrim($file, '/');
    }

    private function relativePath(string $path): string
    {
        return str_starts_with($path, $this->projectDir.'/')
            ? substr($path, \strlen($this->projectDir) + 1)
            : $path;
    }
}
