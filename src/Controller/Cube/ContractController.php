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
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cube/contract', name: 'app_cube_contract_')]
#[IsGranted('ROLE_USER')]
class ContractController extends AbstractController
{

    public function __construct(
        private readonly BrokerService $brokerService,
        private readonly AssetRepository $assetRepo,
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(BrokerOpportunity $opportunity): Response
    {
        // Security check: ensure session belongs to campaign user has access to (TODO)

        return $this->render('cube/show.html.twig', [
            'opportunity' => $opportunity,
            'assets' => $this->assetRepo->findAll(), // TODO: Filter by Campaign
            'localLaws' => $this->em->getRepository(LocalLaw::class)->findAll(),
            'companyRoles' => $this->em->getRepository(\App\Entity\CompanyRole::class)->findAll(),
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
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error signing contract: ' . $e->getMessage());
            return $this->redirectToRoute('app_cube_contract_show', ['id' => $opportunity->getId()]);
        }
    }
}
