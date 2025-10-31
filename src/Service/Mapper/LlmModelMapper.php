<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service\Mapper;

use Tourze\RAGFlowApiBundle\Entity\LlmModel;

/**
 * LLM Model映射器
 */
final class LlmModelMapper
{
    /**
     * @param array<string, mixed> $apiData
     */
    public function mapApiDataToEntity(LlmModel $llmModel, array $apiData, string $providerName): void
    {
        if (isset($apiData['llm_name']) && is_string($apiData['llm_name'])) {
            $llmModel->setLlmName($apiData['llm_name']);
        }
        $llmModel->setProviderName($providerName);

        $available = $apiData['available'] ?? false;
        $llmModel->setAvailable(is_bool($available) ? $available : false);

        $modelType = $apiData['model_type'] ?? 'unknown';
        $llmModel->setModelType(is_string($modelType) ? $modelType : 'unknown');

        $this->mapOptionalFields($llmModel, $apiData);
        $this->mapTimestamps($llmModel, $apiData);
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapOptionalFields(LlmModel $llmModel, array $apiData): void
    {
        if (isset($apiData['max_tokens']) && is_int($apiData['max_tokens'])) {
            $llmModel->setMaxTokens($apiData['max_tokens']);
        }

        if (isset($apiData['status']) && is_int($apiData['status'])) {
            $llmModel->setStatus($apiData['status']);
        }

        if (isset($apiData['is_tools']) && is_bool($apiData['is_tools'])) {
            $llmModel->setIsTools($apiData['is_tools']);
        }

        if (isset($apiData['tags']) && is_array($apiData['tags'])) {
            /** @var array<string> $tags */
            $tags = array_values(array_filter($apiData['tags'], 'is_string'));
            $llmModel->setTags($tags);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapTimestamps(LlmModel $llmModel, array $apiData): void
    {
        $this->mapCreateTimestamps($llmModel, $apiData);
        $this->mapUpdateTimestamps($llmModel, $apiData);
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapCreateTimestamps(LlmModel $llmModel, array $apiData): void
    {
        if (isset($apiData['create_date']) && is_string($apiData['create_date'])) {
            $createDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $apiData['create_date']);
            if (false !== $createDate) {
                $llmModel->setApiCreateDate($createDate);
            }
        }

        if (isset($apiData['create_time']) && is_string($apiData['create_time'])) {
            $createTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $apiData['create_time']);
            if (false !== $createTime) {
                $llmModel->setApiCreateTime($createTime);
            }
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapUpdateTimestamps(LlmModel $llmModel, array $apiData): void
    {
        if (isset($apiData['update_date']) && is_string($apiData['update_date'])) {
            $updateDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $apiData['update_date']);
            if (false !== $updateDate) {
                $llmModel->setApiUpdateDate($updateDate);
            }
        }

        if (isset($apiData['update_time']) && is_string($apiData['update_time'])) {
            $updateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $apiData['update_time']);
            if (false !== $updateTime) {
                $llmModel->setApiUpdateTime($updateTime);
            }
        }
    }
}
