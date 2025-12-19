<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Agent;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;
use Tourze\RAGFlowApiBundle\Service\AgentApiService;

/**
 * 同步智能体到远程
 */
final class SyncController extends AbstractController
{
    use AgentControllerTrait;

    public function __construct(
        private readonly RAGFlowAgentRepository $agentRepository,
        private readonly AgentApiService $agentApiService,
    ) {
    }

    #[Route(path: '/api/ragflow/agents/{id}/sync', name: 'ragflow_api_agents_sync', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id): JsonResponse
    {
        $agent = $this->agentRepository->find($id);
        if (null === $agent) {
            return new JsonResponse([
                'code' => 404,
                'message' => '智能体不存在',
            ], Response::HTTP_NOT_FOUND);
        }

        $result = $this->agentApiService->updateAgent($agent);

        /** @var array{success: bool, message?: string|null, data?: mixed} $result */
        return $this->formatApiResult($result);
    }
}
