<?php

namespace App\Command;

use App\Service\Pdf\PdfGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Comando di test per verificare la connettività con Gotenberg.
 * Genera un PDF di prova e lo salva in var/test.pdf.
 */
#[AsCommand(
    name: 'app:test:pdf',
    description: 'Test PDF generation via Gotenberg',
)]
class TestPdfCommand extends Command
{
    public function __construct(
        private readonly PdfGeneratorInterface $pdfGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing PDF Generation');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test PDF</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; }
        h1 { color: #0ea5e9; }
        .success { color: #22c55e; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Nav-Fi³ PDF Test</h1>
    <p class="success">✓ Gotenberg connection successful!</p>
    <p>Generated at: %s</p>
</body>
</html>
HTML;

        $html = sprintf($html, date('Y-m-d H:i:s'));

        try {
            $io->text('Sending HTML to Gotenberg...');
            $pdfContent = $this->pdfGenerator->renderFromHtml($html);

            $outputPath = 'var/test.pdf';
            file_put_contents($outputPath, $pdfContent);

            $io->success(sprintf('PDF generated successfully! File: %s (%d bytes)', $outputPath, strlen($pdfContent)));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(sprintf('PDF generation failed: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
