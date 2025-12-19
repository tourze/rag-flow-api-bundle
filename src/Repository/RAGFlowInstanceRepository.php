<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * @extends ServiceEntityRepository<RAGFlowInstance>
 */
#[AsRepository(entityClass: RAGFlowInstance::class)]
final class RAGFlowInstanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RAGFlowInstance::class);
    }

    /**
     * 查找所有启用的实例
     *
     * @return array<RAGFlowInstance>
     */
    public function findEnabled(): array
    {
        /** @var array<RAGFlowInstance> $result */
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_values($result);
    }

    /**
     * 查找所有健康的实例
     *
     * @return array<RAGFlowInstance>
     */
    public function findHealthy(): array
    {
        /** @var array<RAGFlowInstance> $result */
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.healthy = :healthy')
            ->andWhere('r.enabled = :enabled')
            ->setParameter('healthy', true)
            ->setParameter('enabled', true)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_values($result);
    }

    /**
     * 根据名称查找实例
     */
    public function findByName(string $name): ?RAGFlowInstance
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof RAGFlowInstance ? $result : null;
    }

    /**
     * 查找需要健康检查的实例（超过指定分钟数未检查）
     *
     * @return array<RAGFlowInstance>
     */
    public function findNeedHealthCheck(int $minutes = 30): array
    {
        $checkTime = new \DateTimeImmutable(sprintf('-%d minutes', $minutes));

        /** @var array<RAGFlowInstance> $result */
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.enabled = :enabled')
            ->andWhere('r.lastHealthCheck IS NULL OR r.lastHealthCheck < :checkTime')
            ->setParameter('enabled', true)
            ->setParameter('checkTime', $checkTime)
            ->orderBy('r.lastHealthCheck', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_values($result);
    }

    /**
     * 保存实例
     */
    public function save(RAGFlowInstance $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实例
     */
    public function remove(RAGFlowInstance $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
