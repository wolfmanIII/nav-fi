<?php

namespace App\Service\Trade;

use App\Entity\Cost;

class TradePricer
{
    /**
     * Calcola il prezzo di mercato per una merce.
     * 
     * Logica:
     * - Standard (70%): Raggiunge il Target (1.50x o markup promesso)
     * - Volatile (30%): Rischio di ribasso (frazione del markup promesso)
     * 
     * NOTA: Il calcolo è DETERMINISTICO basato sull'UUID della Cost.
     * Lo stesso oggetto avrà sempre lo stesso prezzo, non cambia al refresh.
     */
    public function calculateMarketPrice(Cost $cost): string
    {
        $costAmount = abs((float)$cost->getAmount());

        // Seed deterministico basato sull'ID univoco (UUID) della spesa.
        // Utilizziamo crc32 per ottenere un intero stabile dall'UUID.
        // In questo modo, lo stesso Item avrà sempre lo stesso "destino" di mercato.
        $uuid = $cost->getCode();

        // Fallback su ID se UUID non presente (anche se dovrebbe esserci)
        $seedString = $uuid ?? (string)$cost->getId();
        $seed = crc32($seedString);

        // 1. Estrarre il Markup Promesso (Base) dai dettagli
        $details = $cost->getDetailItems();
        $baseMarkup = 1.50; // Default di fallback standard

        // Logica di estrazione robusta per vari formati di dettagli
        if (!empty($details)) {
            // Cerchiamo 'markup_estimate' in un array o oggetto
            foreach ($details as $d) {
                if (is_array($d) && isset($d['markup_estimate'])) {
                    $baseMarkup = (float)$d['markup_estimate'];
                    break;
                } elseif (is_object($d) && property_exists($d, 'markupEstimate')) {
                    $baseMarkup = (float)$d->markupEstimate;
                    break;
                }
            }
        }

        // 2. Determinare se il mercato è volatile per questo specifico item
        // Modulo 100 ci dà un numero tra 0 e 99.
        $chance = abs($seed % 100);

        // 30% di probabilità (0-29) che il mercato sia "sfavorevole" (frizione)
        $isVolatile = $chance < 30;

        if ($isVolatile) {
            // Mercato Volatile (Frizione)
            // Invece di un range fisso (1.10-1.40) che ignora il contratto,
            // riduciamo il markup promesso di una percentuale (5% - 20%).
            // Questo mantiene i contratti "High Value" ancora profittevoli, ma meno del previsto.

            $varianceSeed = crc32($seedString . '_volatility_factor');
            // Genera numero tra 80 e 95
            $retentionPercent = 80 + abs($varianceSeed % 16);

            // Il nuovo moltiplicatore è una frazione del promesso
            // Es. Promesso 1.78, Retention 90% -> 1.60
            $multiplier = max(1.05, $baseMarkup * ($retentionPercent / 100.0));
        } else {
            // Mercato Standard (Target): Mantiene esattamente la promessa
            $multiplier = $baseMarkup;
        }

        return (string)floor($costAmount * $multiplier);
    }
}
