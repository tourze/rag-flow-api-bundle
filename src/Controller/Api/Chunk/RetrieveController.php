<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Chunk;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Service\ChunkService;

/**
 * 检索知识块
 */
final class RetrieveController extends AbstractController
{
    public function __construct(
        private readonly ChunkService $chunkService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/chunks/retrieve', name: 'api_chunks_retrieve', methods: ['POST'])]
    public function __invoke(string $datasetId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid JSON data',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['query']) || '' === $data['query']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Query is required',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $query = $data['query'];
            if (!is_string($query)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Query must be a string',
                    'timestamp' => date('c'),
                ], Response::HTTP_BAD_REQUEST);
            }

            unset($data['query']);

            /** @var array<string, mixed> $options */
            $options = $data;
            $result = $this->chunkService->retrieve($datasetId, $query, $options);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chunks retrieved successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve chunks',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
