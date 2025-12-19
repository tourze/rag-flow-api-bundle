<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Conversation;

/**
 * 对话Repository
 *
 * @extends ServiceEntityRepository<Conversation>
 */
#[AsRepository(entityClass: Conversation::class)]
final class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * 根据远程ID查找对话
     */
    public function findByRemoteId(string $remoteId): ?Conversation
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.remoteId = :remoteId')
            ->setParameter('remoteId', $remoteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof Conversation ? $result : null;
    }

    /**
     * 根据聊天助手查找对话
     *
     * @return array<Conversation>
     */
    public function findByChatAssistant(ChatAssistant $chatAssistant): array
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.chatAssistant = :chatAssistant')
            ->setParameter('chatAssistant', $chatAssistant)
            ->orderBy('c.lastActivityTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Conversation));
    }

    /**
     * 根据用户ID查找对话
     *
     * @return array<Conversation>
     */
    public function findByUserId(string $userId): array
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.lastActivityTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Conversation));
    }

    /**
     * 根据标题查找对话
     *
     * @return array<Conversation>
     */
    public function findByTitle(string $title): array
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.title LIKE :title')
            ->setParameter('title', '%' . $title . '%')
            ->orderBy('c.lastActivityTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Conversation));
    }

    /**
     * 查找活跃对话
     *
     * @return array<Conversation>
     */
    public function findActiveConversations(\DateTimeImmutable $since): array
    {
        $result = $this->createQueryBuilder('c')
            ->where('c.lastActivityTime >= :since')
            ->setParameter('since', $since)
            ->orderBy('c.lastActivityTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Conversation));
    }

    /**
     * 查找需要同步的对话
     *
     * @return array<Conversation>
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

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof Conversation));
    }

    /**
     * 分页查询对话
     *
     * @param array<string, mixed> $filters
     * @return array{items: array<Conversation>, total: int}
     */
    public function findWithFilters(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.chatAssistant', 'ca')
        ;

        $this->applyTitleFilter($qb, $filters);
        $this->applyUserFilter($qb, $filters);
        $this->applyChatAssistantFilter($qb, $filters);
        $this->applyStatusFilter($qb, $filters);
        $this->applySinceFilter($qb, $filters);

        $totalQb = clone $qb;
        $total = (int) $totalQb->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

        $result = $qb->orderBy('c.lastActivityTime', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            $result = [];
        }

        $items = array_values(array_filter($result, static fn ($item): bool => $item instanceof Conversation));

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyTitleFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['title']) && is_string($filters['title']) && '' !== $filters['title']) {
            $qb->andWhere('c.title LIKE :title')
                ->setParameter('title', '%' . $filters['title'] . '%')
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyUserFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['user_id']) && is_string($filters['user_id']) && '' !== $filters['user_id']) {
            $qb->andWhere('c.userId = :userId')
                ->setParameter('userId', $filters['user_id'])
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyChatAssistantFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['chat_assistant_id'])) {
            $qb->andWhere('c.chatAssistant = :chatAssistant')
                ->setParameter('chatAssistant', $filters['chat_assistant_id'])
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyStatusFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['status']) && is_string($filters['status']) && '' !== $filters['status']) {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applySinceFilter(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['since']) && $filters['since'] instanceof \DateTimeImmutable) {
            $qb->andWhere('c.lastActivityTime >= :since')
                ->setParameter('since', $filters['since'])
            ;
        }
    }

    /**
     * 统计对话数量
     */
    public function countByChatAssistant(ChatAssistant $chatAssistant): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.chatAssistant = :chatAssistant')
            ->setParameter('chatAssistant', $chatAssistant)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 保存对话
     */
    public function save(Conversation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除对话
     */
    public function remove(Conversation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
