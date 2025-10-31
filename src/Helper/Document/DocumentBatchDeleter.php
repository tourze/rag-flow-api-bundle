<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\Document;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * 文档批量删除器
 *
 * 负责处理文档批量删除操作
 */
final class DocumentBatchDeleter
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentService $documentService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 执行批量删除
     *
     * @param array<int> $documentIds
     * @return array{deleted_count: int, errors: array<string>}
     */
    public function batchDelete(int $datasetId, array $documentIds): array
    {
        $deletedCount = 0;
        $errors = [];

        foreach ($documentIds as $documentId) {
            $result = $this->deleteSingleDocument($datasetId, $documentId);
            if ($result['success']) {
                ++$deletedCount;
            }
            if (null !== $result['error']) {
                $errors[] = $result['error'];
            }
        }

        return ['deleted_count' => $deletedCount, 'errors' => $errors];
    }

    /**
     * 删除单个文档
     *
     * @return array{success: bool, error: string|null}
     */
    private function deleteSingleDocument(int $datasetId, int $documentId): array
    {
        try {
            $document = $this->findAndValidateDocument($datasetId, $documentId);
            if (null === $document) {
                return [
                    'success' => false,
                    'error' => sprintf('Document %d not found or not belongs to this dataset', $documentId),
                ];
            }

            $this->deleteDocument($document);

            return ['success' => true, 'error' => null];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => sprintf('Delete document %d failed: %s', $documentId, $e->getMessage()),
            ];
        }
    }

    /**
     * 查找并验证文档
     */
    private function findAndValidateDocument(int $datasetId, int $documentId): ?Document
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
    private function isValidDocumentForDataset(Document $document, int $datasetId): bool
    {
        $datasetIdOfDocument = $document->getDataset()?->getId();
        if (null === $datasetIdOfDocument) {
            return false;
        }

        return $datasetIdOfDocument === $datasetId;
    }

    /**
     * 删除文档（本地+远程）
     */
    private function deleteDocument(Document $document): void
    {
        $this->deleteRemoteDocument($document);
        $this->deleteLocalRecord($document);
    }

    /**
     * 删除远程API文档
     */
    private function deleteRemoteDocument(Document $document): void
    {
        $remoteId = $document->getRemoteId();
        $datasetRemoteId = $document->getDataset()?->getRemoteId();

        if (null === $remoteId || '' === $remoteId || null === $datasetRemoteId || '' === $datasetRemoteId) {
            return;
        }

        try {
            $this->documentService->delete($datasetRemoteId, $remoteId);
        } catch (\Exception $e) {
            error_log(sprintf('Failed to delete document from RAGFlow API: %s', $e->getMessage()));
        }
    }

    /**
     * 删除本地数据库记录
     */
    private function deleteLocalRecord(Document $document): void
    {
        $this->entityManager->remove($document);
        $this->entityManager->flush();
    }
}
