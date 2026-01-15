<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FinancialCoreGuideController extends BaseController
{
    const CONTROLLER_NAME = 'FinancialCoreGuideController';

    #[Route('/guide/financial-core', name: 'app_financial_core_guide')]
    public function index(): Response
    {
        return $this->render('guide/financial_core_guide.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }
}
