<?php

namespace App\Service;

class UwpDecoderService
{
    /**
     * Decodifica una stringa UWP (es. "A788899-C") in un array associativo leggibile.
     * 
     * @param string $uwp La stringa UWP
     * @return array Dati decodificati
     */
    public function decode(string $uwp): array
    {
        // Rimuove spazi e trattini
        $cleanUwp = str_replace('-', '', trim($uwp));

        // Formato atteso: 7 o 8 caratteri (con Tech Level)
        // Esempio: A788899C (8 char)
        // Indici:
        // 0: Starport
        // 1: Size
        // 2: Atmosphere
        // 3: Hydrographics
        // 4: Population
        // 5: Government
        // 6: Law Level
        // 7: Tech Level

        if (strlen($cleanUwp) < 7) {
            return ['error' => 'Stringa UWP non valida (troppo corta)'];
        }

        $data = [
            'original' => $uwp,
            'starport' => $this->decodeStarport($cleanUwp[0]),
            'size' => $this->decodeSize($cleanUwp[1]),
            'atmosphere' => $this->decodeAtmosphere($cleanUwp[2]),
            'hydrographics' => $this->decodeHydrographics($cleanUwp[3]),
            'population' => $this->decodePopulation($cleanUwp[4]),
            'government' => $this->decodeGovernment($cleanUwp[5]),
            'law_level' => $this->decodeLawLevel($cleanUwp[6]),
        ];

        if (isset($cleanUwp[7])) {
            $data['tech_level'] = $this->decodeTechLevel($cleanUwp[7]);
        }

        return $data;
    }

    private function decodeStarport(string $code): array
    {
        $code = strtoupper($code);
        $map = [
            'A' => 'Eccellente (Cantieri Navali, Rifornimento Raffinato)',
            'B' => 'Buono (Costruzione Navi, Rifornimento Raffinato)',
            'C' => 'Routine (Manutenzione, Rifornimento Grezzo)',
            'D' => 'Povero (Riparazioni Limitate, Rifornimento Grezzo)',
            'E' => 'Frontiera (Nessuna struttura, Nessun Rifornimento)',
            'X' => 'Nessuno (Nessuna struttura)',
            'F' => 'Buono (Spazioporto Minore)', // T5 extension
            'G' => 'Povero (Spazioporto Minore)', // T5 extension
            'H' => 'Primitivo (Semplice atterraggio)', // T5 extension
            'Y' => 'Nessuno (Orbita pericolosa)' // T5 extension
        ];

        return [
            'code' => $code,
            'label' => $map[$code] ?? 'Sconosciuto'
        ];
    }

    private function decodeSize(string $char): array
    {
        $val = hexdec($char);
        $km = $val * 1600;
        
        $desc = match(true) {
            $val === 0 => 'Asteroide / < 800 km',
            $val <= 2 => 'Piccolo (Bassa gravità)',
            $val <= 4 => 'Marte (Gravità leggera)',
            $val <= 7 => 'Standard (Tipo Terra)',
            $val <= 9 => 'Grande (Alta gravità)',
            default => 'Gigante'
        };

        if ($val === 0) $kmText = '< 800';
        else $kmText = number_format($km, 0, ',', '.') . '';

        return [
            'code' => strtoupper($char),
            'value' => $val,
            'label' => $desc,
            'km' => $kmText
        ];
    }

    private function decodeAtmosphere(string $char): array
    {
        $val = hexdec($char);
        $map = [
            0 => 'Nessuna (Richiede tuta a vuoto)',
            1 => 'Tracce (Richiede tuta a vuoto)',
            2 => 'Molto Sottile, Contaminata (Richiede Respiratore/Filtro)',
            3 => 'Molto Sottile (Richiede Respiratore)',
            4 => 'Sottile, Contaminata (Richiede Filtro)',
            5 => 'Sottile (Respirabile)',
            6 => 'Standard (Respirabile)',
            7 => 'Standard, Contaminata (Richiede Filtro)',
            8 => 'Densa (Respirabile)',
            9 => 'Densa, Contaminata (Richiede Filtro)',
            10 => 'Esotica (Richiede scorte d\'aria)', // A
            11 => 'Corrosiva (Tuta protettiva necessaria)', // B
            12 => 'Insidiosa (Tuta protettiva necessaria)', // C
            13 => 'Densa, Alta (Richiede compressori)', // D
            14 => 'Ellissoidale (Esotica)', // E
            15 => 'Sottile (Panthalassic)' // F
        ];

        return [
            'code' => strtoupper($char),
            'label' => $map[$val] ?? 'Sconosciuta/Anomala'
        ];
    }

