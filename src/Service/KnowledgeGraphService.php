<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Service\DatasetService;

/**
 * 知识图谱处理服务
 *
 * 负责处理知识图谱的复杂业务逻辑，包括实体搜索、关系提取、统计计算等
 */
final class KnowledgeGraphService
{
    public function __construct(
        private readonly DatasetService $datasetService,
    ) {
    }

    /**
     * 根据查询条件搜索实体
     *
     * @param array<string, mixed> $searchData
     * @return array<int, array<string, mixed>>
     */
    public function searchEntities(string $datasetId, array $searchData): array
    {
        $graphResult = $this->datasetService->getKnowledgeGraph($datasetId);
        $entities = $this->extractEntities($graphResult);

        if ([] === $entities) {
            return [];
        }

        $query = $this->normalizeSearchQuery($searchData);
        $entityType = $searchData['entity_type'] ?? null;
        $limitValue = $searchData['limit'] ?? 20;
        assert(is_int($limitValue) || is_numeric($limitValue));
        $limit = (int) $limitValue;

        $entityTypeStr = is_string($entityType) ? $entityType : null;

        return $this->filterEntities($entities, $query, $entityTypeStr, $limit);
    }

    /**
     * 获取实体的关系数据
     *
     * @return array{relations: array<int, array<string, mixed>>, entities: array<int, array<string, mixed>>}
     */
    public function getEntityRelations(string $datasetId, string $entityId, int $maxRelations): array
    {
        $graphResult = $this->datasetService->getKnowledgeGraph($datasetId);

        if (!$this->hasRelationsArray($graphResult)) {
            return ['relations' => [], 'entities' => []];
        }

        /** @var array<int, array<string, mixed>> $relations PHPStan type refinement */
        $relations = $graphResult['relations'];
        $matchingRelations = $this->findRelationsInvolvingEntity($relations, $entityId, $maxRelations);
        $relatedEntityIds = $this->extractRelatedEntityIds($matchingRelations, $entityId);
        $relatedEntities = $this->findEntitiesByIds($graphResult, $relatedEntityIds);

        return ['relations' => $matchingRelations, 'entities' => $relatedEntities];
    }

    /**
     * 计算图谱统计信息
     *
     * @return array{total_entities: int, total_relations: int, entity_types: array<string, int>, relation_types: array<string, int>}
     */
    public function calculateStats(string $datasetId): array
    {
        $graphResult = $this->datasetService->getKnowledgeGraph($datasetId);

        return [
            'total_entities' => $this->countTotalEntities($graphResult),
            'total_relations' => $this->countTotalRelations($graphResult),
            'entity_types' => $this->countEntityTypes($graphResult),
            'relation_types' => $this->countRelationTypes($graphResult),
        ];
    }

    /**
     * @param array<string, mixed> $graphResult
     * @return array<int, array<string, mixed>>
     */
    private function extractEntities(array $graphResult): array
    {
        if (!$this->hasEntitiesArray($graphResult)) {
            return [];
        }

        assert(is_array($graphResult['entities']));

        /** @var array<int, array<string, mixed>> */
        return array_filter($graphResult['entities'], 'is_array');
    }

    /**
     * 检查图谱结果是否包含实体数组
     *
     * @param array<string, mixed> $graphResult
     */
    private function hasEntitiesArray(array $graphResult): bool
    {
        return isset($graphResult['entities']) && is_array($graphResult['entities']);
    }

    /**
     * 检查图谱结果是否包含关系数组
     *
     * @param array<string, mixed> $graphResult
     */
    private function hasRelationsArray(array $graphResult): bool
    {
        return isset($graphResult['relations']) && is_array($graphResult['relations']);
    }

    /**
     * 规范化搜索查询
     *
     * @param array<string, mixed> $searchData
     */
    private function normalizeSearchQuery(array $searchData): string
    {
        /** @var mixed $queryRaw */
        $queryRaw = $searchData['query'] ?? '';

        return is_string($queryRaw) ? strtolower($queryRaw) : '';
    }

    /**
     * 过滤实体
     *
     * @param array<int, array<string, mixed>> $entities
     * @return array<int, array<string, mixed>>
     */
    private function filterEntities(array $entities, string $query, ?string $entityType, int $limit): array
    {
        $filtered = [];
        $count = 0;

        foreach ($entities as $entity) {
            if ($this->entityMatchesCriteria($entity, $query, $entityType)) {
                $filtered[] = $entity;
                ++$count;

                if ($count >= $limit) {
                    break;
                }
            }
        }

        return $filtered;
    }

    /**
     * 检查实体是否匹配搜索条件
     *
     * @param array<string, mixed> $entity
     */
    private function entityMatchesCriteria(array $entity, string $query, ?string $entityType): bool
    {
        return $this->entityNameContainsQuery($entity, $query)
            && $this->entityTypeMatches($entity, $entityType);
    }

    /**
     * 检查实体名称是否包含查询字符串
     *
     * @param array<string, mixed> $entity
     */
    private function entityNameContainsQuery(array $entity, string $query): bool
    {
        if ('' === $query) {
            return true;
        }

        /** @var mixed $nameRaw */
        $nameRaw = $entity['name'] ?? '';
        $name = is_string($nameRaw) ? strtolower($nameRaw) : '';

        return str_contains($name, $query);
    }

