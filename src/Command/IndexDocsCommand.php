<?php

namespace App\Command;

use App\Entity\DocumentChunk;
use App\Service\DocumentTextExtractor;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:index-docs')]
class IndexDocsCommand extends Command
{
    // cartelle da escludere (relative a var/knowledge)
    private array $excludedDirs = [
        'images',
        'img',
        'tmp',
        '.git',
        '.idea',
    ];

    // pattern per filename da escludere (regex)
    private array $excludedNamePatterns = [
        '/^~.*$/',         // file temporanei tipo ~qualcosa.docx
        '/^\.~lock\..*/',  // lock file LibreOffice
        '/^\.gitkeep$/',   // file di servizio
        '/^\.DS_Store$/',  // Mac
    ];

    // estensioni supportate
    private array $extensions = ['pdf', 'md', 'odt', 'docx'];

    public function __construct(
        private EntityManagerInterface $em,
        private DocumentTextExtractor $extractor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Indicizza i documenti in var/knowledge (PDF/MD/ODT/DOCX) con embeddings.')
            ->addOption(
                'force-reindex',
                null,
                InputOption::VALUE_NONE,
                'Ignora hash e reindicizza tutti i file (anche se non modificati)'
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Sotto-percorso relativo da indicizzare (es: "manuali", "log/2025"). Puoi usarlo più volte.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Mostra cosa verrebbe indicizzato, senza scrivere su DB e senza chiamare OpenAI'
            )
            ->addOption(
                'test-mode',
                null,
                InputOption::VALUE_NONE,
                'Usa embeddings finti invece di chiamare OpenAI (non vengono consumati crediti)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rootDir = __DIR__ . '/../../var/knowledge';

        if (!is_dir($rootDir)) {
            $output->writeln('<error>Cartella non trovata: '.$rootDir.'</error>');
            return Command::FAILURE;
        }

        $forceReindex = (bool) $input->getOption('force-reindex');
        $dryRun       = (bool) $input->getOption('dry-run');

        // test-mode via opzione OPPURE via env
        $testMode     =
            (bool) $input->getOption('test-mode') ||
            (($_ENV['APP_AI_TEST_MODE'] ?? 'false') === 'true');

        $offlineFallback =
            ($_ENV['APP_AI_OFFLINE_FALLBACK'] ?? 'true') === 'true';

        /** @var string[] $pathsFilter */
        $pathsFilter  = $input->getOption('path') ?? [];

        if (!empty($pathsFilter)) {
            $pathsFilter = array_map(static fn(string $p) => trim($p, '/'), $pathsFilter);
            $output->writeln('<info>Filtro path attivo:</info> '.implode(', ', $pathsFilter));
        }

        if ($forceReindex) {
            $output->writeln('<comment>--force-reindex: tutti i file selezionati saranno reindicizzati.</comment>');
        }

        if ($dryRun) {
            $output->writeln('<comment>--dry-run: nessuna scrittura su DB, nessuna chiamata reale a OpenAI.</comment>');
        }

        if ($testMode) {
            $output->writeln('<comment>Test mode attivo: uso embeddings finti, niente OpenAI.</comment>');
        }

        // 1) Raccogliamo i file candidati (ricorsivo)
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootDir, \FilesystemIterator::SKIP_DOTS)
        );

        $files = [];

        foreach ($iterator as $filePath => $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $ext = strtolower($fileInfo->getExtension());
            if (!in_array($ext, $this->extensions, true)) {
                continue;
            }

            $relPath = substr($filePath, strlen($rootDir) + 1);
            $dirName = trim(dirname($relPath), '.');

            if ($this->isInExcludedDir($dirName)) {
                continue;
            }

            $fileName = $fileInfo->getFilename();
            if ($this->isExcludedName($fileName)) {
                continue;
            }

            if (!empty($pathsFilter) && !$this->matchesPathsFilter($relPath, $pathsFilter)) {
                continue;
            }

            $files[] = $filePath;
        }

        if (!$files) {
            $output->writeln('<comment>Nessun file supportato/filtro corrispondente trovato in '.$rootDir.'</comment>');
            return Command::SUCCESS;
        }

        // 2) Progress bar se verbose
        $progressBar = null;
        if ($output->isVerbose()) {
            $progressBar = new ProgressBar($output, count($files));
            $progressBar->start();
        }

        // 3) Client OpenAI solo se serve
        $client = null;
        if (!$dryRun && !$testMode) {
            $client = OpenAI::client($_ENV['OPENAI_API_KEY']);
        }

