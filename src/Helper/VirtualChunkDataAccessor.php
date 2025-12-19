<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Tourze\RAGFlowApiBundle\Entity\VirtualChunk;

/**
 * VirtualChunk 数据访问器
 * 负责数据访问、过滤和排序逻辑
 *
 * @internal
 */
final class VirtualChunkDataAccessor
{
    private VirtualChunkFieldValidator $fieldValidator;

    public function __construct()
    {
        $this->fieldValidator = new VirtualChunkFieldValidator();
    }

    /**
     * 从 UnitOfWork 获取实体列表
     *
     * @return list<VirtualChunk>
     */
    public function getEntitiesFromUnitOfWork(EntityManagerInterface $entityManager): array
    {
        $uow = $entityManager->getUnitOfWork();
        $identityMap = $uow->getIdentityMap();

        if (isset($identityMap[VirtualChunk::class])) {
            return array_values($identityMap[VirtualChunk::class]);
        }

        return [];
    }

    /**
     * 根据条件过滤实体列表
     *
     * @param list<VirtualChunk> $entities
     * @param array<string, mixed> $criteria
     * @return list<VirtualChunk>
     */
    public function filterEntities(array $entities, array $criteria): array
    {
        if ($criteria === []) {
            return $entities;
        }

        $filtered = [];
        foreach ($entities as $chunk) {
            if ($this->entityMatchesCriteria($chunk, $criteria)) {
                $filtered[] = $chunk;
            }
        }
        return $filtered;
    }

    /**
     * 对实体列表进行排序
     *
     * @param list<VirtualChunk> $entities
     * @param array<string, string> $orderBy
     * @return list<VirtualChunk>
     */
    public function sortEntities(array $entities, array $orderBy): array
    {
        usort($entities, function ($a, $b) use ($orderBy) {
            return $this->compareEntities($a, $b, $orderBy);
        });

        return $entities;
    }

    /**
     * 比较两个实体
     *
     * @param array<string, string> $orderBy
     */
    private function compareEntities(VirtualChunk $a, VirtualChunk $b, array $orderBy): int
    {
        foreach ($orderBy as $field => $direction) {
            $result = $this->compareEntitiesByField($a, $b, $field, $direction);
            if ($result !== 0) {
                return $result;
            }
        }
        return 0;
    }

    /**
     * 按指定字段比较两个实体
     *
     * @param 'asc'|'desc'|string $direction
     */
    private function compareEntitiesByField(VirtualChunk $a, VirtualChunk $b, string $field, string $direction): int
    {
        $valueA = $this->getFieldValue($a, $field);
        $valueB = $this->getFieldValue($b, $field);

        if ($valueA === null || $valueB === null) {
            return 0;
        }

        if (strtolower($direction) === 'desc') {
            return $valueB <=> $valueA;
        }

        return $valueA <=> $valueB;
    }

    /**
     * 检查实体是否匹配指定条件
     *
     * @param array<string, mixed> $criteria
     */
    private function entityMatchesCriteria(VirtualChunk $chunk, array $criteria): bool
    {
        foreach ($criteria as $field => $value) {
            if (!$this->entityMatchesFieldCriterion($chunk, $field, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 检查实体是否匹配指定字段条件
     *
     * @param mixed $value
     */
    private function entityMatchesFieldCriterion(VirtualChunk $chunk, string $field, $value): bool
    {
        $chunkValue = $this->getFieldValue($chunk, $field);
        if ($chunkValue === null) {
            return false;
        }

        // 处理数组查询条件（如 findBy(['id' => [1, 2, 3]])）
        if (is_array($value)) {
            return in_array($chunkValue, $value, true);
        }

        // 使用严格比较
        return $chunkValue === $value;
    }

    /**
     * 获取字段的值，避免动态方法调用
     *
     * @return mixed
     */
    private function getFieldValue(VirtualChunk $chunk, string $field)
    {
        return match ($field) {
            'id' => $chunk->getId(),
            'datasetId' => $chunk->getDatasetId(),
            'documentId' => $chunk->getDocumentId(),
            'title' => $chunk->getTitle(),
            'content' => $chunk->getContent(),
            'keywords' => $chunk->getKeywords(),
            'similarityScore' => $chunk->getSimilarityScore(),
            'position' => $chunk->getPosition(),
            'length' => $chunk->getLength(),
            'status' => $chunk->getStatus(),
            'language' => $chunk->getLanguage(),
            'createTime' => $chunk->getCreateTime(),
            'updateTime' => $chunk->getUpdateTime(),
            default => null,
        };
    }

    /**
     * 在 UnitOfWork 中注册测试数据
     *
     * @param list<VirtualChunk> $testData
     */
    public function registerTestDataInUnitOfWork(EntityManagerInterface $entityManager, array $testData): void
    {
        $uow = $entityManager->getUnitOfWork();
        foreach ($testData as $entity) {
            $uow->registerManaged(
                $entity,
                ['id' => $entity->getId()],
                []
            );
        }
    }

    /**
     * 根据ID查找实体
     *
     * @param list<VirtualChunk> $entities
     * @param list<VirtualChunk> $testData
     * @param mixed $id
     */
    public function findEntityById(array $entities, array $testData, $id): ?VirtualChunk
    {
        // 首先尝试从实体列表中查找
        foreach ($entities as $entity) {
            if ($entity->getId() === $id) {
                return $entity;
            }
        }

        // 在测试数据中查找
        foreach ($testData as $chunk) {
            if ($chunk->getId() === $id) {
                return $chunk;
            }
        }

        return null;
    }
}