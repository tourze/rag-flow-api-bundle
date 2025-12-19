<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\DocumentDeletionService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * 批量删除数据集中的文档
 */
final class BatchDeleteController extends AbstractController
{
    public function __construct(
        private readonly DocumentValidationService $validationService,
        private readonly DocumentDeletionService $deletionService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/documents/batch-delete', name: 'api_dataset_documents_batch_delete', methods: ['DELETE', 'POST'])]
    public function __invoke(int $datasetId, Request $request): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $documentIds = $this->deletionService->extractDocumentIds($request);

            [$deletedCount, $errors] = $this->deletionService->performBatchDelete($datasetId, $documentIds);

            return $this->successResponse(
                sprintf('Successfully deleted %d documents', $deletedCount),
                [
                    'deleted_count' => $deletedCount,
                    'errors' => $errors,
                    'dataset' => $this->formatDatasetInfo($dataset),
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to batch delete documents', $e->getMessage());
        }
    }

    /**
     * 创建成功响应
     *
     * @param array<string, mixed> $data
     */
    private function successResponse(string $message, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ]);
    }

    /**
     * 创建错误响应
     */
    private function errorResponse(string $message, string $error = ''): JsonResponse
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c'),
        ];

        if ('' !== $error) {
            $data['error'] = $error;
        }

        return new JsonResponse($data);
    }

    /**
     * 格式化数据集信息
     *
     * @return array<string, mixed>
     */
    private function formatDatasetInfo(Dataset $dataset): array
    {
        return [
            'id' => $dataset->getId(),
            'name' => $dataset->getName(),
            'remoteId' => $dataset->getRemoteId(),
        ];
    }
}
