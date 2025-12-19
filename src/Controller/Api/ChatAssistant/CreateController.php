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
 * 创建聊天助手
 */
final class CreateController extends AbstractController
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/api/v1/chat-assistants', name: 'api_chat_assistants_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
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
}
