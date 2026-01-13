<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpClient\Exception\TransportException;

final class ChatController extends BaseController
{
    public const CONTROLLER_NAME = 'ChatController';
    private string $navfiBaseUrl;

    public function __construct(
        #[Autowire(env: 'NAVFI_API_TOKEN')] private readonly string $navfiApiToken,
        #[Autowire(env: 'NAVFI_BASE_URL')] string $navfiBaseUrl,
    ) {
        $this->navfiBaseUrl = rtrim($navfiBaseUrl, '/');
    }

    #[Route('/ai/console', name: 'app_ai_console', methods: ['GET'])]
    public function console(HttpClientInterface $httpClient): Response
    {
        $testMode = false;
        $offlineFallback = false;
        $navfiIsReachable = false;

        try {
            $response = $httpClient->request(
                'GET',
                sprintf('%s/status/engine', $this->navfiBaseUrl),
                [
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $this->navfiApiToken),
                        'Accept'        => 'application/json',
                    ],
                    // Nav-Fi risponde 302 prima del 200, quindi seguiamo i redirect
                    'max_redirects' => 5,
                ]
            );
            $status = $response->getStatusCode();
            if (in_array($status, [Response::HTTP_OK, Response::HTTP_FOUND], true)) {
                $navfiStatus = json_decode($response->getContent(false), true) ?? [];
                $testMode = ($navfiStatus["test_mode"] ?? 'false') === 'true';
                $offlineFallback = ($navfiStatus["offline_fallback"] ?? 'true') === 'true';
                $navfiIsReachable = true;
            }
        } catch (TransportException $e) {
            // keep defaults
        }



        return $this->render('ai/console.html.twig', [
            'controller_name'   => self::CONTROLLER_NAME,
            'test_mode'         => $testMode,
            'offline_fallback'  => $offlineFallback,
            'navfi_reachable' => $navfiIsReachable,
        ]);
    }

    #[Route('/nav-fi/api/chat', name: 'navfi_api_chat', methods: ['POST'])]
    public function chat(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $response = $httpClient->request(
            'POST',
            sprintf('%s/api/chat', $this->navfiBaseUrl),
            [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $this->navfiApiToken),
                    'Accept'        => 'application/json',
                ],
                'json'          => $data,
                // Nav-Fi risponde 302 prima del 200, quindi seguiamo i redirect
                'max_redirects' => 5,
            ]
        );

        $statusCode = $response->getStatusCode();
        $body = $response->getContent(false);
        $payload = json_decode($body, true) ?? [];

        if ($statusCode >= Response::HTTP_BAD_REQUEST || !is_array($payload)) {
            $message = $payload['message'] ?? $payload['error'] ?? 'Errore durante la chiamata al motore Nav-Fi.';

            return $this->json(
                [
                    'error'  => $message,
                    'status' => $statusCode,
                ],
                $statusCode
            );
        }

        return $this->json([
            'question' => $payload["question"] ?? ($data['question'] ?? $data['message'] ?? ''),
            'answer'   => $payload["answer"] ?? $payload["message"] ?? '',
        ]);
    }

    #[Route('/nav-fi/api/chat/stream', name: 'navfi_api_chat_stream', methods: ['POST'])]
    public function chatStream(Request $request, HttpClientInterface $httpClient): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $upstreamResponse = $httpClient->request(
                'POST',
                sprintf('%s/api/chat/stream', $this->navfiBaseUrl),
                [
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $this->navfiApiToken),
                        'Accept'        => 'text/event-stream',
                    ],
                    'json'          => $data,
                    // Nav-Fi risponde 302 prima del 200, quindi seguiamo i redirect
                    'max_redirects' => 5,
                ]
            );
        } catch (TransportException $e) {
            return $this->json(
                [
                    'error'  => 'Errore di comunicazione con il motore Nav-Fi (stream).',
                    'status' => Response::HTTP_BAD_GATEWAY,
                ],
                Response::HTTP_BAD_GATEWAY
            );
        }

        $statusCode = $upstreamResponse->getStatusCode();
        if ($statusCode >= Response::HTTP_BAD_REQUEST) {
            $body = $upstreamResponse->getContent(false);
            $payload = json_decode($body, true) ?? [];
            $message = $payload['message'] ?? $payload['error'] ?? 'Errore durante la chiamata al motore Nav-Fi (stream).';

            return $this->json(
                [
                    'error'  => $message,
                    'status' => $statusCode,
                ],
                $statusCode
            );
        }

        $headers = $upstreamResponse->getHeaders(false);

        $streamedResponse = new StreamedResponse(
            function () use ($httpClient, $upstreamResponse): void {
                foreach ($httpClient->stream($upstreamResponse) as $chunk) {
                    if ($chunk->isTimeout()) {
                        continue;
                    }

                    echo $chunk->getContent();
                    @ob_flush();
                    flush();
                }
            }
        );

        $streamedResponse->headers->set(
            'Content-Type',
            $headers['content-type'][0] ?? 'text/event-stream'
        );
        $streamedResponse->headers->set('X-Accel-Buffering', 'no');
        $streamedResponse->headers->set('Cache-Control', 'no-cache');
        $streamedResponse->setStatusCode($statusCode);

        return $streamedResponse;
    }
}
