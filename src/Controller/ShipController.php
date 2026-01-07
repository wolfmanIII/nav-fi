<?php

namespace App\Controller;

use App\Dto\CrewSelection;
use App\Entity\Crew;
use App\Entity\Ship;
use App\Entity\Campaign;
use App\Service\PdfGenerator;
use App\Form\CrewSelectType;
use App\Form\ShipType;
use App\Security\Voter\ShipVoter;
use App\Dto\ShipDetailsData;
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
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $campaignFilter = trim((string) $request->query->get('campaign', ''));
        $filters = [
            'name' => trim((string) $request->query->get('name', '')),
            'type_class' => trim((string) $request->query->get('type_class', '')),
            'campaign' => $campaignFilter !== '' && ctype_digit($campaignFilter) ? (int) $campaignFilter : null,
        ];
        $page = max(1, (int) $request->query->get('page', 1));
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
            if ($page > $totalPages) {
                $page = $totalPages;
                $result = $em->getRepository(Ship::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $ships = $result['items'];
            }

            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pages = $this->buildPagination($page, $totalPages);
        $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $to = $total > 0 ? min($page * $perPage, $total) : 0;
        return $this->render('ship/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'ships' => $ships,
            'filters' => $filters,
            'campaigns' => $campaigns,
            'pagination' => [
                'current' => $page,
                'total' => $total,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'pages' => $pages,
                'from' => $from,
                'to' => $to,
            ],
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

        $form = $this->createForm(ShipType::class, $ship);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ShipDetailsData|null $details */
            $details = $form->get('shipDetails')->getData();
            if ($details instanceof ShipDetailsData) {
                $ship->setShipDetails($details->toArray());
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
        EntityManagerInterface $em
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
        // Tutti i crew che non hanno una nave
        $crewToSelect = $em->getRepository(Crew::class)->getCrewNotInAnyShip($needCaptain, $user);

        // Costruisci le DTO
        $rows = [];
        foreach ($crewToSelect as $crew) {
            $dto = (new CrewSelection())
                ->setCrew($crew)
                ->setSelected(false);

            $rows[] = $dto;
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

            return $this->redirectToRoute('app_ship_crew', ['id' => $ship->getId()]);
        }

        return $this->renderTurbo('ship/crew_select.html.twig', [
            'ship' => $ship,
            'form' => $form,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
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

    /**
     * @return array<int, int|null>
     */
    private function buildPagination(int $current, int $totalPages): array
    {
        if ($totalPages <= 1) {
            return [1];
        }

        if ($totalPages <= 7) {
            return range(1, $totalPages);
        }

        $pages = [1];
        $windowStart = max(2, $current - 2);
        $windowEnd = min($totalPages - 1, $current + 2);

        if ($windowStart > 2) {
            $pages[] = null;
        }

        for ($i = $windowStart; $i <= $windowEnd; $i++) {
            $pages[] = $i;
        }

        if ($windowEnd < $totalPages - 1) {
            $pages[] = null;
        }

        $pages[] = $totalPages;

        return $pages;
    }

}
