<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * 删除聊天助手
 */
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/chat-assistants/{assistantId}', name: 'api_chat_assistants_delete', methods: ['DELETE'])]
    public function __invoke(string $assistantId): JsonResponse
    {
        try {
            $this->conversationService->deleteChatAssistant($assistantId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chat assistant deleted successfully',
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete chat assistant',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
