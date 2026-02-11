<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TravellerMapDataService
{
    private const METADATA_FILENAME = 'sectors_metadata.json';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly TravellerMapSectorLookup $sectorLookup,
        #[Autowire('%app.travellermap.base_url%')]
        private readonly string $baseUrl,
        #[Autowire('%app.travellermap.sector_storage_path%')]
        private readonly string $storagePath
    ) {}

    /**
     * Restituisce la lista dei mondi per un settore.
     * @return array<int, array{label: string, value: string}>
     */
    public function getWorldsForSector(string $sectorName): array
    {
        try {
            $systems = $this->sectorLookup->parseSector($sectorName);
            $worlds = [];
            foreach ($systems as $system) {
                $label = sprintf('%s (%s) - %s', $system['name'], $system['hex'], $system['uwp']);
                $worlds[$label] = $system['hex'];
            }
            
            // Ordina per label (chiave)
            ksort($worlds);
            
            return $worlds;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get worlds for sector', [
                'sector' => $sectorName,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Restituisce i metadati dei settori, scaricandoli se necessario.
     */
    public function getSectorsMetadata(bool $forceRefresh = false): array
    {
        $filePath = Path::join($this->storagePath, self::METADATA_FILENAME);
        $fs = new Filesystem();

        if ($forceRefresh || $this->isExpired($filePath)) {
            $this->refreshMetadata($filePath);
        }

        if (!$fs->exists($filePath)) {
            return [];
        }

        try {
            return json_decode(file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('Failed to decode sectors metadata', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Restituisce le coordinate X e Y di un settore cercandolo nei metadati per nome.
     * @return array{x: int, y: int}|null
     */
    public function getSectorCoordinates(string $sectorName): ?array
    {
        $data = $this->getSectorsMetadata();
        $sectors = $data['Sectors'] ?? [];

        foreach ($sectors as $sector) {
            foreach ($sector['Names'] ?? [] as $nameObj) {
                if (strcasecmp($nameObj['Text'] ?? '', $sectorName) === 0) {
                    return [
                        'x' => (int) ($sector['X'] ?? 0),
                        'y' => (int) ($sector['Y'] ?? 0),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Restituisce solo i settori che contengono il tag "OTU".
     * Restituisce un array associativo [Nome Settore => Nome Settore] per l'uso nei form.
     */
    public function getOtuSectors(): array
    {
        $data = $this->getSectorsMetadata();
        $sectors = $data['Sectors'] ?? [];
        $otuSectors = [];

        foreach ($sectors as $sector) {
            $tags = $sector['Tags'] ?? '';
            if (str_contains($tags, 'OTU')) {
                // Prendi il primo nome disponibile
                $name = $sector['Names'][0]['Text'] ?? null;
                if ($name) {
                    $otuSectors[$name] = $name;
                }
            }
        }

        ksort($otuSectors);

        return $otuSectors;
    }

    /**
     * Verifica se il file è scaduto (se il mese corrente è diverso dal mese di modifica).
     */
    private function isExpired(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return true;
        }

        $lastModified = filemtime($filePath);
        $currentMonth = date('Y-m');
        $fileMonth = date('Y-m', $lastModified);

        return $currentMonth !== $fileMonth;
    }

    /**
     * Scarica e salva i metadati da TravellerMap.
     */
    private function refreshMetadata(string $filePath): void
    {
        $url = $this->baseUrl . '/data';
        $this->logger->info('Refreshing TravellerMap sectors metadata', ['url' => $url]);

        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $content = $response->getContent();
            
            // Verifica base della validità del JSON
            json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $fs = new Filesystem();
            $fs->dumpFile($filePath, $content);
        } catch (\Exception $e) {
            $this->logger->error('Failed to refresh TravellerMap sectors metadata', ['error' => $e->getMessage()]);
            if (!file_exists($filePath)) {
                // Se il file non esiste e il download fallisce, non possiamo fare molto
                throw $e;
            }
        }
    }
}
