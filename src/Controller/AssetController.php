<?php

namespace App\Controller;

use App\Dto\AssetDetailsData;
use App\Dto\CrewSelection;
use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\CostCategory;
use App\Entity\Crew;
use App\Entity\LocalLaw;
use App\Form\AssetRoleAssignmentType;
use App\Form\AssetType;
use App\Form\CargoLootType;
use App\Form\CrewSelectType;
use App\Repository\CostRepository;
use App\Security\Voter\AssetVoter;
use App\Service\Cube\TradeService;
use App\Service\FinancialAccountManager;
use App\Service\ListViewHelper;
use App\Service\Pdf\PdfGeneratorInterface;
use App\Service\Trade\TradePricer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Cost;
use App\Service\CrewAssignmentService;

final class AssetController extends BaseController
{
    const CONTROLLER_NAME = "AssetController";

    #[Route('/asset/index', name: 'app_asset_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'name',
            'type_class',
            'category',
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $assets = [];
        $total = 0;
        $campaigns = [];

        if ($user instanceof User) {
            $result = $em->getRepository(Asset::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $assets = $result['items'];
            $total = $result['total'];

            $totalPages = max(1, (int) ceil($total / $perPage));
            $clampedPage = $listViewHelper->clampPage($page, $totalPages);
            if ($clampedPage !== $page) {
                $page = $clampedPage;
                $result = $em->getRepository(Asset::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $assets = $result['items'];
            }

            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('asset/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'assets' => $assets,
            'filters' => $filters,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/asset/new', name: 'app_asset_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        FinancialAccountManager $financialAccountManager
    ): Response {
        $asset = new Asset();
        $user = $this->getUser();
        if ($user instanceof User) {
            $asset->setUser($user);
        }

        $category = $request->query->get('category');
        if ($category && in_array($category, [Asset::CATEGORY_SHIP, Asset::CATEGORY_BASE, Asset::CATEGORY_TEAM])) {
            $asset->setCategory($category);
        }

        $form = $this->createForm(AssetType::class, $asset);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $details = $this->extractAssetDetails($form, $asset);

            if (is_object($details) && method_exists($details, 'toArray')) {
                $asset->setAssetDetails($details->toArray());
            }

            // Gestione FinancialAccount: esistente o nuovo
            $existingAccount = $form->get('financialAccount')->getData();
            $bank = $form->get('bank')->getData();
            $bankName = $form->get('bankName')->getData();

            if ($existingAccount) {
                // Usa l'account esistente selezionato
                $account = $existingAccount;
                $account->setAsset($asset);
            } else {
                // Crea nuovo account solo se c'e' almeno bank o bankName
                if ($bank || $bankName) {
                    $account = $financialAccountManager->createForAsset(
                        $asset,
                        $user,
                        '0',
                        $bank,
                        $bankName
                    );
                } else {
                    $account = null;
                }
            }

            $em->persist($asset);
            $em->flush();

            if ($account && $account->getBank()) {
                $this->addFlash('success', sprintf(
                    'Asset protocol committed. Linked Financial Account at %s.',
                    $account->getBank()->getName()
                ));
            } else {
                $this->addFlash('success', 'Asset protocol committed successfully.');
            }

            return $this->redirectToRoute('app_asset_edit', ['id' => $asset->getId()]);
        }

        return $this->renderTurbo('asset/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'asset' => $asset,
            'form' => $form,
        ]);
    }

    #[Route('/asset/edit/{id}', name: 'app_asset_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        FinancialAccountManager $financialAccountManager
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $originalCampaign = $asset->getCampaign();

        $form = $this->createForm(AssetType::class, $asset);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $details = $this->extractAssetDetails($form, $asset);

            if (is_object($details) && method_exists($details, 'toArray')) {
                $asset->setAssetDetails($details->toArray());
            }

            // Gestione FinancialAccount: esistente selezionato o aggiornamento
            $existingAccount = $form->get('financialAccount')->getData();
            $bank = $form->get('bank')->getData();
            $bankName = $form->get('bankName')->getData();

            if ($existingAccount) {
                // Usa l'account esistente selezionato
                $account = $existingAccount;
                $account->setAsset($asset);
            } else if ($bank || $bankName) {
                // Aggiorna o crea account solo se c'e' almeno bank o bankName
                $account = $financialAccountManager->updateForAsset(
                    $asset,
                    $user,
                    null,
                    $bank,
                    $bankName
                );
            } else {
                $account = $asset->getFinancialAccount();
            }

            if ($originalCampaign && $asset->getCampaign() === null) {
                if (!$this->isGranted(AssetVoter::CAMPAIGN_REMOVE, $asset)) {
                    $asset->setCampaign($originalCampaign);
                    $this->addFlash('error', 'Linked records prevent detaching the campaign.');
                    return $this->redirectToRoute('app_asset_edit', ['id' => $asset->getId()]);
                }
            }

            $em->persist($asset);
            $em->flush();

            if ($account && $account->getBank()) {
                $this->addFlash('info', sprintf(
                    'Unit ledger synchronized. Account status updated at %s (Current Balance: %s Cr).',
                    $account->getBank()->getName(),
                    number_format((float)$account->getCredits(), 0)
                ));
            } else {
                $this->addFlash('info', 'Asset updated successfully.');
            }

            return $this->redirectToRoute('app_asset_edit', ['id' => $asset->getId()]);
        }

