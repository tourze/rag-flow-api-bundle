<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\ChatAssistant;

/**
 * LLM模型提取器
 *
 * 负责从API响应中提取和处理LLM模型数据
 */
final readonly class LlmModelExtractor
{
    /**
     * 从API响应中提取模型列表
     *
     * @param array<string, mixed> $response
     * @return array<string, string>
     */
    public function extractModels(array $response): array
    {
        if (!isset($response['data']) || !is_array($response['data'])) {
            return [];
        }

        $choices = [];

        foreach ($response['data'] as $providerName => $models) {
            if (!is_string($providerName)) {
                continue;
            }
            if (is_array($models)) {
                // 确保 $models 是正确的类型结构
                /** @var array<int, array<string, mixed>> $validModels */
                $validModels = array_filter($models, 'is_array');
                $choices = array_merge($choices, $this->extractProviderModels($providerName, $validModels));
            }
        }

        return $choices;
    }

    /**
     * 从提供商模型列表中提取模型
     *
     * @param array<int, array<string, mixed>> $models
     * @return array<string, string>
     */
    private function extractProviderModels(string $providerName, array $models): array
    {
        $choices = [];

        foreach ($models as $model) {
            if ($this->isValidChatModel($model)) {
                // PHPDoc from isValidChatModel ensures these properties exist
                assert(isset($model['llm_name']));
                assert(isset($model['fid']));

                /** @var bool|float|int|string $fid */
                $fid = $model['fid'];

                /** @var string $llmName */
                $llmName = $model['llm_name'];

                $displayName = sprintf('%s (%s)', $llmName, $providerName);
                $choices[$displayName] = (string) $fid;
            }
        }

        /** @var array<string, string> $choices */
        return $choices;
    }

    /**
     * 验证是否为有效的聊天模型
     *
     * @param mixed $model
     */
    public function isValidChatModel($model): bool
    {
        return is_array($model)
            && isset($model['available'])
            && true === $model['available']
            && isset($model['llm_name'])
            && is_string($model['llm_name'])
            && isset($model['model_type'])
            && 'chat' === $model['model_type']
            && isset($model['fid']);
    }

    /**
     * 获取默认的LLM模型选项（fallback）
     *
     * @return array<string, string>
     */
    public function getDefaultModels(): array
    {
        return [
            'DeepSeek Chat (DeepSeek)' => 'deepseek-chat',
            'GPT-3.5 Turbo (OpenAI)' => 'gpt-3.5-turbo',
            'GPT-4 (OpenAI)' => 'gpt-4',
            'Claude 3 Sonnet (Anthropic)' => 'claude-3-sonnet',
        ];
    }
}
