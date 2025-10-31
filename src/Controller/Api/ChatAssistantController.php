<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * 聊天助手API Controller
 *
 * 提供聊天助手管理、会话创建的RESTful API接口
 */
#[Route(path: '/api/v1/chat-assistants', name: 'api_chat_assistants_')]
final class ChatAssistantController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    /**
     * 获取聊天助手列表
     */
    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
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

    /**
     * 创建聊天助手
     */
    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
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

            if (!isset($data['name']) || !is_string($data['name']) || '' === $data['name']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Assistant name is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $name = $data['name'];
            /** @var array<string> $datasetIds */
            $datasetIds = $data['dataset_ids'] ?? [];
            /** @var array<string, mixed>|null $options */
            $options = isset($data['options']) && is_array($data['options']) ? $data['options'] : null;

            $result = $this->conversationService->createChatAssistant($name, $datasetIds, $options);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chat assistant created successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to create chat assistant',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 更新聊天助手
     */
    #[Route(path: '/{assistantId}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(string $assistantId, Request $request): JsonResponse
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

            /** @var array<string, mixed> $config */
            $config = $data;
            $result = $this->conversationService->updateChatAssistant($assistantId, $config);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chat assistant updated successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to update chat assistant',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 删除聊天助手
     */
    #[Route(path: '/{assistantId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $assistantId): JsonResponse
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

    /**
     * 为聊天助手创建会话
     */
    #[Route(path: '/{assistantId}/sessions', name: 'create_session', methods: ['POST'])]
    public function createSession(string $assistantId, Request $request): JsonResponse
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
