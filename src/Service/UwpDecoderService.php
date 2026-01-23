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
            'A' => 'Excellent (Starport, Refined Fuel)',
            'B' => 'Good (Starport, Refined Fuel)',
            'C' => 'Routine (Starport, Unrefined Fuel)',
            'D' => 'Poor (Starport, Unrefined Fuel)',
            'E' => 'Frontier (No Starport, No Fuel)',
            'X' => 'None (No Starport)',
            'F' => 'Good (Minor Spaceport)', // T5 extension
            'G' => 'Poor (Minor Spaceport)', // T5 extension
            'H' => 'Primitive (Simple landing site)', // T5 extension
            'Y' => 'None (Hazardous orbit)' // T5 extension
        ];

        return [
            'code' => $code,
            'label' => $map[$code] ?? 'Unknown'
        ];
    }

    private function decodeSize(string $char): array
    {
        $val = hexdec($char);
        $km = $val * 1600;
        
        $desc = match(true) {
            $val === 0 => 'Asteroid / < 800 km',
            $val <= 2 => 'Small (Low Gravity)',
            $val <= 4 => 'Mars-sized (Light Gravity)',
            $val <= 7 => 'Standard (Earth-sized)',
            $val <= 9 => 'Large (High Gravity)',
            default => 'Giant'
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
            0 => 'None (Vacuum suit required)',
            1 => 'Trace (Vacuum suit required)',
            2 => 'Very Thin, Tainted (Respirator/Filter required)',
            3 => 'Very Thin (Respirator required)',
            4 => 'Thin, Tainted (Filter required)',
            5 => 'Thin (Breathable)',
            6 => 'Standard (Breathable)',
            7 => 'Standard, Tainted (Filter required)',
            8 => 'Dense (Breathable)',
            9 => 'Dense, Tainted (Filter required)',
            10 => 'Exotic (Air supply required)', // A
            11 => 'Corrosive (Protective suit required)', // B
            12 => 'Insidious (Protective suit required)', // C
            13 => 'Dense, High (Compressors required)', // D
            14 => 'Ellipsoid (Exotic)', // E
            15 => 'Thin (Panthalassic)' // F
        ];

        return [
            'code' => strtoupper($char),
            'label' => $map[$val] ?? 'Unknown/Anomalous'
        ];
    }

    private function decodeHydrographics(string $char): array
    {
        $val = hexdec($char);
        // % approx = val * 10
        $pct = $val * 10;
        if ($pct > 100) $pct = 100;

        $desc = match(true) {
            $val === 0 => 'Desert World',
            $val < 3 => 'Dry World',
            $val < 6 => 'Non-water World', // Or "Wet World" but standard traveller usually distinguishes around 60%
            $val < 9 => 'Wet World (Earth-like)',
            default => 'Water World'
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
            $val === 0 => 'None (0)',
            $val < 4 => 'Low (< 10,000)',
            $val < 7 => 'Mid (Millions)',
            $val < 10 => 'High (Billions)',
            default => 'Very High (Trillions)'
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
            0 => 'None (Anarchy/Family Structure)',
            1 => 'Company/Corporation',
            2 => 'Participating Democracy',
            3 => 'Self-Perpetuating Oligarchy',
            4 => 'Representative Democracy',
            5 => 'Feudal Technocracy',
            6 => 'Captive Government (Colony/Military)',
            7 => 'Balkanization (Multiple Governments)',
            8 => 'Civil Service Bureaucracy',
            9 => 'Impersonal Bureaucracy',
            10 => 'Charismatic Dictatorship', // A
            11 => 'Non-Charismatic Dictatorship', // B
            12 => 'Charismatic Oligarchy', // C
            13 => 'Religious Dictatorship', // D
            14 => 'Religious Technocracy', // E
            15 => 'Totalitarian Oligarchy' // F
        ];

        return [
            'code' => strtoupper($char),
            'label' => $map[$val] ?? 'Unknown'
        ];
    }

    private function decodeLawLevel(string $char): array
    {
        $val = hexdec($char);
        $desc = match(true) {
            $val === 0 => 'No Law (Anarchy)',
            $val <= 3 => 'Low (Heavy weapons banned)',
            $val <= 7 => 'Moderate (Gun carrying regulated)',
            $val <= 9 => 'High (Weapons banned, rigid control)',
            default => 'Extreme (Police State / Totalitarian)'
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
            $val === 0 => 'Stone Age (Primitive)',
            $val <= 3 => 'Pre-Industrial',
            $val <= 5 => 'Industrial (Atomic Age)',
            $val <= 8 => 'Pre-Stellar (Space Age)',
            $val <= 10 => 'Early Stellar (Jump-1)',
            $val <= 12 => 'Average Stellar (Standard Empire)',
            $val <= 14 => 'High Stellar',
            default => 'Ultra-Tech / Singularity'
        };

        return [
            'code' => strtoupper($char),
            'value' => $val,
            'label' => $desc
        ];
    }
}
