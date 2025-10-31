<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service\Mapper;

use Tourze\RAGFlowApiBundle\Entity\Dataset;

/**
 * 数据集映射器
 *
 * 负责将API数据映射到Dataset实体
 */
final class DatasetMapper
{
    /**
     * @param array<string, mixed> $apiData
     */
    public function mapApiDataToEntity(Dataset $dataset, array $apiData): void
    {
        $this->mapBasicFields($dataset, $apiData);
        $this->mapTimestamps($dataset, $apiData);
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapBasicFields(Dataset $dataset, array $apiData): void
    {
        // 使用闭包映射替代动态方法调用，满足 PHPStan 静态分析要求
        /** @var array<string, \Closure(Dataset, string): void> $fieldMappings */
        $fieldMappings = [
            'id' => static fn (Dataset $m, string $v) => $m->setRemoteId($v),
            'name' => static fn (Dataset $m, string $v) => $m->setName($v),
            'description' => static fn (Dataset $m, string $v) => $m->setDescription($v),
            'chunk_method' => static fn (Dataset $m, string $v) => $m->setChunkMethod($v),
            'language' => static fn (Dataset $m, string $v) => $m->setLanguage($v),
            'embedding_model' => static fn (Dataset $m, string $v) => $m->setEmbeddingModel($v),
            'status' => static fn (Dataset $m, string $v) => $m->setStatus($v),
        ];

        foreach ($fieldMappings as $apiKey => $apply) {
            $value = $apiData[$apiKey] ?? null;
            if (!is_string($value)) {
                continue;
            }
            $apply($dataset, $value);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapTimestamps(Dataset $dataset, array $apiData): void
    {
        if (isset($apiData['create_time'])) {
            $timestamp = $this->convertTimestamp($apiData['create_time']);
            $dataset->setRemoteCreateTime(new \DateTimeImmutable('@' . $timestamp));
        }

        if (isset($apiData['update_time'])) {
            $timestamp = $this->convertTimestamp($apiData['update_time']);
            $dataset->setRemoteUpdateTime(new \DateTimeImmutable('@' . $timestamp));
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
