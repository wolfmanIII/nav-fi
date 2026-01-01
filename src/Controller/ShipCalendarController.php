<?php

namespace App\Controller;

use App\Entity\Ship;
use App\Form\ShipCalendarType;
use App\Security\Voter\ShipVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShipCalendarController extends BaseController
{
    #[Route('/ship/{id}/calendar', name: 'app_ship_calendar', methods: ['GET', 'POST'])]
    public function calendar(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $ship = $em->getRepository(Ship::class)->findOneForUser($id, $user);
        if (!$ship) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(ShipVoter::EDIT, $ship);

        $form = $this->createForm(ShipCalendarType::class, $ship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Session date updated');
            return $this->redirectToRoute('app_ship_calendar', ['id' => $ship->getId()]);
        }

        return $this->render('ship/calendar.html.twig', [
            'ship' => $ship,
            'form' => $form->createView(),
        ]);
    }
}
