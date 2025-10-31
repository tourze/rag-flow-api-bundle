<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClientInterface;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

interface RAGFlowInstanceManagerInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function createInstance(array $config): RAGFlowInstance;

    public function getClient(string $instanceName): RAGFlowApiClientInterface;

    public function getDefaultClient(): RAGFlowApiClientInterface;

    public function getDefaultInstance(): RAGFlowInstance;

    public function checkHealth(string $instanceName): bool;

    /**
     * @return RAGFlowInstance[]
     */
    public function getActiveInstances(): array;
}
