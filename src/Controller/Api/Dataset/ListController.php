<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Dataset;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;

/**
 * 获取数据集列表
 */
final class ListController extends AbstractController
{
    public function __construct(
        private readonly DatasetRepository $datasetRepository,
    ) {
    }

    #[Route(path: '/api/v1/datasets/list', name: 'api_datasets_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 20);
            $offset = ($page - 1) * $limit;

            $criteria = [];
            $name = $request->query->get('name');
            if (null !== $name && '' !== $name) {
                $criteria['name'] = $name;
            }
            $status = $request->query->get('status');
            if (null !== $status && '' !== $status) {
                $criteria['status'] = $status;
            }

            $datasets = $this->datasetRepository->findBy($criteria, ['createTime' => 'DESC'], $limit, $offset);
            $total = $this->datasetRepository->count($criteria);

            $data = [];
            foreach ($datasets as $dataset) {
                $data[] = [
                    'id' => $dataset->getId(),
                    'remoteId' => $dataset->getRemoteId(),
                    'name' => $dataset->getName(),
                    'description' => $dataset->getDescription(),
                    'chunkMethod' => $dataset->getChunkMethod(),
                    'embeddingModel' => $dataset->getEmbeddingModel(),
                    'status' => $dataset->getStatus(),
                    'remoteCreateTime' => $dataset->getRemoteCreateTime()?->format('c'),
                    'remoteUpdateTime' => $dataset->getRemoteUpdateTime()?->format('c'),
                    'lastSyncTime' => $dataset->getLastSyncTime()?->format('c'),
                    'createTime' => $dataset->getCreateTime()?->format('c'),
                    'updateTime' => $dataset->getUpdateTime()?->format('c'),
                ];
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Datasets retrieved successfully',
                'data' => [
                    'datasets' => $data,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'totalPages' => (int) ceil($total / $limit),
                    ],
                ],
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve datasets',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
