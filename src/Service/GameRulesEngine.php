<?php

namespace App\Service;

use App\Repository\GameRuleRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GameRulesEngine
{
    private const CACHE_TTL = 3600; // 1 hour cache
    private array $localCache = [];

    public function __construct(
        private readonly GameRuleRepository $repository,
        private readonly CacheInterface $cache
    ) {}

    /**
     * Recupera il valore di una regola di gioco.
     * Ordine di precedenza:
     * 1. Cache Locale (Memoria per richiesta)
     * 2. Cache Persistente (Redis/Filesystem)
     * 3. Database
     * 4. Valore di Default (Fornito nel codice)
     *
     * @param string $key La chiave in dot.notation (es. 'passenger.base_price')
     * @param mixed $default Valore di fallback se la regola non esiste nel DB
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 1. Local Memory Cache (per request)
        if (array_key_exists($key, $this->localCache)) {
            return $this->localCache[$key];
        }

        // 2. Persistent Cache
        $value = $this->cache->get('gamerule_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $key), function (ItemInterface $item) use ($key, $default) {
            $item->expiresAfter(self::CACHE_TTL);

            $rule = $this->repository->findOneByKey($key);

            if (!$rule) {
                // Cache the MISS as null to avoid DB hammering, but maybe with shorter TTL?
                // For now, if missing in DB, we return DEFAULT. 
                // We do NOT cache the default value permanently as 'null' because the DB might update.
                // Actually, let's return a special sentinel or just the value.
                // If we cache the default, and then add to DB, we need to clear cache.
                return '__MISSING__';
            }

            return $rule->getTypedValue();
        });

        if ($value === '__MISSING__') {
            $result = $default;
        } else {
            $result = $value;
        }

        $this->localCache[$key] = $result;
        return $result;
    }

    /**
     * Invalida la cache per una chiave specifica (utile dopo aggiornamenti admin).
     */
    public function invalidate(string $key): void
    {
        $this->cache->delete('gamerule_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $key));
        unset($this->localCache[$key]);
    }
}
