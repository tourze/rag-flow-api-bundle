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
 * OpenAI兼容的聊天完成API
 */
final class OpenAIChatCompletionController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/chat/openai/{chatId}/completions', name: 'api_chat_openai_completions', methods: ['POST'])]
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
}
