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
 * 更新知识块
 */
final class UpdateController extends AbstractController
{
    public function __construct(
        private readonly ChunkService $chunkService,
    ) {
    }

    #[Route(path: '/api/v1/datasets/{datasetId}/chunks/{chunkId}', name: 'api_chunks_update', methods: ['PUT', 'PATCH'])]
    public function __invoke(string $datasetId, string $chunkId, Request $request): JsonResponse
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

            /** @var array<string, mixed> $updateData */
            $updateData = $data;
            $result = $this->chunkService->update($datasetId, $chunkId, $updateData);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Chunk updated successfully',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to update chunk',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
