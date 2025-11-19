<?php

namespace App\Controller;

use App\Entity\Ship;
use App\Form\ShipType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function delete(Ship $ship, Request $request, EntityManagerInterface $em): Response
    {
        $em->remove($ship);
        $em->flush();

        return $this->redirectToRoute('app_ship_index');
    }
}
