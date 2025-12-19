<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Dataset;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * 创建新数据集
 */
final class CreateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DatasetService $datasetService,
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
    }

    #[Route(path: '/api/v1/datasets/create', name: 'api_datasets_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid JSON data',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            // 验证必需字段
            if (!isset($data['name']) || !is_string($data['name']) || '' === $data['name']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Dataset name is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            // 创建新的数据集实体
            $dataset = new Dataset();
            $dataset->setName($data['name']);
            $dataset->setDescription(isset($data['description']) && is_string($data['description']) ? $data['description'] : null);
            $dataset->setChunkMethod(isset($data['chunkMethod']) && is_string($data['chunkMethod']) ? $data['chunkMethod'] : 'naive');
            $dataset->setEmbeddingModel(isset($data['embeddingModel']) && is_string($data['embeddingModel']) ? $data['embeddingModel'] : 'BAAI/bge-large-zh-v1.5');
            $dataset->setRagFlowInstance($this->instanceManager->getDefaultInstance());
            $dataset->setLastSyncTime(new \DateTimeImmutable());

            // 先保存到本地数据库
            $this->entityManager->persist($dataset);
            $this->entityManager->flush();

            // 尝试同步到RAGFlow API
            try {
                $apiData = [
                    'name' => $dataset->getName(),
                    'description' => $dataset->getDescription(),
                    'chunk_method' => $dataset->getChunkMethod(),
                    'embedding_model' => $dataset->getEmbeddingModel(),
                ];

                $result = $this->datasetService->create($apiData);

                // 更新本地记录的remoteId
                $dataset->setRemoteId($result->getRemoteId());
                $dataset->setRemoteCreateTime($result->getRemoteCreateTime());
                $dataset->setRemoteUpdateTime($result->getRemoteUpdateTime());
                $dataset->setStatus('synced');

                $this->entityManager->flush();
            } catch (\Exception $e) {
                // API同步失败,设置状态但不影响本地创建
                $dataset->setStatus('sync_failed');
                $this->entityManager->flush();
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Dataset created successfully',
                'data' => [
                    'id' => $dataset->getId(),
                    'remoteId' => $dataset->getRemoteId(),
                    'name' => $dataset->getName(),
                    'description' => $dataset->getDescription(),
                    'status' => $dataset->getStatus(),
                    'createTime' => $dataset->getCreateTime()?->format('c'),
                ],
                'timestamp' => date('c'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to create dataset',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
