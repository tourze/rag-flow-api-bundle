<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\DatasetDocumentSyncService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 手动同步所有文档的分块数据
 */
final class SyncAllChunksController extends AbstractController
{
    public function __construct(
        private readonly DatasetDocumentSyncService $syncService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/sync-all-chunks', name: 'dataset_documents_sync_all_chunks', methods: ['POST'])]
    public function __invoke(int $datasetId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        try {
            $syncResult = $this->processBatchChunkSync($dataset);

            $syncedCount = is_int($syncResult['synced_count']) ? $syncResult['synced_count'] : 0;

            return new JsonResponse([
                'success' => true,
                'synced_count' => $syncedCount,
                'errors' => $syncResult['errors'],
                'message' => sprintf('成功同步 %d 个文档的分块数据', $syncedCount),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 处理批量文档分块同步
     *
     * @return array<string, mixed>
     */
    private function processBatchChunkSync(Dataset $dataset): array
    {
        return $this->syncService->processBatchChunkSync($dataset);
    }
}
