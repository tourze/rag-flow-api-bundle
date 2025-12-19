<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Agent;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Helper\AgentStatsProcessor;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;

/**
 * 获取智能体状态统计
 */
final class StatsController extends AbstractController
{
    public function __construct(
        private readonly RAGFlowAgentRepository $agentRepository,
    ) {
    }

    #[Route(path: '/api/ragflow/agents/stats', name: 'ragflow_api_agents_stats', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $stats = $this->getAgentStats();
        $statusStats = $this->processStatsData($stats);
        $totalCount = array_sum($statusStats);

        return new JsonResponse([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'total' => $totalCount,
                'by_status' => $statusStats,
            ],
        ]);
    }

    /**
     * 获取智能体统计数据
     */
    private function getAgentStats(): mixed
    {
        return $this->agentRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->groupBy('a.status')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param mixed $stats
     * @return array<string, int>
     */
    private function processStatsData($stats): array
    {
        if (!is_array($stats)) {
            return [];
        }

        $processor = new AgentStatsProcessor();

        return $processor->processStats($stats);
    }
}
