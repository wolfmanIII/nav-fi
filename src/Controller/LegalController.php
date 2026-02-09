<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends BaseController
{
    #[Route('/legal/fair-use', name: 'app_legal_fair_use')]
    public function fairUse(): Response
    {
        return $this->render('legal/fair_use.html.twig');
    }
}
