<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;
use App\Repository\CompanyRepository;
use App\Service\Cube\NameGeneratorService;
use App\Service\GameRulesEngine;
use Random\Randomizer;

class TradeGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        private readonly CompanyRepository $companyRepo,
        private readonly NameGeneratorService $nameGenerator,
        private readonly GameRulesEngine $rules
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'TRADE';
    }

    public function getType(): string
    {
        return 'TRADE';
    }

    public function generate(array $context, int $maxDist, Randomizer $randomizer): CubeOpportunityData
    {
        // Risorse disponibili (TODO: Spostare in configurazione o DB in futuro)
        $resources = ['Radioactives', 'Metals', 'Crystals', 'Luxuries', 'Electronics', 'Pharmaceuticals', 'Industrial Machinery'];
        $resource = $resources[$randomizer->getInt(0, count($resources) - 1)];

        // Quantità (Tonnellate) con Distribuzione Pesata
        // Recupera le soglie di probabilità dalle Game Rules
        $rollSmall = $this->rules->get('trade.quantity_chance.small', 50);
        $rollMedium = $this->rules->get('trade.quantity_chance.medium', 85);

        $sizeRoll = $randomizer->getInt(1, 100);

        if ($sizeRoll <= $rollSmall) {
            // Piccolo: default 5-20 tons
            $min = $this->rules->get('trade.tonnage.small.min', 5);
            $max = $this->rules->get('trade.tonnage.small.max', 20);
            $tons = $randomizer->getInt($min, $max);
        } elseif ($sizeRoll <= $rollMedium) {
            // Medio: default 25-80 tons
            $min = $this->rules->get('trade.tonnage.medium.min', 25);
            $max = $this->rules->get('trade.tonnage.medium.max', 80);
            $tons = $randomizer->getInt($min, $max);
        } else {
            // Grande: default 100-500 tons
            $minBase = $this->rules->get('trade.tonnage.large.base_min', 10);
            $maxBase = $this->rules->get('trade.tonnage.large.base_max', 50);
            $multiplier = $this->rules->get('trade.tonnage.large.multiplier', 10);
            $tons = $randomizer->getInt($minBase, $maxBase) * $multiplier;
        }

        // Prezzi
        $minPrice = $this->rules->get('trade.price.min', 1000);
        $maxPrice = $this->rules->get('trade.price.max', 5000);
        $basePrice = $randomizer->getInt($minPrice, $maxPrice); // Per Tonnellata
        $totalCost = $basePrice * $tons;

        // Logica Profitto Potenziale (solo per flavor/UI)
        $minMarkup = $this->rules->get('trade.markup.min', 120);
        $maxMarkup = $this->rules->get('trade.markup.max', 180);
        $markup = $randomizer->getInt($minMarkup, $maxMarkup) / 100;

        // Selezione Fornitore (Compagnia)
        // Ottimizzazione: In prod sarebbe meglio filtrare o cachare.
        // Selezione Fornitore (Hybrid Logic)
        $user = $context['user'] ?? null;
        $supplier = null;
        $supplierName = 'Local Free Trader';

        // 30% chance to use existing company if User is provided
        if ($user && $randomizer->getInt(1, 100) <= 30) {
            $existing = $this->companyRepo->findRandomForUser($user, 1);
            if (!empty($existing)) {
                $supplier = $existing[0];
                $supplierName = $supplier->getName();
            }
        }

        // 70% (or fallback) generate new name
        if (!$supplier) {
            $supplierName = $this->nameGenerator->generateForCompany($randomizer);
        }

        $summary = "Bulk Sale: $tons tons of $resource";
        if ($supplierName) {
            $summary .= " from " . $supplierName;
        }

        return new CubeOpportunityData(
            signature: '', // Sarà impostato dall'engine
            type: 'TRADE',
            summary: $summary,
            distance: $context['distance'],
            amount: (float)$totalCost, // L'utente PAGA questo importo
            details: [
                'origin' => $context['origin'],
                'destination' => $context['destination'],
                'dest_hex' => $context['dest_hex'],
                'goods' => $resource, // Chiave attesa dal Converter
                'tons' => $tons,      // Chiave attesa dal Converter
                'unit_price' => $basePrice,
                'markup_estimate' => $markup,
                'company_id' => $supplier?->getId(),
                'patron' => $supplierName,
                'start_day' => $context['session_day'],
                'start_year' => $context['session_year']
            ]
        );
    }
}
