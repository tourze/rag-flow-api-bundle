<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 数据集文档管理服务
 *
 * 将DatasetDocumentController中的复杂逻辑提取到此服务类，降低控制器复杂度
 */
class DatasetDocumentManagementService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DatasetRepository $datasetRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentService $documentService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 获取数据集文档统计信息
     * @return array{total: int, pending: int, processing: int, completed: int, failed: int, totalSize: string}
     */
    public function getDatasetDocumentStats(Dataset $dataset): array
    {
        return [
            'total' => $dataset->getDocumentCount(),
            'pending' => count(array_filter(
                $dataset->getDocuments()->toArray(),
                fn (Document $doc) => DocumentStatus::PENDING === $doc->getStatus()
            )),
            'processing' => $dataset->getProcessingDocumentCount(),
            'completed' => $dataset->getCompletedDocumentCount(),
            'failed' => $dataset->getFailedDocumentCount(),
            'totalSize' => $dataset->getDocumentsTotalSizeFormatted(),
        ];
    }

    /**
     * 同步所有数据集的文档
     */
    public function syncAllDatasetDocuments(): void
    {
        try {
            $datasets = $this->datasetRepository->findAll();

            foreach ($datasets as $dataset) {
                if (null !== $dataset->getRemoteId()) {
                    $this->documentService->list($dataset->getRemoteId());
                }
            }

            $this->logger->info('Successfully synced all dataset documents');
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync dataset documents', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 处理文档上传
     * @param array<string, mixed> $uploadResult
     * @return array<int, array<string, mixed>>
     */
    public function handleDocumentUpload(Dataset $dataset, array $uploadResult): array
    {
        try {
            $this->entityManager->beginTransaction();

            // 处理上传结果
            $processedResults = [];
            if (isset($uploadResult['data']) && is_array($uploadResult['data'])) {
                foreach ($uploadResult['data'] as $docData) {
                    if (is_array($docData)) {
                        /** @var array<string, mixed> $typedDocData */
                        $typedDocData = $docData;
                        $processedResults[] = $this->processUploadedDocument($dataset, $typedDocData);
                    }
                }
            }

            $this->entityManager->commit();

            $this->logger->info('Document upload processed successfully', [
                'dataset_id' => $dataset->getId(),
                'document_count' => count($processedResults),
            ]);

            return $processedResults;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to process document upload', [
                'dataset_id' => $dataset->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 处理单个上传的文档
     * @param array<string, mixed> $docData
     * @return array<string, mixed>
     */
    private function processUploadedDocument(Dataset $dataset, array $docData): array
    {
        $document = $this->findOrCreateDocument($dataset, $docData);
        $this->updateDocumentFromData($document, $docData);
        $this->saveDocument($document);

        return $docData;
    }

    /**
     * @param array<string, mixed> $docData
     */
    private function findOrCreateDocument(Dataset $dataset, array $docData): Document
    {
        $document = null;
        if (isset($docData['id'])) {
            $document = $this->documentRepository->findOneBy([
                'remoteId' => $docData['id'],
                'dataset' => $dataset,
            ]);
        }

        if (null === $document) {
            $document = new Document();
            $document->setDataset($dataset);
        }

        return $document;
    }

    /**
     * @param array<string, mixed> $docData
     */
    private function updateDocumentFromData(Document $document, array $docData): void
    {
        if (isset($docData['id']) && (is_string($docData['id']) || is_int($docData['id']))) {
            $document->setRemoteId((string) $docData['id']);
        }
        if (isset($docData['name']) && is_string($docData['name'])) {
            $document->setName($docData['name']);
        }
        if (isset($docData['type']) && is_string($docData['type'])) {
            $document->setType($docData['type']);
        }
        if (isset($docData['size']) && (is_int($docData['size']) || is_string($docData['size']))) {
            $document->setSize((int) $docData['size']);
        }
        if (isset($docData['status']) && is_string($docData['status'])) {
            $document->setStatus(DocumentStatus::tryFrom($docData['status']) ?? DocumentStatus::PENDING);
        }

        $document->setLastSyncTime(new \DateTimeImmutable());
    }

    private function saveDocument(Document $document): void
    {
        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    /**
     * 删除文档
     */
    public function deleteDocument(Document $document): void
    {
        try {
            $this->entityManager->beginTransaction();

            // 如果有远程ID，尝试从API删除
            if (null !== $document->getRemoteId() && null !== $document->getDataset()?->getRemoteId()) {
                try {
                    $this->documentService->delete($document->getDataset()->getRemoteId(), $document->getRemoteId());
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to delete document from API, removing local record only', [
                        'document_id' => $document->getId(),
                        'remote_id' => $document->getRemoteId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 删除本地记录
            $this->entityManager->remove($document);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Document deleted successfully', [
                'document_id' => $document->getId(),
                'remote_id' => $document->getRemoteId(),
            ]);
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to delete document', [
                'document_id' => $document->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 启动文档解析
     * @param string[] $documentIds
     * @return array<string, mixed>
     */
    public function startDocumentParsing(Dataset $dataset, array $documentIds): array
    {
        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        // 批量解析：对每个文档ID调用parse方法
        $results = [];
        foreach ($documentIds as $documentId) {
            $results[] = $this->documentService->parse($datasetRemoteId, $documentId);
        }

        return ['results' => $results, 'total' => count($results)];
    }

    /**
     * 停止文档解析
     * @param string[] $documentIds
     * @return array<string, mixed>
     */
    public function stopDocumentParsing(Dataset $dataset, array $documentIds): array
    {
        if (null === $dataset->getRemoteId()) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        return $this->documentService->stopParsing($dataset->getRemoteId(), $documentIds);
    }

    /**
     * 获取解析状态
     * @return array<string, mixed>
     */
    public function getParseStatus(Dataset $dataset, string $documentId): array
    {
        if (null === $dataset->getRemoteId()) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        return $this->documentService->getParseStatus($dataset->getRemoteId(), $documentId);
    }
}
