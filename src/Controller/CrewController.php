<?php

namespace App\Controller;

use App\Entity\Crew;
use App\Entity\Campaign;
use App\Entity\Ship;
use App\Form\CrewType;
use App\Security\Voter\CrewVoter;
use App\Service\ListViewHelper;
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
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'search',
            'ship' => ['type' => 'int'],
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $crew = [];
        $total = 0;
        $totalPages = 1;
        $ships = [];
        $campaigns = [];

        if ($user instanceof \App\Entity\User) {
            $result = $em->getRepository(Crew::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $crew = $result['items'];
            $total = $result['total'];

            $totalPages = max(1, (int) ceil($total / $perPage));
            $clampedPage = $listViewHelper->clampPage($page, $totalPages);
            if ($clampedPage !== $page) {
                $page = $clampedPage;
                $result = $em->getRepository(Crew::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $crew = $result['items'];
            }

            $ships = $em->getRepository(Ship::class)->findAllForUser($user);
            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('crew/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'crew'            => $crew,
            'filters'         => $filters,
            'ships'           => $ships,
            'campaigns'       => $campaigns,
            'pagination'      => $pagination,
        ]);
    }

    #[Route('/crew/new', name: 'app_crew_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $crew = new Crew();
        $form = $this->createForm(CrewType::class, $crew, [
            'user' => $user,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
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
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $crew = $em->getRepository(Crew::class)->findOneForUser($id, $user);
        if (!$crew) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $form = $this->createForm(CrewType::class, $crew, [
            'user' => $user,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
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
    public function delete(
        Request $request,
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $crew = $em->getRepository(Crew::class)->findOneForUser($id, $user);
        if (!$crew) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(CrewVoter::DELETE, $crew);

        $em->remove($crew);
        $em->flush();

        return $this->redirectToRoute('app_crew_index');
    }

}
