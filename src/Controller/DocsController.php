<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * DocsController
 * Gestisce la visualizzazione della documentazione tecnica e dei manuali operativi dello spazioporto.
 */
final class DocsController extends BaseController
{
    /**
     * Visualizza il manuale operativo dei sistemi di navigazione imperiale.
     * Ritorna la vista del manuale configurata per occupare l'intero spazio HUD.
     */
    #[Route('/docs/navigation', name: 'app_docs_navigation', methods: ['GET'])]
    public function navigation(): Response
    {
        return $this->render('docs/navigation.html.twig');
    }
}
