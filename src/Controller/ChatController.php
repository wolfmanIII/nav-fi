<?php

namespace App\Controller;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpClient\Exception\TransportException;

final class ChatController extends BaseController
{
    public const CONTROLLER_NAME = 'ChatController';

    #[Route('/ai/console', name: 'app_ai_console', methods: ['GET'])]
    public function console(HttpClientInterface $httpClient): Response
    {
        try {
            $response = $httpClient->request(
                'GET',
                'http://127.0.0.1:8000/engine/status'
            );
            $elaraStatus = $response->toArray(false);
            $testMode = ($elaraStatus["test_mode"] ?? 'false') === 'true';
            $offlineFallback = ($elaraStatus["offline_fallback"] ?? 'true') === 'true';
            $elaraIsReachable = true;
        } catch (TransportException $e) {
            $testMode = false;
            $offlineFallback = false;
            $elaraIsReachable = false;
        }

        

        return $this->render('ai/console.html.twig', [
            'controller_name'   => self::CONTROLLER_NAME,
            'test_mode'         => $testMode,
            'offline_fallback'  => $offlineFallback,
            'elara_reachable' => $elaraIsReachable,
        ]);
    }

    #[Route('/elara/api/chat', name: 'elara_api_chat', methods: ['POST'])]
    public function chat(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        
        $response = $httpClient->request(
            'POST',
            'http://127.0.0.1:8000/api/chat',
            ["json" => $data]
        );

        $data = $response->toArray(false);

        return $this->json([
            'question' => $data["question"],
            'answer'   => $data["answer"],
        ]);
    }
}
