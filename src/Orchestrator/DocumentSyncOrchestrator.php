<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Orchestrator;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tourze\RAGFlowApiBundle\Context\DocumentRequestContext;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Service\CurlUploadService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * 文档同步协调器
 * 负责协调文档同步相关的操作
 */
final class DocumentSyncOrchestrator
{
    private ?DocumentService $documentService = null;

    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
        private readonly DatasetRepository $datasetRepository,
        private readonly LocalDataSyncService $localDataSyncService,
        private readonly CurlUploadService $curlUploadService,
        private readonly DocumentRequestContext $requestContext,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    private function getDocumentService(): DocumentService
    {
        if (null === $this->documentService) {
            $this->documentService = new DocumentService(
                $this->instanceManager,
                $this->localDataSyncService,
                $this->datasetRepository,
                $this->curlUploadService
            );
        }

        return $this->documentService;
    }

    /**
     * 为当前请求同步远程文档
     */
    public function syncForRequest(): void
    {
        try {
            $this->performRemoteSync();
        } catch (\Exception $e) {
            $this->handleSyncError($e);
        }
    }

    private function performRemoteSync(): void
    {
        // 在测试环境中，跳过远程API调用以避免API错误
        if ($this->isTestEnvironment()) {
            return;
        }

        $datasetId = $this->requestContext->extractDatasetId();

        if (null !== $datasetId) {
            $this->syncDatasetDocuments($datasetId);
        } else {
            $this->syncAllDocuments();
        }
    }

    private function syncDatasetDocuments(int $datasetId): void
    {
        $dataset = $this->datasetRepository->find($datasetId);
        if (null === $dataset || null === $dataset->getRemoteId()) {
            return;
        }

        $this->getDocumentService()->list($dataset->getRemoteId());
        // 数据已经通过LocalDataSyncService自动同步到本地数据库
    }

    private function syncAllDocuments(): void
    {
        $this->getDocumentService()->listAll();
        // 数据已经通过LocalDataSyncService自动同步到本地数据库
    }

    private function handleSyncError(\Exception $e): void
    {
        error_log(sprintf('Failed to sync remote documents: %s', $e->getMessage()));
        // 注意：这里不直接操作Flash消息，而是抛出异常让上层处理
        throw new \RuntimeException('远程数据同步失败：' . $e->getMessage(), 0, $e);
    }

    private function isTestEnvironment(): bool
    {
        return 'test' === $this->parameterBag->get('kernel.environment');
    }
}
