<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Repository\ChunkRepository;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 数据集文档同步服务
 */
final readonly class DatasetDocumentSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DatasetRepository $datasetRepository,
        private DocumentRepository $documentRepository,
        private ChunkRepository $chunkRepository,
        private DocumentService $documentService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 同步所有数据集的文档数据
     *
     * @return array<string, mixed>
     */
    public function syncAllDatasetDocuments(): array
    {
        $datasets = $this->getValidDatasets();
        $totalSynced = 0;
        $errors = [];

        foreach ($datasets as $dataset) {
            try {
                $syncedCount = $this->syncDatasetDocuments($dataset);
                $totalSynced += $syncedCount;
            } catch (\Exception $e) {
                $errors[] = sprintf(
                    'Failed to sync documents for dataset %d (%s): %s',
                    $dataset->getId(),
                    $dataset->getName(),
                    $e->getMessage()
                );
            }
        }

        return [
            'total_synced' => $totalSynced,
            'errors' => $errors,
        ];
    }

    /**
     * 同步单个数据集的文档
     */
    public function syncDatasetDocuments(Dataset $dataset): int
    {
        $remoteId = $dataset->getRemoteId();
        if (null === $remoteId || '' === $remoteId) {
            return 0;
        }

        $remoteDocuments = $this->documentService->list($remoteId);

        return count($remoteDocuments);
    }

    /**
     * 自动同步文档的chunks数据
     *
     * @param Document[] $documents
     */
    public function autoSyncDocumentsChunks(Dataset $dataset, array $documents): void
    {
        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId || '' === $datasetRemoteId) {
            return;
        }

        foreach ($documents as $document) {
            $this->syncDocumentChunks($document, $datasetRemoteId);
        }

        $this->flushChanges();
    }

    /**
     * 同步单个文档的chunks
     */
    public function syncDocumentChunks(Document $document, string $datasetRemoteId): void
    {
        if ($document->getProgress() < 1) {
            return;
        }

        $documentRemoteId = $document->getRemoteId();
        if (null === $documentRemoteId || '' === $documentRemoteId) {
            return;
        }

        try {
            $result = $this->documentService->listChunks($datasetRemoteId, $documentRemoteId);
        } catch (\Throwable $e) {
            // 在API调用失败时，记录警告但不抛出异常
            $this->logger->warning('同步 chunks 失败', [
                'dataset_remote_id' => $datasetRemoteId,
                'document_id' => $document->getId(),
                'document_remote_id' => $document->getRemoteId(),
                'exception' => $e,
            ]);

            return;
        }

        if (!isset($result['chunks'])) {
            return;
        }

        $chunks = $result['chunks'];
        if (!is_array($chunks)) {
            return;
        }

        $totalCount = count($chunks);
        if ($document->getChunkCount() === ($result['total'] ?? 0)) {
            return;
        }

        if (0 === $totalCount) {
            return;
        }

        try {
            $this->processChunks($chunks, $document);
            $total = isset($result['total']) && is_int($result['total']) ? $result['total'] : $totalCount;
            $document->setChunkCount($total);
        } catch (\Throwable $e) {
            $this->logger->warning('处理 chunks 数据失败', [
                'document_id' => $document->getId(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * 获取有效的数据集
     *
     * @return array<Dataset>
     */
    private function getValidDatasets(): array
    {
        /** @var array<Dataset> */
        return $this->datasetRepository->createQueryBuilder('d')
            ->where('d.remoteId IS NOT NULL')
            ->andWhere('d.remoteId != :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 处理chunks数据
     *
     * @param array<mixed> $chunks
     */
    private function processChunks(array $chunks, Document $document): void
    {
        foreach ($chunks as $chunkData) {
            if (!is_array($chunkData)) {
                continue;
            }

            /** @var array<string, mixed> $typedChunkData */
            $typedChunkData = $chunkData;
            $this->processSingleChunk($typedChunkData, $document);
        }
    }

    /**
     * @param array<string, mixed> $chunkData
     */
    private function processSingleChunk(array $chunkData, Document $document): void
    {
        $chunkId = $this->extractChunkId($chunkData);
        if ('' === $chunkId) {
            return;
        }

        $chunk = $this->findOrCreateChunk($chunkId, $document);
        $this->updateChunkData($chunk, $chunkData);
        $this->entityManager->persist($chunk);
    }

    /**
     * @param array<string, mixed> $chunkData
     */
    private function extractChunkId(array $chunkData): string
    {
        return isset($chunkData['id']) && is_string($chunkData['id']) ? $chunkData['id'] : '';
    }

    private function findOrCreateChunk(string $chunkId, Document $document): Chunk
    {
        $chunk = $this->chunkRepository->findOneBy([
            'remoteId' => $chunkId,
            'document' => $document,
        ]);

        if (null === $chunk) {
            $chunk = new Chunk();
            $chunk->setRemoteId($chunkId);
            $chunk->setDocument($document);
        }

        return $chunk;
    }

    /**
     * @param array<string, mixed> $chunkData
     */
    private function updateChunkData(Chunk $chunk, array $chunkData): void
    {
        $content = isset($chunkData['content']) && is_string($chunkData['content']) ? $chunkData['content'] : '';
        $chunk->setContent($content);

        if (isset($chunkData['size']) && is_numeric($chunkData['size'])) {
            $chunk->setSize((int) $chunkData['size']);
        }
    }

    /**
     * 处理单个文档的chunks同步
     *
     * @return array<string, mixed>
     */
    public function processSingleDocumentChunkSync(Dataset $dataset, Document $document): array
    {
        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId || '' === $datasetRemoteId) {
            return [
                'success' => false,
                'error' => '数据集远程ID为空',
                'synced_count' => 0,
                'total_count' => 0,
            ];
        }

        $documentRemoteId = $document->getRemoteId();
        if (null === $documentRemoteId || '' === $documentRemoteId) {
            return [
                'success' => false,
                'error' => '文档远程ID为空',
                'synced_count' => 0,
                'total_count' => 0,
            ];
        }

        try {
            $result = $this->documentService->listChunks($datasetRemoteId, $documentRemoteId);
        } catch (\Exception $e) {
            // 在API调用失败时（如测试环境中），返回错误信息但不抛出异常
            return [
                'success' => false,
                'error' => sprintf('API调用失败: %s', $e->getMessage()),
                'synced_count' => 0,
                'total_count' => 0,
            ];
        }

        if (!isset($result['chunks']) || !is_array($result['chunks'])) {
            return [
                'success' => false,
                'error' => 'API返回数据格式错误',
                'synced_count' => 0,
                'total_count' => 0,
            ];
        }

        $chunks = $result['chunks'];
        $totalCount = isset($result['total']) && is_int($result['total']) ? $result['total'] : count($chunks);

        try {
            $this->processChunks($chunks, $document);
            $document->setChunkCount($totalCount);
            $this->flushChanges();

            return [
                'success' => true,
                'synced_count' => count($chunks),
                'total_count' => $totalCount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced_count' => 0,
                'total_count' => 0,
            ];
        }
    }

    /**
     * 批量保存更改
     */
    private function flushChanges(): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            error_log(sprintf('Failed to flush chunks data: %s', $e->getMessage()));
        }
    }

    /**
     * 批量同步数据集中所有已完成文档的chunks
     *
     * @return array{synced_count: int, errors: array<string>}
     */
    public function processBatchChunkSync(Dataset $dataset): array
    {
        $filters = ['dataset_id' => $dataset->getId(), 'status' => DocumentStatus::COMPLETED->value];
        $result = $this->documentRepository->findWithFilters($filters, 1, 1000);
        $documents = $result['items'];

        $syncedCount = 0;
        $errors = [];

        foreach ($documents as $document) {
            if (!$this->canSyncDocument($document, $dataset)) {
                continue;
            }

            try {
                $chunkSyncResult = $this->syncSingleDocument($document, $dataset);
                if (isset($chunkSyncResult['success']) && true === $chunkSyncResult['success']) {
                    ++$syncedCount;
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('文档 %s 同步失败: %s', $document->getName(), $e->getMessage());
            }
        }

        $this->entityManager->flush();

        return [
            'synced_count' => $syncedCount,
            'errors' => $errors,
        ];
    }

    /**
     * 检查文档是否可以同步
     */
    private function canSyncDocument(Document $document, Dataset $dataset): bool
    {
        if (DocumentStatus::COMPLETED !== $document->getStatus()) {
            return false;
        }

        $documentRemoteId = $document->getRemoteId();
        if (null === $documentRemoteId || '' === $documentRemoteId) {
            return false;
        }

        $datasetRemoteId = $dataset->getRemoteId();

        return null !== $datasetRemoteId && '' !== $datasetRemoteId;
    }

    /**
     * 同步单个文档的chunks
     *
     * @return array<string, mixed>
     */
    private function syncSingleDocument(Document $document, Dataset $dataset): array
    {
        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId || '' === $datasetRemoteId) {
            return ['success' => false, 'error' => '数据集远程ID为空'];
        }

        try {
            $this->syncDocumentChunks($document, $datasetRemoteId);

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
