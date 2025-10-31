<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Context;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * 文档请求上下文
 * 负责解析请求中的数据集ID和实体ID
 */
final class DocumentRequestContext
{
    private ?Request $request = null;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    private function getRequest(): ?Request
    {
        if (null === $this->request) {
            $this->request = $this->requestStack->getCurrentRequest();
        }

        return $this->request;
    }

    /**
     * 从请求中提取数据集ID
     */
    public function extractDatasetId(): ?int
    {
        $request = $this->getRequest();
        if (null === $request) {
            return null;
        }

        $urlFilters = $request->query->all('filters');
        if (!isset($urlFilters['dataset'])) {
            return null;
        }

        $datasetId = $urlFilters['dataset'];

        // 处理数组格式的过滤器值
        if (is_array($datasetId)) {
            $datasetId = [] !== $datasetId ? reset($datasetId) : null;
        }

        if (null === $datasetId || '' === $datasetId) {
            return null;
        }

        return is_numeric($datasetId) ? (int) $datasetId : null;
    }

    /**
     * 获取请求中的原始数据集ID（字符串格式）
     */
    public function getRawDatasetId(): ?string
    {
        $request = $this->getRequest();
        if (null === $request) {
            return null;
        }

        $filters = $request->query->all('filters');
        if (!isset($filters['dataset'])) {
            return null;
        }

        $rawDatasetId = $filters['dataset'];

        // 确保是标量值，如果是数组则取第一个元素
        if (is_array($rawDatasetId) && [] !== $rawDatasetId) {
            $firstValue = reset($rawDatasetId);

            return (false !== $firstValue && (is_string($firstValue) || is_numeric($firstValue)))
                ? (string) $firstValue
                : null;
        }

        if (is_string($rawDatasetId) || is_numeric($rawDatasetId)) {
            return (string) $rawDatasetId;
        }

        return null;
    }

    /**
     * 解析实体ID
     */
    public function resolveEntityId(): ?string
    {
        $request = $this->getRequest();
        if (null === $request) {
            return null;
        }

        $entityId = $request->attributes->get('entityId') ?? $request->query->get('entityId');

        if (null === $entityId || '' === $entityId) {
            return null;
        }

        // 确保entityId是字符串类型
        if (is_string($entityId)) {
            return $entityId;
        }

        // 安全的类型转换，避免mixed to string错误
        if (is_int($entityId) || is_float($entityId)) {
            return (string) $entityId;
        }

        if (is_bool($entityId)) {
            return $entityId ? '1' : '0';
        }

        throw new \InvalidArgumentException(sprintf('Entity ID must be convertable to string, %s given', gettype($entityId)));
    }

    /**
     * 获取过滤器参数用于重定向
     *
     * @return array<string, mixed>
     */
    public function getFiltersForRedirect(): array
    {
        $request = $this->getRequest();
        if (null === $request) {
            return [];
        }

        $filters = $request->query->all('filters');

        return [] !== $filters ? ['filters' => $filters] : [];
    }
}
