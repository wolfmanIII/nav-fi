<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Ship;
use App\Dto\ShipSelection;
use App\Form\CampaignType;
use App\Form\ShipSelectType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CampaignController extends BaseController
{
    public const CONTROLLER_NAME = 'CampaignController';

    #[Route('/campaign/index', name: 'app_campaign_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $campaigns = $em->getRepository(Campaign::class)->findAll();

        return $this->render('campaign/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'campaigns' => $campaigns,
        ]);
    }

    #[Route('/campaign/new', name: 'app_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
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
        $campaign = $em->getRepository(Campaign::class)->find($id);
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
        $campaign = $em->getRepository(Campaign::class)->find($id);
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $em->remove($campaign);
        $em->flush();

        return $this->redirectToRoute('app_campaign_index');
    }

    #[Route('/campaign/{id}/ships', name: 'app_campaign_ships')]
    public function ships(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = $em->getRepository(Campaign::class)->find($id);
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

        $form = $this->createForm(ShipSelectType::class, [
            'shipSelections' => $rows,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ShipSelection[] $selections */
            $selections = $form->get('shipSelections')->getData();

            foreach ($selections as $selection) {
                if ($selection->isSelected()) {
                    $campaign->addShip($selection->getShip());
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_campaign_ships', ['id' => $campaign->getId()]);
        }

        return $this->renderTurbo('campaign/ship_select.html.twig', [
            'campaign' => $campaign,
            'form' => $form,
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

        $campaign->removeShip($ship);
        $em->persist($campaign);
        $em->flush();

        return $this->redirectToRoute('app_campaign_ships', ['id' => $campaign->getId()]);
    }
}
