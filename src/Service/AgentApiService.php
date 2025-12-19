<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\DTO\AgentDataDto;
use Tourze\RAGFlowApiBundle\DTO\ApiResponseDto;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowAgentRepository;
use Tourze\RAGFlowApiBundle\Service\RAGFlowApiClientFactory;

/**
 * RAGFlow智能体API服务
 */
#[WithMonologChannel(channel: 'rag_flow_api')]
final readonly class AgentApiService
{
    public function __construct(
        private RAGFlowApiClientFactory $clientFactory,
        private EntityManagerInterface $entityManager,
        private RAGFlowAgentRepository $agentRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * 获取指定实例的API客户端
     */
    private function getApiClient(RAGFlowInstance $instance): RAGFlowApiClient
    {
        return $this->clientFactory->createClient($instance);
    }

    /**
     * 创建智能体到远程API
     *
     * @return array<string, mixed>
     */
    public function createAgent(RAGFlowAgent $agent): array
    {
        try {
            $requestData = [
                'title' => $agent->getTitle(),
                'description' => $agent->getDescription(),
                'dsl' => $agent->getDsl(),
            ];

            $this->logger->info('Creating agent via API', [
                'title' => $agent->getTitle(),
                'instance' => $agent->getRagFlowInstance()->getName(),
            ]);

            $apiClient = $this->getApiClient($agent->getRagFlowInstance());
            /** @var ApiResponseDto<AgentDataDto> $response */
            $response = $apiClient->createAgent($requestData);

            if ($response->isSuccess()) {
                $agent->setStatus('published');
                $agent->setLastSyncTime(new \DateTimeImmutable());
                $agent->setSyncErrorMessage(null);

                $this->entityManager->flush();

                $this->logger->info('Agent created successfully', [
                    'agent_id' => $agent->getId(),
                    'title' => $agent->getTitle(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Agent created successfully',
                    'data' => $response->getData(),
                ];
            }

            $message = $response->getMessage();
            $code = $response->getCode();

            throw new DocumentOperationException(sprintf('Failed to create agent: %s', $message), $code);
        } catch (\Exception $e) {
            $agent->setStatus('sync_failed');
            $agent->setSyncErrorMessage($e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('Failed to create agent', [
                'agent_id' => $agent->getId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 更新智能体到远程API
     *
     * @return array<string, mixed>
     */
    public function updateAgent(RAGFlowAgent $agent): array
    {
        if (null === $agent->getRemoteId()) {
            return $this->createAgent($agent);
        }

        try {
            $requestData = [
                'title' => $agent->getTitle(),
                'description' => $agent->getDescription(),
                'dsl' => $agent->getDsl(),
            ];

            $this->logger->info('Updating agent via API', [
                'agent_id' => $agent->getId(),
                'remote_id' => $agent->getRemoteId(),
                'title' => $agent->getTitle(),
            ]);

            $apiClient = $this->getApiClient($agent->getRagFlowInstance());
            /** @var ApiResponseDto<AgentDataDto> $response */
            $response = $apiClient->updateAgent($agent->getRemoteId(), $requestData);

            if ($response->isSuccess()) {
                $agent->setStatus('published');
                $agent->setLastSyncTime(new \DateTimeImmutable());
                $agent->setSyncErrorMessage(null);

                $this->entityManager->flush();

                $this->logger->info('Agent updated successfully', [
                    'agent_id' => $agent->getId(),
                    'remote_id' => $agent->getRemoteId(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Agent updated successfully',
                    'data' => $response->getData(),
                ];
            }

            $message = $response->getMessage();
            $code = $response->getCode();

            throw new DocumentOperationException(sprintf('Failed to update agent: %s', $message), $code);
        } catch (\Exception $e) {
            $agent->setStatus('sync_failed');
            $agent->setSyncErrorMessage($e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('Failed to update agent', [
                'agent_id' => $agent->getId(),
                'remote_id' => $agent->getRemoteId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 删除远程智能体
     *
     * @return array<string, mixed>
     */
    public function deleteAgent(RAGFlowAgent $agent): array
    {
        if (null === $agent->getRemoteId()) {
            return [
                'success' => true,
                'message' => 'Agent was not synced to remote, deleted locally only',
                'data' => null,
            ];
        }

        try {
            $this->logger->info('Deleting agent via API', [
                'agent_id' => $agent->getId(),
                'remote_id' => $agent->getRemoteId(),
            ]);

            $apiClient = $this->getApiClient($agent->getRagFlowInstance());
            /** @var ApiResponseDto<array<string, mixed>> $response */
            $response = $apiClient->deleteAgent($agent->getRemoteId());

            if ($response->isSuccess()) {
                $this->logger->info('Agent deleted successfully', [
                    'agent_id' => $agent->getId(),
                    'remote_id' => $agent->getRemoteId(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Agent deleted successfully',
                    'data' => $response->getData(),
                ];
            }

            $message = $response->getMessage();
            $code = $response->getCode();

            throw new DocumentOperationException(sprintf('Failed to delete agent: %s', $message), $code);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete agent', [
                'agent_id' => $agent->getId(),
                'remote_id' => $agent->getRemoteId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 批量同步智能体
     *
     * @return array<string, mixed>
     */
    public function syncAllAgents(RAGFlowInstance $instance): array
    {
        $agents = $this->agentRepository->findNeedingSync();
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($agents as $agent) {
            if ($agent->getRagFlowInstance()->getId() !== $instance->getId()) {
                continue;
            }

            $result = $this->updateAgent($agent);
            $results[] = [
                'agent_id' => $agent->getId(),
                'title' => $agent->getTitle(),
                'success' => $result['success'],
                'message' => $result['message'],
            ];

            if (true === $result['success']) {
                ++$successCount;
            } else {
                ++$failureCount;
            }
        }

        return [
            'success' => 0 === $failureCount,
            'message' => sprintf('Sync completed: %d successful, %d failed', $successCount, $failureCount),
            'data' => [
                'total' => count($results),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
            ],
        ];
    }

    /**
     * 获取智能体列表
     *
     * @return array<string, mixed>
     */
    public function getAgentList(RAGFlowInstance $instance, int $page = 1, int $size = 20): array
    {
        try {
            $this->logger->info('Fetching agent list from API', [
                'instance' => $instance->getName(),
                'page' => $page,
                'size' => $size,
            ]);

            $apiClient = $this->getApiClient($instance);
            /** @var ApiResponseDto<array<AgentDataDto>> $response */
            $response = $apiClient->getAgentList($page, $size);

            if ($response->isSuccess()) {
                return [
                    'success' => true,
                    'message' => 'Agent list fetched successfully',
                    'data' => $response->getData(),
                ];
            }

            $message = $response->getMessage();
            $code = $response->getCode();

            throw new DocumentOperationException(sprintf('Failed to fetch agent list: %s', $message), $code);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch agent list', [
                'instance' => $instance->getName(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
