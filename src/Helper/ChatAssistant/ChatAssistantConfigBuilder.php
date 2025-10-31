<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\ChatAssistant;

use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;

/**
 * 聊天助手配置构建器
 *
 * 负责构建LLM和Prompt配置
 */
final readonly class ChatAssistantConfigBuilder
{
    /**
     * 构建LLM配置
     *
     * @return array<string, mixed>|null
     */
    public function buildLlmConfig(ChatAssistant $entity): ?array
    {
        $llm = [];

        if (null !== $entity->getLlmModel()) {
            $llm['model_name'] = $entity->getLlmModel();
        }
        if (null !== $entity->getTemperature()) {
            $llm['temperature'] = $entity->getTemperature();
        }
        if (null !== $entity->getTopP()) {
            $llm['top_p'] = $entity->getTopP();
        }
        if (null !== $entity->getPresencePenalty()) {
            $llm['presence_penalty'] = $entity->getPresencePenalty();
        }
        if (null !== $entity->getFrequencyPenalty()) {
            $llm['frequency_penalty'] = $entity->getFrequencyPenalty();
        }

        return [] === $llm ? null : $llm;
    }

    /**
     * 构建Prompt配置
     *
     * @return array<string, mixed>
     */
    public function buildPromptConfig(ChatAssistant $entity): array
    {
        $prompt = [];

        if (null !== $entity->getSimilarityThreshold()) {
            $prompt['similarity_threshold'] = $entity->getSimilarityThreshold();
        }
        if (null !== $entity->getKeywordsSimilarityWeight()) {
            $prompt['keywords_similarity_weight'] = $entity->getKeywordsSimilarityWeight();
        }
        if (null !== $entity->getTopN()) {
            $prompt['top_n'] = $entity->getTopN();
        }
        if (null !== $entity->getVariables()) {
            $prompt['variables'] = $entity->getVariables();
        }
        if (null !== $entity->getRerankModel()) {
            $prompt['rerank_model'] = $entity->getRerankModel();
        }
        if (null !== $entity->getTopK()) {
            $prompt['top_k'] = $entity->getTopK();
        }
        if (null !== $entity->getEmptyResponse()) {
            $prompt['empty_response'] = $entity->getEmptyResponse();
        }
        if (null !== $entity->getOpener()) {
            $prompt['opener'] = $entity->getOpener();
        }
        if (null !== $entity->getSystemPrompt()) {
            $prompt['prompt'] = $entity->getSystemPrompt();
        }

        $prompt['show_quote'] = $entity->getShowQuote();

        return $prompt;
    }

    /**
     * 构建完整的API数据
     *
     * @return array<string, mixed>
     */
    public function buildApiData(ChatAssistant $entity): array
    {
        $data = [
            'name' => $entity->getName(),
            'dataset_ids' => $entity->getDatasetIds() ?? [],
        ];

        $data = $this->addOptionalFields($data, $entity);
        $data = $this->addLlmConfig($data, $entity);

        return $this->addPromptConfig($data, $entity);
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
     * 添加Prompt配置
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
}
