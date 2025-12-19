<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Agent;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;
use Tourze\RAGFlowApiBundle\Service\AgentDataFormatter;

/**
 * 获取智能体详情
 */
final class DetailController extends AbstractController
{
    use AgentControllerTrait;

    public function __construct(
        private readonly RAGFlowAgentRepository $agentRepository,
        private readonly AgentDataFormatter $dataFormatter,
    ) {
    }

    #[Route(path: '/api/ragflow/agents/{id}', name: 'ragflow_api_agents_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id): JsonResponse
    {
        $agent = $this->agentRepository->find($id);

        if (null === $agent) {
            return new JsonResponse([
                'code' => 404,
                'message' => '智能体不存在',
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'code' => 0,
            'message' => 'success',
            'data' => $this->dataFormatter->formatAgentDetail($agent),
        ]);
    }
}
