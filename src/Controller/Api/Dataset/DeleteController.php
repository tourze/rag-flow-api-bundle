<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Dataset;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Service\DatasetService;

/**
 * 删除数据集
 */
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DatasetRepository $datasetRepository,
        private readonly DatasetService $datasetService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}', name: 'api_datasets_delete', methods: ['DELETE'])]
    public function __invoke(int $datasetId): JsonResponse
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

            // 如果有remoteId,先尝试从RAGFlow API删除
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
}
