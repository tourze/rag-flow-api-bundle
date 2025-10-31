<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Tourze\RAGFlowApiBundle\Entity\Document;

/**
 * 文档删除服务
 */
final readonly class DocumentDeletionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentService $documentService,
        private DocumentValidationService $validationService,
    ) {
    }

    /**
     * 提取文档ID列表
     *
     * @return array<int>
     */
    public function extractDocumentIds(Request $request): array
    {
        $data = $this->decodeRequestData($request);
        $documentIds = $this->getRawDocumentIds($data);

        return $this->normalizeToIntegerIds($documentIds);
    }

    /**
     * 执行批量删除
     *
     * @param array<int> $documentIds
     * @return array{int, array<int, string>}
     */
    public function performBatchDelete(int $datasetId, array $documentIds): array
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

        return [$deletedCount, $errors];
    }

    /**
     * 删除单个文档
     */
    public function deleteDocument(Document $document): void
    {
        $this->deleteRemoteDocument($document);
        $this->deleteLocalFile($document);
        $this->deleteLocalRecord($document);
    }

    /**
     * 解码请求数据
     *
     * @return array<string, mixed>
     */
    private function decodeRequestData(Request $request): array
    {
        $content = $request->getContent();
        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid request data');
        }

        /** @var array<string, mixed> */
        return $data;
    }

    /**
     * 获取原始文档ID数组
     *
     * @param array<string, mixed> $data
     * @return array<mixed>
     */
    private function getRawDocumentIds(array $data): array
    {
        $documentIds = $data['document_ids'] ?? [];

        if (!is_array($documentIds) || [] === $documentIds) {
            throw new \InvalidArgumentException('No document IDs provided');
        }

        return $documentIds;
    }

    /**
     * 规范化为整数ID数组
     *
     * @param array<mixed> $documentIds
     * @return array<int>
     */
    private function normalizeToIntegerIds(array $documentIds): array
    {
        $validIds = $this->collectValidIntegerIds($documentIds);

        if ([] === $validIds) {
            throw new \InvalidArgumentException('No valid document IDs provided');
        }

        return $validIds;
    }

    /**
     * 收集有效的整数ID
     *
     * @param array<mixed> $documentIds
     * @return array<int>
     */
    private function collectValidIntegerIds(array $documentIds): array
    {
        $validIds = [];
        foreach ($documentIds as $id) {
            $intId = $this->convertToIntOrNull($id);
            if (null !== $intId) {
                $validIds[] = $intId;
            }
        }

        return $validIds;
    }

    /**
     * 转换为整数或返回null
     */
    private function convertToIntOrNull(mixed $id): ?int
    {
        if (is_int($id)) {
            return $id;
        }

        if (is_numeric($id)) {
            return (int) $id;
        }

        return null;
    }

    /**
     * 删除单个文档
     *
     * @return array{success: bool, error: string|null}
     */
    private function deleteSingleDocument(int $datasetId, mixed $documentId): array
    {
        if (!is_int($documentId)) {
            return ['success' => false, 'error' => sprintf('Invalid document ID: %s', var_export($documentId, true))];
        }

        try {
            $document = $this->validationService->findAndValidateDocument($datasetId, $documentId);
            if (null === $document) {
                return ['success' => false, 'error' => sprintf('Document %d not found or not belongs to this dataset', $documentId)];
            }

            $this->deleteDocument($document);

            return ['success' => true, 'error' => null];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => sprintf('Delete document %d failed: %s', $documentId, $e->getMessage())];
        }
    }

    /**
     * 删除远程API文档
     */
    private function deleteRemoteDocument(Document $document): void
    {
        if (!$this->canDeleteRemoteDocument($document)) {
            return;
        }

        $this->performRemoteDelete($document);
    }

    /**
     * 检查是否可以删除远程文档
     */
    private function canDeleteRemoteDocument(Document $document): bool
    {
        $remoteId = $document->getRemoteId();
        if (!$this->validationService->isValidRemoteId($remoteId)) {
            return false;
        }

        $dataset = $document->getDataset();
        $datasetRemoteId = $dataset?->getRemoteId();

        return $this->validationService->isValidRemoteId($datasetRemoteId);
    }

    /**
     * 执行远程删除
     */
    private function performRemoteDelete(Document $document): void
    {
        try {
            $remoteId = $document->getRemoteId();
            $dataset = $document->getDataset();
            $datasetRemoteId = $dataset?->getRemoteId();

            assert(null !== $remoteId && null !== $datasetRemoteId);
            $this->documentService->delete($datasetRemoteId, $remoteId);
        } catch (\Exception $e) {
            error_log(sprintf('Failed to delete document from RAGFlow API: %s', $e->getMessage()));
        }
    }

    /**
     * 删除本地文件
     */
    private function deleteLocalFile(Document $document): void
    {
        $filePath = $this->getValidFilePath($document);
        if (null === $filePath) {
            return;
        }

        $this->attemptFileDelete($filePath);
    }

    /**
     * 获取有效文件路径
     */
    private function getValidFilePath(Document $document): ?string
    {
        $filePath = $document->getFilePath();

        return $this->validationService->isNonEmptyString($filePath) ? $filePath : null;
    }

    /**
     * 尝试删除文件
     */
    private function attemptFileDelete(string $filePath): void
    {
        try {
            // TODO: 使用FileStorageBundle
            if (is_file($filePath)) {
                unlink($filePath);
            }
        } catch (\Exception $e) {
            error_log(sprintf('Failed to delete local file: %s', $e->getMessage()));
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
