<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Dataset;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\DatasetService;

/**
 * 获取数据集知识图谱
 */
final class GetKnowledgeGraphController extends AbstractController
{
    public function __construct(
        private readonly DatasetService $datasetService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/knowledge-graph', name: 'api_datasets_knowledge_graph', methods: ['GET'])]
    public function __invoke(string $datasetId): JsonResponse
    {
        try {
            $result = $this->datasetService->getKnowledgeGraph($datasetId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Knowledge graph retrieved successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve knowledge graph',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
