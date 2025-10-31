<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service\Mapper;

use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;

/**
 * 聊天助手映射器
 *
 * 负责将API数据映射到ChatAssistant实体
 */
final class ChatAssistantMapper
{
    /**
     * @param array<string, mixed> $apiData
     */
    public function mapApiDataToEntity(ChatAssistant $chatAssistant, array $apiData): void
    {
        $this->mapBasicData($chatAssistant, $apiData);
        $this->mapLlmConfiguration($chatAssistant, $apiData);
        $this->mapPromptConfiguration($chatAssistant, $apiData);
        $this->mapTimestamps($chatAssistant, $apiData);
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapBasicData(ChatAssistant $chatAssistant, array $apiData): void
    {
        $this->mapStringFields($chatAssistant, $apiData);
        $this->mapDatasetIds($chatAssistant, $apiData);
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapStringFields(ChatAssistant $chatAssistant, array $apiData): void
    {
        // 使用闭包映射替代动态方法调用，满足 PHPStan 静态分析要求
        /** @var array<string, \Closure(ChatAssistant, string): void> $fieldMappings */
        $fieldMappings = [
            'name' => static fn (ChatAssistant $m, string $v) => $m->setName($v),
            'description' => static fn (ChatAssistant $m, string $v) => $m->setDescription($v),
            'avatar' => static fn (ChatAssistant $m, string $v) => $m->setAvatar($v),
            'language' => static fn (ChatAssistant $m, string $v) => $m->setLanguage($v),
            'status' => static fn (ChatAssistant $m, string $v) => $m->setStatus($v),
            'prompt_type' => static fn (ChatAssistant $m, string $v) => $m->setPromptType($v),
            'do_refer' => static fn (ChatAssistant $m, string $v) => $m->setDoRefer($v),
            'tenant_id' => static fn (ChatAssistant $m, string $v) => $m->setTenantId($v),
        ];

        foreach ($fieldMappings as $apiKey => $apply) {
            $value = $apiData[$apiKey] ?? null;
            if (!is_string($value)) {
                continue;
            }
            $apply($chatAssistant, $value);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapDatasetIds(ChatAssistant $chatAssistant, array $apiData): void
    {
        if (isset($apiData['dataset_ids']) && is_array($apiData['dataset_ids'])) {
            /** @var array<int, string> $datasetIds */
            $datasetIds = array_filter($apiData['dataset_ids'], 'is_string');
            $chatAssistant->setDatasetIds($datasetIds);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapLlmConfiguration(ChatAssistant $chatAssistant, array $apiData): void
    {
        if (!isset($apiData['llm']) || !is_array($apiData['llm'])) {
            return;
        }

        /** @var array<string, mixed> $llmData */
        $llmData = $apiData['llm'];
        $this->mapLlmFloatFields($chatAssistant, $llmData);
        $this->mapLlmIntegerFields($chatAssistant, $llmData);
        $this->mapLlmModelName($chatAssistant, $llmData);
    }

    /**
     * @param array<string, mixed> $llmData
     */
    private function mapLlmModelName(ChatAssistant $chatAssistant, array $llmData): void
    {
        if (isset($llmData['model_name']) && is_string($llmData['model_name'])) {
            $chatAssistant->setLlmModel($llmData['model_name']);
        }
    }

    /**
     * @param array<string, mixed> $llmData
     */
    private function mapLlmFloatFields(ChatAssistant $chatAssistant, array $llmData): void
    {
        // 使用闭包映射替代动态方法调用，满足 PHPStan 静态分析要求
        /** @var array<string, \Closure(ChatAssistant, float): void> $floatMappings */
        $floatMappings = [
            'temperature' => static fn (ChatAssistant $m, float $v) => $m->setTemperature($v),
            'top_p' => static fn (ChatAssistant $m, float $v) => $m->setTopP($v),
            'presence_penalty' => static fn (ChatAssistant $m, float $v) => $m->setPresencePenalty($v),
            'frequency_penalty' => static fn (ChatAssistant $m, float $v) => $m->setFrequencyPenalty($v),
        ];

        foreach ($floatMappings as $key => $apply) {
            $value = $llmData[$key] ?? null;
            if (!is_float($value)) {
                continue;
            }
            $apply($chatAssistant, $value);
        }
    }

    /**
     * @param array<string, mixed> $llmData
     */
    private function mapLlmIntegerFields(ChatAssistant $chatAssistant, array $llmData): void
    {
        if (isset($llmData['max_tokens']) && is_int($llmData['max_tokens'])) {
            $chatAssistant->setMaxTokens($llmData['max_tokens']);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapPromptConfiguration(ChatAssistant $chatAssistant, array $apiData): void
    {
        // 处理新的prompt结构（直接在根级别）
        if (isset($apiData['prompt']) && is_array($apiData['prompt'])) {
            /** @var array<string, mixed> $promptData */
            $promptData = $apiData['prompt'];
            $this->mapPromptFields($chatAssistant, $promptData);
        }

        // 处理top_k（直接在根级别）
        if (isset($apiData['top_k']) && is_int($apiData['top_k'])) {
            $chatAssistant->setTopK($apiData['top_k']);
        }

        // 兼容旧的prompt_config结构
        if (isset($apiData['prompt_config']) && is_array($apiData['prompt_config'])) {
            /** @var array<string, mixed> $promptConfig */
            $promptConfig = $apiData['prompt_config'];
            $this->mapLegacyPromptConfig($chatAssistant, $promptConfig);
        }
    }

    /**
     * @param array<string, mixed> $promptConfig
     */
    private function mapPromptFields(ChatAssistant $chatAssistant, array $promptConfig): void
    {
        $this->mapPromptNumericFields($chatAssistant, $promptConfig);
        $this->mapPromptStringFields($chatAssistant, $promptConfig);
        $this->mapPromptBooleanFields($chatAssistant, $promptConfig);
        $this->mapPromptVariables($chatAssistant, $promptConfig);
    }

    /**
     * @param array<string, mixed> $promptConfig
     */
    private function mapPromptNumericFields(ChatAssistant $chatAssistant, array $promptConfig): void
    {
        if (isset($promptConfig['similarity_threshold']) && is_numeric($promptConfig['similarity_threshold'])) {
            $chatAssistant->setSimilarityThreshold((float) $promptConfig['similarity_threshold']);
        }

        if (isset($promptConfig['keywords_similarity_weight']) && is_numeric($promptConfig['keywords_similarity_weight'])) {
            $chatAssistant->setKeywordsSimilarityWeight((float) $promptConfig['keywords_similarity_weight']);
        }

        if (isset($promptConfig['top_n']) && is_int($promptConfig['top_n'])) {
            $chatAssistant->setTopN($promptConfig['top_n']);
        }
    }

    /**
     * @param array<string, mixed> $promptConfig
     */
    private function mapPromptStringFields(ChatAssistant $chatAssistant, array $promptConfig): void
    {
        // 使用闭包映射替代动态方法调用，满足 PHPStan 静态分析要求
        /** @var array<string, \Closure(ChatAssistant, string): void> $fieldMappings */
        $fieldMappings = [
            'rerank_model' => static fn (ChatAssistant $m, string $v) => $m->setRerankModel($v),
            'opener' => static fn (ChatAssistant $m, string $v) => $m->setOpener($v),
            'empty_response' => static fn (ChatAssistant $m, string $v) => $m->setEmptyResponse($v),
            'prompt' => static fn (ChatAssistant $m, string $v) => $m->setSystemPrompt($v),
        ];

        foreach ($fieldMappings as $key => $apply) {
            $value = $promptConfig[$key] ?? null;
            if (!is_string($value)) {
                continue;
            }
            $apply($chatAssistant, $value);
        }
    }

    /**
     * @param array<string, mixed> $promptConfig
     */
    private function mapPromptBooleanFields(ChatAssistant $chatAssistant, array $promptConfig): void
    {
        if (isset($promptConfig['show_quote']) && is_bool($promptConfig['show_quote'])) {
            $chatAssistant->setShowQuote($promptConfig['show_quote']);
        }
    }

    /**
     * @param array<string, mixed> $promptConfig
     */
    private function mapPromptVariables(ChatAssistant $chatAssistant, array $promptConfig): void
    {
        if (isset($promptConfig['variables']) && is_array($promptConfig['variables'])) {
            /** @var array<string, mixed> $variables */
            $variables = $promptConfig['variables'];
            $chatAssistant->setVariables($variables);
        }
    }

    /**
     * @param array<string, mixed> $promptConfig
     */
    private function mapLegacyPromptConfig(ChatAssistant $chatAssistant, array $promptConfig): void
    {
        if (isset($promptConfig['opener']) && is_string($promptConfig['opener'])) {
            $chatAssistant->setOpener($promptConfig['opener']);
        }

        if (isset($promptConfig['empty_response']) && is_string($promptConfig['empty_response'])) {
            $chatAssistant->setEmptyResponse($promptConfig['empty_response']);
        }

        if (isset($promptConfig['show_quote']) && is_bool($promptConfig['show_quote'])) {
            $chatAssistant->setShowQuote($promptConfig['show_quote']);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapTimestamps(ChatAssistant $chatAssistant, array $apiData): void
    {
        if (isset($apiData['create_time'])) {
            $timestamp = $this->convertTimestamp($apiData['create_time']);
            $chatAssistant->setRemoteCreateTime(new \DateTimeImmutable('@' . $timestamp));
        }

        if (isset($apiData['update_time'])) {
            $timestamp = $this->convertTimestamp($apiData['update_time']);
            $chatAssistant->setRemoteUpdateTime(new \DateTimeImmutable('@' . $timestamp));
        }
    }

    /**
     * 转换时间戳
     *
     * 支持多种时间格式：
     * - 数值型（毫秒级时间戳）
     * - 字符串型（ISO 8601、'Y-m-d H:i:s'等strtotime支持的格式）
     *
     * @param mixed $timeValue
     */
    private function convertTimestamp($timeValue): int
    {
        if (is_numeric($timeValue)) {
            return (int) ((float) $timeValue / 1000);
        }

        if (is_string($timeValue)) {
            $timestamp = strtotime($timeValue);

            return false !== $timestamp ? $timestamp : 0;
        }

        return 0;
    }
}
