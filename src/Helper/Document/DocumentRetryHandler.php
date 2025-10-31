<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\Document;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * 文档重试处理器
 *
 * 负责文档上传失败后的重试逻辑
 */
final readonly class DocumentRetryHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentService $documentService,
    ) {
    }

    /**
     * 检查文档是否需要重试
     */
    public function shouldRetry(Document $document): bool
    {
        $filePath = $document->getFilePath();

        return $document->isUploadRequired()
            && null !== $filePath
            && '' !== $filePath
            && file_exists($filePath);
    }

    /**
     * 处理文档重试
     */
    public function processRetry(Document $document, Dataset $dataset): void
    {
        $document->setStatus(DocumentStatus::UPLOADING);
        $this->entityManager->flush();

        $filePath = $document->getFilePath();
        if (null === $filePath) {
            throw DocumentOperationException::uploadFailed($document->getName(), 'File path is null');
        }

        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        $result = $this->documentService->upload(
            $datasetRemoteId,
            ['file' => $filePath],
            ['file' => $document->getName()]
        );

        $this->updateAfterRetry($document, $result);
    }

    /**
     * 重试后更新文档
     *
     * @param array<string, mixed> $result
     */
    public function updateAfterRetry(Document $document, array $result): void
    {
        if (isset($result['data']) && is_array($result['data']) && [] !== $result['data']) {
            $firstItem = $result['data'][0];
            if (is_array($firstItem) && isset($firstItem['id']) && is_string($firstItem['id'])) {
                $document->setRemoteId($firstItem['id']);
            }
        }

        $document->setStatus(DocumentStatus::UPLOADED);
        $document->setLastSyncTime(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * 处理重试错误
     */
    public function handleError(Document $document, \Exception $e): string
    {
        $document->setStatus(DocumentStatus::SYNC_FAILED);
        $this->entityManager->flush();

        return sprintf('重试文档 %s 失败: %s', $document->getName(), $e->getMessage());
    }
}
