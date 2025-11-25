<?php

namespace App\Controller;

use App\Entity\Crew;
use App\Entity\Ship;
use App\Form\CrewSelectType;
use App\Form\ShipType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

final class ShipController extends AbstractController
{
    const CONTROLLER_NAME = "ShipController";
    #[Route('/ship/index', name: 'app_ship_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $ships = $em->getRepository(Ship::class)->findAll();
        return $this->render('ship/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'ships' => $ships,
        ]);
    }

    #[Route('/ship/new', name: 'app_ship_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ship = new Ship();
        $form = $this->createForm(ShipType::class, $ship);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $ship->setCode(Uuid::v7());

            $em->persist($ship);
            $em->flush();
            return $this->redirectToRoute('app_ship_index');
        }

        return $this->render('ship/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'ship' => $ship,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/ship/edit/{id}', name: 'app_ship_edit', methods: ['GET', 'POST'])]
    public function edit(Ship $ship, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ShipType::class, $ship);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ship);
            $em->flush();
            return $this->redirectToRoute('app_ship_index');
        }

        return $this->render('ship/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'ship' => $ship,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/ship/delete/{id}', name: 'app_ship_delete', methods: ['GET', 'POST'])]
    #[IsGranted('SHIP_DELETE', 'ship')]
    public function delete(Ship $ship, Request $request, EntityManagerInterface $em): Response
    {
        $em->remove($ship);
        $em->flush();

        return $this->redirectToRoute('app_ship_index');
    }

    #[Route('/ship/crew/select/{id}', name: 'app_ship_crew_select', methods: ['GET', 'POST'])]
    public function crewSelect(Ship $ship, Request $request, EntityManagerInterface $em): Response
    {

        $crewToSelect = $em->getRepository(Crew::class)->findBy(['ship' => null]);
        $form = $this->createForm(CrewSelectType::class, null, [
            'crewToSelect' => $crewToSelect,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $selectedIds = $form->get('crewIds')->getData();
            foreach ($selectedIds as $selectedId) {
                $crewMember = $em->getRepository(Crew::class)->find($selectedId);
                $ship->addCrew($crewMember);
                $em->persist($ship);
                $em->flush();
            }

            return $this->redirectToRoute('app_ship_crew_select', ['id' => $ship->getId()]);
        }

        return $this->render('ship/crew_select.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'form' => $form->createView(),
            'ship' => $ship,
            'crewToSelect' => $crewToSelect,
        ]);
    }

    #[Route('/ship/crew/remove/{id}', name: 'app_ship_crew_remove', methods: ['GET', 'POST'])]
    public function removeCrew(Crew $crew, Request $request, EntityManagerInterface $em): Response
    {
        $ship = $crew->getShip();
        $ship->removeCrew($crew);
        $em->persist($ship);
        $em->flush();
        return $this->redirectToRoute('app_ship_crew_select', ['id' => $ship->getId()]);
    }

}
