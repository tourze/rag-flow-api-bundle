<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Repository\RAGFlowInstanceRepository;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManager;

class RAGFlowApiClientFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CacheInterface $cache,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
        private readonly AsyncInsertService $asyncInsertService,
        private readonly LocalDataSyncService $localDataSyncService,
        private readonly DatasetRepository $datasetRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly CurlUploadService $curlUploadService,
        private readonly EntityManagerInterface $entityManager,
        private readonly RAGFlowInstanceRepository $ragFlowInstanceRepository,
    ) {
    }

    public function createClient(RAGFlowInstance $instance): RAGFlowApiClient
    {
        $instanceManager = new RAGFlowInstanceManager(
            $this->entityManager,
            $this->ragFlowInstanceRepository,
            $this,
            $this->logger
        );

        $client = new RAGFlowApiClient(
            $instanceManager,
            $this->logger,
            $this->httpClient,
            $this->lockFactory,
            $this->cache,
            $this->eventDispatcher,
            $this->asyncInsertService,
            $this->localDataSyncService,
            $this->datasetRepository,
            $this->documentRepository,
            $this->curlUploadService
        );

        // 设置指定的实例
        $client->setInstance($instance);

        return $client;
    }
}
