<?php

namespace App\Controller;

use App\Entity\Crew;
use App\Entity\Ship;
use App\Form\CrewType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class CrewController extends AbstractController
{
    const CONTROLLER_NAME = "CrewController";

    #[Route('/crew', name: 'app_crew_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $crew = $em->getRepository(Crew::class)->findAll();
        return $this->render('crew/index.html.twig', [
            'controller_name' => 'CrewController',
            'crew' => $crew,
        ]);
    }

    #[Route('/crew/new', name: 'app_crew_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $crew = new Crew();
        $form = $this->createForm(CrewType::class, $crew);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $crew->setCode(Uuid::v7());

            $em->persist($crew);
            $em->flush();
            return $this->redirectToRoute('app_crew_index');
        }

        return $this->render('crew/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'crew' => $crew,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/crew/edit/{id}', name: 'app_crew_edit', methods: ['GET', 'POST'])]
    public function edit(Crew $crew, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CrewType::class, $crew);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($crew);
            $em->flush();
            return $this->redirectToRoute('app_crew_index');
        }

        return $this->render('crew/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'crew' => $crew,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/crew/delete/{id}', name: 'app_crew_delete', methods: ['GET', 'POST'])]
    public function delete(Crew $crew, Request $request, EntityManagerInterface $em): Response
    {
        $em->remove($crew);
        $em->flush();

        return $this->redirectToRoute('app_crew_index');
    }
}
