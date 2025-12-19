<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 重传单个文档
 */
final class RetryDocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/{documentId}/retry', name: 'dataset_documents_retry', methods: ['POST'])]
    public function __invoke(int $datasetId, int $documentId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        $document = $this->validator->findDocumentInDataset($documentId, $dataset);
        if (null === $document) {
            return new JsonResponse(['error' => '文档不存在或不属于当前数据集'], 404);
        }

        try {
            $result = $this->operationService->retryDocumentUpload($document, $dataset);

            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'status' => $result['status'] ?? null,
                'error' => $result['error'] ?? null,
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
