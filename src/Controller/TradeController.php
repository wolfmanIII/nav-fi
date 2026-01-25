<?php

namespace App\Controller;

use App\Entity\Cost;
use App\Entity\Income;
use App\Entity\IncomeTradeDetails;
use App\Form\IncomeType;
use App\Repository\CostRepository;
use App\Repository\IncomeCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\Trade\TradePricer;
use App\Service\Cost\CostDetailManager;

#[Route('/trade')]
#[IsGranted('ROLE_USER')]
class TradeController extends BaseController
{
    #[Route('/unsold', name: 'app_trade_unsold', methods: ['GET'])]
    public function unsold(CostRepository $costRepository, TradePricer $tradePricer): Response
    {
        $unsoldCosts = $costRepository->findUnsoldTradeGoods($this->getUser());

        $marketValues = [];
        foreach ($unsoldCosts as $cost) {
            $marketValues[$cost->getId()] = $tradePricer->calculateMarketPrice($cost);
        }

        return $this->renderTurbo('trade/unsold.html.twig', [
            'costs' => $unsoldCosts,
            'marketValues' => $marketValues,
        ]);
    }

    #[Route('/sell/{id}', name: 'app_trade_sell', methods: ['GET', 'POST'])]
    public function sell(
        Cost $cost,
        Request $request,
        EntityManagerInterface $em,
        IncomeCategoryRepository $categoryRepository,
        TradePricer $tradePricer,
        CostDetailManager $costDetailManager
    ): Response {
        // Verifica che sia un costo TRADE
        if ($cost->getCostCategory()->getCode() !== 'TRADE') {
            $this->addFlash('error', 'This item is not a trade good.');
            return $this->redirectToRoute('app_trade_unsold');
        }

        $income = new Income();
        $income->setUser($this->getUser());
        $campaign = $cost->getAsset()?->getCampaign();
        $income->setAsset($cost->getAsset());
        $income->setTitle('Sale: ' . $cost->getTitle());

        // Pre-fill con la data attuale della sessione della campagna
        if ($campaign) {
            $income->setSigningDay($campaign->getSessionDay());
            $income->setSigningYear($campaign->getSessionYear());
            $income->setPaymentDay($campaign->getSessionDay());
            $income->setPaymentYear($campaign->getSessionYear());
        }

        // Categoria predefinita: TRADE (Vendita)
        $category = $categoryRepository->findOneBy(['code' => 'TRADE']);
        if ($category) {
            $income->setIncomeCategory($category);
        }

        // Pre-fill Details
        $details = new IncomeTradeDetails();
        $details->setIncome($income);
        $details->setPurchaseCost($cost);

        // Utilizza il Manager per ottenere la quantità totale in sicurezza tramite DTO
        $totalQty = $costDetailManager->getTotalQuantity($cost);
        $details->setQty($totalQty > 0 ? $totalQty : 0);

        // Fallback descrizione (logica base, potrebbe essere migliorata)
        $detailObjects = $costDetailManager->getDetails($cost);
        $resName = count($detailObjects) > 0 ? $detailObjects[0]->description : '';
        $details->setGoodsDescription($resName);

        // Assumiamo che vendiamo TUTTO per ora
        // TODO: Gestire vendita parziale in futuro

        $income->setTradeDetails($details);

        // Calcolo del prezzo tramite Servizio dedicato (SOLID)
        $marketPrice = $tradePricer->calculateMarketPrice($cost);

        $income->setAmount($marketPrice);

        $form = $this->createForm(IncomeType::class, $income, ['user' => $this->getUser()]);
        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $em->persist($income);
            $em->persist($details);

            // Forza i dati tecnici dopo l'invio del form per evitare sovrascritture se il form li considera vuoti
            $details->setQty((int)$totalQty);
            $details->setGoodsDescription($resName);
            $details->setLocation($income->getSigningLocation() ?: $cost->getTargetDestination());

            if ($totalQty > 0) {
                // Prezzo unitario basato sull'offerta di mercato
                $unitPrice = (float)$income->getAmount() / $totalQty;
                $details->setUnitPrice((string)$unitPrice);
            }

            // Sincronizzazione date (incasso immediato e conforme alla sessione)
            if ($campaign) {
                $income->setSigningDay($campaign->getSessionDay());
                $income->setSigningYear($campaign->getSessionYear());
                $income->setPaymentDay($campaign->getSessionDay());
                $income->setPaymentYear($campaign->getSessionYear());
            }

            // Segna gli articoli come venduti tramite Manager (SOLID: Logica incapsulata)
            $costDetailManager->markAsSold($cost);

            $em->flush();

            return $this->redirectToRoute('app_income_edit', ['id' => $income->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderTurbo('trade/sell.html.twig', [
            'cost' => $cost,
            'form' => $form,
        ]);
    }
}
