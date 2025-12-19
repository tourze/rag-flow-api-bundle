<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Service\DocumentDeletionService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * 删除指定文档
 */
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly DocumentValidationService $validationService,
        private readonly DocumentDeletionService $deletionService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/documents/{documentId}', name: 'api_dataset_documents_delete', methods: ['DELETE'])]
    public function __invoke(int $datasetId, int $documentId): JsonResponse
    {
        try {
            $dataset = $this->validationService->validateAndGetDataset($datasetId);
            $document = $this->validationService->validateAndGetDocument($datasetId, $documentId);

            $documentInfo = $this->captureDocumentInfo($document);
            $this->deletionService->deleteDocument($document);

            return $this->successResponse('Document deleted successfully', [
                'id' => $documentId,
                'name' => $documentInfo['name'],
                'remoteId' => $documentInfo['remoteId'],
                'dataset' => $this->formatDatasetInfo($dataset),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete document', $e->getMessage());
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
     * 捕获文档信息
     *
     * @return array<string, mixed>
     */
    private function captureDocumentInfo(Document $document): array
    {
        return [
            'name' => $document->getName(),
            'remoteId' => $document->getRemoteId(),
        ];
    }
}
