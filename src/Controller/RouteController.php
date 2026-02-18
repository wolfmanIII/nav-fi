<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Route;
use App\Entity\RouteWaypoint;
use App\Entity\Asset;
use App\Form\RouteType;
use App\Form\RouteWaypointType;
use App\Service\ListViewHelper;
use App\Service\RouteWaypointService;
use App\Service\TravellerMapSectorLookup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route as RouteAttr;
use App\Entity\User;
use App\Service\RouteTravelService;
use App\Service\ImperialDateHelper;
use Throwable;

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
    public function new(Request $request, EntityManagerInterface $em, RouteWaypointService $waypointService): Response
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
            $waypointService->syncFirstWaypoint($route);
            $em->persist($route);
            $em->flush();

            return $this->redirectToRoute('app_route_index');
        }

        return $this->renderTurbo('route/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'form' => $form,

            'asset' => $route->getAsset(),
        ]);
    }

    #[RouteAttr('/route/edit/{id}', name: 'app_route_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, RouteWaypointService $waypointService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            throw new NotFoundHttpException();
        }

        // Support dynamic form updates via Turbo Frames
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
            $waypointService->syncFirstWaypoint($route);
            $em->persist($route); // Verify persistence if asset changed
            $em->flush();

            return $this->redirectToRoute('app_route_index');
        }

        return $this->renderTurbo('route/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'form' => $form,
            'asset' => $route->getAsset(),
        ]);
    }

    #[RouteAttr('/route/details/{id}', name: 'app_route_details', methods: ['GET'])]
    public function details(
        Route $route,
        string $sector = null,
        string $hex = null,
        Request $request,
        EntityManagerInterface $em,
        TravellerMapSectorLookup $travellerMap,
        RouteWaypointService $waypointService
    ): Response {
        $user = $this->getUser();
        if ($route->getAsset()->getUser() !== $user) {
            throw $this->createAccessDeniedException('You do not have access to this route.');
        }

        // Handle "Jump to" logic
        $queryHex = trim((string) $request->query->get('marker_hex'));
        $querySector = trim((string) $request->query->get('marker_sector'));

        if ($queryHex !== '') {
            $hex = $queryHex;
            $sector = $querySector !== '' ? $querySector : null;
        } elseif ($route->getWaypoints()->count() > 0) {
            $firstWaypoint = $route->getWaypoints()->first() ?: null;
            $hex = $route->getStartHex() ?: $firstWaypoint?->getHex();
            $sector = $firstWaypoint?->getSector();
        } else {
            $hex = $route->getStartHex();
            $sector = null;
        }

        // URL per Mappa Interattiva Standard (senza overlay per limiti URL/API)
        $mapUrl = $travellerMap->buildMapUrl($sector, $hex);

        // Form per modale aggiunta waypoint
        $waypointForm = $this->createForm(RouteWaypointType::class, new RouteWaypoint());

        return $this->render('route/details.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'route' => $route,
            'mapUrl' => $mapUrl,
            'currentHex' => $hex,
            'currentSector' => $sector,
            'asset' => $route->getAsset(),
            'waypointForm' => $waypointForm->createView(),
            'hasInvalidJumps' => $waypointService->hasInvalidJumps($route),
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

        // Verifica restrizioni operative via Voter
        $this->denyAccessUnlessGranted('route_delete', $route);

        $em->remove($route);
        $em->flush();

        return $this->redirectToRoute('app_route_index');
    }

    #[RouteAttr('/route/{id}/waypoint', name: 'app_route_waypoint_add', methods: ['POST'])]
    public function addWaypoint(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        RouteWaypointService $waypointService,
        TravellerMapSectorLookup $sectorLookup
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            return new JsonResponse(['error' => 'Route not found'], Response::HTTP_NOT_FOUND);
        }

        $waypoint = new RouteWaypoint();
        $form = $this->createForm(RouteWaypointType::class, $waypoint);
        $form->submit($request->request->all()['route_waypoint'] ?? []);

        if (!$form->isValid()) {
            return new JsonResponse(['error' => 'Invalid form data'], Response::HTTP_BAD_REQUEST);
        }

        $waypointService->addWaypoint($route, $waypoint);
        $em->flush();

        // Ottieni zona per JS
        $zone = null;
        if ($waypoint->getSector() && $waypoint->getHex()) {
            $worldData = $sectorLookup->lookupWorld($waypoint->getSector(), $waypoint->getHex());
            $zone = $worldData['zone'] ?? null;
        }

        return new JsonResponse([
            'success' => true,
            'routeActive' => $route->isActive(),
            'waypoint' => [
                'id' => $waypoint->getId(),
                'position' => $waypoint->getPosition(),
                'hex' => $waypoint->getHex(),
                'sector' => $waypoint->getSector(),
                'world' => $waypoint->getWorld(),
                'uwp' => $waypoint->getUwp(),
                'notes' => $waypoint->getNotes(),
                'jumpDistance' => $waypoint->getJumpDistance(),
                'zone' => $zone,
                'deletable' => $this->isGranted('waypoint_delete', $waypoint),
            ],
            // Return FULL list for map update
            'allWaypoints' => $this->serializeWaypoints($route, $sectorLookup),
            'routeFuelEstimate' => $route->getFuelEstimate(),
            'hasInvalidJumps' => $waypointService->hasInvalidJumps($route),
        ]);
    }

    #[RouteAttr('/route/{id}/waypoint/{waypointId}', name: 'app_route_waypoint_delete', methods: ['DELETE'])]
    public function removeWaypoint(
        int $id,
        int $waypointId,
        EntityManagerInterface $em,
        RouteWaypointService $waypointService,
        TravellerMapSectorLookup $sectorLookup
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            return new JsonResponse(['error' => 'Route not found'], Response::HTTP_NOT_FOUND);
        }

        $waypoint = $em->getRepository(RouteWaypoint::class)->find($waypointId);
        if (!$waypoint || $waypoint->getRoute()?->getId() !== $route->getId()) {
            return new JsonResponse(['error' => 'Waypoint not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('waypoint_delete', $waypoint);

        $waypointService->removeWaypoint($route, $waypoint);
        $em->flush();

        // Serialize updated waypoints (id + jumpDistance)
        $updatedWaypoints = [];
        foreach ($route->getWaypoints() as $wp) {
            $updatedWaypoints[] = [
                'id' => $wp->getId(),
                'jumpDistance' => $wp->getJumpDistance(),
                'position' => $wp->getPosition(), // Also update position if needed
            ];
        }

        return new JsonResponse([
            'success' => true,
            'routeActive' => $route->isActive(),
            'updatedWaypoints' => $updatedWaypoints,
            // Return FULL list for map update
            'allWaypoints' => $this->serializeWaypoints($route, $sectorLookup),
            'routeFuelEstimate' => $route->getFuelEstimate(),
            'hasInvalidJumps' => $waypointService->hasInvalidJumps($route),
        ]);
    }

    #[RouteAttr('/route/{id}/recalculate', name: 'app_route_recalculate', methods: ['POST'])]
    public function recalculateRoute(
        int $id,
        EntityManagerInterface $em,
        RouteWaypointService $waypointService,
        TravellerMapSectorLookup $sectorLookup
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            return new JsonResponse(['error' => 'Route not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $waypointService->recalculateRoute($route);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'routeActive' => $route->isActive(),
                'allWaypoints' => $this->serializeWaypoints($route, $sectorLookup),
                'hasInvalidJumps' => $waypointService->hasInvalidJumps($route),
            ]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[RouteAttr('/route/{id}/activate', name: 'app_route_activate', methods: ['POST'])]
    public function activate(
        int $id,
        EntityManagerInterface $em,
        RouteTravelService $travelService,
        TravellerMapSectorLookup $sectorLookup
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            return new JsonResponse(['error' => 'Route not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $travelService->activate($route);
            $em->flush();
            return new JsonResponse([
                'success' => true,
                'routeActive' => $route->isActive(),
                'allWaypoints' => $this->serializeWaypoints($route, $sectorLookup)
            ]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[RouteAttr('/route/{id}/close', name: 'app_route_close', methods: ['POST'])]
    public function close(
        int $id,
        EntityManagerInterface $em,
        RouteTravelService $travelService,
        TravellerMapSectorLookup $sectorLookup
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            return new JsonResponse(['error' => 'Route not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $travelService->close($route);
            $em->flush();
            return new JsonResponse([
                'success' => true,
                'routeActive' => $route->isActive(),
                'allWaypoints' => $this->serializeWaypoints($route, $sectorLookup)
            ]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[RouteAttr('/route/{id}/travel/{direction}', name: 'app_route_travel', methods: ['POST'])]
    public function travel(
        int $id,
        string $direction,
        EntityManagerInterface $em,
        RouteTravelService $travelService,
        ImperialDateHelper $dateHelper,
        TravellerMapSectorLookup $sectorLookup
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $route = $em->getRepository(Route::class)->findOneForUserWithWaypoints($id, $user);
        if (!$route) {
            return new JsonResponse(['error' => 'Route not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $targetWp = $travelService->travel($route, $direction);
            $campaign = $route->getCampaign();

            return new JsonResponse([
                'success' => true,
                'routeActive' => $route->isActive(),
                'activeWaypointId' => $targetWp->getId(),
                'activeWaypointHex' => $targetWp->getHex(),
                'activeWaypointSector' => $targetWp->getSector(),
                'sessionDate' => $campaign ? $dateHelper->format($campaign->getSessionDay(), $campaign->getSessionYear()) : null,
                'allWaypoints' => $this->serializeWaypoints($route, $sectorLookup)
            ]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Helper to serialize all waypoints for map
     */
    #[RouteAttr('/route/{id}/set-bookmark/{waypointId}', name: 'app_route_set_bookmark', methods: ['POST'])]
    public function setBookmark(
        int $id,
        int $waypointId,
        EntityManagerInterface $em,
        RouteWaypointService $waypointService,
        TravellerMapSectorLookup $sectorLookup
    ): JsonResponse {
        $route = $em->getRepository(Route::class)->find($id);
        if (!$route) {
            return new JsonResponse(['error' => 'Route not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted('route_edit', $route);

        if ($route->isActive()) {
            return new JsonResponse(['error' => 'NAV-ERROR: Active link detected. Termination required for re-sync.'], Response::HTTP_BAD_REQUEST);
        }

        $targetWaypoint = $em->getRepository(RouteWaypoint::class)->find($waypointId);
        if (!$targetWaypoint || $targetWaypoint->getRoute()->getId() !== $route->getId()) {
            return new JsonResponse(['error' => 'Waypoint mismatch'], Response::HTTP_NOT_FOUND);
        }

        try {
            foreach ($route->getWaypoints() as $wp) {
                $wp->setActive($wp->getId() === $targetWaypoint->getId());
            }
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'allWaypoints' => $this->serializeWaypoints($route, $sectorLookup),
                'hasInvalidJumps' => $waypointService->hasInvalidJumps($route),
            ]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function serializeWaypoints(Route $route, TravellerMapSectorLookup $sectorLookup): array
    {
        $data = [];
        $sectorCache = [];
        foreach ($route->getWaypoints() as $wp) {
            $zone = null;
            if ($wp->getSector() && $wp->getHex()) {
                if (!isset($sectorCache[$wp->getSector()])) {
                    $sectorCache[$wp->getSector()] = $sectorLookup->parseSector($wp->getSector());
                }

                foreach ($sectorCache[$wp->getSector()] as $system) {
                    if ($system['hex'] === $wp->getHex()) {
                        $zone = $system['zone'] ?? null;
                        break;
                    }
                }
            }

            $data[] = [
                'id' => $wp->getId(),
                'position' => $wp->getPosition(),
                'hex' => $wp->getHex(),
                'sector' => $wp->getSector(),
                'world' => $wp->getWorld(),
                'uwp' => $wp->getUwp(),
                'notes' => $wp->getNotes(),
                'jumpDistance' => $wp->getJumpDistance(),
                'active' => $wp->isActive(),
                'deletable' => $this->isGranted('waypoint_delete', $wp),
                'zone' => $zone,
            ];
        }
        return $data;
    }
}
