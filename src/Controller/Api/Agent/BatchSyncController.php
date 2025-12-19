<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Agent;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;
use Tourze\RAGFlowApiBundle\Service\AgentApiService;

/**
 * 批量同步智能体
 */
final class BatchSyncController extends AbstractController
{
    use AgentControllerTrait;

    public function __construct(
        private readonly RAGFlowInstanceRepository $instanceRepository,
        private readonly AgentApiService $agentApiService,
    ) {
    }

    #[Route(path: '/api/ragflow/agents/batch-sync', name: 'ragflow_api_agents_batch_sync', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->parseRequestData($request);
        if (!is_array($data)) {
            return $this->createErrorResponse(400, '无效的JSON数据');
        }

        /** @var array<string, mixed> $data */
        $data = $data;

        $instanceId = $data['instance_id'] ?? null;
        if (null === $instanceId) {
            return $this->createErrorResponse(400, 'RAGFlow实例ID不能为空');
        }

        $instanceId = is_numeric($instanceId) ? (int) $instanceId : 0;
        $instance = $this->instanceRepository->find($instanceId);
        if (null === $instance) {
            return $this->createErrorResponse(404, 'RAGFlow实例不存在', Response::HTTP_NOT_FOUND);
        }

        $result = $this->agentApiService->syncAllAgents($instance);

        /** @var array{success: bool, message?: string|null, data?: mixed} $result */
        return $this->formatApiResult($result);
    }
}
