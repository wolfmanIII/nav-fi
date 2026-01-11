<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Route;
use App\Entity\Ship;
use App\Form\RouteType;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route as RouteAttr;

final class RouteController extends BaseController
{
    public const CONTROLLER_NAME = 'RouteController';

    #[RouteAttr('/route/index', name: 'app_route_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'name',
            'ship' => ['type' => 'int'],
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $routes = [];
        $total = 0;
        $ships = [];
        $campaigns = [];

        if ($user instanceof \App\Entity\User) {
            $result = $em->getRepository(Route::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $routes = $result['items'];
            $total = $result['total'];

            $ships = $em->getRepository(Ship::class)->findAllForUser($user);
            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('route/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'routes' => $routes,
            'filters' => $filters,
            'ships' => $ships,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
        ]);
    }

    #[RouteAttr('/route/new', name: 'app_route_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $route = new Route();
        $form = $this->createForm(RouteType::class, $route, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($route);
            $em->flush();

            return $this->redirectToRoute('app_route_index');
        }

        return $this->renderTurbo('route/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'form' => $form,
        ]);
    }

    #[RouteAttr('/route/edit/{id}', name: 'app_route_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(RouteType::class, $route, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_route_index');
        }

        return $this->renderTurbo('route/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'form' => $form,
        ]);
    }

    #[RouteAttr('/route/details/{id}', name: 'app_route_details', methods: ['GET'])]
    public function details(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            throw new NotFoundHttpException();
        }

        $startHex = $route->getStartHex();
        if (!$startHex && $route->getWaypoints()->count() > 0) {
            $startHex = $route->getWaypoints()->first()?->getHex();
        }
        $mapUrl = $startHex ? 'https://travellermap.com/?p='.rawurlencode($startHex) : 'https://travellermap.com';

        return $this->render('route/details.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'mapUrl' => $mapUrl,
        ]);
    }

    #[RouteAttr('/route/delete/{id}', name: 'app_route_delete', methods: ['GET', 'POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $route = $em->getRepository(Route::class)->findOneForUser($id, $user);
        if (!$route) {
            throw new NotFoundHttpException();
        }

        $em->remove($route);
        $em->flush();

        return $this->redirectToRoute('app_route_index');
    }
}
