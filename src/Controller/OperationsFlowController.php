<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OperationsFlowController extends BaseController
{
    const CONTROLLER_NAME = 'OperationsFlowController';

    #[Route('/operations/flow', name: 'app_operations_flow')]
    public function index(): Response
    {
        return $this->render('guide/operations_flow.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }
}
