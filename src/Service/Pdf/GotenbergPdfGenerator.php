<?php

namespace App\Service\Pdf;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

/**
 * Implementazione di PdfGeneratorInterface basata su Gotenberg.
 * Invia richieste HTTP al container Gotenberg per la conversione HTML->PDF.
 */
#[AsAlias(PdfGeneratorInterface::class)]
class GotenbergPdfGenerator implements PdfGeneratorInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Environment $twig,
        #[Autowire('%env(GOTENBERG_ENDPOINT)%')]
        private readonly string $gotenbergEndpoint = 'http://localhost:3000',
    ) {}

    public function render(string $template, array $context = [], array $options = []): string
    {
        $html = $this->twig->render($template, $context);

        return $this->renderFromHtml($html, $options);
    }

    public function renderFromHtml(string $html, array $options = []): string
    {
        // Creiamo un file temporaneo con l'HTML
        $tempFile = tempnam(sys_get_temp_dir(), 'gotenberg_');
        file_put_contents($tempFile, $html);

        $formFields = [
            'index.html' => DataPart::fromPath($tempFile, 'index.html', 'text/html'),
        ];

        // Normalizza le opzioni (kebab-case -> camelCase)
        $normalizedOptions = [];
        foreach ($options as $key => $value) {
            $camelKey = lcfirst(str_replace('-', '', ucwords($key, '-')));
            $normalizedOptions[$camelKey] = $value;
        }

        // Mapping margins
        $margins = ['marginTop', 'marginBottom', 'marginLeft', 'marginRight'];
        foreach ($margins as $margin) {
            if (isset($normalizedOptions[$margin])) {
                $val = $normalizedOptions[$margin];
                if (is_string($val) && str_ends_with($val, 'mm')) {
                    $mm = (float)str_replace('mm', '', $val);
                    $inches = $mm * 0.0393701;
                    $formFields[$margin] = number_format($inches, 4, '.', ''); // Ensure dot separator
                } elseif (is_numeric($val)) {
                    $formFields[$margin] = number_format((float)$val, 4, '.', '');
                }
            }
        }

        // Orientamento
        if (isset($normalizedOptions['landscape']) && $normalizedOptions['landscape']) {
            $formFields['landscape'] = 'true';
        }

        // Handle Footers (Legacy wkhtmltopdf support)
        // Supported: footer-right with "[page]" and "[toPage]" substitution
        if (isset($options['footer-right'])) {
            $footerContent = $options['footer-right'];
            $footerContent = str_replace('[page]', '<span class="pageNumber"></span>', $footerContent);
            $footerContent = str_replace('[toPage]', '<span class="totalPages"></span>', $footerContent);

            $fontSize = $options['footer-font-size'] ?? '8';
            $fontName = $options['footer-font-name'] ?? 'Arial';

            // Build HTML for footer
            // Chromium footer template needs specific styling
            $footerHtml = <<<HTML
            <html>
            <head>
                <style>
                    body {
                        font-family: "$fontName", sans-serif;
                        font-size: {$fontSize}pt;
                        width: 100%;
                        margin: 0;
                        padding: 0 10mm; /* Match document margins roughly */
                        display: flex;
                        justify-content: flex-end; /* footer-right */
                    }
                </style>
            </head>
            <body>
                <div style="font-family: '$fontName', sans-serif;">$footerContent</div>
            </body>
            </html>
HTML;
            $tempFooter = tempnam(sys_get_temp_dir(), 'gotenberg_footer_');
            file_put_contents($tempFooter, $footerHtml);
            $formFields['footer.html'] = DataPart::fromPath($tempFooter, 'footer.html', 'text/html');
        }

        $formData = new FormDataPart($formFields);

        try {
            $response = $this->httpClient->request('POST', $this->gotenbergEndpoint . '/forms/chromium/convert/html', [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
            ]);

            return $response->getContent();
        } finally {
            @unlink($tempFile);
        }
    }
}
