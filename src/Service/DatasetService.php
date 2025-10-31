<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Request\CreateDatasetRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteDatasetRequest;
use Tourze\RAGFlowApiBundle\Request\GetKnowledgeGraphRequest;
use Tourze\RAGFlowApiBundle\Request\ListDatasetsRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateDatasetRequest;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

class DatasetService
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
        private readonly LocalDataSyncService $localDataSyncService,
    ) {
    }

    private function getClient(): RAGFlowApiClient
    {
        $client = $this->instanceManager->getDefaultClient();
        assert($client instanceof RAGFlowApiClient);

        return $client;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function create(array $config): Dataset
    {
        $request = new CreateDatasetRequest($config);
        $apiResponse = $this->getClient()->request($request);

        if (!is_array($apiResponse)) {
            throw new \RuntimeException('Invalid API response format');
        }

        // 确保API响应数据格式正确
        /** @var array<string, mixed> $validatedApiResponse */
        $validatedApiResponse = $apiResponse;

        return $this->localDataSyncService->syncDatasetFromApi(
            $this->getClient()->getInstance(),
            $validatedApiResponse
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return Dataset[]
     */
    public function list(array $filters = []): array
    {
        $request = new ListDatasetsRequest($filters);
        $apiResponse = $this->getClient()->request($request);

        $datasets = [];
        if (is_array($apiResponse)) {
            foreach ($apiResponse as $datasetData) {
                if (is_array($datasetData)) {
                    // PHPStan无法推断数组键类型，添加类型断言
                    $typedDatasetData = array_map(fn ($value) => $value, $datasetData);
                    /** @var array<string, mixed> $typedDatasetData */
                    $datasets[] = $this->localDataSyncService->syncDatasetFromApi(
                        $this->getClient()->getInstance(),
                        $typedDatasetData
                    );
                }
            }
        }

        return $datasets;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function update(string $datasetId, array $config): void
    {
        $request = new UpdateDatasetRequest($datasetId, $config);
        $this->getClient()->request($request);
    }

    public function delete(string $datasetId): bool
    {
        $request = new DeleteDatasetRequest($datasetId);
        $this->getClient()->request($request);

        $this->localDataSyncService->deleteLocalDataset($datasetId, $this->getClient()->getInstance());

        return true;
    }

    /** @return array<string, mixed> */
    public function getKnowledgeGraph(string $datasetId): array
    {
        $request = new GetKnowledgeGraphRequest($datasetId);
        $result = $this->getClient()->request($request);

        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }
}
