<?php

namespace App\Controller;

use App\Dto\CrewSelection;
use App\Entity\Crew;
use App\Entity\Ship;
use App\Form\CrewSelectType;
use App\Form\ShipType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

            $em->persist($ship);
            $em->flush();
            return $this->redirectToRoute('app_ship_index');
        }

        return $this->renderTurboForm('ship/edit.html.twig', $form, [
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

        return $this->renderTurboForm('ship/edit.html.twig', $form, [
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

    #[Route('/ship/{id}/crew', name: 'app_ship_crew')]
    public function crew(
        Ship $ship,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $needCaptain = !$ship->hasCaptain();
        // Tutti i crew che non hanno una nave
        $crewToSelect = $em->getRepository(Crew::class)->getCrewNotInAnyShip($needCaptain);

        // Costruisco le DTO per la Collection
        $rows = array_map(
            fn (Crew $crew) => new CrewSelection($crew),
            $crewToSelect
        );

        $form = $this->createForm(CrewSelectType::class, [
            'crewSelections' => $rows,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CrewSelection[] $selections */
            $selections = $form->get('crewSelections')->getData();

            foreach ($selections as $selection) {
                if ($selection->selected) {
                    $ship->addCrew($selection->crew);
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_ship_crew', ['id' => $ship->getId()]);
        }

        return $this->renderTurboForm('ship/crew_select.html.twig', $form, [
            'ship' => $ship,
            'form' => $form->createView(),
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/ship/crew/{id}/remove', name: 'app_ship_crew_remove', methods: ['GET', 'POST'])]
    public function removeCrew(Crew $crew, Request $request, EntityManagerInterface $em): Response
    {
        $ship = $crew->getShip();
        $ship->removeCrew($crew);
        $em->persist($ship);
        $em->flush();
        return $this->redirectToRoute('app_ship_crew', ['id' => $ship->getId()]);
    }

    /**
     * Render del form con supporto a Turbo:
     * - 200 se form non sottomesso
     * - 422 se form sottomesso ma NON valido (altrimenti Turbo non mostra gli errori)
     */
    private function renderTurboForm(string $template, FormInterface $form, array $options): Response
    {
        $response = $this->render($template, $options);

        if ($form->isSubmitted() && !$form->isValid()) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        return $response;
    }

}
