<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\KnowledgeGraph;

/**
 * 知识图谱实体过滤器
 *
 * 负责实体的搜索、过滤和匹配逻辑
 */
final class EntityFilter
{
    /**
     * 根据查询条件过滤实体
     *
     * @param array<string, mixed> $graphResult
     * @param array<string, mixed> $searchData
     * @return array<int, array<string, mixed>>
     */
    public function filterByQuery(array $graphResult, array $searchData): array
    {
        $entities = $this->extractEntities($graphResult);
        if ([] === $entities) {
            return [];
        }

        $query = $this->extractSearchQuery($searchData);
        $entityType = $this->extractEntityType($searchData);

        // 确保 limit 是整数
        $limitRaw = $searchData['limit'] ?? 20;
        assert(is_int($limitRaw) || is_string($limitRaw) || is_float($limitRaw));
        $limit = (int) $limitRaw;

        return $this->filterByLimit($entities, $query, $entityType, $limit);
    }

    /**
     * 根据ID列表获取实体
     *
     * @param array<string, mixed> $graphResult
     * @param array<string> $entityIds
     * @return array<int, array<string, mixed>>
     */
    public function getByIds(array $graphResult, array $entityIds): array
    {
        if (!isset($graphResult['entities']) || !is_array($graphResult['entities'])) {
            return [];
        }

        return $this->filterByIds($graphResult['entities'], $entityIds);
    }

    /**
     * @param array<string, mixed> $graphResult
     * @return array<int, array<string, mixed>>
     */
    private function extractEntities(array $graphResult): array
    {
        if (!isset($graphResult['entities']) || !is_array($graphResult['entities'])) {
            return [];
        }

        /** @var array<int, array<string, mixed>> */
        return array_values(array_filter($graphResult['entities'], 'is_array'));
    }

    /**
     * @param array<string, mixed> $searchData
     */
    private function extractSearchQuery(array $searchData): string
    {
        $queryRaw = $searchData['query'] ?? '';

        return is_string($queryRaw) ? strtolower($queryRaw) : '';
    }

    /**
     * @param array<string, mixed> $searchData
     */
    private function extractEntityType(array $searchData): ?string
    {
        $entityTypeRaw = $searchData['entity_type'] ?? null;

        return is_string($entityTypeRaw) ? $entityTypeRaw : null;
    }

    /**
     * @param array<int, array<string, mixed>> $entities
     * @return array<int, array<string, mixed>>
     */
    private function filterByLimit(array $entities, string $query, ?string $entityType, int $limit): array
    {
        $filtered = [];
        foreach ($entities as $entity) {
            if ($this->matches($entity, $query, $entityType)) {
                $filtered[] = $entity;
                if (count($filtered) >= $limit) {
                    break;
                }
            }
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function matches(array $entity, string $query, ?string $entityType): bool
    {
        $nameMatches = $this->nameMatches($entity, $query);
        $typeMatches = $this->typeMatches($entity, $entityType);

        return $nameMatches && $typeMatches;
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function nameMatches(array $entity, string $query): bool
    {
        $name = $this->extractName($entity);

        return '' !== $name && str_contains(strtolower($name), $query);
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function extractName(array $entity): string
    {
        $nameRaw = $entity['name'] ?? '';

        return is_string($nameRaw) ? $nameRaw : '';
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function typeMatches(array $entity, ?string $entityType): bool
    {
        if (null === $entityType) {
            return true;
        }

        return isset($entity['type']) && $entity['type'] === $entityType;
    }

    /**
     * @param array<mixed> $entities
     * @param array<string> $entityIds
     * @return array<int, array<string, mixed>>
     */
    private function filterByIds(array $entities, array $entityIds): array
    {
        $filtered = [];
        foreach ($entities as $entity) {
            if ($this->shouldInclude($entity, $entityIds)) {
                assert(is_array($entity));
                /** @var array<string, mixed> $entity */
                $filtered[] = $entity;
            }
        }

        return $filtered;
    }

    /**
     * @param array<string> $entityIds
     */
    private function shouldInclude(mixed $entity, array $entityIds): bool
    {
        if (!is_array($entity)) {
            return false;
        }

        $entityIdRaw = $entity['id'] ?? null;

        return is_string($entityIdRaw) && in_array($entityIdRaw, $entityIds, true);
    }
}
