<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * 为聊天助手创建会话
 */
final class CreateSessionController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/chat-assistants/{assistantId}/sessions', name: 'api_chat_assistants_create_session', methods: ['POST'])]
    public function __invoke(string $assistantId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            /** @var array<string, mixed>|null $options */
            $options = is_array($data) ? $data : null;

            $result = $this->conversationService->createSession($assistantId, $options);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chat session created successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to create chat session',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
