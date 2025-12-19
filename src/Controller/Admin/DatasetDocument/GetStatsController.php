<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocument;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Service\DocumentOperationService;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 获取数据集文档统计信息
 */
final class GetStatsController extends AbstractController
{
    public function __construct(
        private readonly DocumentOperationService $operationService,
        private readonly DocumentValidator $validator,
    ) {
    }

    #[Route(path: '/admin/datasets/{datasetId}/documents/stats', name: 'dataset_documents_stats', methods: ['GET'])]
    public function __invoke(int $datasetId): JsonResponse
    {
        $dataset = $this->validator->findDataset($datasetId);
        if (null === $dataset) {
            return new JsonResponse(['error' => '数据集不存在'], 404);
        }

        try {
            $stats = $this->getDatasetDocumentStats($dataset);

            return new JsonResponse([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 获取数据集文档统计信息
     *
     * @return array<string, mixed>
     */
    private function getDatasetDocumentStats(Dataset $dataset): array
    {
        return $this->operationService->getDatasetDocumentStats($dataset);
    }
}
