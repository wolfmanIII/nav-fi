<?php

namespace App\Controller;

use App\Entity\BrokerSession;
use App\Entity\Campaign;
use App\Repository\BrokerSessionRepository;
use App\Service\Cube\BrokerService;
use App\Service\TravellerMapSectorLookup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cube')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CubeController extends AbstractController
{
    public function __construct(
        private readonly BrokerService $brokerService,
        private readonly BrokerSessionRepository $sessionRepo,
        private readonly TravellerMapSectorLookup $travellerMap,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/', name: 'app_cube_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        // Assuming current campaign context logic or selecting first active campaign
        // For MVP, we'll try to get the campaign from query param or session, fallback to latest

        // Simpler: Just get the latest active session for the user's campaigns, or show setup
        // We'll require a campaign_id in the query for now if not session bound

        // Mocking campaign context for MVP: select first valid campaign
        $campaign = $request->query->get('campaign_id')
            ? $this->entityManager->getRepository(Campaign::class)->find($request->query->get('campaign_id'))
            : null;

        $activeSession = null;
        if ($campaign) {
            // Check if user wants to force a new session
            $forceNew = $request->query->getBoolean('force_new', false);

            if (!$forceNew) {
                // Try to resume latest DRAFT session
                $activeSession = $this->sessionRepo->findLatestDraftByCampaign($campaign->getId());
            }
        }

        $allCampaigns = $this->entityManager->getRepository(Campaign::class)->findAll();

        return $this->render('cube/index.html.twig', [
            'active_session' => $activeSession,
            'campaign' => $campaign,
            'campaigns' => $allCampaigns,
        ]);
    }

    #[Route('/session/new', name: 'app_cube_session_new', methods: ['POST'])]
    public function newSession(Request $request, EntityManagerInterface $em): Response
    {
        $campaignId = $request->request->get('campaign_id');
        $sector = $request->request->get('sector');
        $hex = $request->request->get('hex');
        $range = (int) $request->request->get('range', 2);

        $campaign = $em->getRepository(Campaign::class)->find($campaignId);
        if (!$campaign) {
            throw $this->createNotFoundException('Campaign not found');
        }

        $session = $this->brokerService->createSession($campaign, $sector, $hex, $range);

        $this->addFlash('success', 'Broker Session initialized.');
        return $this->redirectToRoute('app_cube_index', ['campaign_id' => $campaign->getId()]);
    }

    #[Route('/generate/{id}', name: 'app_cube_generate', methods: ['GET'])]
    public function generate(BrokerSession $session): JsonResponse
    {
        // 1. Get Full Sector Data context (to find neighbors)
        // We use parseSector to get all systems
        $systems = $this->travellerMap->parseSector($session->getSector());

        // Find Origin Data in the list
        $originData = null;
        $originHex = $session->getOriginHex();

        foreach ($systems as $sys) {
            if ($sys['hex'] === $originHex) {
                $originData = $sys;
                break;
            }
        }

        if (!$originData) {
            return $this->json(['error' => 'Origin World not found in Sector Data'], 404);
        }

        // Pass ALL systems to the engine so it can pick destinations
        $opportunities = $this->brokerService->generateOpportunities($session, $originData, $systems);

        // Filter out already saved opportunities
        // Optimization: For MVP we iterate. TBD: JSON query if list grows.
        $savedOpps = $session->getOpportunities();
        $savedSignatures = [];
        foreach ($savedOpps as $saved) {
            $data = $saved->getData();
            if (isset($data['signature'])) {
                $savedSignatures[$data['signature']] = true;
            }
        }

        $filtered = array_values(array_filter($opportunities, function ($opp) use ($savedSignatures) {
            // Keep if signature is NOT in saved list
            return !isset($savedSignatures[$opp['signature'] ?? '']);
        }));

        return $this->json($filtered);
    }

    #[Route('/save/{id}', name: 'app_cube_save', methods: ['POST'])]
    public function save(Request $request, BrokerSession $session): JsonResponse
    {
        try {
            $payload = $request->toArray();

            // Check if already saved (Double submission protection)
            // Or if strict uniqueness is needed

            $opp = $this->brokerService->saveOpportunity($session, $payload);

            return $this->json(['status' => 'saved', 'id' => $opp->getId()]);
        } catch (\Exception $e) {
            // Log for debugging
            // $this->logger->error(...) - needing logger injection or simplified usage
            error_log('Cube Save Error: ' . $e->getMessage());

            return $this->json(['error' => 'Persistence Failed: ' . $e->getMessage()], 500);
        }
    }
    #[Route('/contract/{id}', name: 'app_cube_contract_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $opportunity = $em->getRepository(\App\Entity\BrokerOpportunity::class)->find($id);

        if (!$opportunity) {
            throw $this->createNotFoundException('Contract Manifest not found.');
        }

        return $this->render('cube/show.html.twig', [
            'opportunity' => $opportunity,
        ]);
    }
}
