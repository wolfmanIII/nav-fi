<?php

namespace App\Controller;

use App\Dto\CrewSelection;
use App\Entity\Crew;
use App\Entity\Ship;
use App\Form\CrewSelectType;
use App\Form\ShipType;
use App\Security\Voter\ShipVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ShipController extends BaseController
{
    const CONTROLLER_NAME = "ShipController";
    #[Route('/ship/index', name: 'app_ship_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $ships = $user ? $em->getRepository(Ship::class)->findAllForUser($user) : [];
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
    public function edit(Ship $ship, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ShipType::class, $ship);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

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

    #[Route('/ship/delete/{id}', name: 'app_ship_delete', methods: ['GET', 'POST'])]
    #[IsGranted(ShipVoter::DELETE, 'ship')]
    public function delete(Ship $ship, Request $request, EntityManagerInterface $em): Response
    {
        $em->remove($ship);
        $em->flush();

        return $this->redirectToRoute('app_ship_index');
    }

    #[Route('/ship/{id}/crew', name: 'app_ship_crew')]
    public function crew(
        Ship $ship,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $needCaptain = !$ship->hasCaptain();
        // Tutti i crew che non hanno una nave
        $crewToSelect = $em->getRepository(Crew::class)->getCrewNotInAnyShip($needCaptain, $this->getUser());

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
    #[isGranted(ShipVoter::CREW_REMOVE, 'ship.getCrew()')]
    public function removeCrew(Crew $crew, Request $request, EntityManagerInterface $em): Response
    {
        $ship = $crew->getShip();
        $ship->removeCrew($crew);
        $em->persist($ship);
        $em->flush();
        return $this->redirectToRoute('app_ship_crew', ['id' => $ship->getId()]);
    }

}
