<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\KnowledgeGraph;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\KnowledgeGraphService;

/**
 * 获取知识图谱统计信息
 */
final class GetStatsController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeGraphService $knowledgeGraphService,
    ) {
    }

    #[Route(path: '/api/v1/knowledge-graph/datasets/{datasetId}/stats', name: 'api_knowledge_graph_get_stats', methods: ['GET'])]
    public function __invoke(string $datasetId): JsonResponse
    {
        try {
            $stats = $this->knowledgeGraphService->calculateStats($datasetId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Knowledge graph statistics retrieved successfully',
                'data' => $stats,
                'dataset_id' => $datasetId,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve knowledge graph statistics',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e->getMessage(),
                $datasetId
            );
        }
    }

    /**
     * 创建错误响应
     */
    private function errorResponse(string $message, int $statusCode, ?string $error = null, ?string $datasetId = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c'),
        ];

        if (null !== $error) {
            $response['error'] = $error;
        }

        if (null !== $datasetId) {
            $response['dataset_id'] = $datasetId;
        }

        return new JsonResponse($response, $statusCode);
    }
}
