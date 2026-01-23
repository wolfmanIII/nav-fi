<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TravellerMapSectorLookup
{
    private const BASE_URL = 'https://travellermap.com';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.cube.sector_storage_path%')]
        private readonly string $storagePath
    ) {}

    public function lookupWorld(string $sector, string $hex): ?array
    {
        // Fallback elegante per input vuoto
        if (empty(trim($sector)) || empty(trim($hex))) {
            return null;
        }

        try {
            $data = $this->parseSector($sector);
            $hex = strtoupper(trim($hex));

            foreach ($data as $system) {
                if ($system['hex'] === $hex) {
                    return [
                        'world' => $system['name'],
                        'uwp' => $system['uwp'],
                        'trade_codes' => $system['trade_codes'],
                        'pop_multiplier' => $system['pop_multiplier'] ?? 0,
                        'belts' => $system['belts'] ?? 0,
                        'gas_giants' => $system['gas_giants'] ?? 0,
                        'ix' => $system['ix'],
                        'ex' => $system['ex'],
                        'cx' => $system['cx'],
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error looking up world', [
                'sector' => $sector,
                'hex' => $hex,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Scarica e analizza i dati del settore.
     * restituisce un array di ['hex' => '...', 'name' => '...', 'uwp' => '...', 'trade_codes' => [], ...]
     */
    public function parseSector(string $sectorName): array
    {
        $filePath = $this->ensureSectorData($sectorName);
        $content = file_get_contents($filePath);
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $systems = [];

        // Mappatura colonne default (Standard T5)
        $colMap = [
            'hex' => 0,
            'name' => 1,
            'uwp' => 2,
            'remarks' => 3
        ];
        $headerFound = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Dividi per tabulazioni
            $parts = preg_split('/\t+/', $line);

            // Controlla Riga Intestazione
            // Se la riga contiene "Hex" e "Name", la trattiamo come intestazione e rimappiamo le colonne
            $upperLine = strtoupper($line);
            if (str_contains($upperLine, 'HEX') && str_contains($upperLine, 'NAME')) {
                // Rimappa colonne basandosi sull'intestazione
                // Normalizziamo le intestazioni a MAIUSCOLO per trovare gli indici
                $headerParts = array_map('strtoupper', array_map('trim', $parts));

                $hexIndex = array_search('HEX', $headerParts);
                $nameIndex = array_search('NAME', $headerParts);
                $uwpIndex = array_search('UWP', $headerParts);
                $remarksIndex = array_search('REMARKS', $headerParts);
                $pbgIndex = array_search('PBG', $headerParts);

                if ($hexIndex !== false && $nameIndex !== false && $uwpIndex !== false) {
                    $colMap['hex'] = $hexIndex;
                    $colMap['name'] = $nameIndex;
                    $colMap['uwp'] = $uwpIndex;
                    // Remarks è opzionale o potrebbe chiamarsi diversamente (Comments?) ma di solito Remarks
                    if ($remarksIndex !== false) {
                        $colMap['remarks'] = $remarksIndex;
                    }
                    if ($pbgIndex !== false) {
                        $colMap['pbg'] = $pbgIndex;
                    }
                    $headerFound = true;
                }
                continue;
            }

            // Se non abbiamo ancora trovato un'intestazione, e la riga non sembra dati (nessun hex alla pos prevista), 
            // forse è una riga pre-intestazione?
            // Ma se ci fidiamo della mappa default, controlliamo se la col hex default è valida.
            $potentialHex = $parts[$colMap['hex']] ?? '';
            if (!preg_match('/^\d{4}$/', $potentialHex) && !$headerFound) {
                // Proviamo a indovinare se questa è una riga intestazione che abbiamo perso o solo spazzatura?
                // Se è il formato "Sector SS Hex" ma nessuna riga intestazione è stata ancora trovata (improbabile se il file ha intestazione)
                // Ma assumiamo che le righe dati valide DEBBANO avere un hex valido alla posizione mappata.
                continue;
            }

            // Estrai Dati
            $hex = trim($parts[$colMap['hex']] ?? '');
            $name = trim($parts[$colMap['name']] ?? '');
            $uwp = trim($parts[$colMap['uwp']] ?? '');
            $remarks = isset($colMap['remarks']) ? trim($parts[$colMap['remarks']] ?? '') : '';
            $pbg = isset($colMap['pbg']) ? trim($parts[$colMap['pbg']] ?? '') : '';

            // Valida Hex (stretto 4 cifre)
            if (!preg_match('/^\d{4}$/', $hex)) {
                continue;
            }

            // Valida UWP (Controllo lunghezza base)
            if (strlen($uwp) < 7) { // X000000-0 è 9 car solitamente, ma forse alcuni ne permettono meno? standard è 9. Diciamo 7 per sicurezza.
                continue;
            }
            
            // Parse PBG (Pop Multiplier, Belts, Gas Giants)
            // Es: 123 -> 1 PopMult, 2 Belts, 3 Gas Giants
            $popMultiplier = 0;
            $belts = 0;
            $gasGiants = 0;
            
            if (strlen($pbg) >= 3 && is_numeric($pbg)) {
                $popMultiplier = (int) substr($pbg, 0, 1);
                $belts = (int) substr($pbg, 1, 1);
                $gasGiants = (int) substr($pbg, 2, 1);
            }

            $systems[] = [
                'hex' => $hex,
                'name' => $name,
                'uwp' => $uwp,
                'trade_codes' => $this->parseTradeCodes($remarks),
                'pbg' => $pbg,
                'pop_multiplier' => $popMultiplier,
                'belts' => $belts,
                'gas_giants' => $gasGiants,
                'ix' => '', // Dati estesi non critici per ora
                'ex' => '',
                'cx' => '',
            ];
        }

        return $systems;
    }

    public function ensureSectorData(string $sectorName): string
    {
        $fs = new Filesystem();
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sectorName);
        // Usa un versionamento semplice per data (cache giornaliera)
        $date = date('Y-m-d');
        $filename = sprintf('%s_%s.tab', $safeName, $date);
        $fullPath = Path::join($this->storagePath, $filename);

        if ($fs->exists($fullPath)) {
            return $fullPath;
        }

        // Download
        $url = sprintf('%s/data/%s/tab', self::BASE_URL, rawurlencode($sectorName));
        $this->logger->info("Downloading sector data from TravellerMap", ['url' => $url]);

        try {
            $response = $this->client->request('GET', $url);
            $content = $response->getContent();

            // Validazione semplice: controlla se sembra un file tab
            if (!str_contains($content, 'Hex') && !str_contains($content, 'Name')) {
                throw new \RuntimeException('Invalid sector data received');
            }

            // Pulisci vecchi file per questo settore
            $this->cleanupOldFiles($safeName);

            $fs->dumpFile($fullPath, $content);
        } catch (\Exception $e) {
            $this->logger->error("Failed to download sector data", ['error' => $e->getMessage()]);
            throw $e;
        }

        return $fullPath;
    }

    private function parseTradeCodes(string $remarks): array
    {
        // Remarks include Trade Codes (Ba, In, Hi) e commenti.
        // Trade codes sono tipicamente 2 car, titolo o maiuscolo.
        // Dividiamo per spazio
        $parts = explode(' ', $remarks);
        $codes = [];
        foreach ($parts as $part) {
            // Filtra trade codes comuni (2-4 car, lettere)
            if (preg_match('/^[A-Za-z]{2,4}$/', $part)) {
                $codes[] = $part;
            }
        }
        return $codes;
    }

    private function cleanupOldFiles(string $safeSectorName): void
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->storagePath)) {
            return;
        }

        $files = glob($this->storagePath . '/' . $safeSectorName . '_*.tab');
        foreach ($files as $file) {
            try {
                $fs->remove($file);
            } catch (\Exception $e) {
                // Ignora errori cancellazione
            }
        }
    }
}
