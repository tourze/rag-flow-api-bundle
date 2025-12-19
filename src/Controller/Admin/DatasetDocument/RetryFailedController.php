<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 重试失败的文档上传
 */
final class RetryFailedController extends AbstractController
{
    public function __construct(
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/retry-failed', name: 'dataset_documents_retry_failed', methods: ['POST'])]
    public function __invoke(int $datasetId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        try {
            $result = $this->operationService->retryFailedDocuments($dataset);

            return new JsonResponse([
                'success' => true,
                'retry_count' => $result['retry_count'],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
