<?php

namespace App\Controller;

use App\Entity\Crew;
use App\Form\CrewType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CrewController extends BaseController
{
    public const CONTROLLER_NAME = 'CrewController';

    #[Route('/crew/index', name: 'app_crew_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $crew = $em->getRepository(Crew::class)->findAll();

        return $this->render('crew/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'crew'            => $crew,
        ]);
    }

    #[Route('/crew/new', name: 'app_crew_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $crew = new Crew();
        $form = $this->createForm(CrewType::class, $crew);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($crew);
            $em->flush();

            return $this->redirectToRoute('app_crew_index');
        }

        return $this->renderTurbo('crew/edit.html.twig', $form, [
            'controller_name' => self::CONTROLLER_NAME,
            'crew'            => $crew,
            'form'            => $form->createView(),
        ]);
    }

    #[Route('/crew/edit/{id}', name: 'app_crew_edit', methods: ['GET', 'POST'])]
    public function edit(Crew $crew, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CrewType::class, $crew);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_crew_index');
        }

        return $this->renderTurbo('crew/edit.html.twig', $form, [
            'controller_name' => self::CONTROLLER_NAME,
            'crew'            => $crew,
            'form'            => $form->createView(),
        ]);
    }

    #[Route('/crew/delete/{id}', name: 'app_crew_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, Crew $crew, EntityManagerInterface $em): Response
    {

        $em->remove($crew);
        $em->flush();

        return $this->redirectToRoute('app_crew_index');
    }
}
