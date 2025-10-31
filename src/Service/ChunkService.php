<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\Chunk;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Request\AddChunksRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteChunkRequest;
use Tourze\RAGFlowApiBundle\Request\RetrieveChunksRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateChunkRequest;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

class ChunkService
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
        private readonly LocalDataSyncService $localDataSyncService,
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    private function getClient(): RAGFlowApiClient
    {
        $client = $this->instanceManager->getDefaultClient();
        assert($client instanceof RAGFlowApiClient);

        return $client;
    }

    /**
     * @param array<string, mixed> $options
     * @return Chunk[]
     */
    public function retrieve(string $datasetId, string $query, array $options = []): array
    {
        $request = new RetrieveChunksRequest($datasetId, $options);
        $apiResponse = $this->getClient()->request($request);

        if (!is_array($apiResponse)) {
            return [];
        }

        $chunks = [];
        $chunksData = $apiResponse['chunks'] ?? [];

        if (is_array($chunksData)) {
            foreach ($chunksData as $chunkData) {
                if (!is_array($chunkData)) {
                    continue;
                }
                /** @var array<string, mixed> $chunkData */
                if (isset($chunkData['document_id']) && is_string($chunkData['document_id'])) {
                    $documentId = $chunkData['document_id'];
                    $document = $this->getLocalDocument($documentId);
                    $chunks[] = $this->localDataSyncService->syncChunkFromApi($document, $chunkData);
                }
            }
        }

        return $chunks;
    }

    /**
     * @param array<int, array<string, mixed>> $chunks
     * @return array<string, mixed>
     */
    public function add(string $datasetId, array $chunks): array
    {
        $request = new AddChunksRequest($datasetId, $chunks);

        $result = $this->getClient()->request($request);

        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(string $datasetId, string $chunkId, array $data): array
    {
        $request = new UpdateChunkRequest($datasetId, $chunkId, $data);

        $result = $this->getClient()->request($request);

        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    public function delete(string $datasetId, string $chunkId): bool
    {
        $request = new DeleteChunkRequest($datasetId, $chunkId);
        $this->getClient()->request($request);

        return true;
    }

    private function getLocalDocument(string $remoteDocumentId): Document
    {
        $document = $this->documentRepository->findOneBy([
            'remoteId' => $remoteDocumentId,
        ]);

        if (null === $document) {
            throw new \RuntimeException(sprintf('Local document not found for remote ID: %s', $remoteDocumentId));
        }

        return $document;
    }
}
