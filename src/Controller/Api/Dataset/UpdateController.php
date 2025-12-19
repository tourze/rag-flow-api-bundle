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
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Service\DatasetService;

/**
 * 更新数据集
 */
final class UpdateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DatasetRepository $datasetRepository,
        private readonly DatasetService $datasetService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}', name: 'api_datasets_update', methods: ['PUT', 'PATCH'])]
    public function __invoke(int $datasetId, Request $request): JsonResponse
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
}
