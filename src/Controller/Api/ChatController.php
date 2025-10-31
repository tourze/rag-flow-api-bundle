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
 * AI聊天API Controller
 *
 * 提供聊天完成、OpenAI兼容接口的RESTful API
 */
#[Route(path: '/api/v1/chat', name: 'api_chat_')]
final class ChatController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    /**
     * 标准聊天完成API
     */
    #[Route(path: '/{chatId}/completions', name: 'completions', methods: ['POST'])]
    public function chatCompletion(string $chatId, Request $request): JsonResponse
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

    /**
     * OpenAI兼容的聊天完成API
     */
    #[Route(path: '/openai/{chatId}/completions', name: 'openai_completions', methods: ['POST'])]
    public function openAIChatCompletion(string $chatId, Request $request): JsonResponse
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

            // 验证OpenAI格式必需字段
            if (!isset($data['model']) || !is_string($data['model']) || '' === $data['model']) {
                return new JsonResponse([
                    'error' => [
                        'message' => 'Model is required',
                        'type' => 'invalid_request_error',
                        'param' => 'model',
                        'code' => null,
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['messages']) || !is_array($data['messages'])) {
                return new JsonResponse([
                    'error' => [
                        'message' => 'Messages array is required',
                        'type' => 'invalid_request_error',
                        'param' => 'messages',
                        'code' => null,
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }

            $model = $data['model'];
            /** @var array<int, array<string, mixed>> $messages */
            $messages = $data['messages'];
            $stream = isset($data['stream']) && is_bool($data['stream']) ? $data['stream'] : false;

            // 提取其他选项
            /** @var array<string, mixed> $options */
            $options = $data;
            unset($options['model'], $options['messages'], $options['stream']);

            $result = $this->conversationService->openAIChatCompletion(
                $chatId,
                $model,
                $messages,
                $stream,
                [] === $options ? null : $options
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => [
                    'message' => $e->getMessage(),
                    'type' => 'server_error',
                    'param' => null,
                    'code' => null,
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 发送消息到聊天助手
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

            if (!isset($data['question']) || !is_string($data['question']) || '' === $data['question']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Question is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $question = $data['question'];
            unset($data['question']);

            /** @var array<string, mixed> $options */
            $options = $data;
            $result = $this->conversationService->sendMessage($chatId, $question, $options);

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
     * 获取聊天历史
     */
    #[Route(path: '/{chatId}/history', name: 'get_history', methods: ['GET'])]
    public function getHistory(string $chatId, Request $request): JsonResponse
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
