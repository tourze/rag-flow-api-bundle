<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Exception\InstanceNotFoundException;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;
use Tourze\RAGFlowApiBundle\Request\HealthCheckRequest;

#[WithMonologChannel(channel: 'rag_flow_api')]
readonly class RAGFlowInstanceManager implements RAGFlowInstanceManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RAGFlowInstanceRepository $repository,
        private RAGFlowApiClientFactory $clientFactory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function createInstance(array $config): RAGFlowInstance
    {
        if (!isset($config['name'], $config['api_url'], $config['api_key'])) {
            throw new \InvalidArgumentException('Missing required config parameters');
        }

        $name = $config['name'];
        $apiUrl = $config['api_url'];
        $apiKey = $config['api_key'];

        if (!\is_string($name) || !\is_string($apiUrl) || !\is_string($apiKey)) {
            throw new \InvalidArgumentException('name, api_url, and api_key must be strings');
        }

        // 检查是否已存在同名实例，如果存在则先删除（主要用于测试环境）
        $existingInstance = $this->repository->findByName($name);
        if (null !== $existingInstance) {
            $this->repository->remove($existingInstance);
        }

        $instance = new RAGFlowInstance();
        $instance->setName($name);
        $instance->setApiUrl($apiUrl);
        $encryptedKey = $instance->encryptApiKey($apiKey);
        $instance->setApiKey($encryptedKey);

        $description = $config['description'] ?? null;
        if (null !== $description && !\is_string($description)) {
            throw new \InvalidArgumentException('description must be a string');
        }
        $instance->setDescription($description);

        $timeout = $config['timeout'] ?? 30;
        if (!\is_int($timeout)) {
            throw new \InvalidArgumentException('timeout must be an integer');
        }
        $instance->setTimeout($timeout);

        $enabled = $config['enabled'] ?? true;
        if (!\is_bool($enabled)) {
            throw new \InvalidArgumentException('enabled must be a boolean');
        }
        $instance->setEnabled($enabled);

        $instance->setCreateTime(new \DateTimeImmutable());

        $this->repository->save($instance);

        return $instance;
    }

    public function getClient(string $instanceName): RAGFlowApiClient
    {
        $instance = $this->getInstanceByName($instanceName);

        return $this->clientFactory->createClient($instance);
    }

    public function getDefaultClient(): RAGFlowApiClient
    {
        $instance = $this->getDefaultInstance();

        return $this->clientFactory->createClient($instance);
    }

    public function getDefaultInstance(): RAGFlowInstance
    {
        return $this->getEarliestEnabledInstance();
    }

    public function checkHealth(string $instanceName): bool
    {
        try {
            $client = $this->getClient($instanceName);
            $response = $client->request(new HealthCheckRequest());

            $instance = $this->getInstanceByName($instanceName);
            $instance->setLastHealthCheck(new \DateTimeImmutable());
            $instance->setHealthy(true);

            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            $instance = $this->getInstanceByName($instanceName);
            $instance->setLastHealthCheck(new \DateTimeImmutable());
            $instance->setHealthy(false);

            $this->entityManager->flush();

            $this->logger->error('RAGFlow health check failed', [
                'instance' => $instanceName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return RAGFlowInstance[]
     */
    public function getActiveInstances(): array
    {
        return $this->repository->findHealthy();
    }

    private function getEarliestEnabledInstance(): RAGFlowInstance
    {
        $instance = $this->repository->findOneBy(
            ['enabled' => true],
            ['createTime' => 'ASC']
        );

        if (null === $instance) {
            throw new InstanceNotFoundException('No enabled RAGFlow instance found');
        }

        return $instance;
    }

    private function getInstanceByName(string $instanceName): RAGFlowInstance
    {
        $instance = $this->repository->findByName($instanceName);

        if (null === $instance) {
            throw new InstanceNotFoundException($instanceName);
        }

        return $instance;
    }
}
