<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service\Mapper;

use Tourze\RAGFlowApiBundle\Entity\Conversation;

/**
 * Conversation映射器
 */
final class ConversationMapper
{
    /**
     * @param array<string, mixed> $apiData
     */
    public function mapApiDataToEntity(Conversation $conversation, array $apiData): void
    {
        if (isset($apiData['name']) && is_string($apiData['name'])) {
            $conversation->setName($apiData['name']);
        }

        if (isset($apiData['dialog']) && is_array($apiData['dialog'])) {
            /** @var array<string, mixed> $dialog */
            $dialog = $apiData['dialog'];
            $conversation->setDialog($dialog);
        }

        if (isset($apiData['create_time'])) {
            $timestamp = $this->convertTimestamp($apiData['create_time']);
            $conversation->setRemoteCreateTime(new \DateTimeImmutable('@' . $timestamp));
        }

        if (isset($apiData['update_time'])) {
            $timestamp = $this->convertTimestamp($apiData['update_time']);
            $conversation->setRemoteUpdateTime(new \DateTimeImmutable('@' . $timestamp));
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
