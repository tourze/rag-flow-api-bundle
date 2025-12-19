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
 * 获取聊天历史
 */
final class GetHistoryController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/chat/{chatId}/history', name: 'api_chat_get_history', methods: ['GET'])]
    public function __invoke(string $chatId, Request $request): JsonResponse
    {
        try {
            $options = [
                'page' => $request->query->getInt('page', 1),
                'limit' => $request->query->getInt('limit', 50),
                'session_id' => $request->query->get('session_id'),
                'start_time' => $request->query->get('start_time'),
                'end_time' => $request->query->get('end_time'),
            ];

            // 清除空值
            $options = array_filter($options, fn ($value) => null !== $value && '' !== $value);

            $result = $this->conversationService->getHistory($chatId, $options);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chat history retrieved successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve chat history',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
