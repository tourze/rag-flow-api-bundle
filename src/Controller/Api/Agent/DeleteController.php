<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Agent;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;
use Tourze\RAGFlowApiBundle\Service\AgentApiService;

/**
 * 删除智能体
 */
final class DeleteController extends AbstractController
{
    use AgentControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RAGFlowAgentRepository $agentRepository,
        private readonly AgentApiService $agentApiService,
    ) {
    }

    #[Route(path: '/api/ragflow/agents/{id}', name: 'ragflow_api_agents_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id): JsonResponse
    {
        $agent = $this->agentRepository->find($id);
        if (null === $agent) {
            return new JsonResponse([
                'code' => 404,
                'message' => '智能体不存在',
            ], Response::HTTP_NOT_FOUND);
        }

        $remoteDeleteResult = $this->handleRemoteDelete($agent);
        if (null !== $remoteDeleteResult) {
            return $remoteDeleteResult;
        }

        $this->entityManager->remove($agent);
        $this->entityManager->flush();

        return new JsonResponse([
            'code' => 0,
            'message' => '智能体删除成功',
        ]);
    }

    /**
     * 处理远程删除
     */
    private function handleRemoteDelete(RAGFlowAgent $agent): ?JsonResponse
    {
        if (null === $agent->getRemoteId()) {
            return null;
        }

        $result = $this->agentApiService->deleteAgent($agent);

        if (true !== $result['success']) {
            $message = is_string($result['message'] ?? null) ? $result['message'] : '未知错误';

            return new JsonResponse([
                'code' => 500,
                'message' => '远程删除失败: ' . $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return null;
    }
}
