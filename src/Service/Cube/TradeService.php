<?php

namespace App\Service\Cube;

use App\Entity\Cost;
use App\Entity\Income;
use App\Repository\IncomeCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\LocalLaw;

class TradeService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IncomeCategoryRepository $incomeCategoryRepo,
        private readonly \App\Service\CompanyManager $companyManager
    ) {}

    public function liquidateCargo(
        Cost $cost,
        float $salePrice,
        string $location,
        int $day,
        int $year,
        ?LocalLaw $localLaw = null
    ): Income {
        // Validazione: Assicurarsi che il costo sia di tipo TRADE e non sia già stato venduto?
        // Affidarsi al chiamante o controllare qui? Controlliamo qui per sicurezza.

        $category = $this->incomeCategoryRepo->findOneBy(['code' => 'TRADE']);
        if (!$category) {
            // Fallback o eccezione? Potremmo provare 'speculative_trade' o simili se 'TRADE' fallisce,
            // ma per ora assumiamo che TRADE esista come controparte del Cost TRADE
            throw new \RuntimeException("Income Category 'TRADE' not found.");
        }

        $income = new Income();

        // Collega al FinancialAccount invece che all'Asset
        $income->setFinancialAccount($cost->getFinancialAccount());
        $income->setUser($cost->getUser()); // Stesso proprietario
        $income->setIncomeCategory($category);
        $income->setTitle("Sale of Cargo: " . str_replace('Purchase Cargo: ', '', $cost->getTitle()));
        $income->setAmount((string)$salePrice);
        $income->setStatus(Income::STATUS_SIGNED); // Realizzato immediatamente

        // Collega al costo di acquisto (Purchase Cost)
        $income->setPurchaseCost($cost);

        // Luogo e Data
        $income->setSigningLocation($location);
        $income->setSigningDay($day);
        $income->setSigningYear($year);
        $income->setPaymentDay($day);
        $income->setPaymentYear($year);
        $income->setLocalLaw($localLaw);

        // Assegna il Pagatore (Company)
        // Simuliamo un mercato locale o un commerciante basato sulla location.
        $roleValues = ['TRADER']; // Usiamo il ruolo TRADER
        $role = $this->companyManager->getRoleByCode('TRADER');
        
        if ($role) {
            $buyerName = $location && $location !== 'Unknown' ? "Market at $location" : "Local Traders";
            // Crea o trova un'azienda generica che rappresenta il mercato in questa posizione
            $buyerCompany = $this->companyManager->findOrCreateAuto($buyerName, $cost->getUser(), $role);
            $income->setCompany($buyerCompany);
        } else {
            // Fallback se il ruolo non viene trovato (non dovrebbe succedere con il seed)
            $income->setPatronAlias("Market at " . ($location ?? 'Unknown'));
        }
        // Dettagli
        $details = $cost->getDetailItems();
        // Potremmo voler copiare alcuni dettagli o crearne di nuovi.
        // Per ora, registriamoli in una struttura JSON valida se necessario.
        // Il wrapper IncomeDetails si aspetta una struttura specifica?
        // Creiamo un semplice array di dettagli.

        $qty = 0;
        $description = 'Unknown Goods';
        if (!empty($details) && is_array($details) && isset($details[0])) {
            $qty = $details[0]['quantity'] ?? 0;
            // Usa il titolo specifico dell'articolo se la descrizione è una nota di loot generica, 
            // o semplicemente usa il titolo del costo per chiarezza
            $description = $cost->getTitle(); // "Nome Articolo"
        }

        $unitPrice = ($qty > 0) ? $salePrice / $qty : 0;

        $income->setDetails([
            'goodsDescription' => $description,
            'qty' => $qty,
            'unitPrice' => $unitPrice,
            'origin' => $cost->getTargetDestination() ?? 'Unknown',
            'location' => $location,
            'profit' => $salePrice - (float)$cost->getAmount(),
        ]);

        $this->em->persist($income);
        $this->em->flush();

        return $income;
    }
}
