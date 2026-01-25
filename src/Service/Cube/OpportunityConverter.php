<?php

namespace App\Service\Cube;

use App\Dto\Cube\CubeOpportunityData;
use App\Entity\Asset;
use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\IncomeContractDetails;
use App\Entity\IncomeFreightDetails;
use App\Entity\IncomeMailDetails;
use App\Entity\IncomePassengersDetails;
use App\Repository\CompanyRepository;
use App\Repository\CostCategoryRepository;
use App\Repository\IncomeCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class OpportunityConverter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IncomeCategoryRepository $incomeCategoryRepository,
        private readonly CostCategoryRepository $costCategoryRepository,
        private readonly CompanyRepository $companyRepository
    ) {}

    /**
     * Converte un'opportunità in un'entità finanziaria (Income o Cost) collegata a un Asset.
     */
    public function convert(CubeOpportunityData $opportunity, Asset $asset, array $overrides = []): Income|Cost
    {
        return match ($opportunity->type) {
            'TRADE' => $this->createTradePurchase($opportunity, $asset),
            'FREIGHT' => $this->createFreightIncome($opportunity, $asset, $overrides),
            'PASSENGERS' => $this->createPassengersIncome($opportunity, $asset, $overrides),
            'MAIL' => $this->createMailIncome($opportunity, $asset, $overrides),
            'CONTRACT' => $this->createContractIncome($opportunity, $asset, $overrides),
            default => throw new \InvalidArgumentException("Tipo opportunità non supportato: {$opportunity->type}")
        };
    }

    private function createTradePurchase(CubeOpportunityData $opp, Asset $asset): Cost
    {
        $category = $this->getCostCategory('TRADE');
        $cost = new Cost();
        $cost->setAsset($asset);
        $cost->setUser($asset->getUser());
        $cost->setCostCategory($category);
        $cost->setTitle("Trade Purchase: {$opp->details['goods']}");
        $cost->setAmount((string)$opp->amount); // This is the BUY price
        // Imposta pagamento immediato per il workflow "Acquisto"
        $cost->setPaymentDay($opp->details['start_day'] ?? 1);
        $cost->setPaymentYear($opp->details['start_year'] ?? 1105);
        $cost->setTargetDestination($opp->details['destination'] ?? null);

        if (!empty($opp->details['company_id'])) {
            $company = $this->companyRepository->find($opp->details['company_id']);
            if ($company) {
                $cost->setCompany($company);
            }
        } elseif (!empty($opp->details['patron'])) {
            // Helper method or direct setter if Cost has it?
            // Cost entity does NOT have patronAlias field by default? Let's check Cost.php.
            // If Cost doesn't have patronAlias, we might need to add it or put it in notes.
            // For now, let's assume we use Note or add the field. 
            // WAIT: Cost usually has a supplier (Company). It doesn't typically have "PatronAlias".
            // But user wants to know "who".
            // Let's put it in the note if the field doesn't exist, OR check Cost entity.

            // Checking Cost.php in previous turns:
            // private ?Company $company = null;
            // It does NOT have patronAlias.
            // I should add it to Cost entity or append to Note.
            // User requested "same technique as contract", implying we should add it.
            // But modifying entity schema requires migration again.
            // Let's check if I can just append it to the title or note for now to save time, 
            // or if I should do the migration. 
            // User said: "possibile che non si sa che sta pagando?"
            // Adding it to Cost entity seems cleanest.
            // But let's first force it into the Note to verify visibility.
            $cost->setNote("Supplier/Patron: " . $opp->details['patron'] . "\nLocation: " . ($opp->details['origin'] ?? 'Unknown'));
        } else {
            $cost->setNote("Location: " . ($opp->details['origin'] ?? 'Unknown'));
        }

        $cost->setDetailItems([[
            'description' => $opp->details['goods'],
            'quantity' => (float)$opp->details['tons'],
            'cost' => $opp->amount / max(1, $opp->details['tons']), // Unit Price
            'markup_estimate' => $opp->details['markup_estimate'] ?? 1.50, // Critical for TradePricer
            'target_market' => $opp->details['destination'] ?? null,
            'origin_hex' => $opp->details['origin_hex'] ?? 'Unknown'
        ]]);

        $this->entityManager->persist($cost);
        return $cost;
    }

    private function getCostCategory(string $code): CostCategory
    {
        $category = $this->costCategoryRepository->findOneBy(['code' => $code]);
        if (!$category) {
            // Se non esiste, blocchiamo tutto perché i dati di riferimento sono essenziali
            throw new \RuntimeException("Cost Category '$code' not found in database.");
        }
        return $category;
    }

    private function createContractIncome(CubeOpportunityData $opp, Asset $asset, array $overrides = []): Income
    {
        $category = $this->getIncomeCategory('CONTRACT');
        $income = $this->createBaseIncome($opp, $asset, $category, $overrides);

        $details = new IncomeContractDetails();
        $details->setIncome($income);
        $income->setContractDetails($details); // Ensure bidirectional link

        $details->setJobType($opp->details['mission_type'] ?? 'Mission');
        $details->setObjective($opp->summary);
        $details->setLocation($opp->details['origin'] ?? 'Unknown');

        // Date manuali o sessione
        $details->setStartDay($income->getSigningDay());
        $details->setStartYear($income->getSigningYear());

        if (!empty($overrides['deadline_day'])) {
            $details->setDeadlineDay((int)$overrides['deadline_day']);
            $details->setDeadlineYear((int)($overrides['deadline_year'] ?? $income->getSigningYear()));
        }

        $this->entityManager->persist($details);
        return $income;
    }

    private function createBaseIncome(CubeOpportunityData $opp, Asset $asset, IncomeCategory $category, array $overrides = []): Income
    {
        $income = new Income();
        $income->setAsset($asset);
        $income->setUser($asset->getUser());
        $income->setIncomeCategory($category);
        $income->setTitle($opp->summary);
        $income->setAmount((string)$opp->amount);
        $income->setStatus(Income::STATUS_SIGNED); // Firmato ma non pagato

        // Location & Date from Context or Overrides
        $income->setSigningLocation($opp->details['origin'] ?? 'Unknown');
        $income->setSigningDay((int)($overrides['day'] ?? ($opp->details['start_day'] ?? 1)));
        $income->setSigningYear((int)($overrides['year'] ?? ($opp->details['start_year'] ?? 1105)));

        // Mappatura Patron/Company
        if (!empty($opp->details['company_id'])) {
            $company = $this->companyRepository->find($opp->details['company_id']);
            if ($company) {
                $income->setCompany($company);
            }
        } elseif (!empty($opp->details['patron'])) {
            $income->setPatronAlias($opp->details['patron']);
        }

        $this->entityManager->persist($income);
        return $income;
    }

    private function getIncomeCategory(string $code): IncomeCategory
    {
        $category = $this->incomeCategoryRepository->findOneBy(['code' => $code]);
        if (!$category) {
            // Fallback o creazione al volo (per ora assumiamo esistano)
            throw new \RuntimeException("Income Category '$code' not found.");
        }
        return $category;
    }

    private function createFreightIncome(CubeOpportunityData $opp, Asset $asset, array $overrides = []): Income
    {
        $category = $this->getIncomeCategory('FREIGHT');
        $income = $this->createBaseIncome($opp, $asset, $category, $overrides);

        $details = new IncomeFreightDetails();
        $details->setIncome($income);
        $income->setFreightDetails($details);
        $details->setCargoQty("{$opp->details['tons']} tons");
        $details->setDestination($opp->details['destination'] ?? 'Unknown');
        $details->setOrigin($opp->details['origin'] ?? 'Unknown');
        $details->setCargoDescription($opp->details['cargo_type'] ?? 'General Goods');

        // Date manuali o sessione
        $details->setPickupDay($income->getSigningDay());
        $details->setPickupYear($income->getSigningYear());

        $this->entityManager->persist($details);
        return $income;
    }

    private function createPassengersIncome(CubeOpportunityData $opp, Asset $asset, array $overrides = []): Income
    {
        $category = $this->getIncomeCategory('PASSENGERS');
        $income = $this->createBaseIncome($opp, $asset, $category, $overrides);

        $details = new IncomePassengersDetails();
        $details->setIncome($income);
        $income->setPassengersDetails($details);
        $details->setQty($opp->details['pax'] ?? 0);
        $details->setClassOrBerth($opp->details['class'] ?? 'Standard');
        $details->setDestination($opp->details['destination'] ?? 'Unknown');
        $details->setOrigin($opp->details['origin'] ?? 'Unknown');

        // Date manuali o sessione
        $details->setDepartureDay($income->getSigningDay());
        $details->setDepartureYear($income->getSigningYear());

        $this->entityManager->persist($details);
        return $income;
    }

    private function createMailIncome(CubeOpportunityData $opp, Asset $asset, array $overrides = []): Income
    {
        $category = $this->getIncomeCategory('MAIL');
        $income = $this->createBaseIncome($opp, $asset, $category, $overrides);

        $details = new IncomeMailDetails();
        $details->setIncome($income);
        $income->setMailDetails($details);
        $details->setPackageCount($opp->details['containers'] ?? 0);
        $details->setTotalMass((string)($opp->details['tons'] ?? 0));
        $details->setDestination($opp->details['destination'] ?? 'Unknown');
        $details->setOrigin($opp->details['origin'] ?? 'Unknown');
        $details->setMailType('Official Priority');

        // Date manuali o sessione
        $details->setDispatchDay($income->getSigningDay());
        $details->setDispatchYear($income->getSigningYear());

        $this->entityManager->persist($details);
        return $income;
    }
}
