<?php

namespace App\Controller\Api;

use App\Service\TravellerMapDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sector', name: 'api_sector_')]
class SectorController extends AbstractController
{
    public function __construct(
        private readonly TravellerMapDataService $dataService
    ) {}

    #[Route('/worlds', name: 'worlds', methods: ['GET'])]
    public function worlds(Request $request): JsonResponse
    {
        $sector = $request->query->get('sector');
        if (!$sector) {
            return new JsonResponse(['error' => 'Missing sector parameter'], 400);
        }

        $worldsMap = $this->dataService->getWorldsForSector($sector);
        $worlds = [];
        foreach ($worldsMap as $label => $value) {
            $worlds[] = ['label' => $label, 'value' => $value];
        }

        return new JsonResponse($worlds);
    }
}
