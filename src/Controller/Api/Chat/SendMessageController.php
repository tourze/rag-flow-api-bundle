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
 * 发送消息到聊天助手
 */
final class SendMessageController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/chat/{chatId}/messages', name: 'api_chat_send_message', methods: ['POST'])]
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
}
