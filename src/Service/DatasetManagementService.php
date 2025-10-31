<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * 数据集管理核心业务服务
 *
 * 提供数据集相关的业务逻辑，不依赖HTTP层
 */
class DatasetManagementService
{
    public function __construct(
        private readonly DatasetRepository $datasetRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
    ) {
    }

    /**
     * 验证数据集访问权限并返回数据集实体
     */
    public function validateDatasetAccess(int $datasetId): Dataset
    {
        $dataset = $this->datasetRepository->find($datasetId);
        if (!$dataset instanceof Dataset) {
            throw new \InvalidArgumentException(sprintf('数据集 %d 不存在', $datasetId));
        }

        return $dataset;
    }

    /**
     * 根据remoteId查找数据集
     */
    public function findDatasetByRemoteId(string $remoteId): ?Dataset
    {
        return $this->datasetRepository->findOneBy(['remoteId' => $remoteId]);
    }

    /**
     * 获取数据集文档统计信息
     *
     * @return array{total_documents: int, processed_documents: int, pending_documents: int}
     */
    public function getDatasetDocumentStats(int $datasetId): array
    {
        $dataset = $this->validateDatasetAccess($datasetId);

        return [
            'total_documents' => $this->documentRepository->countByDataset($dataset),
            'processed_documents' => $this->documentRepository->countProcessedByDataset($dataset),
            'pending_documents' => $this->documentRepository->countPendingByDataset($dataset),
        ];
    }

    /**
     * 获取数据集文档管理URL（依赖路由）
     *
     * @param array<string, mixed> $params
     */
    public function getDocumentManagementUrl(int $datasetId, array $params = []): ?string
    {
        $urlGenerator = $this->urlGenerator;
        if (null === $urlGenerator) {
            return null;
        }

        try {
            return $urlGenerator->generate('dataset_documents_index', [
                'datasetId' => $datasetId,
                ...$params,
            ]);
        } catch (RouteNotFoundException $e) {
            // 路由不存在时返回null
            return null;
        }
    }

    /**
     * 获取数据集知识图谱URL（依赖路由）
     *
     * @param array<string, mixed> $params
     */
    public function getKnowledgeGraphUrl(int $datasetId, array $params = []): ?string
    {
        $urlGenerator = $this->urlGenerator;
        if (null === $urlGenerator) {
            return null;
        }

        try {
            return $urlGenerator->generate('dataset_knowledge_graph', [
                'datasetId' => $datasetId,
                ...$params,
            ]);
        } catch (RouteNotFoundException $e) {
            // 路由不存在时返回null
            return null;
        }
    }

    /**
     * 检查数据集是否可以删除（没有关联文档）
     */
    public function canDeleteDataset(int $datasetId): bool
    {
        $dataset = $this->validateDatasetAccess($datasetId);

        return 0 === count($dataset->getDocuments());
    }

    /**
     * 获取数据集的完整信息（包含统计数据）
     *
     * @return array{dataset: Dataset, stats: array{total_documents: int, processed_documents: int, pending_documents: int}, can_delete: bool, document_count: int}
     */
    public function getDatasetFullInfo(int $datasetId): array
    {
        $dataset = $this->validateDatasetAccess($datasetId);
        $stats = $this->getDatasetDocumentStats($datasetId);

        return [
            'dataset' => $dataset,
            'stats' => $stats,
            'can_delete' => $this->canDeleteDataset($datasetId),
            'document_count' => $stats['total_documents'],
        ];
    }
}
