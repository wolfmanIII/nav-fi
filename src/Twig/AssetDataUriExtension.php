<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Estensione Twig per il rendering di asset come Data URI base64.
 * Necessario per Gotenberg che non può accedere a URL locali.
 * Utilizza il servizio Packages di Symfony per risolvere i path versionati.
 */
class AssetDataUriExtension extends AbstractExtension
{
    public function __construct(
        private readonly Packages $packages,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_data_uri', [$this, 'assetDataUri']),
        ];
    }

    /**
     * Converte un asset pubblico in una Data URI base64.
     * Usa Symfony Asset per risolvere il path versionato (es. nav-fi-logo-6WwjDfQ.png).
     * Esempio: {{ asset_data_uri('img/nav-fi-logo.png') }} → data:image/png;base64,...
     */
    public function assetDataUri(string $path): string
    {
        // Usa Symfony Asset per ottenere il path versionato (es. /assets/img/nav-fi-logo-6WwjDfQ.png)
        $versionedPath = $this->packages->getUrl($path);

        // Rimuovi eventuali leading slashes e costruisci il path assoluto
        $relativePath = ltrim($versionedPath, '/');
        $fullPath = $this->projectDir . '/public/' . $relativePath;

        if (!file_exists($fullPath)) {
            return '';
        }

        $mimeType = $this->getMimeType($fullPath);
        $content = file_get_contents($fullPath);

        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }

    private function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
