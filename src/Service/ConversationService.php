<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClientInterface;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Conversation;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Request\ChatCompletionRequest;
use Tourze\RAGFlowApiBundle\Request\CreateChatSessionRequest;
use Tourze\RAGFlowApiBundle\Request\CreateConversationRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteChatAssistantRequest;
use Tourze\RAGFlowApiBundle\Request\GetConversationHistoryRequest;
use Tourze\RAGFlowApiBundle\Request\ListChatAssistantsRequest;
use Tourze\RAGFlowApiBundle\Request\OpenAIChatCompletionRequest;
use Tourze\RAGFlowApiBundle\Request\SendMessageRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateChatAssistantRequest;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

class ConversationService
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
        private readonly LocalDataSyncService $localDataSyncService,
        private readonly DatasetRepository $datasetRepository,
    ) {
    }

    private function getClient(): RAGFlowApiClient
    {
        $client = $this->instanceManager->getDefaultClient();
        assert($client instanceof RAGFlowApiClient);

        return $client;
    }

    /**
     * 创建新的聊天助手
     * @param array<string> $datasetIds
     * @param array<string, mixed>|null $options
     */
    public function createChatAssistant(string $name, array $datasetIds = [], ?array $options = null): ChatAssistant
    {
        $request = new CreateConversationRequest($name, $datasetIds, $options);
        $apiResponse = $this->getClient()->request($request);

        $dataset = $this->getFirstDataset($datasetIds);

        if (!is_array($apiResponse)) {
            throw new \RuntimeException('API response should be an array');
        }

        /** @var array<string, mixed> $apiResponse */
        return $this->localDataSyncService->syncChatAssistantFromApiWithDataset($dataset, $apiResponse);
    }

    /**
     * 获取所有聊天助手
     * @param array<string, mixed> $filters
     * @return ChatAssistant[]
     */
    public function listChatAssistants(array $filters = []): array
    {
        $listParams = $this->prepareListParams($filters);
        $request = new ListChatAssistantsRequest(...$listParams);
        $apiResponse = $this->getClient()->request($request);

        return $this->processAssistantListResponse($apiResponse);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: int, 1: int, 2: string|null, 3: bool, 4: string|null, 5: string|null}
     */
    private function prepareListParams(array $filters): array
    {
        $page = $this->extractIntParam($filters, 'page', 1);
        $pageSize = $this->extractPageSizeParam($filters);
        $orderby = $this->extractStringParam($filters, 'orderby');
        $desc = isset($filters['desc']) && is_bool($filters['desc']) ? $filters['desc'] : true;
        $id = $this->extractStringParam($filters, 'id');
        $name = $this->extractStringParam($filters, 'name');

        return [$page, $pageSize, $orderby, $desc, $id, $name];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function extractIntParam(array $filters, string $key, int $default): int
    {
        return isset($filters[$key]) && is_numeric($filters[$key]) ? (int) $filters[$key] : $default;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function extractPageSizeParam(array $filters): int
    {
        if (isset($filters['page_size']) && is_numeric($filters['page_size'])) {
            return (int) $filters['page_size'];
        }

        if (isset($filters['pageSize']) && is_numeric($filters['pageSize'])) {
            return (int) $filters['pageSize'];
        }

        return 30;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function extractStringParam(array $filters, string $key): ?string
    {
        return isset($filters[$key]) && is_string($filters[$key]) ? $filters[$key] : null;
    }

    /**
     * 处理助手列表响应
     *
     * @param mixed $apiResponse
     * @return array<ChatAssistant>
     */
    private function processAssistantListResponse($apiResponse): array
    {
        if (!is_array($apiResponse) || !isset($apiResponse['data']) || !is_array($apiResponse['data'])) {
            return [];
        }

        $chatAssistants = [];
        foreach ($apiResponse['data'] as $assistantData) {
            $assistant = $this->processAssistantData($assistantData);
            if (null !== $assistant) {
                $chatAssistants[] = $assistant;
            }
        }

        return $chatAssistants;
    }

    /**
     * 处理单个助手数据
     *
     * @param mixed $assistantData
     */
    private function processAssistantData($assistantData): ?ChatAssistant
    {
        if (!is_array($assistantData) || !isset($assistantData['dataset_id'])) {
            return null;
        }

        $datasetId = $assistantData['dataset_id'];
        if (!is_string($datasetId)) {
            return null;
        }

        $dataset = $this->getLocalDatasetByRemoteId($datasetId);
        if (null === $dataset) {
            return null;
        }

        /** @var array<string, mixed> $assistantData */
        return $this->localDataSyncService->syncChatAssistantFromApiWithDataset($dataset, $assistantData);
    }

    /**
     * 更新聊天助手
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function updateChatAssistant(string $chatId, array $config): array
    {
        $request = new UpdateChatAssistantRequest($chatId, $config);

        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * 删除聊天助手
     */
    public function deleteChatAssistant(string $chatId): bool
    {
        $request = new DeleteChatAssistantRequest($chatId);
        $this->getClient()->request($request);

        return true;
    }

    /**
     * 创建聊天会话
     * @param array<string, mixed>|null $options
     * @return array<string, mixed>
     */
    public function createSession(string $chatId, ?array $options = null): array
    {
        $request = new CreateChatSessionRequest($chatId, $options);

        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * 向聊天助手发送消息/问题
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function sendMessage(string $chatId, string $question, array $options = []): array
    {
        $request = new SendMessageRequest($chatId, $question, $options);

        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * 获取聊天历史/消息
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function getHistory(string $chatId, array $options = []): array
    {
        $request = new GetConversationHistoryRequest($chatId, $options);

        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * 标准聊天补全
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function chatCompletion(string $chatId, array $messages, array $options = []): array
    {
        $request = new ChatCompletionRequest($chatId, $messages, $options);

        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * OpenAI 兼容的聊天补全
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, mixed>|null $options
     * @return array<string, mixed>
     */
    public function openAIChatCompletion(string $chatId, string $model, array $messages, bool $stream = false, ?array $options = null): array
    {
        $request = new OpenAIChatCompletionRequest($chatId, $model, $messages, $stream, $options);

        $result = $this->getClient()->request($request);
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * @param array<string> $datasetIds
     */
    private function getFirstDataset(array $datasetIds): Dataset
    {
        if ([] === $datasetIds) {
            throw new \InvalidArgumentException('At least one dataset ID is required');
        }

        $firstDatasetId = $datasetIds[0];
        $dataset = $this->getLocalDatasetByRemoteId($firstDatasetId);

        if (null === $dataset) {
            throw new \RuntimeException(sprintf('Local dataset not found for remote ID: %s', $firstDatasetId));
        }

        return $dataset;
    }

    private function getLocalDatasetByRemoteId(string $remoteDatasetId): ?Dataset
    {
        return $this->datasetRepository->findOneBy([
            'remoteId' => $remoteDatasetId,
            'ragFlowInstance' => $this->getClient()->getInstance(),
        ]);
    }
}
