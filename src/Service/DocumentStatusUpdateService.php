<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

/**
 * 文档状态更新服务
 *
 * 专门处理文档状态更新逻辑，降低控制器复杂度
 */
final readonly class DocumentStatusUpdateService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 从解析状态数据更新文档状态
     *
     * @param array<string, mixed> $parseStatus
     */
    public function updateDocumentFromParseStatus(Document $document, array $parseStatus): void
    {
        if (isset($parseStatus['progress']) && is_numeric($parseStatus['progress'])) {
            $document->setProgress((float) $parseStatus['progress']);
        }

        if (isset($parseStatus['progress_msg']) && is_string($parseStatus['progress_msg'])) {
            $document->setProgressMsg($parseStatus['progress_msg']);
        }

        if (isset($parseStatus['status'])) {
            $statusValue = $parseStatus['status'];
            if (is_string($statusValue) || is_int($statusValue)) {
                $document->setStatus(DocumentStatus::fromValue($statusValue) ?? DocumentStatus::PENDING);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * 重置文档状态为准备重传
     */
    public function resetDocumentForRetry(Document $document): void
    {
        $document->setStatus(DocumentStatus::UPLOADING);
        $document->setProgress(0);
        $document->setProgressMsg('准备重传...');
        $document->setRemoteId(null);
        $this->entityManager->flush();
    }

    /**
     * 标记文档上传成功
     */
    public function markDocumentUploaded(Document $document, ?string $remoteId = null): void
    {
        if (null !== $remoteId) {
            $document->setRemoteId($remoteId);
        }
        $document->setStatus(DocumentStatus::UPLOADED);
        $document->setProgressMsg('上传成功');
        $document->setLastSyncTime(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * 标记文档上传失败
     */
    public function markDocumentUploadFailed(Document $document, string $reason): void
    {
        $document->setStatus(DocumentStatus::SYNC_FAILED);
        $document->setProgressMsg("上传失败: {$reason}");
        $this->entityManager->flush();
    }

    /**
     * 开始文档处理
     */
    public function startDocumentProcessing(Document $document): void
    {
        $document->setStatus(DocumentStatus::PROCESSING);
        $document->setProgress(0);
        $document->setProgressMsg('开始解析...');
        $this->entityManager->flush();
    }

    /**
     * 停止文档处理
     */
    public function stopDocumentProcessing(Document $document): void
    {
        $document->setStatus(DocumentStatus::COMPLETED);
        $document->setProgressMsg('解析已取消');
        $this->entityManager->flush();
    }
}
