<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Agent;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;
use Tourze\RAGFlowApiBundle\Service\AgentApiService;
use Tourze\RAGFlowApiBundle\Service\AgentFactory;
use Tourze\RAGFlowApiBundle\Service\AgentRequestValidator;

/**
 * 更新智能体
 */
final class UpdateController extends AbstractController
{
    use AgentControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RAGFlowAgentRepository $agentRepository,
        private readonly AgentApiService $agentApiService,
        private readonly AgentRequestValidator $requestValidator,
        private readonly AgentFactory $agentFactory,
    ) {
    }

    #[Route(path: '/api/ragflow/agents/{id}', name: 'ragflow_api_agents_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $agent = $this->agentRepository->find($id);
        if (null === $agent) {
            return $this->createErrorResponse(404, '智能体不存在', Response::HTTP_NOT_FOUND);
        }

        $data = $this->parseAndValidateRequestData($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $this->updateAgentFields($agent, $data);
        $validationErrors = $this->validateAgent($agent);
        if (null !== $validationErrors) {
            return $validationErrors;
        }

        $this->entityManager->flush();

        return $this->handleUpdateAutoSync($agent, $data);
    }

    /**
     * 解析并验证请求数据
     *
     * @return array<string, mixed>|JsonResponse
     */
    private function parseAndValidateRequestData(Request $request): array|JsonResponse
    {
        $data = $this->parseRequestData($request);
        if (!is_array($data)) {
            return $this->createErrorResponse(400, '无效的JSON数据');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateAgentFields(RAGFlowAgent $agent, array $data): void
    {
        $this->agentFactory->updateFields($agent, $data);
    }

    private function validateAgent(RAGFlowAgent $agent): ?JsonResponse
    {
        return $this->requestValidator->validateAgent($agent);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleUpdateAutoSync(RAGFlowAgent $agent, array $data): JsonResponse
    {
        if (($data['auto_sync'] ?? false) === true && $agent->getRagFlowInstance()->isEnabled()) {
            $result = $this->agentApiService->updateAgent($agent);

            $message = true === $result['success'] ? '智能体更新并同步成功' : '智能体更新成功,但同步失败';

            return new JsonResponse([
                'code' => 0,
                'message' => $message,
                'data' => ['sync_result' => $result],
            ]);
        }

        return new JsonResponse([
            'code' => 0,
            'message' => '智能体更新成功',
        ]);
    }
}
