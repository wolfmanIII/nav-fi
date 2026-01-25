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

        // Get session_id from query if provided
        $sessionId = $request->query->get('session_id');
        $activeSession = null;

        if ($sessionId) {
            $activeSession = $this->sessionRepo->find($sessionId);
            // Verify ownership through campaign
            if ($activeSession && $activeSession->getCampaign()->getUser() !== $user) {
                $activeSession = null;
            }
        }

        $campaign = $activeSession?->getCampaign();

        // Get all campaigns
        $allCampaigns = $this->entityManager->getRepository(Campaign::class)->findAll();

        // Get all DRAFT sessions for the user (across all campaigns)
        $draftSessions = $this->sessionRepo->createQueryBuilder('s')
            ->join('s.campaign', 'c')
            ->where('c.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', BrokerSession::STATUS_DRAFT)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        if ($activeSession) {
            return $this->render('cube/console.html.twig', [
                'active_session' => $activeSession,
                'campaign' => $campaign,
            ]);
        }

        return $this->render('cube/dashboard.html.twig', [
            'active_session' => null,
            'campaign' => $campaign,
            'campaigns' => $allCampaigns,
            'draft_sessions' => $draftSessions,
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
        return $this->redirectToRoute('app_cube_index', ['session_id' => $session->getId()]);
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
        /** @var \App\Dto\Cube\CubeOpportunityData[] $opportunities */
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
            return !isset($savedSignatures[$opp->signature]);
        }));

        // Serialize DTOs to array for JSON response
        $serialized = array_map(fn($opp) => $opp->toArray(), $filtered);

        return $this->json($serialized);
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
    #[Route('/unsave/{id}', name: 'app_cube_unsave', methods: ['POST'])]
    public function unsave(int $id, EntityManagerInterface $em): JsonResponse
    {
        $opportunity = $em->getRepository(\App\Entity\BrokerOpportunity::class)->find($id);

        if (!$opportunity) {
            return $this->json(['error' => 'Opportunity not found'], 404);
        }

        $data = $opportunity->getData();
        $em->remove($opportunity);
        $em->flush();

        return $this->json([
            'status' => 'removed',
            'data' => $data
        ]);
    }

    #[Route('/contract/{id}', name: 'app_cube_contract_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $opportunity = $em->getRepository(\App\Entity\BrokerOpportunity::class)->find($id);

        if (!$opportunity) {
            throw $this->createNotFoundException('Contract Manifest not found.');
        }

        // Fetch assets for the selection modal
        // TODO: Filter assets by campaign or location if needed?
        // For now, show all assets belonging to the user/campaign
        $campaign = $opportunity->getSession()->getCampaign();
        $assets = $em->getRepository(\App\Entity\Asset::class)->findBy([
            'campaign' => $campaign
        ]);

        return $this->render('cube/show.html.twig', [
            'opportunity' => $opportunity,
            'assets' => $assets
        ]);
    }

    #[Route('/contract/{id}/accept', name: 'app_cube_contract_accept', methods: ['POST'])]
    public function accept(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $opportunity = $em->getRepository(\App\Entity\BrokerOpportunity::class)->find($id);
        if (!$opportunity) {
            throw $this->createNotFoundException('Contract not found.');
        }

        $assetId = $request->request->get('asset_id');
        $asset = $em->getRepository(\App\Entity\Asset::class)->find($assetId);

        if (!$asset) {
            $this->addFlash('error', 'Invalid Asset selected.');
            return $this->redirectToRoute('app_cube_contract_show', ['id' => $id]);
        }

        try {
            $overrides = [
                'day' => $request->request->get('day'),
                'year' => $request->request->get('year'),
                'deadline_day' => $request->request->get('deadline_day'),
                'deadline_year' => $request->request->get('deadline_year'),
            ];

            $this->brokerService->acceptOpportunity($opportunity, $asset, array_filter($overrides));
            $this->addFlash('success', 'Contract Accepted! Financial records updated.');

            // Redirect to the Asset's ledger or back to console?
            // For now, back to console seems appropriate.
            return $this->redirectToRoute('app_cube_index', ['session_id' => $opportunity->getSession()->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error accepting contract: ' . $e->getMessage());
            return $this->redirectToRoute('app_cube_contract_show', ['id' => $id]);
        }
    }
    #[Route('/session/delete/{id}', name: 'app_cube_session_delete', methods: ['POST'])]
    public function deleteSession(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $session = $this->sessionRepo->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        // Verify ownership (or security voter)
        if ($session->getCampaign()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $session->getId(), $request->request->get('_token'))) {
            $em->remove($session);
            $em->flush();
            $this->addFlash('success', 'Session deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid token.');
        }

        return $this->redirectToRoute('app_cube_index');
    }
}
