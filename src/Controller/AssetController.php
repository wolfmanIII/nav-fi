<?php

namespace App\Controller;

use App\Dto\CrewSelection;
use App\Entity\Crew;
use App\Entity\Asset;
use App\Entity\Campaign;
use App\Service\PdfGenerator;
use App\Form\CrewSelectType;
use App\Form\AssetType;
use App\Form\AssetRoleAssignmentType;
use App\Security\Voter\AssetVoter;
use App\Dto\AssetDetailsData;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

        if ($user instanceof \App\Entity\User) {
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
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $asset = new Asset();
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $asset->setUser($user);
        }

        $category = $request->query->get('category');
        if ($category && in_array($category, [Asset::CATEGORY_SHIP, Asset::CATEGORY_BASE, Asset::CATEGORY_TEAM])) {
            $asset->setCategory($category);
        }

        $form = $this->createForm(AssetType::class, $asset);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AssetDetailsData|null $details */
            $details = $form->get('assetDetails')->getData();
            if ($details instanceof AssetDetailsData) {
                $asset->setAssetDetails($details->toArray());
            }

            $em->persist($asset);
            $em->flush();
            return $this->redirectToRoute('app_asset_index', ['category' => $asset->getCategory()]);
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
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
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
            /** @var AssetDetailsData|null $details */
            $details = $form->get('assetDetails')->getData();
            if ($details instanceof AssetDetailsData) {
                $asset->setAssetDetails($details->toArray());
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
            return $this->redirectToRoute('app_asset_index', ['category' => $asset->getCategory()]);
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
        PdfGenerator $pdfGenerator,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
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
        if (!$user instanceof \App\Entity\User) {
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
        ]);
    }

    #[Route('/asset/delete/{id}', name: 'app_asset_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
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
        \App\Service\CrewAssignmentService $crewAssignmentService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
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
            ->findUnassignedForAsset($user, $crewFilters, $crewPage, $perPage, $needCaptain); // TODO: Rename this method in CrewRepo

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
                    // TODO: Update CrewAssignmentService to use Asset
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
            // AssetRoleAssignmentType likely needs Asset now.
            $assignmentForm = $this->createForm(AssetRoleAssignmentType::class, null, [
                'asset' => $asset, // Form options probably expect 'asset' key
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
        if (!$user instanceof \App\Entity\User) {
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
        }

        return $this->redirectToRoute('app_asset_crew', ['id' => $asset->getId()]);
    }

    #[Route('/asset/crew/{id}/remove', name: 'app_asset_crew_remove', methods: ['GET', 'POST'])]
    public function removeCrew(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        \App\Service\CrewAssignmentService $crewAssignmentService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
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

        $crewAssignmentService->removeFromAsset($asset, $crew); // TODO: Rename method
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
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $page = $listViewHelper->getPage($request);
        $perPage = 20;

        $transactionRepo = $em->getRepository(\App\Entity\Transaction::class);
        $result = $transactionRepo->findForAsset($asset, $page, $perPage);

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
}
