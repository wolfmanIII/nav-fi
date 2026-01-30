<?php

namespace App\Service\Pdf;

/**
 * Contratto per la generazione di documenti PDF.
 * Astrae il motore di rendering sottostante (Gotenberg, Snappy, ecc.).
 */
interface PdfGeneratorInterface
{
    /**
     * Renderizza un template Twig in PDF.
     *
     * @param string $template Nome del template Twig
     * @param array  $context  Variabili passate al template
     * @param array  $options  Opzioni di rendering (margini, orientamento, ecc.)
     *
     * @return string Contenuto PDF binario
     */
    public function render(string $template, array $context = [], array $options = []): string;

    /**
     * Converte HTML grezzo in PDF.
     *
     * @param string $html    Contenuto HTML
     * @param array  $options Opzioni di rendering
     *
     * @return string Contenuto PDF binario
     */
    public function renderFromHtml(string $html, array $options = []): string;
}
