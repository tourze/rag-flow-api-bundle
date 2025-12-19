<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Document;

/**
 * 文档块Repository
 *
 * @extends ServiceEntityRepository<Chunk>
 */
#[AsRepository(entityClass: Chunk::class)]
final class ChunkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chunk::class);
    }

    /**
     * 根据远程ID查找文档块
     */
    public function findByRemoteId(string $remoteId): ?Chunk
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.remoteId = :remoteId')
            ->setParameter('remoteId', $remoteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof Chunk ? $result : null;
    }

    /**
     * 根据文档查找文档块
     *
     * @return array<Chunk>
     */
    public function findByDocument(Document $document): array
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.document = :document')
            ->setParameter('document', $document)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Chunk));
    }

    /**
     * 根据内容查找文档块
     *
     * @return array<Chunk>
     */
    public function findByContent(string $content): array
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.content LIKE :content')
            ->setParameter('content', '%' . $content . '%')
            ->orderBy('c.similarityScore', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Chunk));
    }

    /**
     * 查找需要同步的文档块
     *
     * @return array<Chunk>
     */
    public function findPendingSync(\DateTimeImmutable $since): array
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.lastSyncTime IS NULL OR c.lastSyncTime < :since')
            ->setParameter('since', $since)
            ->orderBy('c.lastSyncTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Chunk));
    }

    /**
     * 分页查询文档块
     *
     * @param array<string, mixed> $filters
     * @return array{items: array<Chunk>, total: int}
     */
    public function findWithFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.document', 'd')
            ->leftJoin('d.dataset', 'ds')
        ;

        $this->applyContentFilter($qb, $filters);
        $this->applyDocumentFilter($qb, $filters);
        $this->applyDatasetFilter($qb, $filters);
        $this->applySimilarityFilter($qb, $filters);

        $totalQb = clone $qb;
        $total = (int) $totalQb->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

        $result = $qb->orderBy('c.similarityScore', 'DESC')
            ->addOrderBy('c.position', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            $result = [];
        }

        $items = array_values(array_filter($result, static fn ($item): bool => $item instanceof Chunk));

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyContentFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['content']) && is_string($filters['content']) && '' !== $filters['content']) {
            $qb->andWhere('c.content LIKE :content')
                ->setParameter('content', '%' . $filters['content'] . '%')
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyDocumentFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['document_id'])) {
            $qb->andWhere('c.document = :document')
                ->setParameter('document', $filters['document_id'])
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
     * @param array<string, mixed> $filters
     */
    private function applySimilarityFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['min_similarity'])) {
            $qb->andWhere('c.similarityScore >= :minSimilarity')
                ->setParameter('minSimilarity', $filters['min_similarity'])
            ;
        }
    }

    /**
     * 统计文档块数量
     */
    public function countByDocument(Document $document): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.document = :document')
            ->setParameter('document', $document)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 保存文档块
     */
    public function save(Chunk $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除文档块
     */
    public function remove(Chunk $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
