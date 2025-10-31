<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper\KnowledgeGraph;

/**
 * 知识图谱统计计算器
 *
 * 负责知识图谱的统计信息计算
 */
final class StatsCalculator
{
    /**
     * 计算知识图谱统计信息
     *
     * @param array<string, mixed> $graphResult
     * @return array{total_entities: int, total_relations: int, entity_types: array<string, int>, relation_types: array<string, int>}
     */
    public function calculate(array $graphResult): array
    {
        $entityStats = $this->calculateEntityStats($graphResult);
        $relationStats = $this->calculateRelationStats($graphResult);

        return array_merge($entityStats, $relationStats);
    }

    /**
     * @param array<string, mixed> $graphResult
     * @return array{total_entities: int, entity_types: array<string, int>}
     */
    private function calculateEntityStats(array $graphResult): array
    {
        $entities = $this->getEntities($graphResult);
        if ([] === $entities) {
            return ['total_entities' => 0, 'entity_types' => []];
        }

        return [
            'total_entities' => count($entities),
            'entity_types' => $this->countTypes($entities),
        ];
    }

    /**
     * @param array<string, mixed> $graphResult
     * @return array{total_relations: int, relation_types: array<string, int>}
     */
    private function calculateRelationStats(array $graphResult): array
    {
        $relations = $this->getRelations($graphResult);
        if ([] === $relations) {
            return ['total_relations' => 0, 'relation_types' => []];
        }

        return [
            'total_relations' => count($relations),
            'relation_types' => $this->countTypes($relations),
        ];
    }

    /**
     * @param array<string, mixed> $graphResult
     * @return array<int, array<string, mixed>>
     */
    private function getEntities(array $graphResult): array
    {
        if (!isset($graphResult['entities']) || !is_array($graphResult['entities'])) {
            return [];
        }

        /** @var array<int, array<string, mixed>> */
        return array_filter($graphResult['entities'], 'is_array');
    }

    /**
     * @param array<string, mixed> $graphResult
     * @return array<int, array<string, mixed>>
     */
    private function getRelations(array $graphResult): array
    {
        if (!isset($graphResult['relations']) || !is_array($graphResult['relations'])) {
            return [];
        }

        /** @var array<int, array<string, mixed>> */
        return array_filter($graphResult['relations'], 'is_array');
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, int>
     */
    private function countTypes(array $items): array
    {
        $typeCount = [];
        foreach ($items as $item) {
            $typeRaw = $item['type'] ?? null;
            if (is_string($typeRaw)) {
                $typeCount[$typeRaw] = ($typeCount[$typeRaw] ?? 0) + 1;
            }
        }

        return $typeCount;
    }
}
