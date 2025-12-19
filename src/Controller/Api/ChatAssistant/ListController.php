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
 * 获取聊天助手列表
 */
final class ListController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/chat-assistants', name: 'api_chat_assistants_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $filters = [
                'page' => $request->query->getInt('page', 1),
                'limit' => $request->query->getInt('limit', 20),
                'name' => $request->query->get('name'),
                'status' => $request->query->get('status'),
            ];

            // 清除空值
            $filters = array_filter($filters, fn ($value) => null !== $value && '' !== $value);

            $result = $this->conversationService->listChatAssistants($filters);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chat assistants retrieved successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve chat assistants',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
