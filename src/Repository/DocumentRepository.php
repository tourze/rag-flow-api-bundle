<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

/**
 * 文档Repository
 *
 * @extends ServiceEntityRepository<Document>
 */
#[AsRepository(entityClass: Document::class)]
final class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * 根据远程ID查找文档
     */
    public function findByRemoteId(string $remoteId): ?Document
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.remoteId = :remoteId')
            ->setParameter('remoteId', $remoteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof Document ? $result : null;
    }

    /**
     * 根据数据集查找文档
     *
     * @return array<Document>
     */
    public function findByDataset(Dataset $dataset): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.dataset = :dataset')
            ->setParameter('dataset', $dataset)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 根据名称查找文档
     *
     * @return array<Document>
     */
    public function findByName(string $name): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 根据状态查找文档
     *
     * @return array<Document>
     */
    public function findByStatus(DocumentStatus|string $status): array
    {
        $statusValue = $status instanceof DocumentStatus ? $status->value : $status;

        $result = $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->setParameter('status', $statusValue)
            ->orderBy('d.updateTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 查找需要同步的文档
     *
     * @return array<Document>
     */
    public function findPendingSync(\DateTimeImmutable $since): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.lastSyncTime IS NULL OR d.lastSyncTime < :since')
            ->setParameter('since', $since)
            ->orderBy('d.lastSyncTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 分页查询文档
     *
     * @param array<string, mixed> $filters
     * @return array{items: array<Document>, total: int}
     */
    public function findWithFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.dataset', 'ds')
        ;

        $this->applyNameFilter($qb, $filters);
        $this->applyStatusFilter($qb, $filters);
        $this->applyTypeFilter($qb, $filters);
        $this->applyDatasetFilter($qb, $filters);

        $totalQb = clone $qb;
        $total = (int) $totalQb->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();

        $result = $qb->orderBy('d.updateTime', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            $result = [];
        }

        $items = array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyNameFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['name']) && is_string($filters['name']) && '' !== $filters['name']) {
            $qb->andWhere('d.name LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%')
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyStatusFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['status']) && is_string($filters['status']) && '' !== $filters['status']) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyTypeFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['type']) && is_string($filters['type']) && '' !== $filters['type']) {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $filters['type'])
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyDatasetFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['dataset_id'])) {
            $qb->andWhere('d.dataset = :dataset')
                ->setParameter('dataset', $filters['dataset_id'])
            ;
        }
    }

    /**
     * 统计文档数量
     */
    public function countByDataset(Dataset $dataset): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.dataset = :dataset')
            ->setParameter('dataset', $dataset)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 统计已处理的文档数量
     */
    public function countProcessedByDataset(Dataset $dataset): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.dataset = :dataset')
            ->andWhere('d.status = :status')
            ->setParameter('dataset', $dataset)
            ->setParameter('status', 'parsed')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 统计待处理的文档数量
     */
    public function countPendingByDataset(Dataset $dataset): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.dataset = :dataset')
            ->andWhere('d.status IN (:statuses)')
            ->setParameter('dataset', $dataset)
            ->setParameter('statuses', ['pending', 'parsing', 'failed'])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 保存文档
     */
    public function save(Document $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除文档
     */
    public function remove(Document $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找失败的文档(包括FAILED和SYNC_FAILED状态)
     *
     * @return array<Document>
     */
    public function findFailedDocuments(): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->setParameter('statuses', ['failed', 'sync_failed'])
            ->orderBy('d.updateTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 根据文件类型查找文档
     *
     * @return array<Document>
     */
    public function findByFileType(string $fileType): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.type = :type')
            ->setParameter('type', $fileType)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 查找进度大于或等于指定值的文档
     *
     * @return array<Document>
     */
    public function findWithProgress(float $minProgress): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.progress >= :minProgress')
            ->setParameter('minProgress', $minProgress)
            ->orderBy('d.progress', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 根据名称模式查找文档
     *
     * @return array<Document>
     */
    public function findByNamePattern(string $pattern): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.name LIKE :pattern')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 查找最近更新的文档
     *
     * @return array<Document>
     */
    public function findRecentlyUpdated(int $limit = 10): array
    {
        $result = $this->createQueryBuilder('d')
            ->orderBy('d.updateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 查找大于指定大小的文档
     *
     * @return array<Document>
     */
    public function findLargeDocuments(int $minSize): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.size > :minSize')
            ->setParameter('minSize', $minSize)
            ->orderBy('d.size', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }

    /**
     * 根据状态统计文档数量
     */
    public function countByStatus(DocumentStatus|string $status): int
    {
        $statusValue = $status instanceof DocumentStatus ? $status->value : $status;

        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.status = :status')
            ->setParameter('status', $statusValue)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 获取各状态的文档统计
     *
     * @return array<string, int>
     */
    public function getStatusStatistics(): array
    {
        $result = $this->createQueryBuilder('d')
            ->select('d.status, COUNT(d.id) as count')
            ->groupBy('d.status')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        $statistics = [];
        foreach ($result as $row) {
            if (is_array($row) && isset($row['status'], $row['count'])) {
                // 确保 status 是字符串或标量类型
                if (!is_string($row['status']) && !is_scalar($row['status'])) {
                    continue;
                }
                $status = (string) $row['status'];
                // Assert the type before casting
                assert(is_int($row['count']) || is_numeric($row['count']));
                $count = (int) $row['count'];
                $statistics[$status] = $count;
            }
        }

        return $statistics;
    }

    /**
     * 查找需要重试的文档(失败状态且有文件路径)
     *
     * @return array<Document>
     */
    public function findDocumentsNeedingRetry(): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->andWhere('d.filePath IS NOT NULL')
            ->setParameter('statuses', ['failed', 'sync_failed'])
            ->orderBy('d.updateTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Document));
    }
}
