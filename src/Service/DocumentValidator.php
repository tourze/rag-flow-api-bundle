<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 文档验证服务
 *
 * 提取和封装数据集、文档的验证逻辑，减少控制器复杂度
 */
final class DocumentValidator
{
    public function __construct(
        private readonly DatasetRepository $datasetRepository,
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    /**
     * 查找数据集，不存在返回null
     */
    public function findDataset(int $datasetId): ?Dataset
    {
        $dataset = $this->datasetRepository->find($datasetId);

        return $dataset instanceof Dataset ? $dataset : null;
    }

    /**
     * 查找并验证文档是否属于指定数据集
     */
    public function findDocumentInDataset(int $documentId, Dataset $dataset): ?Document
    {
        $document = $this->documentRepository->find($documentId);
        if (!$document instanceof Document) {
            return null;
        }

        $documentDataset = $document->getDataset();
        if (null === $documentDataset || $documentDataset->getId() !== $dataset->getId()) {
            return null;
        }

        return $document;
    }

    /**
     * 验证文档是否可以同步chunks
     *
     * @return JsonResponse|null 如果验证失败返回错误响应，成功返回null
     */
    public function validateDocumentForChunkSync(Document $document): ?JsonResponse
    {
        $documentRemoteId = $document->getRemoteId();
        if (null === $documentRemoteId || '' === $documentRemoteId) {
            return new JsonResponse(['error' => '文档尚未上传到RAGFlow'], 400);
        }

        return null;
    }

    /**
     * 检查数据集是否可以同步
     */
    public function canSyncDataset(Dataset $dataset): bool
    {
        $datasetRemoteId = $dataset->getRemoteId();

        return null !== $datasetRemoteId && '' !== $datasetRemoteId;
    }

    /**
     * 检查文档是否可以同步
     */
    public function canSyncDocument(Document $document): bool
    {
        $documentRemoteId = $document->getRemoteId();

        return null !== $documentRemoteId && '' !== $documentRemoteId;
    }
}
