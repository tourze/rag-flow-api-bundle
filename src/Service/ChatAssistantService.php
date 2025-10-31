<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use HttpClientBundle\Request\RequestInterface;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Repository\LlmModelRepository;
use Tourze\RAGFlowApiBundle\Request\CreateChatAssistantRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteChatAssistantRequest;
use Tourze\RAGFlowApiBundle\Request\ListChatAssistantsRequest;
use Tourze\RAGFlowApiBundle\Request\ListLlmModelsRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateChatAssistantRequest;

class ChatAssistantService
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
        private readonly LocalDataSyncService $localDataSyncService,
        private readonly LlmModelRepository $llmModelRepository,
    ) {
    }

    private function getClient(): RAGFlowApiClient
    {
        $client = $this->instanceManager->getDefaultClient();
        assert($client instanceof RAGFlowApiClient);

        return $client;
    }

    /**
     * 获取聊天助手列表
     * @param array<string, mixed>|null $filters
     * @return array<string, mixed>
     */
    public function list(int $page = 1, int $pageSize = 30, ?array $filters = null): array
    {
        $params = $this->extractFilterParameters($filters);
        $request = $this->createListRequest($page, $pageSize, $params);
        $response = $this->executeRequest($request);

        $this->syncResponseDataToLocal($response);

        return $response;
    }

    /**
     * 提取过滤参数
     *
     * @param array<string, mixed>|null $filters
     * @return array<string, mixed>
     */
    private function extractFilterParameters(?array $filters): array
    {
        if (null === $filters) {
            return [
                'orderby' => null,
                'desc' => true,
                'id' => null,
                'name' => null,
            ];
        }

        return [
            'orderby' => $this->extractStringFilter($filters, 'orderby'),
            'desc' => $this->extractBoolFilter($filters, 'desc', true),
            'id' => $this->extractStringFilter($filters, 'id'),
            'name' => $this->extractStringFilter($filters, 'name'),
        ];
    }

    /**
     * 提取字符串过滤条件
     *
     * @param array<string, mixed> $filters
     */
    private function extractStringFilter(array $filters, string $key): ?string
    {
        return isset($filters[$key]) && is_string($filters[$key]) ? $filters[$key] : null;
    }

    /**
     * 提取布尔过滤条件
     *
     * @param array<string, mixed> $filters
     */
    private function extractBoolFilter(array $filters, string $key, bool $default = false): bool
    {
        return isset($filters[$key]) && is_bool($filters[$key]) ? $filters[$key] : $default;
    }

    /**
     * 创建列表请求
     *
     * @param array<string, mixed> $params
     */
    private function createListRequest(int $page, int $pageSize, array $params): ListChatAssistantsRequest
    {
        $orderby = isset($params['orderby']) && is_string($params['orderby']) ? $params['orderby'] : null;
        $desc = isset($params['desc']) && is_bool($params['desc']) ? $params['desc'] : true;
        $id = isset($params['id']) && is_string($params['id']) ? $params['id'] : null;
        $name = isset($params['name']) && is_string($params['name']) ? $params['name'] : null;

        return new ListChatAssistantsRequest(
            page: $page,
            pageSize: $pageSize,
            orderby: $orderby,
            desc: $desc,
            id: $id,
            name: $name
        );
    }

    /**
     * 执行请求并验证响应
     *
     * @return array<string, mixed>
     */
    private function executeRequest(RequestInterface $request): array
    {
        try {
            $response = $this->getClient()->request($request);

            if (!is_array($response)) {
                throw new \RuntimeException('API response is not an array');
            }

            // 确保所有键为字符串类型
            $result = [];
            foreach ($response as $key => $value) {
                $result[(string) $key] = $value;
            }

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to execute API request: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * 同步响应数据到本地
     *
     * @param array<string, mixed> $response
     */
    private function syncResponseDataToLocal(array $response): void
    {
        foreach ($response as $chatAssistantData) {
            if (!is_array($chatAssistantData)) {
                continue;
            }

            // 确保所有键为字符串类型
            $normalizedData = [];
            foreach ($chatAssistantData as $key => $value) {
                $normalizedData[(string) $key] = $value;
            }

            $this->localDataSyncService->syncChatAssistantFromApi(
                $normalizedData,
                $this->getClient()->getInstance()
            );
        }
    }

    /**
     * 创建聊天助手
     * @param ChatAssistant $entity
     * @return array<string, mixed>
     */
    public function create(ChatAssistant $entity): array
    {
        $name = $entity->getName();
        $datasetIds = $entity->getDatasetIds() ?? [];
        $avatar = $entity->getAvatar();
        $llm = $this->buildLlmConfig($entity);
        $prompt = $this->buildPromptConfig($entity);

        $request = new CreateChatAssistantRequest($name, $datasetIds, $avatar, $llm, $prompt);
        $response = $this->executeRequest($request);

        $this->syncSingleItemToLocal($response);

        return $response;
    }

    /**
     * 更新聊天助手
     * @param string $chatId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(string $chatId, array $data): array
    {
        $request = new UpdateChatAssistantRequest($chatId, $data);
        $response = $this->executeRequest($request);

        $this->syncSingleItemToLocal($response);

        return $response;
    }

    /**
     * 删除聊天助手
     * @param string $chatId
     * @return array<string, mixed>
     */
    public function delete(string $chatId): array
    {
        $request = new DeleteChatAssistantRequest($chatId);
        $response = $this->executeRequest($request);

        $this->syncDeleteToLocal($response, $chatId);

        return $response;
    }

    /**
     * 同步单个项目到本地
     *
     * @param array<string, mixed> $response
     */
    private function syncSingleItemToLocal(array $response): void
    {
        $this->localDataSyncService->syncChatAssistantFromApi(
            $response,
            $this->getClient()->getInstance()
        );
    }

    /**
     * 同步删除到本地
     *
     * @param array<string, mixed> $response
     */
    private function syncDeleteToLocal(array $response, string $chatId): void
    {
        if (isset($response['code']) && is_int($response['code']) && 0 === $response['code']) {
            $this->localDataSyncService->deleteChatAssistant($chatId, $this->getClient()->getInstance());
        }
    }

    /**
     * 根据ID获取单个聊天助手
     * @param string $chatId
     * @return array<string, mixed>|null
     */
    public function getById(string $chatId): ?array
    {
        $response = $this->list(filters: ['id' => $chatId]);

        if (isset($response['data']) && is_array($response['data']) && count($response['data']) > 0) {
            $firstItem = $response['data'][0];

            if (!is_array($firstItem)) {
                return null;
            }

            // 确保所有键为字符串类型
            $result = [];
            foreach ($firstItem as $key => $value) {
                $result[(string) $key] = $value;
            }

            return $result;
        }

        return null;
    }

    /**
     * 转换ChatAssistant实体为API数据格式
     * @param ChatAssistant $entity
     * @return array<string, mixed>
     */
    public function convertToApiData(ChatAssistant $entity): array
    {
        $data = $this->buildBaseData($entity);
        $data = $this->addOptionalFields($data, $entity);
        $data = $this->addLlmConfig($data, $entity);

        return $this->addPromptConfig($data, $entity);
    }

    /**
     * 构建基础数据
     *
     * @return array<string, mixed>
     */
    private function buildBaseData(ChatAssistant $entity): array
    {
        return [
            'name' => $entity->getName(),
            'dataset_ids' => $entity->getDatasetIds() ?? [],
        ];
    }

    /**
     * 添加可选字段
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function addOptionalFields(array $data, ChatAssistant $entity): array
    {
        $optionalFields = [
            'description' => $entity->getDescription(),
            'prompt' => $entity->getSystemPrompt(),
            'avatar' => $entity->getAvatar(),
            'language' => $entity->getLanguage(),
        ];

        foreach ($optionalFields as $key => $value) {
            if (null !== $value) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * 收集非空值到数组
     *
     * @param array<string, mixed> $pairs
     * @return array<string, mixed>
     */
    private function collectNotNullValues(array $pairs): array
    {
        return array_filter($pairs, fn ($value) => null !== $value);
    }

    /**
     * 构建LLM配置
     *
     * @return array<string, mixed>|null
     */
    private function buildLlmConfig(ChatAssistant $entity): ?array
    {
        $llm = $this->collectNotNullValues([
            'model_name' => $entity->getLlmModel(),
            'temperature' => $entity->getTemperature(),
            'top_p' => $entity->getTopP(),
            'presence_penalty' => $entity->getPresencePenalty(),
            'frequency_penalty' => $entity->getFrequencyPenalty(),
        ]);

        return [] === $llm ? null : $llm;
    }

    /**
     * 添加LLM配置
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function addLlmConfig(array $data, ChatAssistant $entity): array
    {
        $llm = $this->buildLlmConfig($entity);
        if (null !== $llm) {
            $data['llm'] = $llm;
        }

        return $data;
    }

    /**
     * 构建Prompt配置
     *
     * @return array<string, mixed>
     */
    private function buildPromptConfig(ChatAssistant $entity): array
    {
        $prompt = $this->collectNotNullValues([
            'similarity_threshold' => $entity->getSimilarityThreshold(),
            'keywords_similarity_weight' => $entity->getKeywordsSimilarityWeight(),
            'top_n' => $entity->getTopN(),
            'variables' => $entity->getVariables(),
            'rerank_model' => $entity->getRerankModel(),
            'top_k' => $entity->getTopK(),
            'empty_response' => $entity->getEmptyResponse(),
            'opener' => $entity->getOpener(),
            'prompt' => $entity->getSystemPrompt(),
        ]);

        $prompt['show_quote'] = $entity->getShowQuote();

        return $prompt;
    }

    /**
     * 添加提示配置
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function addPromptConfig(array $data, ChatAssistant $entity): array
    {
        $prompt = $this->buildPromptConfig($entity);
        $data['prompt'] = $prompt;

        return $data;
    }

    /**
     * 获取可用的LLM模型列表
     * @return array<string, string> 模型选择项数组，key为模型名称，value为显示名称
     */
    public function getAvailableLlmModels(): array
    {
        try {
            $request = new ListLlmModelsRequest();
            $response = $this->executeRequest($request);

            return $this->extractModelsFromResponse($response);
        } catch (\Exception $e) {
            // 如果API调用失败，从本地数据库获取数据作为fallback
            return $this->getAvailableLlmModelsFromLocal();
        }
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, string>
     */
    private function extractModelsFromResponse(array $response): array
    {
        $choices = [];

        // 新的API响应结构按提供商分组
        if (!isset($response['data']) || !is_array($response['data'])) {
            return $choices;
        }

        // 同步LLM模型数据到本地
        $this->syncLlmModelsToLocal($response['data']);

        foreach ($response['data'] as $providerName => $models) {
            if (is_array($models) && is_string($providerName)) {
                /** @var array<int, array<string, mixed>> $typedModels */
                $typedModels = $models;
                $choices = array_merge($choices, $this->extractProviderModels($providerName, $typedModels));
            }
        }

        return $choices;
    }

    /**
     * @param array<int, array<string, mixed>> $models
     * @return array<string, string>
     */
    private function extractProviderModels(string $providerName, array $models): array
    {
        $choices = [];

        foreach ($models as $model) {
            if ($this->isValidChatModel($model)) {
                assert(is_string($model['llm_name']));
                assert(is_string($model['fid']));
                $displayName = sprintf('%s (%s)', $model['llm_name'], $providerName);
                $choices[$displayName] = $model['fid'];
            }
        }

        return $choices;
    }

    /**
     * @param mixed $model
     */
    private function isValidChatModel($model): bool
    {
        if (!is_array($model)) {
            return false;
        }

        if (!isset($model['available']) || true !== $model['available']) {
            return false;
        }

        if (!isset($model['llm_name']) || !is_string($model['llm_name'])) {
            return false;
        }

        if (!isset($model['model_type']) || 'chat' !== $model['model_type']) {
            return false;
        }

        return isset($model['fid']);
    }

    /**
     * 从本地数据库获取可用的LLM模型列表（作为fallback）
     * @return array<string, string>
     */
    private function getAvailableLlmModelsFromLocal(): array
    {
        try {
            $instance = $this->getClient()->getInstance();

            return $this->llmModelRepository->getChoicesForEasyAdmin($instance, 'chat');
        } catch (\Exception $e) {
            // 如果本地数据也获取失败，返回默认选项
            return [
                'DeepSeek Chat (DeepSeek)' => 'deepseek-chat',
                'GPT-3.5 Turbo (OpenAI)' => 'gpt-3.5-turbo',
                'GPT-4 (OpenAI)' => 'gpt-4',
                'Claude 3 Sonnet (Anthropic)' => 'claude-3-sonnet',
            ];
        }
    }

    /**
     * 同步LLM模型数据到本地数据库
     *
     * @param array<mixed> $llmData
     */
    private function syncLlmModelsToLocal(array $llmData): void
    {
        try {
            $instance = $this->getClient()->getInstance();
            // 确保 llmData 是 array<string, mixed>
            /** @var array<string, mixed> $typedLlmData */
            $typedLlmData = $llmData;
            $this->localDataSyncService->syncLlmModelsFromApi($typedLlmData, $instance);
        } catch (\Exception $e) {
            // 忽略同步错误，不影响主要功能
        }
    }
}