    /**
     * 检查实体类型是否匹配
     *
     * @param array<string, mixed> $entity
     */
    private function entityTypeMatches(array $entity, ?string $entityType): bool
    {
        if (null === $entityType) {
            return true;
        }

        /** @var mixed $typeRaw */
        $typeRaw = $entity['type'] ?? null;

        return is_string($typeRaw) && $typeRaw === $entityType;
    }

    /**
     * 查找涉及指定实体的关系
     *
     * @param array<int, array<string, mixed>> $relations
     * @return array<int, array<string, mixed>>
     */
    private function findRelationsInvolvingEntity(array $relations, string $entityId, int $maxRelations): array
    {
        $matchingRelations = [];
        $count = 0;

        foreach ($relations as $relation) {
            /** @var array<string, mixed> $relation PHPStan type refinement */
            if ($this->relationInvolvesEntity($relation, $entityId)) {
                $matchingRelations[] = $relation;
                ++$count;

                if ($count >= $maxRelations) {
                    break;
                }
            }
        }

        return $matchingRelations;
    }

    /**
     * 检查关系是否涉及指定实体
     *
     * @param array<string, mixed> $relation
     */
    private function relationInvolvesEntity(array $relation, string $entityId): bool
    {
        /** @var mixed $sourceRaw */
        $sourceRaw = $relation['source'] ?? null;
        /** @var mixed $targetRaw */
        $targetRaw = $relation['target'] ?? null;

        return ($sourceRaw === $entityId) || ($targetRaw === $entityId);
    }

    /**
     * 从关系中提取相关实体ID
     *
     * @param array<int, array<string, mixed>> $relations
     * @return array<string>
     */
    private function extractRelatedEntityIds(array $relations, string $excludeEntityId): array
    {
        $entityIds = [];

        foreach ($relations as $relation) {
            /** @var array<string, mixed> $relation PHPStan type refinement */
            $sourceId = $this->getRelatedEntityId($relation, 'source', $excludeEntityId);
            $targetId = $this->getRelatedEntityId($relation, 'target', $excludeEntityId);

            if (null !== $sourceId) {
                $entityIds[] = $sourceId;
            }
            if (null !== $targetId) {
                $entityIds[] = $targetId;
            }
        }

        return array_unique($entityIds);
    }

    /**
     * 从关系中获取相关实体ID
     *
     * @param array<string, mixed> $relation
     */
    private function getRelatedEntityId(array $relation, string $key, string $excludeEntityId): ?string
    {
        /** @var mixed $idRaw */
        $idRaw = $relation[$key] ?? null;

        if (!is_string($idRaw) || $idRaw === $excludeEntityId) {
            return null;
        }

        return $idRaw;
    }

    /**
     * 根据ID查找实体
     *
     * @param array<string, mixed> $graphResult
     * @param array<string> $entityIds
     * @return array<int, array<string, mixed>>
     */
    private function findEntitiesByIds(array $graphResult, array $entityIds): array
    {
        if (!$this->hasEntitiesArray($graphResult) || [] === $entityIds) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $matchingEntities */
        $matchingEntities = [];

        assert(is_array($graphResult['entities']));
        foreach ($graphResult['entities'] as $entity) {
            if (!is_array($entity)) {
                continue;
            }

            /** @var mixed $idRaw */
            $idRaw = $entity['id'] ?? null;
            if (is_string($idRaw) && in_array($idRaw, $entityIds, true)) {
                /** @var array<string, mixed> $entity */
                $matchingEntities[] = $entity;
            }
        }

        return $matchingEntities;
    }

    /**
     * 计算总实体数
     *
     * @param array<string, mixed> $graphResult
     */
    private function countTotalEntities(array $graphResult): int
    {
        return count($this->extractEntities($graphResult));
    }

    /**
     * 计算总关系数
     *
     * @param array<string, mixed> $graphResult
     */
    private function countTotalRelations(array $graphResult): int
    {
        if (!$this->hasRelationsArray($graphResult)) {
            return 0;
        }

        assert(is_array($graphResult['relations']));

        return count(array_filter($graphResult['relations'], 'is_array'));
    }

    /**
     * 统计实体类型数量
     *
     * @param array<string, mixed> $graphResult
     * @return array<string, int>
     */
    private function countEntityTypes(array $graphResult): array
    {
        $entities = $this->extractEntities($graphResult);
        $typeCounts = [];

        foreach ($entities as $entity) {
            /** @var mixed $typeRaw */
            $typeRaw = $entity['type'] ?? null;
            if (is_string($typeRaw)) {
                $typeCounts[$typeRaw] = ($typeCounts[$typeRaw] ?? 0) + 1;
            }
        }

        return $typeCounts;
    }

    /**
     * 统计关系类型数量
     *
     * @param array<string, mixed> $graphResult
     * @return array<string, int>
     */
    private function countRelationTypes(array $graphResult): array
    {
        if (!$this->hasRelationsArray($graphResult)) {
            return [];
        }

        assert(is_array($graphResult['relations']));
        $typeCounts = [];
        /** @var array<int, array<string, mixed>> $relations PHPStan type refinement */
        $relations = array_filter($graphResult['relations'], 'is_array');

        foreach ($relations as $relation) {
            /** @var mixed $typeRaw */
            $typeRaw = $relation['type'] ?? null;
            if (is_string($typeRaw)) {
                $typeCounts[$typeRaw] = ($typeCounts[$typeRaw] ?? 0) + 1;
            }
        }

        return $typeCounts;
    }
}
