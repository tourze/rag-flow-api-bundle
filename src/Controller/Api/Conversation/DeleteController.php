<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Conversation;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * 删除对话
 */
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/conversations/{chatId}', name: 'api_conversations_delete', methods: ['DELETE'])]
    public function __invoke(string $chatId): JsonResponse
    {
        try {
            $this->conversationService->deleteChatAssistant($chatId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Conversation deleted successfully',
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete conversation',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
