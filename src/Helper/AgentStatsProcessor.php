<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Helper;

/**
 * 智能体统计数据处理辅助类
 */
final class AgentStatsProcessor
{
    /**
     * 处理统计数据
     *
     * @param mixed $stats
     * @return array<string, int>
     */
    public function processStats(mixed $stats): array
    {
        $statusStats = [];

        if (!is_array($stats)) {
            return $statusStats;
        }

        foreach ($stats as $stat) {
            $processedStat = $this->processSingleStat($stat);
            if (null !== $processedStat) {
                $statusStats[$processedStat['status']] = $processedStat['count'];
            }
        }

        return $statusStats;
    }

    /**
     * 处理单个统计数据项
     *
     * @param mixed $stat
     * @return array{status: string, count: int}|null
     */
    private function processSingleStat(mixed $stat): ?array
    {
        if (!is_array($stat)) {
            return null;
        }

        if (!isset($stat['status'], $stat['count'])) {
            return null;
        }

        $status = $stat['status'];
        $count = $stat['count'];

        if (!is_string($status)) {
            return null;
        }

        if (!is_numeric($count)) {
            return null;
        }

        return [
            'status' => $status,
            'count' => (int) $count,
        ];
    }
}
