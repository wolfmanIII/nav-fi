<?php

namespace App\Service\Cube;

use App\Entity\Cost;
use App\Entity\Income;
use App\Repository\IncomeCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class TradeService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IncomeCategoryRepository $incomeCategoryRepo
    ) {}

    public function liquidateCargo(Cost $cost, float $salePrice, string $location, int $day, int $year): Income
    {
        // Validation: Ensure cost is TRADE type and not already sold?
        // Rely on caller or check here? Check here for safety.

        $category = $this->incomeCategoryRepo->findOneBy(['code' => 'TRADE']);
        if (!$category) {
            // Fallback or throw? Let's try 'speculative_trade' or similar if 'TRADE' fails, 
            // but for now assume TRADE exists as counterpart to Cost TRADE
            throw new \RuntimeException("Income Category 'TRADE' not found.");
        }

        $income = new Income();
        $income->setAsset($cost->getAsset());
        $income->setUser($cost->getUser()); // Same owner
        $income->setIncomeCategory($category);
        $income->setTitle("Sale of Cargo: " . str_replace('Purchase Cargo: ', '', $cost->getTitle()));
        $income->setAmount((string)$salePrice);
        $income->setStatus(Income::STATUS_SIGNED); // Realized immediately

        // Link to Purchase Cost
        $income->setPurchaseCost($cost);

        // Location & Date
        $income->setSigningLocation($location);
        $income->setSigningDay($day);
        $income->setSigningYear($year);

        // Details
        $details = $cost->getDetailItems();
        // We might want to copy some details or create new ones.
        // For now, let's just note it in valid JSON structure if needed.
        // IncomeDetails wrapper expects specific structure?
        // Let's create a simple details array.

        $qty = 0;
        $goods = 'Unknown Goods';
        if (!empty($details) && is_array($details) && isset($details[0])) {
            $qty = $details[0]['quantity'] ?? 0;
            $goods = $details[0]['description'] ?? 'Goods';
        }

        $income->setDetails([
            'goods' => $goods,
            'tons' => $qty,
            'origin' => $cost->getTargetDestination() ?? 'Unknown', // Where it came from? Or original origin?
            'saleLocation' => $location,
            'profit' => $salePrice - (float)$cost->getAmount(),
        ]);

        $this->em->persist($income);
        $this->em->flush();

        return $income;
    }
}
