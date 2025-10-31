<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * RAGFlow URL生成服务
 *
 * 提供路由相关和路由无关的URL生成功能
 */
class RAGFlowUrlService
{
    public function __construct(
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
    ) {
    }

    /**
     * 生成数据集文档管理路径（不依赖路由）
     */
    public function generateDocumentManagementPath(int $datasetId): string
    {
        return "/admin/datasets/{$datasetId}/documents";
    }

    /**
     * 生成数据集知识图谱路径（不依赖路由）
     */
    public function generateKnowledgeGraphPath(int $datasetId): string
    {
        return "/admin/datasets/{$datasetId}/knowledge-graph";
    }

    /**
     * 生成数据集文档上传路径（不依赖路由）
     */
    public function generateDocumentUploadPath(int $datasetId): string
    {
        return "/admin/datasets/{$datasetId}/documents/upload";
    }

    /**
     * 生成数据集详情路径（不依赖路由）
     */
    public function generateDatasetDetailPath(int $datasetId): string
    {
        return "/admin/datasets/{$datasetId}";
    }

    /**
     * 生成基于路由的URL（优先）
     * 如果路由不存在，回退到路径生成
     *
     * @param array<string, mixed> $params
     */
    public function generateDocumentManagementUrl(int $datasetId, array $params = []): string
    {
        if (null !== $this->urlGenerator) {
            try {
                return $this->urlGenerator->generate('dataset_documents_index', [
                    'datasetId' => $datasetId,
                    ...$params,
                ]);
            } catch (RouteNotFoundException $e) {
                // 路由不存在，回退到路径
            }
        }

        // 回退到路径生成
        $path = $this->generateDocumentManagementPath($datasetId);
        if ([] !== $params) {
            $path .= '?' . http_build_query($params);
        }

        return $path;
    }

    /**
     * 生成基于路由的知识图谱URL（优先）
     * 如果路由不存在，回退到路径生成
     *
     * @param array<string, mixed> $params
     */
    public function generateKnowledgeGraphUrl(int $datasetId, array $params = []): string
    {
        if (null !== $this->urlGenerator) {
            try {
                return $this->urlGenerator->generate('dataset_knowledge_graph', [
                    'datasetId' => $datasetId,
                    ...$params,
                ]);
            } catch (RouteNotFoundException $e) {
                // 路由不存在，回退到路径
            }
        }

        // 回退到路径生成
        $path = $this->generateKnowledgeGraphPath($datasetId);
        if ([] !== $params) {
            $path .= '?' . http_build_query($params);
        }

        return $path;
    }

    /**
     * 生成基于路由的文档上传URL（优先）
     * 如果路由不存在，回退到路径生成
     *
     * @param array<string, mixed> $params
     */
    public function generateDocumentUploadUrl(int $datasetId, array $params = []): string
    {
        if (null !== $this->urlGenerator) {
            try {
                return $this->urlGenerator->generate('dataset_documents_upload', [
                    'datasetId' => $datasetId,
                    ...$params,
                ]);
            } catch (RouteNotFoundException $e) {
                // 路由不存在，回退到路径
            }
        }

        // 回退到路径生成
        $path = $this->generateDocumentUploadPath($datasetId);
        if ([] !== $params) {
            $path .= '?' . http_build_query($params);
        }

        return $path;
    }

    /**
     * 检查特定路由是否存在
     */
    public function hasRoute(string $routeName): bool
    {
        if (null === $this->urlGenerator) {
            return false;
        }

        try {
            $this->urlGenerator->generate($routeName);

            return true;
        } catch (RouteNotFoundException $e) {
            return false;
        }
    }

    /**
     * 获取所有可用的RAGFlow相关路由状态
     *
     * @return array<string, bool>
     */
    public function getRouteAvailability(): array
    {
        return [
            'dataset_documents_index' => $this->hasRoute('dataset_documents_index'),
            'dataset_documents_upload' => $this->hasRoute('dataset_documents_upload'),
            'dataset_knowledge_graph' => $this->hasRoute('dataset_knowledge_graph'),
        ];
    }

    /**
     * 检查是否需要路由配置
     */
    public function requiresRouteConfiguration(): bool
    {
        $availability = $this->getRouteAvailability();

        return !in_array(true, $availability, true);
    }
}
