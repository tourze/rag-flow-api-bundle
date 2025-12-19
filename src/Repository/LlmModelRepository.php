<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * LLM模型Repository
 *
 * @extends ServiceEntityRepository<LlmModel>
 */
#[AsRepository(entityClass: LlmModel::class)]
final class LlmModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LlmModel::class);
    }

    /**
     * 根据FID查找LLM模型
     */
    public function findByFid(string $fid, RAGFlowInstance $instance): ?LlmModel
    {
        $result = $this->createQueryBuilder('lm')
            ->where('lm.fid = :fid')
            ->andWhere('lm.ragFlowInstance = :instance')
            ->setParameter('fid', $fid)
            ->setParameter('instance', $instance)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof LlmModel ? $result : null;
    }

    /**
     * 获取所有可用的聊天模型
     *
     * @return array<LlmModel>
     */
    public function findAvailableChatModels(RAGFlowInstance $instance): array
    {
        $result = $this->createQueryBuilder('lm')
            ->where('lm.available = :available')
            ->andWhere('lm.modelType = :modelType')
            ->andWhere('lm.ragFlowInstance = :instance')
            ->setParameter('available', true)
            ->setParameter('modelType', 'chat')
            ->setParameter('instance', $instance)
            ->orderBy('lm.providerName', 'ASC')
            ->addOrderBy('lm.llmName', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof LlmModel));
    }

    /**
     * 获取所有可用模型（按提供商分组）
     *
     * @return array<string, array<LlmModel>>
     */
    public function findAvailableModelsByProvider(RAGFlowInstance $instance): array
    {
        $result = $this->createQueryBuilder('lm')
            ->where('lm.available = :available')
            ->andWhere('lm.ragFlowInstance = :instance')
            ->setParameter('available', true)
            ->setParameter('instance', $instance)
            ->orderBy('lm.providerName', 'ASC')
            ->addOrderBy('lm.llmName', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        $models = array_filter($result, static fn ($item): bool => $item instanceof LlmModel);

        $groupedModels = [];
        foreach ($models as $model) {
            $providerName = $model->getProviderName();
            if (!isset($groupedModels[$providerName])) {
                $groupedModels[$providerName] = [];
            }
            $groupedModels[$providerName][] = $model;
        }

        return $groupedModels;
    }

    /**
     * 根据模型类型获取可用模型
     *
     * @return array<LlmModel>
     */
    public function findAvailableModelsByType(string $modelType, RAGFlowInstance $instance): array
    {
        $result = $this->createQueryBuilder('lm')
            ->where('lm.available = :available')
            ->andWhere('lm.modelType = :modelType')
            ->andWhere('lm.ragFlowInstance = :instance')
            ->setParameter('available', true)
            ->setParameter('modelType', $modelType)
            ->setParameter('instance', $instance)
            ->orderBy('lm.providerName', 'ASC')
            ->addOrderBy('lm.llmName', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof LlmModel));
    }

    /**
     * 获取某个提供商的所有可用模型
     *
     * @return array<LlmModel>
     */
    public function findAvailableModelsByProviderName(string $providerName, RAGFlowInstance $instance): array
    {
        $result = $this->createQueryBuilder('lm')
            ->where('lm.available = :available')
            ->andWhere('lm.providerName = :providerName')
            ->andWhere('lm.ragFlowInstance = :instance')
            ->setParameter('available', true)
            ->setParameter('providerName', $providerName)
            ->setParameter('instance', $instance)
            ->orderBy('lm.llmName', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof LlmModel));
    }

    /**
     * 为EasyAdmin选择字段生成选项数组
     *
     * @return array<string, string>
     */
    public function getChoicesForEasyAdmin(RAGFlowInstance $instance, ?string $modelType = null): array
    {
        $qb = $this->createQueryBuilder('lm')
            ->where('lm.available = :available')
            ->andWhere('lm.ragFlowInstance = :instance')
            ->setParameter('available', true)
            ->setParameter('instance', $instance)
        ;

        if (null !== $modelType) {
            $qb->andWhere('lm.modelType = :modelType')
                ->setParameter('modelType', $modelType)
            ;
        }

        $result = $qb->orderBy('lm.providerName', 'ASC')
            ->addOrderBy('lm.llmName', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        $models = array_filter($result, static fn ($item): bool => $item instanceof LlmModel);

        $choices = [];
        foreach ($models as $model) {
            $displayName = sprintf('%s (%s)', $model->getLlmName(), $model->getProviderName());
            // 使用llm_name作为value，因为fid可能重复
            $choices[$displayName] = $model->getLlmName();
        }

        return $choices;
    }

    /**
     * 删除RAGFlow实例的所有LLM模型
     */
    public function deleteByInstance(RAGFlowInstance $instance): int
    {
        $result = $this->createQueryBuilder('lm')
            ->delete()
            ->where('lm.ragFlowInstance = :instance')
            ->setParameter('instance', $instance)
            ->getQuery()
            ->execute()
        ;

        return is_int($result) ? $result : 0;
    }

    /**
     * 保存 LLM 模型
     */
    public function save(LlmModel $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除 LLM 模型
     */
    public function remove(LlmModel $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
