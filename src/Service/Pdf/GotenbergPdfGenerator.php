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
            'files' => DataPart::fromPath($tempFile, 'index.html', 'text/html'),
        ];

        // Margini
        if (isset($options['marginTop'])) {
            $formFields['marginTop'] = (string) $options['marginTop'];
        }
        if (isset($options['marginBottom'])) {
            $formFields['marginBottom'] = (string) $options['marginBottom'];
        }
        if (isset($options['marginLeft'])) {
            $formFields['marginLeft'] = (string) $options['marginLeft'];
        }
        if (isset($options['marginRight'])) {
            $formFields['marginRight'] = (string) $options['marginRight'];
        }

        // Orientamento
        if (isset($options['landscape']) && $options['landscape']) {
            $formFields['landscape'] = 'true';
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
