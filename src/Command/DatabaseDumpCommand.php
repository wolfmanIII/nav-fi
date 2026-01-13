<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:db:dump',
    description: 'Crea un dump del database (Postgres)'
)]
class DatabaseDumpCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%env(resolve:DATABASE_URL)%')] private readonly string $databaseUrl,
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
                'Percorso del file di dump (relativo alla root del progetto se non assoluto)',
                'var/backup/nav-fi.dump'
            )
            ->addOption(
                'data-only',
                null,
                InputOption::VALUE_NONE,
                'Esporta solo i dati'
            )
            ->addOption(
                'schema-only',
                null,
                InputOption::VALUE_NONE,
                'Esporta solo lo schema'
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

        $dataOnly = (bool) $input->getOption('data-only');
        $schemaOnly = (bool) $input->getOption('schema-only');

        if ($dataOnly && $schemaOnly) {
            $io->error('Non puoi usare insieme --data-only e --schema-only.');
            return Command::FAILURE;
        }

        $db = $this->parseDatabaseUrl($io);
        if ($db === null) {
            return Command::FAILURE;
        }

        $args = [
            'pg_dump',
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--file=' . $targetPath,
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--username=' . $db['user'],
            $db['dbname'],
        ];

        if ($dataOnly) {
            $args[] = '--data-only';
        } elseif ($schemaOnly) {
            $args[] = '--schema-only';
        }

        $process = new Process($args, $this->projectDir, $this->buildEnv($db));
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Errore durante il dump del database.');
            $io->text($process->getErrorOutput() ?: $process->getOutput());
            return Command::FAILURE;
        }

        $io->success(sprintf('Dump creato in %s', $this->relativePath($targetPath)));
        return Command::SUCCESS;
    }

    /**
     * @return array{host: string, port: string, user: string, password: string, dbname: string}|null
     */
    private function parseDatabaseUrl(SymfonyStyle $io): ?array
    {
        $parts = parse_url($this->databaseUrl);
        $scheme = $parts['scheme'] ?? '';

        if ($scheme === '' || !str_starts_with($scheme, 'postgres')) {
            $io->error('DATABASE_URL non è PostgreSQL.');
            return null;
        }

        $host = $parts['host'] ?? 'localhost';
        $port = (string) ($parts['port'] ?? 5432);
        $user = $parts['user'] ?? '';
        $password = $parts['pass'] ?? '';
        $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

        if ($dbname === '') {
            $io->error('DATABASE_URL non contiene il nome del database.');
            return null;
        }

        return [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'dbname' => $dbname,
        ];
    }

    /**
     * @param array{password: string} $db
     *
     * @return array<string, string>
     */
    private function buildEnv(array $db): array
    {
        if ($db['password'] === '') {
            return [];
        }

        return ['PGPASSWORD' => $db['password']];
    }

    private function resolvePath(string $file): string
    {
        if (str_starts_with($file, '/')) {
            return $file;
        }

        return $this->projectDir . '/' . ltrim($file, '/');
    }

    private function relativePath(string $path): string
    {
        return str_starts_with($path, $this->projectDir . '/')
            ? substr($path, \strlen($this->projectDir) + 1)
            : $path;
    }
}
