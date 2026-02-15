<?php

namespace App\Controller\Cube;

use App\Entity\BrokerOpportunity;
use App\Entity\Asset;
use App\Repository\AssetRepository;
use App\Service\Cube\BrokerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\LocalLaw;
use App\Entity\User;
use App\Entity\Company;
use App\Entity\CompanyRole;
use App\Service\CompanyManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cube/contract', name: 'app_cube_contract_')]
#[IsGranted('ROLE_USER')]
class ContractController extends AbstractController
{
    public function __construct(
        private readonly BrokerService $brokerService,
        private readonly AssetRepository $assetRepo,
        private readonly EntityManagerInterface $em,
        private readonly CompanyManager $companyManager,
    ) {}

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(BrokerOpportunity $opportunity): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Security check: l'opportunità deve appartenere a una campagna accessibile dall'utente
        $campaign = $opportunity->getSession()?->getCampaign();
        if ($campaign && $campaign->getUser() !== $user) {
            throw $this->createAccessDeniedException("SECURITY ALERT: Accesso non autorizzato ai dati tattici della campagna.");
        }

        // Check if patron exists as an existing Company for this user
        $existingPatron = null;
        $patronName = $opportunity->getData()['details']['patron'] ?? null;
        if ($patronName) {
            // Centralizzato tramite CompanyManager (SOLID)
            $existingPatron = $this->companyManager->findByCanonical($patronName, $user);
        }

        return $this->render('cube/show.html.twig', [
            'opportunity' => $opportunity,
            'assets' => $this->assetRepo->findBy(['campaign' => $campaign]), // Filtro per Campagna
            'localLaws' => $this->em->getRepository(LocalLaw::class)->findAll(),
            'companyRoles' => $this->em->getRepository(CompanyRole::class)->findAll(),
            'existingPatron' => $existingPatron,
        ]);
    }

    #[Route('/{id}/accept', name: 'accept', methods: ['POST'])]
    public function accept(Request $request, BrokerOpportunity $opportunity): Response
    {
        if ($opportunity->getStatus() === 'CONVERTED') {
            $this->addFlash('warning', 'Contract already signed.');
            return $this->redirectToRoute('app_cube_contract_show', ['id' => $opportunity->getId()]);
        }

        $assetId = $request->request->get('asset_id');
        $asset = $this->assetRepo->find($assetId);

        if (!$asset) {
            $this->addFlash('error', 'Invalid asset selected.');
            return $this->redirectToRoute('app_cube_contract_show', ['id' => $opportunity->getId()]);
        }

        // Extract dates and overrides from form
        $overrides = [
            'startDay' => $request->request->get('day'),
            'startYear' => $request->request->get('year'),
            'deadlineDay' => $request->request->get('deadline_day'),
            'deadlineYear' => $request->request->get('deadline_year'),
            'local_law_id' => $request->request->get('localLaw'),
            'patron_role_id' => $request->request->get('patron_role_id'),
            'patron_company_id' => $request->request->get('patron_company_id'),
        ];

        try {
            $result = $this->brokerService->acceptOpportunity($opportunity, $asset, $overrides);

            $type = $opportunity->getData()['type'] ?? 'JOB';
            $msg = ($type === 'TRADE')
                ? 'Trade deal executed. Cargo loaded and account debited.'
                : 'Contract accepted. Mission added to ledger.';

            $this->addFlash('success', $msg);

            // Redirect to the Asset's ledger or dashboard (TBD, for now back to show)
            return $this->redirectToRoute('app_cube_contract_show', ['id' => $opportunity->getId()]);
        } catch (\Throwable $e) { // Passaggio a Throwable per catturare tutti gli errori fatali
            $this->addFlash('error', 'CRITICAL SYSTEM ERROR signing contract: ' . $e->getMessage());
            return $this->redirectToRoute('app_cube_contract_show', ['id' => $opportunity->getId()]);
        }
    }
}