    private function decodeHydrographics(string $char): array
    {
        $val = hexdec($char);
        // % approx = val * 10
        $pct = $val * 10;
        if ($pct > 100) $pct = 100;

        $desc = match(true) {
            $val === 0 => 'Mondo Desertico',
            $val < 3 => 'Mondo Arido',
            $val < 6 => 'Mondo Umido',
            $val < 9 => 'Mondo Bagnato (Simile alla Terra)',
            default => 'Mondo Oceanico'
        };

        return [
            'code' => strtoupper($char),
            'value' => $pct,
            'label' => $desc
        ];
    }

    private function decodePopulation(string $char): array
    {
        $val = hexdec($char);
        
        // Popolazione è 10^val
        $desc = match(true) {
            $val === 0 => 'Nessuna (0)',
            $val < 4 => 'Bassa (< 10.000)',
            $val < 7 => 'Media (Milioni)',
            $val < 10 => 'Alta (Miliardi)',
            default => 'Altissima (Trilioni)'
        };

        return [
            'code' => strtoupper($char),
            'val_exponent' => $val,
            'label' => $desc
        ];
    }

    private function decodeGovernment(string $char): array
    {
        $val = hexdec($char);
        $map = [
            0 => 'Nessuno (Anarchia/Struttura familiare)',
            1 => 'Azienda/Corporazione',
            2 => 'Democrazia Partecipativa',
            3 => 'Oligarchia Autoperpetuante',
            4 => 'Democrazia Rappresentativa',
            5 => 'Tecnocrazia Feudale',
            6 => 'Dittatura Captive (Colonia/Militare)',
            7 => 'Balcanizzazione (Governi multipli)',
            8 => 'Burocrazia Civile',
            9 => 'Burocrazia Impersonale',
            10 => 'Dittatura Carismatica', // A
            11 => 'Dittatura Non Carismatica', // B
            12 => 'Oligarchia Carismatica', // C
            13 => 'Oligarchia Religiosa', // D
            14 => 'Tecnocrazia Religiosa', // E
            15 => 'Totalitarismo' // F
        ];

        return [
            'code' => strtoupper($char),
            'label' => $map[$val] ?? 'Sconosciuto'
        ];
    }

    private function decodeLawLevel(string $char): array
    {
        $val = hexdec($char);
        $desc = match(true) {
            $val === 0 => 'Nessuna legge (Anarchia)',
            $val <= 3 => 'Basso (Armi pesanti vietate)',
            $val <= 7 => 'Moderato (Porto d\'armi regolamentato)',
            $val <= 9 => 'Alto (Armi vietate, controlli rigidi)',
            default => 'Estremo (Stato di polizia / Totalitario)'
        };

        return [
            'code' => strtoupper($char),
            'value' => $val,
            'label' => $desc
        ];
    }

    private function decodeTechLevel(string $char): array
    {
        $val = hexdec($char);
        
        $desc = match(true) {
            $val === 0 => 'Età della Pietra (Primitivo)',
            $val <= 3 => 'Pre-Industriale',
            $val <= 5 => 'Industriale (Era Atomica)',
            $val <= 8 => 'Pre-Stellare (Era Spaziale)',
            $val <= 10 => 'Stellare Iniziale (Jump-1)',
            $val <= 12 => 'Stellare Medio (Impero Standard)',
            $val <= 14 => 'Stellare Alto',
            default => 'Ultra-Tech / Singolarità'
        };

        return [
            'code' => strtoupper($char),
            'value' => $val,
            'label' => $desc
        ];
    }
}
