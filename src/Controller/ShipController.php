<?php

namespace App\Controller;

use App\Dto\CrewSelection;
use App\Entity\Crew;
use App\Entity\Ship;
use App\Entity\Campaign;
use App\Service\PdfGenerator;
use App\Form\CrewSelectType;
use App\Form\ShipType;
use App\Form\ShipRoleAssignmentType;
use App\Security\Voter\ShipVoter;
use App\Dto\ShipDetailsData;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ShipController extends BaseController
{
    const CONTROLLER_NAME = "ShipController";
    #[Route('/ship/index', name: 'app_ship_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'name',
            'type_class',
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $ships = [];
        $total = 0;
        $totalPages = 1;
        $campaigns = [];

        if ($user instanceof \App\Entity\User) {
            $result = $em->getRepository(Ship::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $ships = $result['items'];
            $total = $result['total'];

            $totalPages = max(1, (int) ceil($total / $perPage));
            $clampedPage = $listViewHelper->clampPage($page, $totalPages);
            if ($clampedPage !== $page) {
                $page = $clampedPage;
                $result = $em->getRepository(Ship::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $ships = $result['items'];
            }

            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('ship/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'ships' => $ships,
            'filters' => $filters,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/ship/new', name: 'app_ship_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ship = new Ship();
        $form = $this->createForm(ShipType::class, $ship);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ShipDetailsData|null $details */
            $details = $form->get('shipDetails')->getData();
            if ($details instanceof ShipDetailsData) {
                $ship->setShipDetails($details->toArray());
            }

            $em->persist($ship);
            $em->flush();
            return $this->redirectToRoute('app_ship_index');
        }

        return $this->renderTurbo('ship/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'ship' => $ship,
            'form' => $form,
        ]);
    }

    #[Route('/ship/edit/{id}', name: 'app_ship_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $ship = $em->getRepository(Ship::class)->findOneForUser($id, $user);
        if (!$ship) {
            throw new NotFoundHttpException();
        }

        $originalCampaign = $ship->getCampaign();

        $form = $this->createForm(ShipType::class, $ship);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ShipDetailsData|null $details */
            $details = $form->get('shipDetails')->getData();
            if ($details instanceof ShipDetailsData) {
                $ship->setShipDetails($details->toArray());
            }

            if ($originalCampaign && $ship->getCampaign() === null) {
                if (!$this->isGranted(ShipVoter::CAMPAIGN_REMOVE, $ship)) {
                    $ship->setCampaign($originalCampaign);
                    $this->addFlash('error', 'Linked records prevent detaching the campaign.');
                    return $this->redirectToRoute('app_ship_edit', ['id' => $ship->getId()]);
                }
            }

            if (!$this->isGranted(ShipVoter::EDIT, $ship)) {
                $this->addFlash('error', 'Mortgage Signed, Action Denied!');
                return $this->redirectToRoute('app_mortgage_edit', ['id' => $ship->getId()]);
            }

            $em->persist($ship);
            $em->flush();
            return $this->redirectToRoute('app_ship_index');
        }

        return $this->renderTurbo('ship/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'ship' => $ship,
            'form' => $form,
        ]);
    }

    #[Route('/ship/{id}/pdf', name: 'app_ship_pdf', methods: ['GET'])]
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

        $ship = $em->getRepository(Ship::class)->findOneForUser($id, $user);
        if (!$ship) {
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

        $pdfContent = $pdfGenerator->render('pdf/ship/SHEET.html.twig', [
            'ship' => $ship,
            'user' => $user,
            'locale' => $request->getLocale(),
        ], $options);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename=\"ship-%s.pdf\"', $ship->getCode()),
        ]);
    }

    #[Route('/ship/{id}/pdf/preview', name: 'app_ship_pdf_preview', methods: ['GET'])]
    public function pdfPreview(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $ship = $em->getRepository(Ship::class)->findOneForUser($id, $user);
        if (!$ship) {
            throw new NotFoundHttpException();
        }

        return $this->render('pdf/ship/SHEET.html.twig', [
            'ship' => $ship,
            'user' => $user,
            'locale' => $request->getLocale(),
        ]);
    }

    #[Route('/ship/delete/{id}', name: 'app_ship_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $ship = $em->getRepository(Ship::class)->findOneForUser($id, $user);
        if (!$ship) {
            throw new NotFoundHttpException();
        }

        if (!$this->isGranted(ShipVoter::DELETE, $ship)) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($ship);
        $em->flush();

        return $this->redirectToRoute('app_ship_index');
    }

    #[Route('/ship/{id}/crew', name: 'app_ship_crew')]
    public function crew(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ListViewHelper $listViewHelper
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $ship = $em->getRepository(Ship::class)->findOneForUser($id, $user);
        if (!$ship) {
            throw new NotFoundHttpException();
        }

        $needCaptain = !$ship->hasCaptain();
        $crewFilters = $listViewHelper->collectFilters($request, [
            'search' => ['param' => 'crew_search'],
            'nickname' => ['param' => 'crew_nickname'],
        ]);
        $crewPage = $listViewHelper->getPage($request, 'crew_page');

        $perPage = 10;
        $crewResult = $em->getRepository(Crew::class)
            ->findUnassignedForShip($user, $crewFilters, $crewPage, $perPage, $needCaptain);

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
                    $ship->addCrew($selection->getCrew());
                }
            }

            $em->flush();

            $redirectParams = ['id' => $ship->getId()];
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

            return $this->redirectToRoute('app_ship_crew', $redirectParams);
        }

        $crewTotal = $crewResult['total'];
        $crewPagination = $listViewHelper->buildPaginationPayload($crewPage, $perPage, $crewTotal);

        $roleForms = [];
        foreach ($ship->getCrews() as $crewMember) {
            $assignmentForm = $this->createForm(ShipRoleAssignmentType::class, null, [
                'ship' => $ship,
                'user' => $user,
            ]);
            $assignmentForm->get('shipRoles')->setData($crewMember->getShipRoles()->toArray());
            $roleForms[$crewMember->getId()] = $assignmentForm->createView();
        }

        return $this->renderTurbo('ship/crew_select.html.twig', [
            'ship' => $ship,
            'form' => $form,
            'roleForms' => $roleForms,
            'controller_name' => self::CONTROLLER_NAME,
            'crewFilters' => $crewFilters,
            'crewPagination' => $crewPagination,
        ]);
    }

    #[Route('/ship/{shipId}/crew/{crewId}/roles', name: 'app_ship_crew_assign_roles', methods: ['POST'])]
    public function assignCrewRoles(
        int $shipId,
        int $crewId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $ship = $em->getRepository(Ship::class)->findOneForUser($shipId, $user);
        if (!$ship) {
            throw new NotFoundHttpException();
        }

        $crew = $em->getRepository(Crew::class)->findOneForUser($crewId, $user);
        if (!$crew || $crew->getShip()?->getId() !== $ship->getId()) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(ShipRoleAssignmentType::class, null, [
            'ship' => $ship,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRoles = $form->get('shipRoles')->getData();
            $crew->getShipRoles()->clear();
            foreach ($selectedRoles as $role) {
                $crew->addShipRole($role);
            }

            $capSelected = false;
            foreach ($selectedRoles as $role) {
                if ($role->getCode() === 'CAP') {
                    $capSelected = true;
                    break;
                }
            }

            if ($capSelected) {
                foreach ($ship->getCrews() as $otherCrew) {
                    if ($otherCrew === $crew) {
                        continue;
                    }

                    foreach ($otherCrew->getShipRoles() as $otherRole) {
                        if ($otherRole->getCode() === 'CAP') {
                            $this->addFlash('error', 'Another crew member already holds the captain role. Remove that role first.');
                            return $this->redirectToRoute('app_ship_crew', ['id' => $ship->getId()]);
                        }
                    }
                }
            }

            $em->persist($crew);
            $em->flush();
            $this->addFlash('success', 'Crew roles updated.');
        }

        return $this->redirectToRoute('app_ship_crew', ['id' => $ship->getId()]);
    }

    #[Route('/ship/crew/{id}/remove', name: 'app_ship_crew_remove', methods: ['GET', 'POST'])]
    public function removeCrew(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $crew = $em->getRepository(Crew::class)->findOneForUser($id, $user);
        if (!$crew) {
            throw new NotFoundHttpException();
        }

        $ship = $crew->getShip();
        if (!$ship) {
            throw new NotFoundHttpException();
        }

        if (!$this->isGranted(ShipVoter::CREW_REMOVE, $ship)) {
            throw $this->createAccessDeniedException();
        }

        $ship->removeCrew($crew);
        $em->persist($ship);
        $em->flush();
        return $this->redirectToRoute('app_ship_crew', ['id' => $ship->getId()]);
    }

}
