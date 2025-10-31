<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Helper\AgentStatsProcessor;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;
use Tourze\RAGFlowApiBundle\Service\AgentApiService;
use Tourze\RAGFlowApiBundle\Service\AgentDataFormatter;
use Tourze\RAGFlowApiBundle\Service\AgentFactory;
use Tourze\RAGFlowApiBundle\Service\AgentRequestValidator;

/**
 * RAGFlow智能体API控制器
 */
#[Route(path: '/api/ragflow/agents', name: 'ragflow_api_agents_')]
final class AgentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RAGFlowAgentRepository $agentRepository,
        private readonly RAGFlowInstanceRepository $instanceRepository,
        private readonly AgentApiService $agentApiService,
        private readonly AgentRequestValidator $requestValidator,
        private readonly AgentDataFormatter $dataFormatter,
        private readonly AgentFactory $agentFactory,
    ) {
    }

    /**
     * 获取智能体列表
     */
    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        return $this->handleAgentList($request);
    }

    /**
     * 处理智能体列表请求
     */
    private function handleAgentList(Request $request): JsonResponse
    {
        $filters = $this->parseListFilters($request);
        $agentsData = $this->getAgentsData($filters);

        return new JsonResponse([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $agentsData['list'],
                'total' => $agentsData['total'],
                'page' => $filters['page'],
                'limit' => $filters['limit'],
            ],
        ]);
    }

    /**
     * 获取智能体数据
     *
     * @param array{page: int, limit: int, instance_id: int, status: string|null} $filters
     * @return array{list: array<array<string, mixed>>, total: int}
     */
    private function getAgentsData(array $filters): array
    {
        $agents = $this->findAgentsByFilters($filters);
        $total = $this->countAgentsByFilters($filters);

        return [
            'list' => $this->dataFormatter->formatAgentsForList($agents),
            'total' => $total,
        ];
    }

    /**
     * 获取智能体详情
     */
    #[Route(path: '/{id}', name: 'detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(int $id): JsonResponse
    {
        $agent = $this->findAgentById($id);

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

    /**
     * 创建智能体
     */
    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->handleAgentCreation($request);
    }

    /**
     * 处理智能体创建逻辑
     */
    private function handleAgentCreation(Request $request): JsonResponse
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
     * @param array<string, mixed> $data
     */
    private function handleAutoSync(RAGFlowAgent $agent, array $data): JsonResponse
    {
        if (($data['auto_sync'] ?? false) === true && $agent->getRagFlowInstance()->isEnabled()) {
            $result = $this->agentApiService->createAgent($agent);

            $message = true === $result['success'] ? '智能体创建并同步成功' : '智能体创建成功，但同步失败';

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

    private function createErrorResponse(int $code, string $message, int $httpStatus = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
        ], $httpStatus);
    }

    /**
     * 更新智能体
     */
    #[Route(path: '/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->handleAgentUpdate($id, $request);
    }

    /**
     * 处理智能体更新逻辑
     */
    private function handleAgentUpdate(int $id, Request $request): JsonResponse
    {
        $agent = $this->findAgentById($id);
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

    /**
     * @param array<string, mixed> $data
     */
    private function handleUpdateAutoSync(RAGFlowAgent $agent, array $data): JsonResponse
    {
        if (($data['auto_sync'] ?? false) === true && $agent->getRagFlowInstance()->isEnabled()) {
            $result = $this->agentApiService->updateAgent($agent);

            $message = true === $result['success'] ? '智能体更新并同步成功' : '智能体更新成功，但同步失败';

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

    /**
     * 删除智能体
     */
    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        return $this->handleAgentDeletion($id);
    }

    /**
     * 处理智能体删除逻辑
     */
    private function handleAgentDeletion(int $id): JsonResponse
    {
        $agent = $this->findAgentById($id);
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
     * 同步智能体到远程
     */
    #[Route(path: '/{id}/sync', name: 'sync', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sync(int $id): JsonResponse
    {
        $agent = $this->findAgentById($id);
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

    /**
     * 批量同步智能体
     */
    #[Route(path: '/batch-sync', name: 'batch_sync', methods: ['POST'])]
    public function batchSync(Request $request): JsonResponse
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
        $instance = $this->findInstance($instanceId);
        if (null === $instance) {
            return $this->createErrorResponse(404, 'RAGFlow实例不存在', Response::HTTP_NOT_FOUND);
        }

        $result = $this->agentApiService->syncAllAgents($instance);

        /** @var array{success: bool, message?: string|null, data?: mixed} $result */
        return $this->formatApiResult($result);
    }

    /**
     * 获取智能体状态统计
     */
    #[Route(path: '/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $stats = $this->getAgentStats();
        $statusStats = $this->processStatsData($stats);
        $totalCount = array_sum($statusStats);

        return new JsonResponse([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'total' => $totalCount,
                'by_status' => $statusStats,
            ],
        ]);
    }

    /**
     * 获取智能体统计数据
     */
    private function getAgentStats(): mixed
    {
        return $this->agentRepository->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) as count')
            ->groupBy('a.status')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param mixed $stats
     * @return array<string, int>
     */
    private function processStatsData($stats): array
    {
        if (!is_array($stats)) {
            return [];
        }

        $processor = new AgentStatsProcessor();

        return $processor->processStats($stats);
    }

    /**
     * 解析列表查询参数
     *
     * @return array{page: int, limit: int, instance_id: int, status: string|null}
     */
    private function parseListFilters(Request $request): array
    {
        $status = $request->query->get('status');
        $status = is_string($status) ? $status : null;

        return [
            'page' => $request->query->getInt('page', 1),
            'limit' => $request->query->getInt('limit', 20),
            'instance_id' => $request->query->getInt('instance_id'),
            'status' => $status,
        ];
    }

    /**
     * 根据过滤条件查找智能体
     *
     * @param array{page: int, limit: int, instance_id: int, status: string|null} $filters
     * @return array<RAGFlowAgent>
     */
    private function findAgentsByFilters(array $filters): array
    {
        $qb = $this->agentRepository->createQueryBuilder('a')
            ->orderBy('a.createTime', 'DESC')
            ->setFirstResult(($filters['page'] - 1) * $filters['limit'])
            ->setMaxResults($filters['limit'])
        ;

        if ($filters['instance_id'] > 0) {
            $qb->andWhere('a.ragFlowInstance = :instanceId')
                ->setParameter('instanceId', $filters['instance_id'])
            ;
        }

        if (null !== $filters['status'] && '' !== $filters['status']) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        $result = $qb->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        return array_values(array_filter($result, static fn ($item): bool => $item instanceof RAGFlowAgent));
    }

    /**
     * 根据过滤条件统计智能体数量
     *
     * @param array{page: int, limit: int, instance_id: int, status: string|null} $filters
     */
    private function countAgentsByFilters(array $filters): int
    {
        $qb = $this->agentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
        ;

        if ($filters['instance_id'] > 0) {
            $qb->andWhere('a.ragFlowInstance = :instanceId')
                ->setParameter('instanceId', $filters['instance_id'])
            ;
        }

        if (null !== $filters['status'] && '' !== $filters['status']) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 解析请求数据
     */
    private function parseRequestData(Request $request): mixed
    {
        return json_decode($request->getContent(), true);
    }

    /**
     * 根据ID查找智能体
     */
    private function findAgentById(int $id): ?RAGFlowAgent
    {
        return $this->agentRepository->find($id);
    }

    /**
     * 根据ID查找实例
     */
    private function findInstance(int $instanceId): ?RAGFlowInstance
    {
        return $this->instanceRepository->find($instanceId);
    }

    /**
     * 查找有效的实例
     *
     * @param array<string, mixed> $data
     */
    private function findValidInstance(array $data): ?RAGFlowInstance
    {
        $instanceId = is_numeric($data['instance_id']) ? (int) $data['instance_id'] : 0;

        return $this->findInstance($instanceId);
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

    /**
     * 格式化API结果
     *
     * @param array{success: bool, message?: string|null, data?: mixed} $result
     */
    private function formatApiResult(array $result): JsonResponse
    {
        $code = true === $result['success'] ? 0 : 500;
        $message = is_string($result['message'] ?? null) ? $result['message'] : '未知错误';

        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'data' => $result['data'] ?? null,
        ]);
    }
}
