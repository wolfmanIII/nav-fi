<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TravellerMapSectorLookup
{
    private const BASE_URL = 'https://travellermap.com';
    private const CACHE_TTL = 86400;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $cache
    ) {}

    public function lookupWorld(string $sector, string $hex): ?array
    {
        $sector = trim($sector);
        $hex = strtoupper(trim($hex));
        if ($sector === '' || $hex === '') {
            return null;
        }

        $data = $this->fetchSectorData($sector);
        return $this->parseWorldFromData($data, $hex);
    }

    private function fetchSectorData(string $sector): string
    {
        $cacheKey = 'travellermap_sector_' . sha1($sector);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($sector): string {
            $item->expiresAfter(self::CACHE_TTL);

            $url = sprintf('%s/data/%s', self::BASE_URL, rawurlencode($sector));
            $response = $this->client->request('GET', $url);

            return $response->getContent();
        });
    }

    private function parseWorldFromData(string $data, string $hex): ?array
    {
        $lines = preg_split('/\r\n|\r|\n/', $data) ?: [];
        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (!preg_match('/^(\d{4})\s+(.+?)\s+([0-9A-Z]{7}-[0-9A-Z])\b/', $line, $matches)) {
                continue;
            }

            if (strtoupper($matches[1]) !== $hex) {
                continue;
            }

            return [
                'world' => trim($matches[2]),
                'uwp' => $matches[3],
            ];
        }

        return null;
    }
}
