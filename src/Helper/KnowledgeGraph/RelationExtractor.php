<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\KnowledgeGraph;

/**
 * 知识图谱关系提取器
 *
 * 负责实体关系的提取和处理逻辑
 */
final class RelationExtractor
{
    /**
     * 提取实体的所有关系
     *
     * @param array<string, mixed> $graphResult
     * @return array{relations: array<int, array<string, mixed>>, entities: array<string>}
     */
    public function extractEntityRelations(array $graphResult, string $entityId, int $maxRelations): array
    {
        if (!isset($graphResult['relations']) || !is_array($graphResult['relations'])) {
            return ['relations' => [], 'entities' => []];
        }

        $relations = $graphResult['relations'];
        $result = $this->findRelated($relations, $entityId, $maxRelations);

        return [
            'relations' => $result['relations'],
            'entities' => $result['entities'],
        ];
    }

    /**
     * @param array<mixed> $relations
     * @return array{relations: array<int, array<string, mixed>>, entities: array<string>}
     */
    private function findRelated(array $relations, string $entityId, int $maxRelations): array
    {
        $entityRelations = [];
        $relatedEntityIds = [];

        foreach ($relations as $relation) {
            if (!is_array($relation)) {
                continue;
            }

            // Assert the type to array<string, mixed> for subsequent method calls
            /** @var array<string, mixed> $typedRelation */
            $typedRelation = $relation;

            if ($this->involves($typedRelation, $entityId)) {
                $entityRelations[] = $typedRelation;
                $relatedEntityIds = array_merge($relatedEntityIds, $this->getRelatedIds($typedRelation, $entityId));

                if (count($entityRelations) >= $maxRelations) {
                    break;
                }
            }
        }

        return [
            'relations' => $entityRelations,
            'entities' => array_values(array_unique($relatedEntityIds)),
        ];
    }

    /**
     * @param array<string, mixed> $relation
     */
    private function involves(array $relation, string $entityId): bool
    {
        return $this->isSource($relation, $entityId) || $this->isTarget($relation, $entityId);
    }

    /**
     * @param array<string, mixed> $relation
     */
    private function isSource(array $relation, string $entityId): bool
    {
        return isset($relation['source']) && $relation['source'] === $entityId;
    }

    /**
     * @param array<string, mixed> $relation
     */
    private function isTarget(array $relation, string $entityId): bool
    {
        return isset($relation['target']) && $relation['target'] === $entityId;
    }

    /**
     * @param array<string, mixed> $relation
     * @return array<string>
     */
    private function getRelatedIds(array $relation, string $entityId): array
    {
        $ids = [];

        $sourceId = $this->extractSourceId($relation, $entityId);
        if (null !== $sourceId) {
            $ids[] = $sourceId;
        }

        $targetId = $this->extractTargetId($relation, $entityId);
        if (null !== $targetId) {
            $ids[] = $targetId;
        }

        return $ids;
    }

    /**
     * @param array<string, mixed> $relation
     */
    private function extractSourceId(array $relation, string $entityId): ?string
    {
        if (!isset($relation['source'])) {
            return null;
        }

        $source = $relation['source'];
        if (!is_string($source) || $source === $entityId) {
            return null;
        }

        return $source;
    }

    /**
     * @param array<string, mixed> $relation
     */
    private function extractTargetId(array $relation, string $entityId): ?string
    {
        if (!isset($relation['target'])) {
            return null;
        }

        $target = $relation['target'];
        if (!is_string($target) || $target === $entityId) {
            return null;
        }

        return $target;
    }
}
