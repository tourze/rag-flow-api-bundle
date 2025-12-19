<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

/**
 * 文档同步服务
 *
 * 负责处理文档与远程API的同步操作
 */
#[WithMonologChannel(channel: 'rag_flow_api')]
final class DocumentSyncService
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 同步文档到远程API
     *
     * @throws \RuntimeException 当同步所需数据缺失时
     * @throws \Exception 当远程API调用失败时
     */
    public function syncDocumentToRemote(Document $document, Dataset $dataset): void
    {
        $this->validateSyncRequirements($dataset, $document);

        $this->prepareDocumentForSync($document);

        try {
            $this->performSync($document, $dataset);
            $this->finalizeSyncSuccess($document);
        } catch (\Exception $e) {
            $this->finalizeSyncFailure($document, $e);
            throw $e;
        }
    }

    /**
     * 重新上传文档
     *
     * @throws \RuntimeException 当上传所需数据缺失时
     * @throws \Exception 当远程API调用失败时
     */
    public function retryUpload(Document $document, Dataset $dataset): void
    {
        $this->validateUploadRequirements($dataset, $document);

        $this->prepareForUpload($document);

        try {
            $this->performUpload($document, $dataset);
            $this->markUploadSuccess($document);
        } catch (\Exception $e) {
            $this->markUploadFailure($document);
            throw $e;
        }
    }

    /**
     * 从远程API删除文档
     */
    public function deleteFromRemote(Document $document, Dataset $dataset): void
    {
        if (!$this->hasValidRemoteIds($document, $dataset)) {
            return;
        }

        try {
            $this->performRemoteDelete($document, $dataset);
        } catch (\Exception $e) {
            $this->logger->warning('文档删除失败', [
                'document_id' => $document->getId(),
                'document_name' => $document->getName(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * 验证同步所需的数据
     *
     * @throws \RuntimeException 当数据缺失时
     */
    private function validateSyncRequirements(Dataset $dataset, Document $document): void
    {
        if (null === $dataset->getRemoteId()) {
            throw new \RuntimeException('Dataset remote ID is missing');
        }

        if (null === $document->getFilePath()) {
            throw new \RuntimeException('Document file path is missing');
        }
    }

    /**
     * 验证上传所需的数据
     *
     * @throws \RuntimeException 当数据缺失时
     */
    private function validateUploadRequirements(Dataset $dataset, Document $document): void
    {
        if (null === $dataset->getRemoteId() || null === $document->getFilePath()) {
            throw new \RuntimeException('Missing required data for upload');
        }
    }

    /**
     * 准备文档进行同步
     */
    private function prepareDocumentForSync(Document $document): void
    {
        $document->setStatus(DocumentStatus::UPLOADING);
        $this->entityManager->flush();
    }

    /**
     * 准备上传
     */
    private function prepareForUpload(Document $document): void
    {
        $document->setStatus(DocumentStatus::UPLOADING);
        $this->entityManager->flush();
    }

    /**
     * 执行同步操作
     */
    private function performSync(Document $document, Dataset $dataset): void
    {
        $remoteId = $dataset->getRemoteId();
        $filePath = $document->getFilePath();
        assert(null !== $remoteId && null !== $filePath);

        $result = $this->documentService->upload(
            $remoteId,
            [$document->getName() => $filePath],
            [$document->getName() => $document->getName()]
        );

        $this->updateDocumentFromAPIResult($document, $result);
    }

    /**
     * 执行上传操作
     */
    private function performUpload(Document $document, Dataset $dataset): void
    {
        $datasetRemoteId = $dataset->getRemoteId();
        $filePath = $document->getFilePath();
        assert(null !== $datasetRemoteId && null !== $filePath);

        $fileName = $document->getName();
        $result = $this->documentService->upload(
            $datasetRemoteId,
            [$fileName => $filePath],
            [$fileName => $fileName]
        );

        $this->updateDocumentFromAPIResult($document, $result);
    }

    /**
     * 完成同步成功
     */
    private function finalizeSyncSuccess(Document $document): void
    {
        $document->setStatus(DocumentStatus::UPLOADED);
        $document->setLastSyncTime(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * 标记上传成功
     */
    private function markUploadSuccess(Document $document): void
    {
        $document->setStatus(DocumentStatus::UPLOADED);
        $document->setLastSyncTime(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * 完成同步失败
     */
    private function finalizeSyncFailure(Document $document, \Exception $e): void
    {
        $document->setStatus(DocumentStatus::SYNC_FAILED);
        $this->entityManager->flush();
        $this->logger->warning('文档上传失败', [
            'document_id' => $document->getId(),
            'document_name' => $document->getName(),
            'exception' => $e,
        ]);
    }

    /**
     * 标记上传失败
     */
    private function markUploadFailure(Document $document): void
    {
        $document->setStatus(DocumentStatus::SYNC_FAILED);
        $this->entityManager->flush();
    }

    /**
     * 检查是否有有效的远程ID
     */
    private function hasValidRemoteIds(Document $document, Dataset $dataset): bool
    {
        $remoteId = $document->getRemoteId();
        $datasetRemoteId = $dataset->getRemoteId();

        return null !== $remoteId && '' !== $remoteId
            && null !== $datasetRemoteId && '' !== $datasetRemoteId;
    }

    /**
     * 执行远程删除
     */
    private function performRemoteDelete(Document $document, Dataset $dataset): void
    {
        $remoteId = $document->getRemoteId();
        $datasetRemoteId = $dataset->getRemoteId();
        assert(null !== $remoteId && null !== $datasetRemoteId);

        $this->documentService->delete($datasetRemoteId, $remoteId);
    }

    /**
     * 从API结果更新文档
     *
     * @param array<string, mixed> $result
     */
    private function updateDocumentFromAPIResult(Document $document, array $result): void
    {
        $firstItem = $this->extractFirstDataItem($result);
        if (null !== $firstItem) {
            $this->updateRemoteIdFromItem($document, $firstItem);
        }
    }

    /**
     * 提取第一个数据项
     *
     * @param array<string, mixed> $result
     * @return array<string, mixed>|null
     */
    private function extractFirstDataItem(array $result): ?array
    {
        if (!isset($result['data']) || !is_array($result['data']) || [] === $result['data']) {
            return null;
        }

        $firstItem = $result['data'][0];

        if (!is_array($firstItem)) {
            return null;
        }

        /** @var array<string, mixed> */
        return $firstItem;
    }

    /**
     * 从项目更新远程ID
     *
     * @param array<string, mixed> $item
     */
    private function updateRemoteIdFromItem(Document $document, array $item): void
    {
        $remoteId = $item['id'] ?? null;
        if (is_string($remoteId)) {
            $document->setRemoteId($remoteId);
        }
    }
}
