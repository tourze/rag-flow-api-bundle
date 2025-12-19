<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 批量删除文档
 */
final class BatchDeleteController extends AbstractController
{
    public function __construct(
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/batch-delete', name: 'dataset_documents_batch_delete', methods: ['POST'])]
    public function __invoke(int $datasetId, Request $request): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        try {
            $documentIdsRaw = $request->request->all('document_ids');

            /** @var array<int|string> $documentIds */
            $documentIds = array_filter($documentIdsRaw, fn ($id) => is_int($id) || is_string($id));
            if ([] === $documentIds) {
                return new JsonResponse(['error' => '未选择要删除的文档'], 400);
            }

            $result = $this->operationService->batchDeleteDocuments($dataset, $documentIds);

            return new JsonResponse([
                'success' => true,
                'deleted_count' => $result['deleted_count'],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
