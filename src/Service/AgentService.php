<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;

/**
 * 智能体服务类
 */
final class AgentService
{
    public function __construct(
        private readonly RAGFlowAgentRepository $agentRepository,
        private readonly RAGFlowInstanceRepository $instanceRepository,
    ) {
    }

    /**
     * 根据ID查找智能体
     */
    public function findAgentById(int $id): ?RAGFlowAgent
    {
        return $this->agentRepository->find($id);
    }

    /**
     * 根据ID查找实例
     */
    public function findInstance(int $instanceId): ?RAGFlowInstance
    {
        return $this->instanceRepository->find($instanceId);
    }

    /**
     * 创建智能体不存在错误响应
     */
    public function createNotFoundError(string $message = '智能体不存在'): JsonResponse
    {
        return new JsonResponse([
            'code' => 404,
            'message' => $message,
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * 根据过滤条件查找智能体
     *
     * @param array{page: int, limit: int, instance_id: int, status: string|null} $filters
     * @return array<RAGFlowAgent>
     */
    public function findAgentsByFilters(array $filters): array
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

        /** @var array<RAGFlowAgent> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据过滤条件统计智能体数量
     *
     * @param array{page: int, limit: int, instance_id: int, status: string|null} $filters
     */
    public function countAgentsByFilters(array $filters): int
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

    /**
     * 获取智能体统计数据
     *
     * @return array<int, array{status: string, count: int}>
     */
    public function getAgentStats(): array
    {
        /** @var array<int, array{status: string, count: int}> */
        return $this->agentRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->groupBy('a.status')
            ->getQuery()
            ->getResult()
        ;
    }
}
