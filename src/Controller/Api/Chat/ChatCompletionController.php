<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Chat;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * 标准聊天完成API
 */
final class ChatCompletionController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/chat/{chatId}/completions', name: 'api_chat_completions', methods: ['POST'])]
    public function __invoke(string $chatId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid JSON data',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['messages']) || !is_array($data['messages'])) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Messages array is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            /** @var array<int, array<string, mixed>> $messages */
            $messages = $data['messages'];
            unset($data['messages']);

            /** @var array<string, mixed> $options */
            $options = $data;
            $result = $this->conversationService->chatCompletion($chatId, $messages, $options);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chat completion successful',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Chat completion failed',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
