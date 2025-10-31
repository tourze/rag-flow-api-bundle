<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Tourze\RAGFlowApiBundle\Entity\Dataset;

/**
 * RAGFlow 门面服务
 *
 * 为外部项目提供统一的RAGFlow功能访问接口
 * 不依赖HTTP层和路由配置
 */
class RAGFlowFacadeService
{
    public function __construct(
        private readonly DatasetManagementService $datasetService,
        private readonly RAGFlowUrlService $urlService,
    ) {
    }

    /**
     * 获取数据集管理信息（外部项目主要入口）
     *
     * @return array{dataset: mixed, stats: mixed, can_delete: bool, urls: array{document_management_path: string, document_management_url: string, knowledge_graph_path: string, knowledge_graph_url: string, upload_path: string, upload_url: string}, route_status: array<string, bool>}
     */
    public function getDatasetManagementInfo(int $datasetId): array
    {
        $fullInfo = $this->datasetService->getDatasetFullInfo($datasetId);

        return [
            'dataset' => $fullInfo['dataset'],
            'stats' => $fullInfo['stats'],
            'can_delete' => $fullInfo['can_delete'],
            'urls' => [
                'document_management_path' => $this->urlService->generateDocumentManagementPath($datasetId),
                'document_management_url' => $this->urlService->generateDocumentManagementUrl($datasetId),
                'knowledge_graph_path' => $this->urlService->generateKnowledgeGraphPath($datasetId),
                'knowledge_graph_url' => $this->urlService->generateKnowledgeGraphUrl($datasetId),
                'upload_path' => $this->urlService->generateDocumentUploadPath($datasetId),
                'upload_url' => $this->urlService->generateDocumentUploadUrl($datasetId),
            ],
            'route_status' => $this->urlService->getRouteAvailability(),
        ];
    }

    /**
     * 验证数据集访问权限
     */
    public function validateDatasetAccess(int $datasetId): Dataset
    {
        return $this->datasetService->validateDatasetAccess($datasetId);
    }

    /**
     * 获取数据集基本信息
     *
     * @return array{id: int|null, name: string, description: string|null, remote_id: string|null, status: string|null, document_count: int, processed_count: int, pending_count: int}
     */
    public function getDatasetBasicInfo(int $datasetId): array
    {
        $dataset = $this->datasetService->validateDatasetAccess($datasetId);
        $stats = $this->datasetService->getDatasetDocumentStats($datasetId);

        return [
            'id' => $dataset->getId(),
            'name' => $dataset->getName(),
            'description' => $dataset->getDescription(),
            'remote_id' => $dataset->getRemoteId(),
            'status' => $dataset->getStatus(),
            'document_count' => $stats['total_documents'],
            'processed_count' => $stats['processed_documents'],
            'pending_count' => $stats['pending_documents'],
        ];
    }

    /**
     * 获取文档管理URL（优先路由，回退到路径）
     *
     * @param array<string, mixed> $params
     */
    public function getDocumentManagementUrl(int $datasetId, array $params = []): string
    {
        // 先验证数据集存在
        $this->datasetService->validateDatasetAccess($datasetId);

        return $this->urlService->generateDocumentManagementUrl($datasetId, $params);
    }

    /**
     * 获取知识图谱URL（优先路由，回退到路径）
     *
     * @param array<string, mixed> $params
     */
    public function getKnowledgeGraphUrl(int $datasetId, array $params = []): string
    {
        // 先验证数据集存在
        $this->datasetService->validateDatasetAccess($datasetId);

        return $this->urlService->generateKnowledgeGraphUrl($datasetId, $params);
    }

    /**
     * 检查是否需要路由配置
     */
    public function requiresRouteConfiguration(): bool
    {
        return $this->urlService->requiresRouteConfiguration();
    }

    /**
     * 获取路由配置建议
     *
     * @return array{status: string, message: string, action_required: bool, missing_routes?: list<string>, suggested_config?: list<string>}
     */
    public function getRouteConfigurationAdvice(): array
    {
        $availability = $this->urlService->getRouteAvailability();
        $missing = array_filter($availability, static fn ($available) => !$available);

        if ([] === $missing) {
            return [
                'status' => 'ok',
                'message' => '所有路由都已正确配置',
                'action_required' => false,
            ];
        }

        return [
            'status' => 'missing_routes',
            'message' => '缺少路由配置，建议在项目的 config/routes.yaml 中添加以下配置',
            'action_required' => true,
            'missing_routes' => array_keys($missing),
            'suggested_config' => [
                'rag_flow_api_controllers:',
                '  resource: ../../../packages/rag-flow-api-bundle/src/Controller/',
                '  type: attribute',
            ],
        ];
    }

    /**
     * 从AdminContext中提取数据集ID（兼容原Controller逻辑）
     */
    public function extractDatasetIdFromAdminContext(AdminContext $context): int
    {
        // 从query参数获取
        $entityId = $context->getRequest()->query->get('entityId');

        if (null === $entityId || '' === $entityId) {
            // 尝试从entity中获取
            $entityDto = $context->getEntity();
            if ($entityDto->getInstance() instanceof Dataset) {
                $entityId = $entityDto->getInstance()->getId();
            }
        }

        if (null === $entityId || '' === $entityId) {
            throw new \InvalidArgumentException('无法获取数据集ID');
        }

        // 如果是数字，直接使用
        if (is_numeric($entityId)) {
            return (int) $entityId;
        }

        // 如果不是数字，可能是remoteId，需要通过remoteId查找实际的数据库ID
        $dataset = $this->datasetService->findDatasetByRemoteId((string) $entityId);
        if (null === $dataset) {
            throw new \InvalidArgumentException('数据集不存在或无法访问');
        }

        $datasetId = $dataset->getId();
        if (null === $datasetId) {
            throw new \InvalidArgumentException('数据集ID无效');
        }

        return $datasetId;
    }

    /**
     * 批量获取多个数据集的基本信息
     *
     * @param array<int> $datasetIds
     * @return array<int, array<string, mixed>>
     */
    public function getMultipleDatasetsInfo(array $datasetIds): array
    {
        $results = [];

        foreach ($datasetIds as $datasetId) {
            try {
                $results[$datasetId] = $this->getDatasetBasicInfo($datasetId);
            } catch (\InvalidArgumentException $e) {
                $results[$datasetId] = [
                    'error' => $e->getMessage(),
                    'exists' => false,
                ];
            }
        }

        return $results;
    }

    /**
     * 获取系统状态概览
     *
     * @return array<string, mixed>
     */
    public function getSystemOverview(): array
    {
        return [
            'service_available' => true,
            'route_configuration_required' => $this->requiresRouteConfiguration(),
            'available_routes' => $this->urlService->getRouteAvailability(),
            'service_version' => '1.0.0',
            'features' => [
                'dataset_management' => true,
                'document_upload' => true,
                'knowledge_graph' => true,
                'route_fallback' => true,
            ],
        ];
    }
}
