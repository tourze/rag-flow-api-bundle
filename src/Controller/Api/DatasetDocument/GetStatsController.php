<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * 获取数据集文档统计信息
 */
final class GetStatsController extends AbstractController
{
    public function __construct(
        private readonly DocumentValidationService $validationService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/documents/stats', name: 'api_dataset_documents_stats', methods: ['GET'])]
    public function __invoke(int $datasetId): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $stats = $this->buildDatasetStats($dataset);

            return $this->successResponse('Dataset document statistics retrieved successfully', [
                'dataset' => $this->formatDatasetInfo($dataset),
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve dataset statistics', $e->getMessage());
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

    /**
     * 构建数据集统计信息
     *
     * @return array<string, mixed>
     */
    private function buildDatasetStats(Dataset $dataset): array
    {
        return [
            'total_count' => $dataset->getDocumentCount(),
            'total_size' => $dataset->getDocumentsTotalSize(),
            'total_size_formatted' => $dataset->getDocumentsTotalSizeFormatted(),
            'completed_count' => $dataset->getCompletedDocumentCount(),
            'processing_count' => $dataset->getProcessingDocumentCount(),
            'failed_count' => $dataset->getFailedDocumentCount(),
            'status_counts' => $dataset->getDocumentStatusStats(),
            'has_retry_required' => $dataset->hasDocumentsRequiringRetry(),
        ];
    }
}
