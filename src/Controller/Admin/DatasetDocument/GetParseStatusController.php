<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 获取文档解析状态 (AJAX)
 */
final class GetParseStatusController extends AbstractController
{
    public function __construct(
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/{documentId}/parse-status', name: 'dataset_documents_parse_status', methods: ['GET'])]
    public function __invoke(int $datasetId, int $documentId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        $document = $this->validator->findDocumentInDataset($documentId, $dataset);
        if (null === $document) {
            return new JsonResponse(['error' => '文档不存在'], 404);
        }

        try {
            $this->updateDocumentStatusFromApi($document, $dataset);

            $progress = $document->getProgress();
            $progressValue = null !== $progress ? $progress / 100 : 0.0;

            return new JsonResponse([
                'progress' => $progressValue,
                'progress_msg' => $document->getProgressMsg() ?? '',
                'status' => $document->getStatus(),
                'chunk_count' => $document->getChunkCount() ?? 0,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 从API更新文档状态
     */
    private function updateDocumentStatusFromApi(Document $document, Dataset $dataset): void
    {
        $this->operationService->updateDocumentStatusFromApi($document, $dataset);
    }
}
