<?php

namespace App\Service;

/**
 * Servizio per la normalizzazione di stringhe e nomi.
 * Utilizzato per creare versioni "canoniche" dei nomi dei patroni per la ricerca elastica.
 */
class NormalizationService
{
    /**
     * Lista di titoli imperiali da ignorare durante la normalizzazione.
     */
    private const IMPERIAL_TITLES = [
        'knight',
        'dame',
        'baronet',
        'baroness',
        'baron',
        'marchioness',
        'marquis',
        'countess',
        'count',
        'duchess',
        'duke',
        'archduchess',
        'archduke',
        'sir',
        'lady'
    ];

    /**
     * Normalizza un nome rimuovendo titoli, spazi e caratteri speciali.
     * Ritorna una stringa pulita utilizzata per il campo canonical_name.
     */
    public function normalize(string $name): string
    {
        // 1. Tutto in minuscolo
        $normalized = mb_strtolower($name);

        // 2. Rimuove titoli imperiali comuni (ricerca per parola intera)
        foreach (self::IMPERIAL_TITLES as $title) {
            $normalized = preg_replace('/\b' . preg_quote($title, '/') . '\b/u', '', $normalized);
        }

        // 3. Rimuove tutto ciò che non è alfanumerico (compresi spazi e trattini)
        $normalized = preg_replace('/[^a-z0-9]/u', '', $normalized);

        return $normalized;
    }
}
