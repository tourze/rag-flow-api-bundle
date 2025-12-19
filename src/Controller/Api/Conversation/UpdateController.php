<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Conversation;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * 更新对话
 */
final class UpdateController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/conversations/{chatId}', name: 'api_conversations_update', methods: ['PUT', 'PATCH'])]
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

            /** @var array<string, mixed> $updateConfig */
            $updateConfig = $data;
            $result = $this->conversationService->updateChatAssistant($chatId, $updateConfig);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Conversation updated successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to update conversation',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
