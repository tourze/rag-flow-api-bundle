<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper;

use Doctrine\ORM\Persisters\Exception\UnrecognizedField;

/**
 * VirtualChunk 字段验证器
 * 负责验证查询条件和排序字段的有效性
 *
 * @internal
 */
final class VirtualChunkFieldValidator
{
    /**
     * 获取有效的字段列表
     *
     * @return array<string>
     */
    public function getValidFields(): array
    {
        return [
            'id', 'datasetId', 'documentId', 'title', 'content', 'keywords',
            'similarityScore', 'position', 'length', 'status', 'language',
            'createTime', 'updateTime'
        ];
    }

    /**
     * 验证查询条件中的字段
     *
     * @param array<string, mixed> $criteria
     * @throws UnrecognizedField
     */
    public function validateCriteriaFields(array $criteria): void
    {
        if ($criteria === []) {
            return;
        }

        $this->validateFields(array_keys($criteria));
    }

    /**
     * 验证排序字段
     *
     * @param array<string, string>|null $orderBy
     * @throws UnrecognizedField
     */
    public function validateOrderByFields(?array $orderBy): void
    {
        if ($orderBy === null) {
            return;
        }

        $this->validateFields(array_keys($orderBy));
    }

    /**
     * 验证字段列表
     *
     * @param array<string> $fields
     * @throws UnrecognizedField
     */
    private function validateFields(array $fields): void
    {
        $validFields = $this->getValidFields();
        foreach ($fields as $field) {
            if ($this->isInvalidField($field, $validFields)) {
                throw new UnrecognizedField("Field '{$field}' is not recognized.");
            }
        }
    }

    /**
     * 检查字段是否无效
     *
     * @param array<string> $validFields
     */
    private function isInvalidField(string $field, array $validFields): bool
    {
        return str_contains($field, '_') || !in_array($field, $validFields, true);
    }
}