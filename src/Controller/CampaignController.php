<?php

namespace App\Controller;

use App\Dto\ShipSelection;
use App\Entity\Campaign;
use App\Entity\Ship;
use App\Form\Config\DayYearLimits;
use App\Form\CampaignType;
use App\Form\ShipSelectType;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Security\Voter\CampaignVoter;
use App\Security\Voter\ShipVoter;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CampaignController extends BaseController
{
    public const CONTROLLER_NAME = 'CampaignController';

    #[Route('/campaign/index', name: 'app_campaign_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $filters = $listViewHelper->collectFilters($request, [
            'title',
            'starting_year' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $result = $em->getRepository(Campaign::class)->findWithFilters($filters, $page, $perPage, $user);
        $campaigns = $result['items'];
        $total = $result['total'];

        $totalPages = max(1, (int) ceil($total / $perPage));
        $clampedPage = $listViewHelper->clampPage($page, $totalPages);
        if ($clampedPage !== $page) {
            $page = $clampedPage;
            $result = $em->getRepository(Campaign::class)->findWithFilters($filters, $page, $perPage, $user);
            $campaigns = $result['items'];
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('campaign/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'campaigns' => $campaigns,
            'filters' => $filters,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/campaign/new', name: 'app_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = new Campaign();
        $form = $this->createForm(CampaignType::class, $campaign);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($campaign);
            $em->flush();

            return $this->redirectToRoute('app_campaign_index');
        }

        return $this->renderTurbo('campaign/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'campaign' => $campaign,
            'form' => $form,
        ]);
    }

    #[Route('/campaign/edit/{id}', name: 'app_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = $em->getRepository(Campaign::class)->findOneForUser($id, $user);
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(CampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_campaign_index');
        }

        return $this->renderTurbo('campaign/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'campaign' => $campaign,
            'form' => $form,
        ]);
    }

    #[Route('/campaign/delete/{id}', name: 'app_campaign_delete', methods: ['GET', 'POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = $em->getRepository(Campaign::class)->findOneForUser($id, $user);
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(CampaignVoter::DELETE, $campaign);

        $em->remove($campaign);
        $em->flush();

        return $this->redirectToRoute('app_campaign_index');
    }

    #[Route('/campaign/{id}/details', name: 'app_campaign_details')]
    public function ships(
        int $id,
        Request $request,
        DayYearLimits $limits,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = $em->getRepository(Campaign::class)->findOneForUser($id, $user);
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $shipsToSelect = $em->getRepository(Ship::class)->findWithoutCampaignForUser($user);

        $rows = [];
        foreach ($shipsToSelect as $ship) {
            $dto = (new ShipSelection())
                ->setShip($ship)
                ->setSelected(false);

            $rows[] = $dto;
        }

        $shipForm = $this->createForm(ShipSelectType::class, [
            'shipSelections' => $rows,
        ]);

        $sessionDate = new ImperialDate($campaign->getSessionYear(), $campaign->getSessionDay());
        $calendarForm = $this->createForm(ImperialDateType::class, $sessionDate, [
            'min_year' => max($limits->getYearMin(), $campaign->getStartingYear() ?? $limits->getYearMin()),
            'max_year' => $limits->getYearMax(),
        ]);

        $shipForm->handleRequest($request);
        if ($shipForm->isSubmitted() && $shipForm->isValid()) {
            /** @var ShipSelection[] $selections */
            $selections = $shipForm->get('shipSelections')->getData();

            foreach ($selections as $selection) {
                if ($selection->isSelected()) {
                    $campaign->addShip($selection->getShip());
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
        }

        $calendarForm->handleRequest($request);
        if ($calendarForm->isSubmitted() && $calendarForm->isValid()) {
            /** @var ImperialDate $session */
            $session = $calendarForm->getData();

            $campaign->setSessionDay($session->getDay());
            $campaign->setSessionYear($session->getYear());

            $em->flush();
            $this->addFlash('success', 'Session updated');

            return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
        }

        return $this->renderTurbo('campaign/details.html.twig', [
            'campaign' => $campaign,
            'form' => $shipForm,
            'calendar_form' => $calendarForm,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/campaign/ship/{id}/remove', name: 'app_campaign_ship_remove', methods: ['GET', 'POST'])]
    public function removeShip(
        int $id,
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

        $campaign = $ship->getCampaign();
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(ShipVoter::CAMPAIGN_REMOVE, $ship);

        $campaign->removeShip($ship);
        $em->persist($campaign);
        $em->flush();

        return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
    }
}
