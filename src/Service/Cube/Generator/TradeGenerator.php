<?php

namespace App\Service\Cube\Generator;

use App\Dto\Cube\CubeOpportunityData;

class TradeGenerator implements OpportunityGeneratorInterface
{
    public function __construct(
        private readonly \App\Repository\CompanyRepository $companyRepo
    ) {}

    public function supports(string $type): bool
    {
        return $type === 'TRADE';
    }

    public function getType(): string
    {
        return 'TRADE';
    }

    public function generate(array $context, int $maxDist, \Random\Randomizer $randomizer): CubeOpportunityData
    {
        $resources = ['Radioactives', 'Metals', 'Crystals', 'Luxuries', 'Electronics', 'Pharmaceuticals', 'Industrial Machinery'];
        $resource = $resources[$randomizer->getInt(0, count($resources) - 1)];

        // Quantity (Tons) with Weighted Distribution
        $sizeRoll = $randomizer->getInt(1, 100);
        if ($sizeRoll <= 50) {
            // Small: 5-20 tons
            $tons = $randomizer->getInt(5, 20);
        } elseif ($sizeRoll <= 85) {
            // Medium: 25-80 tons
            $tons = $randomizer->getInt(25, 80);
        } else {
            // Large: 100-500 tons
            $tons = $randomizer->getInt(10, 50) * 10;
        }

        // Pricing
        $basePrice = $randomizer->getInt(1000, 5000); // Per Ton
        $totalCost = $basePrice * $tons;

        // Potential Profit logic (just for flavor/UI)
        $markup = $randomizer->getInt(120, 180) / 100;

        // Select Supplier (Company)
        // Optimization: In a real app, filter by sector or context. Here random is fine for MVP.
        // We'll fetch a random active company.
        $companies = $this->companyRepo->findAll(); // Caching/limiting advisable in prod
        $supplier = null;
        if (!empty($companies)) {
            $supplier = $companies[$randomizer->getInt(0, count($companies) - 1)];
        }

        $summary = "Bulk Sale: $tons tons of $resource";
        if ($supplier) {
            $summary .= " from " . $supplier->getName();
        }

        return new CubeOpportunityData(
            signature: '', // Will be set by engine
            type: 'TRADE',
            summary: $summary,
            distance: $context['distance'],
            amount: (float)$totalCost, // User PAYS this amount
            details: [
                'origin' => $context['origin'],
                'destination' => $context['destination'],
                'dest_hex' => $context['dest_hex'],
                'goods' => $resource, // Key expected by Converter
                'tons' => $tons,      // Key expected by Converter
                'unit_price' => $basePrice,
                'markup_estimate' => $markup,
                'company_id' => $supplier?->getId(),
                'patron' => $supplier?->getName() ?? 'Local Free Trader',
                'start_day' => $context['session_day'],
                'start_year' => $context['session_year']
            ]
        );
    }
}
