<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentSyncService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 同步文档的chunks到本地数据库
 */
final class SyncChunksController extends AbstractController
{
    public function __construct(
        private readonly DatasetDocumentSyncService $syncService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/{documentId}/sync-chunks', name: 'dataset_documents_sync_chunks', methods: ['POST'])]
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

        $validationError = $this->validator->validateDocumentForChunkSync($document);
        if (null !== $validationError) {
            return $validationError;
        }

        try {
            $syncResult = $this->processSingleDocumentChunkSync($dataset, $document);

            $syncedCount = is_int($syncResult['synced_count']) ? $syncResult['synced_count'] : 0;
            $totalCount = is_int($syncResult['total_count']) ? $syncResult['total_count'] : 0;

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('成功同步 %d 个chunks', $syncedCount),
                'total' => $totalCount,
                'synced' => $syncedCount,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 处理单个文档分块同步
     *
     * @return array<string, mixed>
     */
    private function processSingleDocumentChunkSync(Dataset $dataset, Document $document): array
    {
        return $this->syncService->processSingleDocumentChunkSync($dataset, $document);
    }
}
