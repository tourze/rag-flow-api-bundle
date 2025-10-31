<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Conversation;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\ChatAssistantRepository;
use Tourze\RAGFlowApiBundle\Repository\ChunkRepository;
use Tourze\RAGFlowApiBundle\Repository\ConversationRepository;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Repository\LlmModelRepository;
use Tourze\RAGFlowApiBundle\Service\Mapper\ChatAssistantMapper;
use Tourze\RAGFlowApiBundle\Service\Mapper\ChunkMapper;
use Tourze\RAGFlowApiBundle\Service\Mapper\ConversationMapper;
use Tourze\RAGFlowApiBundle\Service\Mapper\DatasetMapper;
use Tourze\RAGFlowApiBundle\Service\Mapper\DocumentMapper;
use Tourze\RAGFlowApiBundle\Service\Mapper\LlmModelMapper;

class LocalDataSyncService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly DatasetRepository $datasetRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly ChunkRepository $chunkRepository,
        private readonly ConversationRepository $conversationRepository,
        private readonly ChatAssistantRepository $chatAssistantRepository,
        private readonly LlmModelRepository $llmModelRepository,
        private readonly DatasetMapper $datasetMapper,
        private readonly DocumentMapper $documentMapper,
        private readonly ChatAssistantMapper $chatAssistantMapper,
        private readonly ChunkMapper $chunkMapper,
        private readonly ConversationMapper $conversationMapper,
        private readonly LlmModelMapper $llmModelMapper,
    ) {
    }

    /**
     * @param array<string, mixed> $apiData
     */
    public function syncDatasetFromApi(RAGFlowInstance $instance, array $apiData): Dataset
    {
        $this->entityManager->beginTransaction();

        try {
            $dataset = null;

            // 优先通过remoteId查找
            if (isset($apiData['id'])) {
                $dataset = $this->datasetRepository->findOneBy([
                    'remoteId' => $apiData['id'],
                    'ragFlowInstance' => $instance,
                ]);
            }

            // 如果找不到且有name，则尝试通过name查找
            if (null === $dataset && isset($apiData['name'])) {
                $dataset = $this->datasetRepository->findOneBy([
                    'name' => $apiData['name'],
                    'ragFlowInstance' => $instance,
                ]);
            }

            if (null === $dataset) {
                $dataset = new Dataset();
                if (isset($apiData['name']) && is_string($apiData['name'])) {
                    $dataset->setName($apiData['name']);
                }
                $dataset->setRagFlowInstance($instance);
            }

            $this->datasetMapper->mapApiDataToEntity($dataset, $apiData);
            $dataset->setLastSyncTime(new \DateTimeImmutable());

            $this->entityManager->persist($dataset);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Dataset synced successfully', [
                'remote_id' => $dataset->getRemoteId(),
                'local_id' => $dataset->getId(),
            ]);

            return $dataset;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to sync dataset', [
                'remote_id' => $apiData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    public function syncDocumentFromApi(Dataset $dataset, array $apiData): Document
    {
        $this->entityManager->beginTransaction();

        try {
            $document = $this->documentRepository->findOneBy([
                'remoteId' => $apiData['id'],
                'dataset' => $dataset,
            ]);

            if (null === $document) {
                $document = new Document();
                if (is_string($apiData['id'])) {
                    $document->setRemoteId($apiData['id']);
                }
                $document->setDataset($dataset);
            }

            $this->documentMapper->mapApiDataToEntity($document, $apiData);
            $document->setLastSyncTime(new \DateTimeImmutable());

            $this->entityManager->persist($document);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Document synced successfully', [
                'remote_id' => $document->getRemoteId(),
                'local_id' => $document->getId(),
            ]);

            return $document;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to sync document', [
                'remote_id' => $apiData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    public function syncChunkFromApi(Document $document, array $apiData): Chunk
    {
        $this->entityManager->beginTransaction();

        try {
            $chunk = $this->chunkRepository->findOneBy([
                'remoteId' => $apiData['id'],
                'document' => $document,
            ]);

            if (null === $chunk) {
                $chunk = new Chunk();
                if (is_string($apiData['id'])) {
                    $chunk->setRemoteId($apiData['id']);
                }
                $chunk->setDocument($document);
            }

            $this->chunkMapper->mapApiDataToEntity($chunk, $apiData);
            $chunk->setLastSyncTime(new \DateTimeImmutable());

            $this->entityManager->persist($chunk);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Chunk synced successfully', [
                'remote_id' => $chunk->getRemoteId(),
                'local_id' => $chunk->getId(),
            ]);

            return $chunk;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to sync chunk', [
                'remote_id' => $apiData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $apiData
     */
    public function syncConversationFromApi(RAGFlowInstance $instance, array $apiData): Conversation
    {
        $this->entityManager->beginTransaction();

        try {
            $conversation = $this->conversationRepository->findOneBy([
                'remoteId' => $apiData['id'],
                'ragFlowInstance' => $instance,
            ]);

            if (null === $conversation) {
                $conversation = new Conversation();
                if (is_string($apiData['id'])) {
                    $conversation->setRemoteId($apiData['id']);
                }
                $conversation->setRagFlowInstance($instance);
            }

            $this->conversationMapper->mapApiDataToEntity($conversation, $apiData);
            $conversation->setLastSyncTime(new \DateTimeImmutable());

            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Conversation synced successfully', [
                'remote_id' => $conversation->getRemoteId(),
                'local_id' => $conversation->getId(),
            ]);

            return $conversation;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to sync conversation', [
                'remote_id' => $apiData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 从API数据同步聊天助手（独立模式）
     * @param array<string, mixed> $apiData
     */
    public function syncChatAssistantFromApi(array $apiData, RAGFlowInstance $instance): ChatAssistant
    {
        $this->entityManager->beginTransaction();

        try {
            $chatAssistant = $this->chatAssistantRepository->findOneBy([
                'remoteId' => $apiData['id'],
            ]);

            if (null === $chatAssistant) {
                $chatAssistant = new ChatAssistant();
                if (is_string($apiData['id'])) {
                    $chatAssistant->setRemoteId($apiData['id']);
                }
            }

            $this->chatAssistantMapper->mapApiDataToEntity($chatAssistant, $apiData);
            $chatAssistant->setLastSyncTime(new \DateTimeImmutable());

            $this->entityManager->persist($chatAssistant);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('ChatAssistant synced successfully', [
                'remote_id' => $chatAssistant->getRemoteId(),
                'local_id' => $chatAssistant->getId(),
            ]);

            return $chatAssistant;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to sync chat assistant', [
                'remote_id' => $apiData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 从API数据同步聊天助手（关联数据集模式，保持兼容性）
     * @param array<string, mixed> $apiData
     */
    public function syncChatAssistantFromApiWithDataset(Dataset $dataset, array $apiData): ChatAssistant
    {
        $this->entityManager->beginTransaction();

        try {
            $chatAssistant = $this->chatAssistantRepository->findOneBy([
                'remoteId' => $apiData['id'],
                'dataset' => $dataset,
            ]);

            if (null === $chatAssistant) {
                $chatAssistant = new ChatAssistant();
                if (is_string($apiData['id'])) {
                    $chatAssistant->setRemoteId($apiData['id']);
                }
                $chatAssistant->setDataset($dataset);
            }

            $this->chatAssistantMapper->mapApiDataToEntity($chatAssistant, $apiData);
            $chatAssistant->setLastSyncTime(new \DateTimeImmutable());

            $this->entityManager->persist($chatAssistant);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('ChatAssistant synced successfully', [
                'remote_id' => $chatAssistant->getRemoteId(),
                'local_id' => $chatAssistant->getId(),
            ]);

            return $chatAssistant;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to sync chat assistant', [
                'remote_id' => $apiData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function deleteLocalDataset(string $remoteId, RAGFlowInstance $instance): void
    {
        $dataset = $this->datasetRepository->findOneBy([
            'remoteId' => $remoteId,
            'ragFlowInstance' => $instance,
        ]);

        if (null !== $dataset) {
            $this->entityManager->remove($dataset);
            $this->entityManager->flush();

            $this->logger->info('Local dataset deleted', [
                'remote_id' => $remoteId,
                'local_id' => $dataset->getId(),
            ]);
        }
    }

    /**
     * 删除本地聊天助手数据
     */
    public function deleteChatAssistant(string $remoteId, RAGFlowInstance $instance): void
    {
        $chatAssistant = $this->chatAssistantRepository->findOneBy([
            'remoteId' => $remoteId,
        ]);

        if (null !== $chatAssistant) {
            $this->entityManager->remove($chatAssistant);
            $this->entityManager->flush();

            $this->logger->info('Local chat assistant deleted', [
                'remote_id' => $remoteId,
                'local_id' => $chatAssistant->getId(),
            ]);
        }
    }

    /**
     * 从API数据同步LLM模型列表
     * @param array<string, mixed> $llmData 按提供商分组的LLM模型数据
     */
    public function syncLlmModelsFromApi(array $llmData, RAGFlowInstance $instance): void
    {
        $this->entityManager->beginTransaction();

        try {
            $syncedModels = [];

            foreach ($llmData as $providerName => $models) {
                if (!is_array($models)) {
                    continue;
                }

                foreach ($models as $modelData) {
                    if (!is_array($modelData) || !isset($modelData['fid']) || !is_string($modelData['fid']) || !isset($modelData['llm_name'])) {
                        continue;
                    }

                    /** @var array<string, mixed> $modelData */
                    /** @var string $fid */
                    $fid = $modelData['fid'];

                    $llmModel = $this->llmModelRepository->findByFid($fid, $instance);

                    if (null === $llmModel) {
                        $llmModel = new LlmModel();
                        $llmModel->setFid($fid);
                        $llmModel->setRagFlowInstance($instance);
                    }

                    $this->llmModelMapper->mapApiDataToEntity($llmModel, $modelData, $providerName);
                    $this->entityManager->persist($llmModel);

                    $syncedModels[] = $fid;
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('LLM models synced successfully', [
                'instance_id' => $instance->getId(),
                'synced_count' => count($syncedModels),
                'synced_models' => $syncedModels,
            ]);
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to sync LLM models', [
                'instance_id' => $instance->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取EntityManager（供其他服务使用）
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