        return $this->renderTurbo('asset/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'asset' => $asset,
            'form' => $form,
        ]);
    }

    #[Route('/asset/{id}/pdf', name: 'app_asset_pdf', methods: ['GET'])]
    public function pdf(
        int $id,
        EntityManagerInterface $em,
        PdfGeneratorInterface $pdfGenerator,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $options = [
            'margin-top' => '14mm',
            'margin-bottom' => '14mm',
            'margin-left' => '10mm',
            'margin-right' => '10mm',
            'footer-right' => 'Page [page] / [toPage]',
            'footer-font-size' => 8,
            'footer-spacing' => 8,
            'disable-smart-shrinking' => true,
            'enable-local-file-access' => true,
        ];

        $templatePath = 'pdf/asset/SHEET.html.twig';

        $pdfContent = $pdfGenerator->render($templatePath, [
            'asset' => $asset,
            'user' => $user,
            'locale' => $request->getLocale(),
            'watermark' => '',
        ], $options);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename=\"asset-%s.pdf\"', $asset->getCode()),
        ]);
    }

    #[Route('/asset/{id}/pdf/preview', name: 'app_asset_pdf_preview', methods: ['GET'])]
    public function pdfPreview(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        return $this->render('pdf/asset/SHEET.html.twig', [
            'asset' => $asset,
            'user' => $user,
            'locale' => $request->getLocale(),
            'watermark' => '',
        ]);
    }

    #[Route('/asset/delete/{id}', name: 'app_asset_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        if (!$this->isGranted(AssetVoter::DELETE, $asset)) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($asset);
        $em->flush();

        return $this->redirectToRoute('app_asset_index', ['category' => $asset->getCategory()]);
    }

    #[Route('/asset/{id}/crew', name: 'app_asset_crew')]
    public function crew(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ListViewHelper $listViewHelper,
        CrewAssignmentService $crewAssignmentService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $needCaptain = !$asset->hasCaptain();
        $crewFilters = $listViewHelper->collectFilters($request, [
            'search' => ['param' => 'crew_search'],
            'nickname' => ['param' => 'crew_nickname'],
        ]);
        $crewPage = $listViewHelper->getPage($request, 'crew_page');

        $perPage = 10;
        $crewResult = $em->getRepository(Crew::class)
            ->findUnassignedForAsset($user, $crewFilters, $crewPage, $perPage, $needCaptain);

        $rows = [];
        foreach ($crewResult['items'] as $crew) {
            $rows[] = (new CrewSelection())->setCrew($crew)->setSelected(false);
        }

        $form = $this->createForm(CrewSelectType::class, [
            'crewSelections' => $rows,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CrewSelection[] $selections */
            $selections = $form->get('crewSelections')->getData();

            foreach ($selections as $selection) {
                if ($selection->isSelected()) {

                    $crewAssignmentService->assignToAsset($asset, $selection->getCrew());
                }
            }

            $em->flush();

            $redirectParams = ['id' => $asset->getId()];
            $submittedSearch = trim((string) $request->request->get('crew_search', ''));
            $submittedNickname = trim((string) $request->request->get('crew_nickname', ''));
            $submittedPage = max(1, (int) $request->request->get('crew_page', 1));
            if ($submittedSearch !== '') {
                $redirectParams['crew_search'] = $submittedSearch;
            }
            if ($submittedNickname !== '') {
                $redirectParams['crew_nickname'] = $submittedNickname;
            }
            if ($submittedPage > 1) {
                $redirectParams['crew_page'] = $submittedPage;
            }

            return $this->redirectToRoute('app_asset_crew', $redirectParams);
        }

        $crewTotal = $crewResult['total'];
        $crewPagination = $listViewHelper->buildPaginationPayload($crewPage, $perPage, $crewTotal);

        $roleForms = [];
        foreach ($asset->getCrews() as $crewMember) {
            // AssetRoleAssignmentType probabilmente necessita Asset ora.
            $assignmentForm = $this->createForm(AssetRoleAssignmentType::class, null, [
                'asset' => $asset, // Le opzioni del form probabilmente si aspettano la chiave 'asset'
                'user' => $user,
            ]);
            $assignmentForm->get('assetRoles')->setData($crewMember->getAssetRoles()->toArray());
            $roleForms[$crewMember->getId()] = $assignmentForm->createView();
        }

        return $this->renderTurbo('asset/crew_select.html.twig', [
            'asset' => $asset,
            'form' => $form,
            'roleForms' => $roleForms,
            'controller_name' => self::CONTROLLER_NAME,
            'crewFilters' => $crewFilters,
            'crewPagination' => $crewPagination,
        ]);
    }

    #[Route('/asset/{assetId}/crew/{crewId}/roles', name: 'app_asset_crew_assign_roles', methods: ['POST'])]
    public function assignCrewRoles(
        int $assetId,
        int $crewId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($assetId, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $crew = $em->getRepository(Crew::class)->findOneForUser($crewId, $user);
        if (!$crew || $crew->getAsset()?->getId() !== $asset->getId()) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(AssetRoleAssignmentType::class, null, [
            'asset' => $asset,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRoles = $form->get('assetRoles')->getData();
            $crew->getAssetRoles()->clear();
            foreach ($selectedRoles as $role) {
                $crew->addAssetRole($role);
            }

            $capSelected = false;
            foreach ($selectedRoles as $role) {
                if ($role->getCode() === 'CAP') {
                    $capSelected = true;
                    break;
                }
            }

            if ($capSelected) {
                foreach ($asset->getCrews() as $otherCrew) {
                    if ($otherCrew === $crew) {
                        continue;
                    }

                    foreach ($otherCrew->getAssetRoles() as $otherRole) {
                        if ($otherRole->getCode() === 'CAP') {
                            $this->addFlash('error', 'Another crew member already holds the captain role. Remove that role first.');
                            return $this->redirectToRoute('app_asset_crew', ['id' => $asset->getId()]);
                        }
                    }
                }
            }

            $em->persist($crew);
            $em->flush();
            $this->addFlash('success', 'Crew roles updated.');
        } elseif ($form->isSubmitted()) {
            $this->flashFormErrors($form);
        }

        return $this->redirectToRoute('app_asset_crew', ['id' => $asset->getId()]);
    }

    #[Route('/asset/crew/{id}/remove', name: 'app_asset_crew_remove', methods: ['GET', 'POST'])]
    public function removeCrew(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CrewAssignmentService $crewAssignmentService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $crew = $em->getRepository(Crew::class)->findOneForUser($id, $user);
        if (!$crew) {
            throw new NotFoundHttpException();
        }

        $asset = $crew->getAsset();
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        if (!$this->isGranted(AssetVoter::CREW_REMOVE, $asset)) {
            throw $this->createAccessDeniedException();
        }

        $crewAssignmentService->removeFromAsset($asset, $crew);
        $em->persist($asset);
        $em->persist($crew);
        $em->flush();
        return $this->redirectToRoute('app_asset_crew', ['id' => $asset->getId()]);
    }

    #[Route('/asset/{id}/ledger', name: 'app_asset_ledger')]
    public function ledger(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ListViewHelper $listViewHelper
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $page = $listViewHelper->getPage($request);
        $perPage = 20;

        $transactionRepo = $em->getRepository(Transaction::class);
        // Usa FinancialAccount
        $account = $asset->getFinancialAccount();
        if (!$account) {
            // Gestisci caso limite: crea al volo o mostra vuoto? 
            // Gli asset dovrebbero avere sempre un account. Se no, mostra vuoto.
            $result = ['items' => [], 'total' => 0];
        } else {
            $result = $transactionRepo->findForAccount($account, $page, $perPage);
        }

        $transactions = $result['items'];
        $total = $result['total'];

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->renderTurbo('asset/ledger.html.twig', [
            'asset' => $asset,
            'transactions' => $transactions,
            'pagination' => $pagination,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/api/assets/by-campaign', name: 'api_assets_by_campaign', methods: ['GET'])]
    public function apiListByCampaign(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $campaignId = $request->query->get('campaign');
        $user = $this->getUser();

        $qb = $em->getRepository(Asset::class)->createQueryBuilder('a')
            ->select('a.id', 'a.name')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.name', 'ASC');

        if ($campaignId) {
            $qb->andWhere('a.campaign = :campaign')
                ->setParameter('campaign', $campaignId);
        }

        $assets = $qb->getQuery()->getArrayResult();

        return new JsonResponse($assets);
    }

    #[Route('/asset/{id}/cargo', name: 'app_asset_cargo')]
    public function cargo(
        int $id,
        EntityManagerInterface $em,
        CostRepository $costRepo,
        TradePricer $tradePricer,
        FormFactoryInterface $formFactory
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $account = $asset->getFinancialAccount();
        if (!$account) {
            $cargoItems = [];
        } else {
            $cargoItems = $costRepo->findUnsoldTradeCargoForAccount($account);
        }
        $localLaws = $em->getRepository(LocalLaw::class)->findAll();

        $marketValues = [];
        foreach ($cargoItems as $item) {
            $marketValues[$item->getId()] = $tradePricer->calculateMarketPrice($item);
        }

        // Crea il form Loot per il modale (solo se consentito)
        $lootFormView = null;
        if ($this->isGranted(AssetVoter::ADD_LOOT, $asset)) {
            $lootForm = $this->createForm(\App\Form\CargoLootType::class, null, [
                'action' => $this->generateUrl('app_asset_cargo_add_loot', ['id' => $id]),
            ]);
            $lootFormView = $lootForm->createView();
        }

        // Crea i form di liquidazione per ogni articolo (cargo)
        $liquidationForms = [];
        foreach ($cargoItems as $item) {
            $form = $formFactory->createNamed('liquidation_' . $item->getId(), \App\Form\CargoLiquidationType::class, [
                'location' => null,
                'localLaw' => null,
            ], [
                'action' => $this->generateUrl('app_asset_cargo_sell', ['id' => $id, 'costId' => $item->getId()]),
            ]);
            $liquidationForms[$item->getId()] = $form->createView();
        }

        return $this->renderTurbo('asset/cargo.html.twig', [
            'asset' => $asset,
            'cargoItems' => $cargoItems,
            'marketValues' => $marketValues,
            'localLaws' => $localLaws,
            'lootForm' => $lootFormView,
            'liquidationForms' => $liquidationForms,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/asset/{id}/cargo/{costId}/sell', name: 'app_asset_cargo_sell', methods: ['POST'])]
    public function sellCargo(
        int $id,
        int $costId,
        Request $request,
        EntityManagerInterface $em,
        TradeService $tradeService,
        TradePricer $tradePricer,
        FormFactoryInterface $formFactory
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        $cost = $em->getRepository(Cost::class)->findOneForUser($costId, $user);

        if (!$asset || !$cost || $cost->getAsset() !== $asset) {
            throw new NotFoundHttpException();
        }

        // RICALCOLA IL PREZZO LATO SERVER (Deterministico)
        // Nessun input manuale permesso. Decide il mercato.
        $salePrice = (float) $tradePricer->calculateMarketPrice($cost);

        $form = $formFactory->createNamed('liquidation_' . $costId, \App\Form\CargoLiquidationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $location = (string) ($data['location'] ?? 'Unknown');

            $campaign = $asset->getCampaign();
            $day = $campaign ? $campaign->getSessionDay() : 1;
            $year = $campaign ? $campaign->getSessionYear() : 1105;

            $localLaw = $data['localLaw'];
            $buyerCompany = $data['company'] ?? null;

            try {
                $tradeService->liquidateCargo($cost, $salePrice, $location, $day, $year, $localLaw, $buyerCompany);
                $this->addFlash('success', 'Cargo sold for ' . number_format($salePrice) . ' Cr.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Liquidation failed: ' . $e->getMessage());
            }
        } else {
            // In caso di errore, reindirizza semplicemente indietro con un flash di errore. 
            // Perdiamo lo stato del form ma essendo un modale, per ora l'esperienza utente è più semplice.
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Form Check Failed: ' . implode(', ', $errors));
        }

        return $this->redirectToRoute('app_asset_cargo', ['id' => $id]);
    }

    #[Route('/asset/{id}/cargo/add-loot', name: 'app_asset_cargo_add_loot', methods: ['POST'])]
    public function addLoot(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $account = $asset->getFinancialAccount();
        if (!$account) {
            $this->addFlash('error', 'Asset has no financial account linked.');
            return $this->redirectToRoute('app_asset_cargo', ['id' => $id]);
        }

        $this->denyAccessUnlessGranted(AssetVoter::ADD_LOOT, $asset);

        $cost = new Cost();
        $form = $this->createForm(\App\Form\CargoLootType::class, $cost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 2. Risolvi CostCategory 'TRADE'
            $tradeCategory = $em->getRepository(CostCategory::class)->findOneBy(['code' => 'TRADE']);
            if (!$tradeCategory) {
                $this->addFlash('error', 'System Error: TRADE category not found.');
                return $this->redirectToRoute('app_asset_cargo', ['id' => $id]);
            }

            // Imposta campi dipendenti dal contesto non gestiti dal Form Type
            $cost->setUser($user);
            $cost->setFinancialAccount($account);
            $cost->setCostCategory($tradeCategory);

            // Imposta data dalla sessione della campagna
            $campaign = $asset->getCampaign(); // Non nullo grazie al Voter
            $cost->setPaymentDay($campaign->getSessionDay());
            $cost->setPaymentYear($campaign->getSessionYear());

            // Quantità e Dettagli sono gestiti dai listener di CargoLootType

            $em->persist($cost);
            $em->flush();

            $this->addFlash('success', 'Loot registered in cargo manifest.');
        } else {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Error registering loot: ' . implode(', ', $errors));
        }

        return $this->redirectToRoute('app_asset_cargo', ['id' => $id]);
    }

    /**
     * Estrae i dati dei dettagli dal form in base alla categoria dell'asset.
     * Mappa automaticamente il campo form corretto (shipDetails, baseDetails, assetDetails).
     */
    private function extractAssetDetails(FormInterface $form, Asset $asset): ?object
    {
        $fieldMap = [
            Asset::CATEGORY_SHIP => 'shipDetails',
            Asset::CATEGORY_BASE => 'baseDetails',
        ];

        $fieldName = $fieldMap[$asset->getCategory()] ?? 'assetDetails';

        return $form->has($fieldName) ? $form->get($fieldName)->getData() : null;
    }
}
