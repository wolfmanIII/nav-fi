<?php

namespace App\Service\Cube;

use Random\Randomizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Servizio per la generazione dinamica di nomi basati su patterns e sillabe (LEGO-style).
 * Supporta le culture di Traveller (Solomani, Vilani, Aslan, Vargr).
 */
class NameGeneratorService
{
    public function __construct(
        #[Autowire('%app.names.lego%')]
        private readonly array $legoConfig,
        #[Autowire('%app.names.imperial_titles%')]
        private readonly array $titlesConfig,
        #[Autowire('%app.names.corporate%')]
        private readonly array $corporateConfig,
    ) {}

    /**
     * Genera un nome per un Patrono basato sul tipo e opzionalmente sulla cultura.
     */
    public function generateForPatron(string $type, ?string $culture = null, ?Randomizer $randomizer = null): string
    {
        if (!$randomizer) {
            $randomizer = new Randomizer();
        }

        // Se la cultura non è specificata, ne sceglie una a caso con pesi (prevalenza umana)
        if (!$culture) {
            $roll = $randomizer->getInt(1, 100);
            if ($roll <= 60) $culture = 'solomani';
            elseif ($roll <= 85) $culture = 'vilani';
            elseif ($roll <= 93) $culture = 'vargr';
            else $culture = 'aslan';
        }

        $baseName = $this->assembleLego($culture, $randomizer);

        return match (strtoupper($type)) {
            'NOBLE' => $this->applyNobleModifier($baseName, $randomizer),
            'CORPORATE' => $this->generateForCompany($randomizer),
            'UNDERWORLD' => $this->applyUnderworldModifier($baseName, $randomizer),
            'OFFICIAL' => $this->applyOfficialModifier($baseName, $randomizer),
            default => $baseName
        };
    }

    /**
     * Genera un nome per una compagnia (Type: CORPORATE).
     */
    public function generateForCompany(?Randomizer $randomizer = null): string
    {
        if (!$randomizer) {
            $randomizer = new Randomizer();
        }

        $prefix = $this->assembleLego('solomani', $randomizer);
        $sector = $this->corporateConfig['sectors'][$randomizer->getInt(0, count($this->corporateConfig['sectors']) - 1)];
        $suffix = $this->corporateConfig['suffixes'][$randomizer->getInt(0, count($this->corporateConfig['suffixes']) - 1)];

        return sprintf("%s %s %s", $prefix, $sector, $suffix);
    }

    /**
     * Assembla fisicamente le sillabe in base alla cultura.
     */
    private function assembleLego(string $culture, Randomizer $randomizer): string
    {
        $config = $this->legoConfig[$culture] ?? $this->legoConfig['solomani'];

        $prefix = $config['prefix'][$randomizer->getInt(0, count($config['prefix']) - 1)];
        $suffix = $config['suffix'][$randomizer->getInt(0, count($config['suffix']) - 1)];

        // 50% di probabilità di inserire una sillaba centrale (middle_parts)
        $name = $prefix;
        if (!empty($config['middle']) && $randomizer->getInt(0, 1) === 1) {
            $name .= $config['middle'][$randomizer->getInt(0, count($config['middle']) - 1)];
        }
        $name .= $suffix;

        // Per Solomani e Vilani, 50% di probabilità di aggiungere un cognome (metodo "LEGO" bis)
        if (in_array($culture, ['solomani', 'vilani']) && $randomizer->getInt(0, 1) === 1) {
            $lastNamePrefix = $config['prefix'][$randomizer->getInt(0, count($config['prefix']) - 1)];
            $lastNameSuffix = $config['suffix'][$randomizer->getInt(0, count($config['suffix']) - 1)];
            $name .= " " . $lastNamePrefix . $lastNameSuffix;
        }

        return $name;
    }

    /**
     * Applica titoli nobiliari imperiali.
     */
    private function applyNobleModifier(string $name, Randomizer $randomizer): string
    {
        $title = $this->titlesConfig[$randomizer->getInt(0, count($this->titlesConfig) - 1)];
        // Nota: non abbiamo il sesso del patrono, usiamo il maschile come default (address)
        return $title['address'] . " " . $name;
    }

    /**
     * Aggiunge soprannomi tipici del sottobosco criminale.
     */
    private function applyUnderworldModifier(string $name, Randomizer $randomizer): string
    {
        $nicknames = ["The Ghost", "Rat-Eyes", "Smiling Jack", "Snake", "Fixer", "Spider", "Void", "Cross"];
        $nick = $nicknames[$randomizer->getInt(0, count($nicknames) - 1)];

        return $randomizer->getInt(0, 1) === 1 ? "$name '$nick'" : $nick;
    }

    /**
     * Aggiunge suffissi istituzionali per funzionari governativi.
     */
    private function applyOfficialModifier(string $name, Randomizer $randomizer): string
    {
        $agencies = ["Bureau", "Agency", "Authority", "Control", "Administration"];
        $agency = $agencies[$randomizer->getInt(0, count($agencies) - 1)];

        return "$name ($agency)";
    }
}
