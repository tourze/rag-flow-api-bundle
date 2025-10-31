<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 文档验证服务
 */
final readonly class DocumentValidationService
{
    public function __construct(
        private DatasetRepository $datasetRepository,
        private DocumentRepository $documentRepository,
    ) {
    }

    /**
     * 验证并获取数据集
     */
    public function validateAndGetDataset(int $datasetId): Dataset
    {
        $dataset = $this->datasetRepository->find($datasetId);
        if (!$dataset instanceof Dataset) {
            throw new \InvalidArgumentException('Dataset not found');
        }

        return $dataset;
    }

    /**
     * 验证并获取文档
     */
    public function validateAndGetDocument(int $datasetId, int $documentId): Document
    {
        $document = $this->documentRepository->find($documentId);
        if (!$document instanceof Document || !$this->isValidDocumentForDataset($document, $datasetId)) {
            throw new \InvalidArgumentException('Document not found or not belongs to this dataset');
        }

        return $document;
    }

    /**
     * 查找并验证文档（返回null表示未找到）
     */
    public function findAndValidateDocument(int $datasetId, int $documentId): ?Document
    {
        $document = $this->documentRepository->find($documentId);
        if (null === $document) {
            return null;
        }

        return $this->isValidDocumentForDataset($document, $datasetId) ? $document : null;
    }

    /**
     * 检查文档是否属于指定数据集
     */
    public function isValidDocumentForDataset(Document $document, int $datasetId): bool
    {
        $datasetIdOfDocument = $document->getDataset()?->getId();
        if (null === $datasetIdOfDocument) {
            return false;
        }

        return $datasetIdOfDocument === $datasetId;
    }

    /**
     * 验证文档是否可以解析
     */
    public function validateDocumentForParsing(Document $document): void
    {
        if (!$this->hasValidRemoteId($document)) {
            throw new \InvalidArgumentException('Document not uploaded to RAGFlow yet');
        }
    }

    /**
     * 检查是否有有效的远程ID
     */
    public function hasValidRemoteId(Document $document): bool
    {
        $remoteId = $document->getRemoteId();

        return $this->isNonEmptyString($remoteId);
    }

    /**
     * 检查是否是非空字符串
     */
    public function isNonEmptyString(?string $value): bool
    {
        return null !== $value && '' !== $value;
    }

    /**
     * 检查是否是有效的远程ID
     */
    public function isValidRemoteId(?string $remoteId): bool
    {
        return null !== $remoteId && '' !== $remoteId;
    }
}
