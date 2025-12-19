<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * 数据集Repository
 *
 * @extends ServiceEntityRepository<Dataset>
 */
#[AsRepository(entityClass: Dataset::class)]
final class DatasetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dataset::class);
    }

    /**
     * 根据远程ID查找数据集
     */
    public function findByRemoteId(string $remoteId, ?RAGFlowInstance $instance = null): ?Dataset
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.remoteId = :remoteId')
            ->setParameter('remoteId', $remoteId)
        ;

        if (null !== $instance) {
            $qb->andWhere('d.ragFlowInstance = :instance')
                ->setParameter('instance', $instance)
            ;
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result instanceof Dataset ? $result : null;
    }

    /**
     * 根据名称查找数据集
     *
     * @return array<Dataset>
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

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 根据状态查找数据集
     *
     * @return array<Dataset>
     */
    public function findByStatus(string $status): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->setParameter('status', $status)
            ->orderBy('d.updateTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 查找需要同步的数据集
     *
     * @return array<Dataset>
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

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 分页查询数据集
     *
     * @param array<string, mixed> $filters
     * @return array{items: array<Dataset>, total: int}
     */
    public function findWithFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('d');

        if (isset($filters['name']) && is_string($filters['name']) && '' !== $filters['name']) {
            $qb->andWhere('d.name LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%')
            ;
        }

        if (isset($filters['status']) && '' !== $filters['status']) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        if (isset($filters['language']) && '' !== $filters['language']) {
            $qb->andWhere('d.language = :language')
                ->setParameter('language', $filters['language'])
            ;
        }

        if (isset($filters['instance_id'])) {
            $qb->andWhere('d.ragFlowInstance = :instance')
                ->setParameter('instance', $filters['instance_id'])
            ;
        }

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

        $items = array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * 保存数据集
     */
    public function save(Dataset $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除数据集
     */
    public function remove(Dataset $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据RAGFlow实例查找数据集
     *
     * @return array<Dataset>
     */
    public function findByInstance(RAGFlowInstance $instance): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.ragFlowInstance = :instance')
            ->setParameter('instance', $instance)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 获取特定实例的数据集选择项（用于EasyAdmin下拉框）
     *
     * @return array<string, string>
     */
    public function getChoicesForEasyAdmin(RAGFlowInstance $instance): array
    {
        $datasets = $this->findByInstance($instance);
        $choices = [];

        foreach ($datasets as $dataset) {
            $remoteId = $dataset->getRemoteId();
            if (null !== $remoteId) {
                $choices[$dataset->getName()] = $remoteId;
            }
        }

        return $choices;
    }

    /**
     * 查找所有启用的数据集
     *
     * @return array<Dataset>
     */
    public function findEnabled(): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 按名称模式查找数据集
     *
     * @return array<Dataset>
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

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 查找最近创建的数据集
     *
     * @return array<Dataset>
     */
    public function findRecentlyCreated(int $limit = 10): array
    {
        $result = $this->createQueryBuilder('d')
            ->orderBy('d.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 分页查询数据集
     *
     * @return array<Dataset>
     */
    public function findWithPagination(int $page = 1, int $limit = 20): array
    {
        $result = $this->createQueryBuilder('d')
            ->orderBy('d.createTime', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 统计数据集总数
     */
    public function countTotalDatasets(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 统计启用的数据集数量
     */
    public function countEnabledDatasets(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 根据远程ID查找或创建数据集
     *
     * 注意：创建新数据集时需要设置 ragFlowInstance 和 name，否则会违反数据库约束
     * 建议使用带 instance 参数的重载版本
     */
    public function findOrCreateByRemoteId(string $remoteId, ?RAGFlowInstance $instance = null): Dataset
    {
        $dataset = $this->findByRemoteId($remoteId, $instance);

        if (null === $dataset) {
            $dataset = new Dataset();
            $dataset->setRemoteId($remoteId);
            $dataset->setName('Dataset-' . $remoteId); // 使用 remoteId 作为默认名称

            // 如果提供了 instance，则设置它
            if (null !== $instance) {
                $dataset->setRagFlowInstance($instance);
            }

            $this->save($dataset);
        }

        return $dataset;
    }

    /**
     * 按关键词搜索数据集
     *
     * @return array<Dataset>
     */
    public function searchByKeywords(string $keywords): array
    {
        $result = $this->createQueryBuilder('d')
            ->where('d.name LIKE :keywords OR d.description LIKE :keywords')
            ->setParameter('keywords', '%' . $keywords . '%')
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 查找带文档统计信息的数据集
     *
     * @return array<array{id: int|null, name: string, document_count: int}>
     */
    public function findWithDocumentStats(): array
    {
        $result = $this->createQueryBuilder('d')
            ->select('d.id', 'd.name', 'COUNT(doc.id) as document_count')
            ->leftJoin('d.documents', 'doc')
            ->groupBy('d.id', 'd.name')
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_map(static function (mixed $item): array {
            if (!is_array($item)) {
                return ['id' => null, 'name' => '', 'document_count' => 0];
            }

            return [
                'id' => isset($item['id']) && is_int($item['id']) ? $item['id'] : null,
                'name' => isset($item['name']) && is_string($item['name']) ? $item['name'] : '',
                'document_count' => isset($item['document_count']) && is_int($item['document_count']) ? $item['document_count'] : 0,
            ];
        }, $result);
    }

    /**
     * 按块配置查找数据集
     *
     * @return array<Dataset>
     */
    public function findByChunkConfig(string $key, mixed $value): array
    {
        // JSON查询在不同数据库中的实现不同，这里使用简单的方式
        $allDatasets = $this->findAll();
        $filtered = [];

        foreach ($allDatasets as $dataset) {
            $config = $dataset->getChunkConfig();
            if (is_array($config) && isset($config[$key]) && $config[$key] === $value) {
                $filtered[] = $dataset;
            }
        }

        return $filtered;
    }

    /**
     * 查找空数据集（没有文档的数据集）
     *
     * @return array<Dataset>
     */
    public function findEmptyDatasets(): array
    {
        $result = $this->createQueryBuilder('d')
            ->leftJoin('d.documents', 'doc')
            ->groupBy('d.id')
            ->having('COUNT(doc.id) = 0')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 查找有聊天助手的数据集
     *
     * @return array<Dataset>
     */
    public function findDatasetsWithChatAssistants(): array
    {
        $result = $this->createQueryBuilder('d')
            ->innerJoin('d.chatAssistants', 'ca')
            ->groupBy('d.id')
            ->having('COUNT(ca.id) > 0')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Dataset));
    }

    /**
     * 获取数据集使用统计
     *
     * @return array{document_count: int, assistant_count: int}
     */
    public function getDatasetUsageStats(Dataset $dataset): array
    {
        // 使用现有的关联关系进行统计，避免直接获取其他Repository
        $qb = $this->createQueryBuilder('d');
        $qb->select(
            'COUNT(DISTINCT doc.id) as document_count',
            'COUNT(DISTINCT ca.id) as assistant_count'
        )
            ->leftJoin('d.documents', 'doc')
            ->leftJoin('d.chatAssistants', 'ca')
            ->where('d.id = :datasetId')
            ->setParameter('datasetId', $dataset->getId())
        ;

        $result = $qb->getQuery()->getSingleResult();
        assert(is_array($result));

        return [
            'document_count' => isset($result['document_count']) && is_int($result['document_count']) ? $result['document_count'] : 0,
            'assistant_count' => isset($result['assistant_count']) && is_int($result['assistant_count']) ? $result['assistant_count'] : 0,
        ];
    }
}
