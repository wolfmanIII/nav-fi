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
     * Render a Twig template to HTML and convert it to PDF.
     *
     * @param string $template Twig template name (es. templates/pdf/contracts/FILE.html.twig)
     * @param array  $context  Context passed to Twig
     * @param array  $options  Snappy options (override config)
     *
     * @return string Binary PDF content
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
