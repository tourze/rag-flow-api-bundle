<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Dataset;

/**
 * 聊天助手Repository
 *
 * @extends ServiceEntityRepository<ChatAssistant>
 */
#[AsRepository(entityClass: ChatAssistant::class)]
final class ChatAssistantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatAssistant::class);
    }

    /**
     * 根据远程ID查找聊天助手
     */
    public function findByRemoteId(string $remoteId): ?ChatAssistant
    {
        $result = $this->createQueryBuilder('ca')
            ->where('ca.remoteId = :remoteId')
            ->setParameter('remoteId', $remoteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof ChatAssistant ? $result : null;
    }

    /**
     * 根据数据集查找聊天助手
     *
     * @return array<ChatAssistant>
     */
    public function findByDataset(?Dataset $dataset): array
    {
        $qb = $this->createQueryBuilder('ca');

        if (null !== $dataset) {
            $qb->where('ca.dataset = :dataset')
                ->setParameter('dataset', $dataset)
            ;
        } else {
            $qb->where('ca.dataset IS NULL');
        }

        $result = $qb->orderBy('ca.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));
    }

    /**
     * 根据名称查找聊天助手
     *
     * @return array<ChatAssistant>
     */
    public function findByName(string $name): array
    {
        $result = $this->createQueryBuilder('ca')
            ->where('ca.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('ca.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));
    }

    /**
     * 查找启用的聊天助手
     *
     * @return array<ChatAssistant>
     */
    public function findEnabled(): array
    {
        $result = $this->createQueryBuilder('ca')
            ->where('ca.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('ca.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));
    }

    /**
     * 查找需要同步的聊天助手
     *
     * @return array<ChatAssistant>
     */
    public function findPendingSync(\DateTimeImmutable $since): array
    {
        $result = $this->createQueryBuilder('ca')
            ->where('ca.lastSyncTime IS NULL OR ca.lastSyncTime < :since')
            ->setParameter('since', $since)
            ->orderBy('ca.lastSyncTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));
    }

    /**
     * 分页查询聊天助手
     *
     * @param array<string, mixed> $filters
     * @return array{items: array<ChatAssistant>, total: int}
     */
    public function findWithFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('ca')
            ->leftJoin('ca.dataset', 'd')
        ;

        $this->applyNameFilter($qb, $filters);
        $this->applyEnabledFilter($qb, $filters);
        $this->applyDatasetFilter($qb, $filters);
        $this->applyLlmModelFilter($qb, $filters);

        $totalQb = clone $qb;
        $total = (int) $totalQb->select('COUNT(ca.id)')->getQuery()->getSingleScalarResult();

        $result = $qb->orderBy('ca.updateTime', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            $result = [];
        }

        $items = array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));

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
            $qb->andWhere('ca.name LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%')
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyEnabledFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['enabled'])) {
            $qb->andWhere('ca.enabled = :enabled')
                ->setParameter('enabled', $filters['enabled'])
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyDatasetFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['dataset_id'])) {
            $qb->andWhere('ca.dataset = :dataset')
                ->setParameter('dataset', $filters['dataset_id'])
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyLlmModelFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['llm_model']) && is_string($filters['llm_model']) && '' !== $filters['llm_model']) {
            $qb->andWhere('ca.llmModel = :llmModel')
                ->setParameter('llmModel', $filters['llm_model'])
            ;
        }
    }

    /**
     * 保存聊天助手
     */
    public function save(ChatAssistant $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除聊天助手
     */
    public function remove(ChatAssistant $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据LLM模型查找聊天助手
     *
     * @return array<ChatAssistant>
     */
    public function findByLlmModel(string $llmModel): array
    {
        $result = $this->createQueryBuilder('ca')
            ->where('ca.llmModel = :llmModel')
            ->setParameter('llmModel', $llmModel)
            ->orderBy('ca.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));
    }

    /**
     * 查找最近创建的聊天助手
     *
     * @return array<ChatAssistant>
     */
    public function findRecentlyCreated(int $limit = 10): array
    {
        $result = $this->createQueryBuilder('ca')
            ->orderBy('ca.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));
    }

    /**
     * 分页查找聊天助手
     *
     * @return array<ChatAssistant>
     */
    public function findWithPagination(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        $result = $this->createQueryBuilder('ca')
            ->orderBy('ca.createTime', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));
    }

    /**
     * 统计数据集中的助手数量
     */
    public function countByDataset(Dataset $dataset): int
    {
        return (int) $this->createQueryBuilder('ca')
            ->select('COUNT(ca.id)')
            ->where('ca.dataset = :dataset')
            ->setParameter('dataset', $dataset)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 根据远程ID查找或创建助手
     */
    public function findOrCreateByRemoteId(string $remoteId): ChatAssistant
    {
        $assistant = $this->findByRemoteId($remoteId);

        if (null === $assistant) {
            $assistant = new ChatAssistant();
            $assistant->setRemoteId($remoteId);
            $assistant->setName('Assistant ' . $remoteId);
            $assistant->setLlmModel('gpt-3.5-turbo');
            $this->getEntityManager()->persist($assistant);
            $this->getEntityManager()->flush();
        }

        return $assistant;
    }

    /**
     * 根据关键词搜索助手
     *
     * @return array<ChatAssistant>
     */
    public function searchByKeywords(string $keywords): array
    {
        $result = $this->createQueryBuilder('ca')
            ->where('ca.name LIKE :keywords OR ca.description LIKE :keywords')
            ->setParameter('keywords', '%' . $keywords . '%')
            ->orderBy('ca.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof ChatAssistant));
    }
}
