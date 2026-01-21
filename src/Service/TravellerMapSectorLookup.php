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
        // Graceful fallback for empty input
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
     * Downloads and parses sector data.
     * returns array of ['hex' => '...', 'name' => '...', 'uwp' => '...', 'trade_codes' => [], ...]
     */
    public function parseSector(string $sectorName): array
    {
        $filePath = $this->ensureSectorData($sectorName);
        $content = file_get_contents($filePath);
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $systems = [];

        // Default column mapping (Standard T5)
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

            // Split by tabs
            $parts = preg_split('/\t+/', $line);

            // Check for Header Line
            // If the line contains "Hex" and "Name", we treat it as a header and remap columns
            $upperLine = strtoupper($line);
            if (str_contains($upperLine, 'HEX') && str_contains($upperLine, 'NAME')) {
                // Remap columns based on header
                // We normalize headers to UPPERCASE to find indexes
                $headerParts = array_map('strtoupper', array_map('trim', $parts));

                $hexIndex = array_search('HEX', $headerParts);
                $nameIndex = array_search('NAME', $headerParts);
                $uwpIndex = array_search('UWP', $headerParts);
                $remarksIndex = array_search('REMARKS', $headerParts);

                if ($hexIndex !== false && $nameIndex !== false && $uwpIndex !== false) {
                    $colMap['hex'] = $hexIndex;
                    $colMap['name'] = $nameIndex;
                    $colMap['uwp'] = $uwpIndex;
                    // Remarks is optional or might be named differently (Comments?) but usually Remarks
                    if ($remarksIndex !== false) {
                        $colMap['remarks'] = $remarksIndex;
                    }
                    $headerFound = true;
                }
                continue;
            }

            // If we haven't found a header yet, and the line doesn't look like data (no hex at expected pos), 
            // maybe it's a pre-header line?
            // But if we trust the default map, we check if default hex col is valid.
            $potentialHex = $parts[$colMap['hex']] ?? '';
            if (!preg_match('/^\d{4}$/', $potentialHex) && !$headerFound) {
                // Try to guess if this is a header line that we missed or just garbage?
                // If it's the "Sector SS Hex" format but no header line was found yet (unlikely if file has header)
                // But let's assume valid data lines MUST have a valid hex at the mapped position.
                continue;
            }

            // Extract Data
            $hex = trim($parts[$colMap['hex']] ?? '');
            $name = trim($parts[$colMap['name']] ?? '');
            $uwp = trim($parts[$colMap['uwp']] ?? '');
            $remarks = isset($colMap['remarks']) ? trim($parts[$colMap['remarks']] ?? '') : '';

            // Validate Hex (strict 4 digits)
            if (!preg_match('/^\d{4}$/', $hex)) {
                continue;
            }

            // Validate UWP (Basic length check)
            if (strlen($uwp) < 7) { // X000000-0 is 9 chars usually, but maybe some allow fewer? standard is 9. Let's say 7 to be safe.
                continue;
            }

            $systems[] = [
                'hex' => $hex,
                'name' => $name,
                'uwp' => $uwp,
                'trade_codes' => $this->parseTradeCodes($remarks),
                'ix' => '', // Ext data not critical for now
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
        // Use a simple versioning by date (daily cache)
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

            // Simple validation: check if it looks like a tab file
            if (!str_contains($content, 'Hex') && !str_contains($content, 'Name')) {
                throw new \RuntimeException('Invalid sector data received');
            }

            // Cleanup old files for this sector
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
        // Remarks include Trade Codes (Ba, In, Hi) and comments.
        // Trade codes are typically 2 chars, title or upper case.
        // We split by space
        $parts = explode(' ', $remarks);
        $codes = [];
        foreach ($parts as $part) {
            // Filter common trade codes (2-4 chars, letters)
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
                // Ignore delete errors
            }
        }
    }
}
