<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Route;
use App\Entity\RouteWaypoint;
use App\Entity\Asset;
use App\Form\RouteType;
use App\Service\ListViewHelper;
use App\Service\TravellerMapSectorLookup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route as RouteAttr;
use App\Entity\User;

final class RouteController extends BaseController
{
    public const CONTROLLER_NAME = 'RouteController';

    #[RouteAttr('/route/index', name: 'app_route_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'name',
            'asset' => ['type' => 'int'],
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $routes = [];
        $total = 0;
        $assets = [];
        $campaigns = [];

        if ($user instanceof User) {
            $result = $em->getRepository(Route::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $routes = $result['items'];
            $total = $result['total'];

            $assets = $em->getRepository(Asset::class)->findAllForUser($user);
            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        // Context for Navbar
        $asset = null;
        if (!empty($filters['asset'])) {
            $asset = $em->getRepository(Asset::class)->find($filters['asset']);
        }

        return $this->render('route/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'routes' => $routes,
            'filters' => $filters,
            'assets' => $assets,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
            'asset' => $asset, // Pass context
        ]);
    }

    #[RouteAttr('/route/new', name: 'app_route_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $route = new Route();
        // Try to prepopulate asset from query if exists
        $assetId = $request->query->get('asset');
        if ($assetId) {
            $asset = $em->getRepository(Asset::class)->find($assetId);
            if ($asset) {
                $route->setAsset($asset);
            }
        }

        $form = $this->createForm(RouteType::class, $route, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($route);
            $em->flush();

            return $this->redirectToRoute('app_route_index');
        }

        if ($form->isSubmitted() && !$form->isValid() && $route->getWaypoints()->count() === 0) {
            $route->addWaypoint(new RouteWaypoint());
            $form = $this->createForm(RouteType::class, $route, ['user' => $user]);
        }

        return $this->renderTurbo('route/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'form' => $form,

            'asset' => $route->getAsset(),
        ]);
    }

    #[RouteAttr('/route/edit/{id}', name: 'app_route_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
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

        if ($form->isSubmitted() && !$form->isValid() && $route->getWaypoints()->count() === 0) {
            $this->addFlash('error', 'Add at least one waypoint to define a route.');
            return $this->redirectToRoute('app_route_edit', ['id' => $route->getId()]);
        }

        return $this->renderTurbo('route/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'form' => $form,
            'asset' => $route->getAsset(),
        ]);
    }

    #[RouteAttr('/route/details/{id}', name: 'app_route_details', methods: ['GET'])]
    public function details(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            throw new NotFoundHttpException();
        }

        $startHex = $route->getStartHex();
        $sector = null;
        $queryHex = trim((string) $request->query->get('marker_hex'));
        $querySector = trim((string) $request->query->get('marker_sector'));
        if ($queryHex !== '') {
            $startHex = $queryHex;
            $sector = $querySector !== '' ? $querySector : null;
        } elseif ($route->getWaypoints()->count() > 0) {
            $firstWaypoint = $route->getWaypoints()->first() ?: null;
            $startHex = $startHex ?: $firstWaypoint?->getHex();
            $sector = $firstWaypoint?->getSector();
        } else {
            $sector = null;
        }
        $mapUrl = 'https://travellermap.com/';
        if ($startHex && $sector) {
            $mapUrl .= 'go/' . rawurlencode($sector) . '/' . rawurlencode($startHex);
        } elseif ($startHex) {
            $mapUrl .= '?marker_hex=' . rawurlencode($startHex);
        }

        return $this->render('route/details.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'mapUrl' => $mapUrl,
            'currentHex' => $startHex,
            'currentSector' => $sector,
            'asset' => $route->getAsset(),
        ]);
    }

    #[RouteAttr('/route/waypoint-lookup', name: 'app_route_waypoint_lookup', methods: ['GET'])]
    public function waypointLookup(Request $request, TravellerMapSectorLookup $lookup): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $sector = trim((string) $request->query->get('sector'));
        $hex = strtoupper(trim((string) $request->query->get('hex')));
        if ($sector === '' || $hex === '') {
            return new JsonResponse(['found' => false], Response::HTTP_BAD_REQUEST);
        }

        $result = $lookup->lookupWorld($sector, $hex);
        if (!$result) {
            return new JsonResponse(['found' => false]);
        }

        return new JsonResponse([
            'found' => true,
            'world' => $result['world'] ?? null,
            'uwp' => $result['uwp'] ?? null,
        ]);
    }

    #[RouteAttr('/route/delete/{id}', name: 'app_route_delete', methods: ['GET', 'POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
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
