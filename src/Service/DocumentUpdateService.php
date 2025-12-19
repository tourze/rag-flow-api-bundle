<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Util\DocumentDataUpdater;

/**
 * 文档更新服务
 *
 * 负责处理文档更新相关的业务逻辑
 */
final class DocumentUpdateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 从数据更新文档
     *
     * @param array<string, mixed> $data
     */
    public function updateDocumentFromData(Document $document, array $data): void
    {
        $updater = new DocumentDataUpdater($document);

        $updater
            ->updateName($this->extractStringValue($data, 'name'))
            ->updateSummary($this->extractSummaryValue($data))
            ->updateLanguage($this->extractLanguageValue($data))
        ;

        $this->updateTimestamps($document);
        $this->entityManager->flush();
    }

    /**
     * 提取字符串值
     *
     * @param array<string, mixed> $data
     */
    private function extractStringValue(array $data, string $key): ?string
    {
        return (isset($data[$key]) && is_string($data[$key])) ? $data[$key] : null;
    }

    /**
     * 提取摘要值
     *
     * @param array<string, mixed> $data
     */
    private function extractSummaryValue(array $data): ?string
    {
        return isset($data['summary']) ? (is_string($data['summary']) ? $data['summary'] : null) : null;
    }

    /**
     * 提取语言值
     *
     * @param array<string, mixed> $data
     */
    private function extractLanguageValue(array $data): ?string
    {
        return isset($data['language']) ? (is_string($data['language']) ? $data['language'] : null) : null;
    }

    /**
     * 更新时间戳
     */
    private function updateTimestamps(Document $document): void
    {
        $now = new \DateTimeImmutable();
        $document->setUpdateTime($now);
        $document->setLastSyncTime($now);
    }
}
