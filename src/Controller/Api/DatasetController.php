<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * 数据集API Controller
 *
 * 提供数据集CRUD操作的RESTful API接口
 */
#[Route(path: '/api/v1/datasets', name: 'api_datasets_')]
final class DatasetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DatasetRepository $datasetRepository,
        private readonly DatasetService $datasetService,
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
    }

    /**
     * 获取数据集列表
     */
    #[Route(path: '/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 20);
            $offset = ($page - 1) * $limit;

            $criteria = [];
            $name = $request->query->get('name');
            if (null !== $name && '' !== $name) {
                $criteria['name'] = $name;
            }
            $status = $request->query->get('status');
            if (null !== $status && '' !== $status) {
                $criteria['status'] = $status;
            }

            $datasets = $this->datasetRepository->findBy($criteria, ['createTime' => 'DESC'], $limit, $offset);
            $total = $this->datasetRepository->count($criteria);

            $data = [];
            foreach ($datasets as $dataset) {
                $data[] = [
                    'id' => $dataset->getId(),
                    'remoteId' => $dataset->getRemoteId(),
                    'name' => $dataset->getName(),
                    'description' => $dataset->getDescription(),
                    'chunkMethod' => $dataset->getChunkMethod(),
                    'embeddingModel' => $dataset->getEmbeddingModel(),
                    'status' => $dataset->getStatus(),
                    'remoteCreateTime' => $dataset->getRemoteCreateTime()?->format('c'),
                    'remoteUpdateTime' => $dataset->getRemoteUpdateTime()?->format('c'),
                    'lastSyncTime' => $dataset->getLastSyncTime()?->format('c'),
                    'createTime' => $dataset->getCreateTime()?->format('c'),
                    'updateTime' => $dataset->getUpdateTime()?->format('c'),
                ];
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Datasets retrieved successfully',
                'data' => [
                    'datasets' => $data,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'totalPages' => (int) ceil($total / $limit),
                    ],
                ],
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve datasets',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 创建新数据集
     */
    #[Route(path: '/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
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
                // API同步失败，设置状态但不影响本地创建
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

    /**
     * 更新数据集
     */
    #[Route(path: '/{datasetId}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $datasetId, Request $request): JsonResponse
    {
        try {
            $data = $this->validateUpdateRequest($request);
            $dataset = $this->findDatasetOrFail($datasetId);

            $this->updateLocalDataset($dataset, $data);
            $this->syncDatasetToRemote($dataset);

            return $this->buildUpdateSuccessResponse($dataset);
        } catch (\Exception $e) {
            return $this->buildUpdateErrorResponse($e);
        }
    }

    /**
     * 验证更新请求
     *
     * @return array<string, mixed>
     */
    private function validateUpdateRequest(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON data');
        }

        // 确保所有键为字符串类型
        $result = [];
        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * 查找数据集或抛出异常
     */
    private function findDatasetOrFail(int $datasetId): Dataset
    {
        $dataset = $this->datasetRepository->find($datasetId);
        if (!$dataset instanceof Dataset) {
            throw new \InvalidArgumentException('Dataset not found');
        }

        return $dataset;
    }

    /**
     * 更新本地数据集
     *
     * @param array<string, mixed> $data
     */
    private function updateLocalDataset(Dataset $dataset, array $data): void
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $dataset->setName($data['name']);
        }
        if (isset($data['description']) && is_string($data['description'])) {
            $dataset->setDescription($data['description']);
        }
        if (isset($data['chunkMethod']) && is_string($data['chunkMethod'])) {
            $dataset->setChunkMethod($data['chunkMethod']);
        }
        if (isset($data['embeddingModel']) && is_string($data['embeddingModel'])) {
            $dataset->setEmbeddingModel($data['embeddingModel']);
        }

        $dataset->setLastSyncTime(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * 同步数据集到远程API
     */
    private function syncDatasetToRemote(Dataset $dataset): void
    {
        $remoteId = $dataset->getRemoteId();
        if (null === $remoteId || '' === $remoteId) {
            return;
        }

        try {
            $apiData = [
                'name' => $dataset->getName(),
                'description' => $dataset->getDescription(),
                'chunk_method' => $dataset->getChunkMethod() ?? 'naive',
                'embedding_model' => $dataset->getEmbeddingModel() ?? 'BAAI/bge-large-zh-v1.5',
            ];

            $this->datasetService->update($remoteId, $apiData);
            $dataset->setStatus('synced');
        } catch (\Exception) {
            $dataset->setStatus('sync_failed');
        }

        $this->entityManager->flush();
    }

    /**
     * 构建更新成功响应
     */
    private function buildUpdateSuccessResponse(Dataset $dataset): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Dataset updated successfully',
            'data' => [
                'id' => $dataset->getId(),
                'remoteId' => $dataset->getRemoteId(),
                'name' => $dataset->getName(),
                'description' => $dataset->getDescription(),
                'status' => $dataset->getStatus(),
                'lastSyncTime' => $dataset->getLastSyncTime()?->format('c'),
            ],
            'timestamp' => date('c'),
        ]);
    }

    /**
     * 构建更新错误响应
     */
    private function buildUpdateErrorResponse(\Exception $e): JsonResponse
    {
        $statusCode = $e instanceof \InvalidArgumentException
            ? Response::HTTP_BAD_REQUEST
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Failed to update dataset',
            'error' => $e->getMessage(),
            'timestamp' => date('c'),
        ], $statusCode);
    }

    /**
     * 删除数据集
     */
    #[Route(path: '/{datasetId}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $datasetId): JsonResponse
    {
        try {
            $dataset = $this->datasetRepository->find($datasetId);
            if (!$dataset instanceof Dataset) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Dataset not found',
                    'timestamp' => date('c'),
                ], Response::HTTP_NOT_FOUND);
            }

            // 如果有remoteId，先尝试从RAGFlow API删除
            $remoteId = $dataset->getRemoteId();
            if (null !== $remoteId && '' !== $remoteId) {
                try {
                    $this->datasetService->delete($remoteId);
                } catch (\Exception $e) {
                    // API删除失败不影响本地删除
                    error_log(sprintf('Failed to delete dataset from RAGFlow API: %s', $e->getMessage()));
                }
            }

            // 删除本地数据库记录
            $this->entityManager->remove($dataset);
            $this->entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Dataset deleted successfully',
                'data' => [
                    'id' => $datasetId,
                    'remoteId' => $remoteId,
                ],
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to delete dataset',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 获取数据集知识图谱
     */
    #[Route(path: '/{datasetId}/knowledge-graph', name: 'knowledge_graph', methods: ['GET'])]
    public function getKnowledgeGraph(string $datasetId): JsonResponse
    {
        try {
            $result = $this->datasetService->getKnowledgeGraph($datasetId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Knowledge graph retrieved successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve knowledge graph',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
