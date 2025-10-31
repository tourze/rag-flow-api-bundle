<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\Document;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentManagementService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * 文档状态更新器
 *
 * 负责文档解析状态的更新和控制
 */
final readonly class DocumentStatusUpdater
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentService $documentService,
        private DatasetDocumentManagementService $managementService,
    ) {
    }

    /**
     * 重新解析文档
     *
     * @return array{success: bool, message: string, data?: array<string, mixed>, error?: string}
     */
    public function reparse(Document $document, Dataset $dataset): array
    {
        try {
            $remoteId = $document->getRemoteId();
            if (null === $remoteId || '' === $remoteId) {
                return [
                    'success' => false,
                    'message' => '文档尚未上传，无法解析',
                ];
            }

            $datasetRemoteId = $this->validateDatasetRemoteId($dataset);

            $result = $this->documentService->parseChunks($datasetRemoteId, [$remoteId]);

            $this->updateParsingStatus($document, 0.0, '重新解析中...');

            return [
                'success' => true,
                'message' => sprintf('文档 %s 重新解析已启动', $document->getName()),
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf('重新解析文档 %s 失败', $document->getName()),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 停止文档解析
     *
     * @return array{success: bool, message: string, data?: array<string, mixed>, error?: string}
     */
    public function stopParsing(Document $document, Dataset $dataset): array
    {
        try {
            $remoteId = $document->getRemoteId();
            if (null === $remoteId || '' === $remoteId) {
                return [
                    'success' => false,
                    'message' => '文档尚未上传，无法停止解析',
                ];
            }

            $datasetRemoteId = $this->validateDatasetRemoteId($dataset);

            $result = $this->documentService->stopParsing($datasetRemoteId, [$remoteId]);

            $this->updateStoppedStatus($document);

            return [
                'success' => true,
                'message' => sprintf('文档 %s 解析已停止', $document->getName()),
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf('停止解析文档 %s 失败', $document->getName()),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 从API更新文档状态
     */
    public function updateFromApi(Document $document, Dataset $dataset): void
    {
        try {
            $remoteId = $document->getRemoteId();
            $datasetRemoteId = $dataset->getRemoteId();

            if (null === $remoteId || null === $datasetRemoteId) {
                return;
            }

            $status = $this->managementService->getParseStatus($dataset, $remoteId);

            $this->applyStatusUpdate($document, $status);
        } catch (\Exception $e) {
            // 静默处理状态更新失败，不影响主流程
        }
    }

    private function validateDatasetRemoteId(Dataset $dataset): string
    {
        $datasetRemoteId = $dataset->getRemoteId();
        if (null === $datasetRemoteId) {
            throw DocumentOperationException::datasetNotFound((string) $dataset->getId());
        }

        return $datasetRemoteId;
    }

    private function updateParsingStatus(Document $document, float $progress, string $message): void
    {
        $document->setStatus(DocumentStatus::PROCESSING);
        $document->setProgress($progress);
        $document->setProgressMsg($message);
        $this->entityManager->flush();
    }

    private function updateStoppedStatus(Document $document): void
    {
        $document->setStatus(DocumentStatus::PENDING);
        $document->setProgress(null);
        $document->setProgressMsg('解析已停止');
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $status
     */
    private function applyStatusUpdate(Document $document, array $status): void
    {
        if (isset($status['progress']) && is_numeric($status['progress'])) {
            $progress = (float) $status['progress'];
            $document->setProgress($progress * 100);
        }

        if (isset($status['progress_msg']) && is_string($status['progress_msg'])) {
            $document->setProgressMsg($status['progress_msg']);
        }

        if (isset($status['chunk_num']) && is_numeric($status['chunk_num'])) {
            $document->setChunkCount((int) $status['chunk_num']);
        }

        $this->entityManager->flush();
    }
}