        foreach ($files as $file) {
            $relPath   = substr($file, strlen($rootDir) + 1); // es: "manuali/cap1.pdf"
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $fileHash  = hash_file('sha256', $file);

            if ($output->isVeryVerbose()) {
                $output->writeln("\nFile: <info>$relPath</info>");
            }

            // 3a) se NON forceReindex & NON dryRun & NON test-mode → controlla hash
            if (!$forceReindex && !$dryRun && !$testMode) {
                $existing = $this->em->createQueryBuilder()
                    ->select('COUNT(c.id)')
                    ->from(DocumentChunk::class, 'c')
                    ->where('c.path = :path')
                    ->andWhere('c.fileHash = :hash')
                    ->setParameter('path', $relPath)
                    ->setParameter('hash', $fileHash)
                    ->getQuery()
                    ->getSingleScalarResult();

                if ((int)$existing > 0) {
                    if ($output->isVeryVerbose()) {
                        $output->writeln('  -> Nessuna modifica (hash uguale), salto');
                    }
                    if ($progressBar) {
                        $progressBar->advance();
                    }
                    continue;
                }
            }

            // 3b) estrai testo
            $text = $this->extractor->extract($file);
            if ($text === null || $text === '') {
                if ($output->isVeryVerbose()) {
                    $output->writeln('  -> Nessun testo estratto, salto');
                }
                if ($progressBar) {
                    $progressBar->advance();
                }
                continue;
            }

            // 3c) split in chunk
            $chunks = $this->splitIntoChunks($text, 1000);
            $now    = new \DateTimeImmutable();

            if ($dryRun) {
                $approxTokens = (int) (mb_strlen($text) / 4);
                $output->writeln(sprintf(
                    "\n[dry-run] %s → %d chunk, ~%d token",
                    $relPath,
                    count($chunks),
                    $approxTokens
                ));
                if ($progressBar) {
                    $progressBar->advance();
                }
                // niente DB, niente embeddings
                continue;
            }

            // 3d) elimina vecchi chunk per questo path
            $this->em->createQueryBuilder()
                ->delete(DocumentChunk::class, 'c')
                ->where('c.path = :path')
                ->setParameter('path', $relPath)
                ->getQuery()
                ->execute();

            foreach ($chunks as $index => $chunkText) {
                // embedding del chunk
                $embedding = null;

                if ($testMode) {
                    // embedding finto deterministico
                    $embedding = $this->fakeEmbeddingFromText($chunkText, 1536);
                } else {
                    try {
                        $embResp = $client->embeddings()->create([
                            'model' => 'text-embedding-3-small',
                            'input' => $chunkText,
                        ]);
                        $embedding = $embResp->embeddings[0]->embedding;
                    } catch (\Throwable $e) {
                        if ($offlineFallback) {
                            if ($output->isVeryVerbose()) {
                                $output->writeln('  -> Errore embeddings, uso fallback finto: '.$e->getMessage());
                            }
                            $embedding = $this->fakeEmbeddingFromText($chunkText, 1536);
                        } else {
                            $output->writeln('<error>Errore embeddings: '.$e->getMessage().'</error>');
                            // saltiamo questo chunk
                            continue;
                        }
                    }
                }

                $chunk = (new DocumentChunk())
                    ->setPath($relPath)
                    ->setExtension($extension)
                    ->setChunkIndex($index)
                    ->setContent($chunkText)
                    ->setIndexedAt($now)
                    ->setFileHash($fileHash)
                    ->setEmbedding($embedding);

                $this->em->persist($chunk);
            }

            $this->em->flush();
            $this->em->clear();

            if ($output->isVeryVerbose()) {
                $output->writeln('  -> Indicizzato (' . count($chunks) . ' chunk)');
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->finish();
            $output->writeln('');
        }

        $output->writeln('<info>Indicizzazione completata.</info>');

        return Command::SUCCESS;
    }

    private function splitIntoChunks(string $text, int $maxLen): array
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $chunks = [];

        while (mb_strlen($text) > $maxLen) {
            $slice = mb_substr($text, 0, $maxLen);
            $pos   = mb_strrpos($slice, '.');

            if ($pos === false) {
                $pos = $maxLen;
            }

            $chunks[] = trim(mb_substr($text, 0, $pos));
            $text     = trim(mb_substr($text, $pos));
        }

        if ($text !== '') {
            $chunks[] = $text;
        }

        return $chunks;
    }

    private function isInExcludedDir(string $dirName): bool
    {
        if ($dirName === '.' || $dirName === '') {
            return false;
        }

        $segments = explode(DIRECTORY_SEPARATOR, $dirName);
        foreach ($segments as $seg) {
            if (in_array($seg, $this->excludedDirs, true)) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedName(string $fileName): bool
    {
        foreach ($this->excludedNamePatterns as $pattern) {
            if (preg_match($pattern, $fileName)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPathsFilter(string $relPath, array $pathsFilter): bool
    {
        $relPathNorm = ltrim($relPath, '/');

        foreach ($pathsFilter as $filter) {
            $filterNorm = trim($filter, '/');

            if ($relPathNorm === $filterNorm) {
                return true;
            }

            if (str_starts_with($relPathNorm, $filterNorm.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera un embedding finto ma deterministico, dato il testo.
     */
    private function fakeEmbeddingFromText(string $text, int $dimensions): array
    {
        $hash = hash('sha256', $text, true); // 32 byte
        $vector = [];

        for ($i = 0; $i < $dimensions; $i++) {
            $b = ord($hash[$i % 32]);       // 0..255
            $vector[] = ($b / 128.0) - 1.0; // circa -1..+1
        }

        return $vector;
    }
}
