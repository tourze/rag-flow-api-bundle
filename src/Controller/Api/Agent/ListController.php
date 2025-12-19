<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Agent;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;
use Tourze\RAGFlowApiBundle\Service\AgentDataFormatter;

/**
 * 获取智能体列表
 */
final class ListController extends AbstractController
{
    public function __construct(
        private readonly RAGFlowAgentRepository $agentRepository,
        private readonly AgentDataFormatter $dataFormatter,
    ) {
    }

    #[Route(path: '/api/ragflow/agents', name: 'ragflow_api_agents_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $this->parseListFilters($request);
        $agentsData = $this->getAgentsData($filters);

        return new JsonResponse([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $agentsData['list'],
                'total' => $agentsData['total'],
                'page' => $filters['page'],
                'limit' => $filters['limit'],
            ],
        ]);
    }

    /**
     * 获取智能体数据
     *
     * @param array{page: int, limit: int, instance_id: int, status: string|null} $filters
     * @return array{list: array<array<string, mixed>>, total: int}
     */
    private function getAgentsData(array $filters): array
    {
        $agents = $this->findAgentsByFilters($filters);
        $total = $this->countAgentsByFilters($filters);

        return [
            'list' => $this->dataFormatter->formatAgentsForList($agents),
            'total' => $total,
        ];
    }

    /**
     * 解析列表查询参数
     *
     * @return array{page: int, limit: int, instance_id: int, status: string|null}
     */
    private function parseListFilters(Request $request): array
    {
        $status = $request->query->get('status');
        $status = is_string($status) ? $status : null;

        return [
            'page' => $request->query->getInt('page', 1),
            'limit' => $request->query->getInt('limit', 20),
            'instance_id' => $request->query->getInt('instance_id'),
            'status' => $status,
        ];
    }

    /**
     * 根据过滤条件查找智能体
     *
     * @param array{page: int, limit: int, instance_id: int, status: string|null} $filters
     * @return array<RAGFlowAgent>
     */
    private function findAgentsByFilters(array $filters): array
    {
        $qb = $this->agentRepository->createQueryBuilder('a')
            ->orderBy('a.createTime', 'DESC')
            ->setFirstResult(($filters['page'] - 1) * $filters['limit'])
            ->setMaxResults($filters['limit'])
        ;

        if ($filters['instance_id'] > 0) {
            $qb->andWhere('a.ragFlowInstance = :instanceId')
                ->setParameter('instanceId', $filters['instance_id'])
            ;
        }

        if (null !== $filters['status'] && '' !== $filters['status']) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        $result = $qb->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof RAGFlowAgent));
    }

    /**
     * 根据过滤条件统计智能体数量
     *
     * @param array{page: int, limit: int, instance_id: int, status: string|null} $filters
     */
    private function countAgentsByFilters(array $filters): int
    {
        $qb = $this->agentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
        ;

        if ($filters['instance_id'] > 0) {
            $qb->andWhere('a.ragFlowInstance = :instanceId')
                ->setParameter('instanceId', $filters['instance_id'])
            ;
        }

        if (null !== $filters['status'] && '' !== $filters['status']) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
