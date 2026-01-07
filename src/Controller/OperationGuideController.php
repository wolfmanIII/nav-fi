<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OperationGuideController extends BaseController
{
    const CONTROLLER_NAME = "OperationGuideController";

    #[Route('/operations/guide', name: 'app_operation_guide')]
    public function index(): Response
    {
        return $this->render('guide/operations.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }
}
