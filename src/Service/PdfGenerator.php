<?php

namespace App\Service;

use Knp\Snappy\Pdf;
use Twig\Environment;

class PdfGenerator
{
    public function __construct(
        private readonly Pdf $snappy,
        private readonly Environment $twig,
    ) {
    }

    /**
     * Renderizza un template Twig in HTML e lo converte in PDF.
     *
     * @param string $template Nome del template Twig (es. templates/pdf/contracts/FILE.html.twig)
     * @param array  $context  Contesto passato a Twig
     * @param array  $options  Opzioni Snappy (override config)
     *
     * @return string Contenuto PDF binario
     */
    public function render(string $template, array $context = [], array $options = []): string
    {
        $html = $this->twig->render($template, $context);

        return $this->snappy->getOutputFromHtml($html, $options);
    }

    public function renderFromHtml(string $html, array $options = []): string
    {
        return $this->snappy->getOutputFromHtml($html, $options);
    }
}
