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
    public function convert(CubeOpportunityData $opportunity, Asset $asset): Income|Cost
    {
        return match ($opportunity->type) {
            'TRADE' => $this->createTradePurchase($opportunity, $asset),
            'FREIGHT' => $this->createFreightIncome($opportunity, $asset),
            'PASSENGER' => $this->createPassengersIncome($opportunity, $asset),
            'MAIL' => $this->createMailIncome($opportunity, $asset),
            'CONTRACT' => $this->createContractIncome($opportunity, $asset),
            default => throw new \InvalidArgumentException("Tipo opportunità non supportato: {$opportunity->type}")
        };
    }

    private function createTradePurchase(CubeOpportunityData $opp, Asset $asset): Cost
    {
        $category = $this->getCostCategory('TRADE');
        $cost = new Cost();
        $cost->setAsset($asset);
        $cost->setCostCategory($category);
        $cost->setTitle("Trade Purchase: {$opp->details['goods']}");
        $cost->setAmount((string)$opp->amount); // This is the BUY price
        // Imposta pagamento immediato per il workflow "Acquisto"
        $cost->setPaymentDay($opp->details['start_day'] ?? 1);
        $cost->setPaymentYear($opp->details['start_year'] ?? 1105);

        // Mappatura Patron/Company (Venditore)
        if (!empty($opp->details['company_id'])) {
            $company = $this->companyRepository->find($opp->details['company_id']);
            if ($company) {
                $cost->setCompany($company);
            }
        }

        $cost->setDetailItems([
            'resource' => $opp->details['goods'],
            'quantity' => $opp->details['tons'],
            'target_market' => $opp->details['destination'],
            'unit_price' => $opp->amount / max(1, $opp->details['tons']), // Stima
            'origin_hex' => $opp->details['origin_hex'] ?? 'Unknown'
        ]);

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

    private function createContractIncome(CubeOpportunityData $opp, Asset $asset): Income
    {
        $category = $this->getIncomeCategory('CONTRACT');
        $income = $this->createBaseIncome($opp, $asset, $category);

        $details = new IncomeContractDetails();
        $details->setIncome($income);
        $income->setContractDetails($details); // Ensure bidirectional link

        $details->setJobType($opp->details['mission_type'] ?? 'Mission');
        $details->setObjective($opp->summary);
        $details->setLocation($opp->details['origin'] ?? 'Unknown');
        // Mappa altri campi specifici...

        $this->entityManager->persist($details);
        return $income;
    }

    private function createBaseIncome(CubeOpportunityData $opp, Asset $asset, IncomeCategory $category): Income
    {
        $income = new Income();
        $income->setAsset($asset);
        $income->setIncomeCategory($category);
        $income->setTitle($opp->summary);
        $income->setAmount((string)$opp->amount);
        $income->setStatus(Income::STATUS_SIGNED); // Firmato ma non pagato

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

    private function createFreightIncome(CubeOpportunityData $opp, Asset $asset): Income
    {
        $category = $this->getIncomeCategory('FREIGHT');
        $income = $this->createBaseIncome($opp, $asset, $category);

        $details = new IncomeFreightDetails();
        $details->setIncome($income);
        $details->setCargoQty("{$opp->details['tons']} tons");
        $details->setDestination($opp->details['destination'] ?? 'Unknown');
        // $details->setCargoDescription(...)

        $this->entityManager->persist($details);
        return $income;
    }

    private function createPassengersIncome(CubeOpportunityData $opp, Asset $asset): Income
    {
        $category = $this->getIncomeCategory('PASSENGER');
        $income = $this->createBaseIncome($opp, $asset, $category);

        $details = new IncomePassengersDetails();
        $details->setIncome($income);
        $details->setQty($opp->details['pax'] ?? 0);
        $details->setClassOrBerth($opp->details['class'] ?? 'Standard');
        $details->setDestination($opp->details['destination'] ?? 'Unknown');

        $this->entityManager->persist($details);
        return $income;
    }

    private function createMailIncome(CubeOpportunityData $opp, Asset $asset): Income
    {
        $category = $this->getIncomeCategory('MAIL');
        $income = $this->createBaseIncome($opp, $asset, $category);

        $details = new IncomeMailDetails();
        $details->setIncome($income);
        $details->setPackageCount($opp->details['containers'] ?? 0);
        $details->setTotalMass($opp->details['tons'] ?? 0);
        $details->setDestination($opp->details['destination'] ?? 'Unknown');

        $this->entityManager->persist($details);
        return $income;
    }
}
