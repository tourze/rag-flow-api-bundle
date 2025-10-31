<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service\Mapper;

use Tourze\RAGFlowApiBundle\Entity\Document;

/**
 * 文档映射器
 *
 * 负责将API数据映射到Document实体
 */
final class DocumentMapper
{
    /**
     * @param array<string, mixed> $apiData
     */
    public function mapApiDataToEntity(Document $document, array $apiData): void
    {
        $this->mapBasicFields($document, $apiData);
        $this->mapStatusFields($document, $apiData);
        $this->mapParserConfig($document, $apiData);
        $this->mapTimestamps($document, $apiData);
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapBasicFields(Document $document, array $apiData): void
    {
        $this->mapStringFields($document, $apiData);
        $this->mapNumericFields($document, $apiData);
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapStringFields(Document $document, array $apiData): void
    {
        if (isset($apiData['id']) && is_string($apiData['id'])) {
            $document->setRemoteId($apiData['id']);
        }
        if (isset($apiData['name']) && is_string($apiData['name'])) {
            $document->setName($apiData['name']);
        }
        if (isset($apiData['filename']) && is_string($apiData['filename'])) {
            $document->setFilename($apiData['filename']);
        }
        if (isset($apiData['type']) && is_string($apiData['type'])) {
            $document->setType($apiData['type']);
        }
        if (isset($apiData['language']) && is_string($apiData['language'])) {
            $document->setLanguage($apiData['language']);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapNumericFields(Document $document, array $apiData): void
    {
        if (isset($apiData['size']) && is_numeric($apiData['size'])) {
            $document->setSize((int) $apiData['size']);
        }

        if (isset($apiData['chunk_num']) && is_numeric($apiData['chunk_num'])) {
            $document->setChunkCount((int) $apiData['chunk_num']);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapStatusFields(Document $document, array $apiData): void
    {
        if (isset($apiData['status']) && is_string($apiData['status'])) {
            $document->setStatus($apiData['status']);
        }

        if (isset($apiData['progress']) && is_numeric($apiData['progress'])) {
            // 将API进度（0-1范围）转换为百分比（0-100范围）
            $progress = (float) $apiData['progress'];
            if ($progress <= 1.0) {
                $progress = $progress * 100;
            }
            $document->setProgress($progress);
        }

        if (isset($apiData['progress_msg']) && is_string($apiData['progress_msg'])) {
            $document->setProgressMsg($apiData['progress_msg']);
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapParserConfig(Document $document, array $apiData): void
    {
        if (isset($apiData['parser_config']) && is_array($apiData['parser_config'])) {
            /** @var array<string, mixed> $parserConfig */
            $parserConfig = $apiData['parser_config'];
            if (isset($parserConfig['chunk_token_num']) && is_numeric($parserConfig['chunk_token_num'])) {
                $document->setChunkCount((int) $parserConfig['chunk_token_num']);
            }
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    private function mapTimestamps(Document $document, array $apiData): void
    {
        if (isset($apiData['create_time'])) {
            $timestamp = $this->convertTimestamp($apiData['create_time']);
            $document->setRemoteCreateTime(new \DateTimeImmutable('@' . $timestamp));
        }

        if (isset($apiData['update_time'])) {
            $timestamp = $this->convertTimestamp($apiData['update_time']);
            $document->setRemoteUpdateTime(new \DateTimeImmutable('@' . $timestamp));
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
