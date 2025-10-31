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
 * 对话API Controller
 *
 * 提供对话创建、消息发送、历史记录等RESTful API接口
 */
#[Route(path: '/api/v1/conversations', name: 'api_conversations_')]
final class ConversationController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    /**
     * 创建对话会话
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

            if (!isset($data['name']) || '' === $data['name']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Conversation name is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $name = $data['name'];
            if (!is_string($name)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Name must be a string',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            /** @var array<string> $datasetIds */
            $datasetIds = isset($data['dataset_ids']) && is_array($data['dataset_ids']) ? $data['dataset_ids'] : [];
            /** @var array<string, mixed>|null $options */
            $options = isset($data['options']) && is_array($data['options']) ? $data['options'] : null;

            $result = $this->conversationService->createChatAssistant($name, $datasetIds, $options);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Conversation created successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to create conversation',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 获取对话列表
     */
    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $filters = [
                'page' => $request->query->getInt('page', 1),
                'limit' => $request->query->getInt('limit', 20),
                'name' => $request->query->get('name'),
            ];

            // 清除空值
            $filters = array_filter($filters, fn ($value) => null !== $value && '' !== $value);

            $result = $this->conversationService->listChatAssistants($filters);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Conversations retrieved successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve conversations',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 更新对话
     */
    #[Route(path: '/{chatId}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(string $chatId, Request $request): JsonResponse
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

    /**
     * 删除对话
     */
    #[Route(path: '/{chatId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $chatId): JsonResponse
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

    /**
     * 创建对话会话
     */
    #[Route(path: '/{chatId}/sessions', name: 'create_session', methods: ['POST'])]
    public function createSession(string $chatId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            /** @var array<string, mixed>|null $options */
            $options = is_array($data) ? $data : null;

            $result = $this->conversationService->createSession($chatId, $options);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Session created successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to create session',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 发送消息
     */
    #[Route(path: '/{chatId}/messages', name: 'send_message', methods: ['POST'])]
    public function sendMessage(string $chatId, Request $request): JsonResponse
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

            if (!isset($data['question']) || '' === $data['question']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Question is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $question = $data['question'];
            if (!is_string($question)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Question must be a string',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            unset($data['question']);

            /** @var array<string, mixed> $messageOptions */
            $messageOptions = $data;
            $result = $this->conversationService->sendMessage($chatId, $question, $messageOptions);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Message sent successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 获取对话历史
     */
    #[Route(path: '/{chatId}/history', name: 'get_history', methods: ['GET'])]
    public function getHistory(string $chatId, Request $request): JsonResponse
    {
        try {
            $options = [
                'page' => $request->query->getInt('page', 1),
                'limit' => $request->query->getInt('limit', 50),
                'session_id' => $request->query->get('session_id'),
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
