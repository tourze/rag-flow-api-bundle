<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service\Mapper;

use Tourze\RAGFlowApiBundle\Entity\Chunk;

/**
 * Chunk映射器
 */
final class ChunkMapper
{
    /**
     * @param array<string, mixed> $apiData
     */
    public function mapApiDataToEntity(Chunk $chunk, array $apiData): void
    {
        $this->mapStringFields($chunk, $apiData);
        $this->mapIntegerFields($chunk, $apiData);
        $this->mapFloatFields($chunk, $apiData);
        $this->mapArrayFields($chunk, $apiData);
        $this->mapTimestamps($chunk, $apiData);
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapStringFields(Chunk $chunk, array $apiData): void
    {
        if (isset($apiData['content']) && is_string($apiData['content'])) {
            $chunk->setContent($apiData['content']);
        }

        if (isset($apiData['content_with_weight']) && is_string($apiData['content_with_weight'])) {
            $chunk->setContentWithWeight($apiData['content_with_weight']);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapIntegerFields(Chunk $chunk, array $apiData): void
    {
        if (isset($apiData['page_number']) && is_numeric($apiData['page_number'])) {
            $chunk->setPageNumber((int) $apiData['page_number']);
        }
        if (isset($apiData['position']) && is_numeric($apiData['position'])) {
            $chunk->setPosition((int) $apiData['position']);
        }
        if (isset($apiData['start_pos']) && is_numeric($apiData['start_pos'])) {
            $chunk->setStartPos((int) $apiData['start_pos']);
        }
        if (isset($apiData['end_pos']) && is_numeric($apiData['end_pos'])) {
            $chunk->setEndPos((int) $apiData['end_pos']);
        }
        if (isset($apiData['token_count']) && is_numeric($apiData['token_count'])) {
            $chunk->setTokenCount((int) $apiData['token_count']);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapFloatFields(Chunk $chunk, array $apiData): void
    {
        if (isset($apiData['similarity_score']) && is_numeric($apiData['similarity_score'])) {
            $chunk->setSimilarityScore((float) $apiData['similarity_score']);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapArrayFields(Chunk $chunk, array $apiData): void
    {
        if (isset($apiData['positions']) && is_array($apiData['positions'])) {
            /** @var array<string, mixed> $positions */
            $positions = $apiData['positions'];
            $chunk->setPositions($positions);
        }

        if (isset($apiData['embedding_vector']) && is_array($apiData['embedding_vector'])) {
            /** @var array<float> $embeddingVector */
            $embeddingVector = array_map('floatval', array_filter($apiData['embedding_vector'], 'is_numeric'));
            $chunk->setEmbeddingVector($embeddingVector);
        }

        if (isset($apiData['keywords']) && is_array($apiData['keywords'])) {
            /** @var array<string> $keywords */
            $keywords = array_values(array_filter($apiData['keywords'], 'is_string'));
            $chunk->setKeywords($keywords);
        }

        if (isset($apiData['metadata']) && is_array($apiData['metadata'])) {
            /** @var array<string, mixed> $metadata */
            $metadata = $apiData['metadata'];
            $chunk->setMetadata($metadata);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapTimestamps(Chunk $chunk, array $apiData): void
    {
        if (isset($apiData['create_time'])) {
            $timestamp = $this->convertTimestamp($apiData['create_time']);
            $chunk->setRemoteCreateTime(new \DateTimeImmutable('@' . $timestamp));
        }

        if (isset($apiData['update_time'])) {
            $timestamp = $this->convertTimestamp($apiData['update_time']);
            $chunk->setRemoteUpdateTime(new \DateTimeImmutable('@' . $timestamp));
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
