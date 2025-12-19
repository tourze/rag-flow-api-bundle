<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * RAGFlow智能体仓储
 *
 * @extends ServiceEntityRepository<RAGFlowAgent>
 */
#[AsRepository(entityClass: RAGFlowAgent::class)]
final class RAGFlowAgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RAGFlowAgent::class);
    }

    /**
     * @return array<RAGFlowAgent>
     */
    public function findByInstance(RAGFlowInstance $instance): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.ragFlowInstance = :instance')
            ->setParameter('instance', $instance)
            ->orderBy('a.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof RAGFlowAgent));
    }

    /**
     * @return array<RAGFlowAgent>
     */
    public function findNeedingSync(): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.remoteId IS NULL OR a.status = :syncFailed')
            ->setParameter('syncFailed', 'sync_failed')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof RAGFlowAgent));
    }

    public function findByRemoteId(string $remoteId, RAGFlowInstance $instance): ?RAGFlowAgent
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.remoteId = :remoteId')
            ->andWhere('a.ragFlowInstance = :instance')
            ->setParameter('remoteId', $remoteId)
            ->setParameter('instance', $instance)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof RAGFlowAgent ? $result : null;
    }

    /**
     * @return array<RAGFlowAgent>
     */
    public function findByStatus(string $status): array
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof RAGFlowAgent));
    }

    /**
     * 保存智能体
     */
    public function save(RAGFlowAgent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除智能体
     */
    public function remove(RAGFlowAgent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
