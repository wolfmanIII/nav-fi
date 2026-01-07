<?php

namespace App\Command;

use App\Repository\InsuranceRepository;
use App\Repository\CostCategoryRepository;
use App\Repository\IncomeCategoryRepository;
use App\Repository\InterestRateRepository;
use App\Repository\LocalLawRepository;
use App\Repository\ShipRoleRepository;
use App\Repository\CompanyRoleRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:context:export',
    description: 'Esporta i dati di contesto su file JSON'
)]
class ExportContextCommand extends Command
{
    public function __construct(
        private readonly InsuranceRepository $insuranceRepository,
        private readonly CostCategoryRepository $costCategoryRepository,
        private readonly IncomeCategoryRepository $incomeCategoryRepository,
        private readonly InterestRateRepository $interestRateRepository,
        private readonly ShipRoleRepository $shipRoleRepository,
        private readonly CompanyRoleRepository $companyRoleRepository,
        private readonly LocalLawRepository $localLawRepository,
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
                'Percorso del file di export (relativo alla root del progetto se non assoluto)',
                'config/seed/context_seed.json'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $file = (string) $input->getOption('file');
        $targetPath = $this->resolvePath($file);
        $filesystem->mkdir(\dirname($targetPath));

        $payload = [
            'insurance' => array_map(
                static function ($insurance): array {
                    return [
                        'name'         => $insurance->getName(),
                        'annual_cost'  => $insurance->getAnnualCost(),
                        'loss_refund'  => $insurance->getLossRefund(),
                        'coverage'     => $insurance->getCoverage(),
                    ];
                },
                $this->insuranceRepository->findAll()
            ),
            'interest_rates' => array_map(
                static function ($rate): array {
                    return [
                        'duration'            => $rate->getDuration(),
                        'price_multiplier'    => $rate->getPriceMultiplier(),
                        'price_divider'       => $rate->getPriceDivider(),
                        'annual_interest_rate'=> $rate->getAnnualInterestRate(),
                    ];
                },
                $this->interestRateRepository->findAll()
            ),
            'ship_roles' => array_map(
                static function ($role): array {
                    return [
                        'code'        => $role->getCode(),
                        'name'        => $role->getName(),
                        'description' => $role->getDescription(),
                    ];
                },
                $this->shipRoleRepository->findAll()
            ),
            'company_roles' => array_map(
                static function ($role): array {
                    return [
                        'code'        => $role->getCode(),
                        'short_description' => $role->getShortDescription(),
                        'description' => $role->getDescription(),
                    ];
                },
                $this->companyRoleRepository->findAll()
            ),
            'cost_categories' => array_map(
                static function ($category): array {
                    return [
                        'code'        => $category->getCode(),
                        'description' => $category->getDescription(),
                    ];
                },
                $this->costCategoryRepository->findAll()
            ),
            'income_categories' => array_map(
                static function ($category): array {
                    return [
                        'code'        => $category->getCode(),
                        'description' => $category->getDescription(),
                    ];
                },
                $this->incomeCategoryRepository->findAll()
            ),
            'local_laws' => array_map(
                static function ($law): array {
                    return [
                        'code'        => $law->getCode(),
                        'short_description' => $law->getShortDescription(),
                        'description' => $law->getDescription(),
                        'disclaimer'  => $law->getDisclaimer(),
                    ];
                },
                $this->localLawRepository->findAll()
            ),
        ];

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            $io->error('Impossibile serializzare i dati in JSON.');

            return Command::FAILURE;
        }

        file_put_contents($targetPath, $encoded);
        $io->success(sprintf('Dati di contesto esportati in %s', $this->relativePath($targetPath)));

        return Command::SUCCESS;
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
