<?php

namespace App\Controller;

use App\Entity\Crew;
use App\Form\CrewType;
use App\Security\Voter\CrewVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CrewController extends BaseController
{
    public const CONTROLLER_NAME = 'CrewController';

    #[Route('/crew/index', name: 'app_crew_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $crew = $user ? $em->getRepository(Crew::class)->findAllForUser($user) : [];

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

        return $this->renderTurbo('crew/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'crew'            => $crew,
            'form'            => $form,
        ]);
    }

    #[Route('/crew/edit/{id}', name: 'app_crew_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $crew = $em->getRepository(Crew::class)->findOneForUser($id, $user);
        if (!$crew) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $form = $this->createForm(CrewType::class, $crew);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_crew_index');
        }

        return $this->renderTurbo('crew/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'crew'            => $crew,
            'form'            => $form,
        ]);
    }

    #[Route('/crew/delete/{id}', name: 'app_crew_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $crew = $em->getRepository(Crew::class)->findOneForUser($id, $user);
        if (!$crew) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        if (!$this->isGranted(CrewVoter::DELETE, $crew)) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($crew);
        $em->flush();

        return $this->redirectToRoute('app_crew_index');
    }
}
