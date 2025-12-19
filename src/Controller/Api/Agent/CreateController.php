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
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;
use Tourze\RAGFlowApiBundle\Service\AgentApiService;
use Tourze\RAGFlowApiBundle\Service\AgentFactory;
use Tourze\RAGFlowApiBundle\Service\AgentRequestValidator;

/**
 * 创建智能体
 */
final class CreateController extends AbstractController
{
    use AgentControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RAGFlowInstanceRepository $instanceRepository,
        private readonly AgentApiService $agentApiService,
        private readonly AgentRequestValidator $requestValidator,
        private readonly AgentFactory $agentFactory,
    ) {
    }

    #[Route(path: '/api/ragflow/agents', name: 'ragflow_api_agents_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->parseAndValidateCreateRequest($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $instance = $this->findValidInstance($data);
        if (null === $instance) {
            return $this->createErrorResponse(404, 'RAGFlow实例不存在', Response::HTTP_NOT_FOUND);
        }

        $agent = $this->createAndValidateAgent($data, $instance);
        if ($agent instanceof JsonResponse) {
            return $agent;
        }

        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        return $this->handleAutoSync($agent, $data);
    }

    /**
     * 解析并验证创建请求
     *
     * @return array<string, mixed>|JsonResponse
     */
    private function parseAndValidateCreateRequest(Request $request): array|JsonResponse
    {
        $data = $this->parseRequestData($request);
        if (!is_array($data)) {
            return $this->createErrorResponse(400, '无效的JSON数据');
        }

        /** @var array<string, mixed> $data */
        $data = $data;

        $validationResponse = $this->validateCreateData($data);
        if (null !== $validationResponse) {
            return $validationResponse;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateCreateData(array $data): ?JsonResponse
    {
        return $this->requestValidator->validateCreateData($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createAgentFromData(array $data, RAGFlowInstance $instance): RAGFlowAgent
    {
        return $this->agentFactory->createFromData($data, $instance);
    }

    private function validateAgent(RAGFlowAgent $agent): ?JsonResponse
    {
        return $this->requestValidator->validateAgent($agent);
    }

    /**
     * 查找有效的实例
     *
     * @param array<string, mixed> $data
     */
    private function findValidInstance(array $data): ?RAGFlowInstance
    {
        $instanceId = is_numeric($data['instance_id']) ? (int) $data['instance_id'] : 0;

        return $this->instanceRepository->find($instanceId);
    }

    /**
     * 创建并验证智能体
     *
     * @param array<string, mixed> $data
     * @return RAGFlowAgent|JsonResponse
     */
    private function createAndValidateAgent(array $data, RAGFlowInstance $instance): RAGFlowAgent|JsonResponse
    {
        $agent = $this->createAgentFromData($data, $instance);
        $validationErrors = $this->validateAgent($agent);
        if (null !== $validationErrors) {
            return $validationErrors;
        }

        return $agent;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleAutoSync(RAGFlowAgent $agent, array $data): JsonResponse
    {
        if (($data['auto_sync'] ?? false) === true && $agent->getRagFlowInstance()->isEnabled()) {
            $result = $this->agentApiService->createAgent($agent);

            $message = true === $result['success'] ? '智能体创建并同步成功' : '智能体创建成功,但同步失败';

            return new JsonResponse([
                'code' => 0,
                'message' => $message,
                'data' => [
                    'id' => $agent->getId(),
                    'sync_result' => $result,
                ],
            ]);
        }

        return new JsonResponse([
            'code' => 0,
            'message' => '智能体创建成功',
            'data' => ['id' => $agent->getId()],
        ]);
    }
}
