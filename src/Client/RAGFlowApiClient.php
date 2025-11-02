<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Client;

use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Request\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\DoctrineAsyncInsertBundle\Service\AsyncInsertService;
use Tourze\RAGFlowApiBundle\DTO\AgentDataDto;
use Tourze\RAGFlowApiBundle\DTO\ApiResponseDto;
use Tourze\RAGFlowApiBundle\DTO\ResponseFactory\ResponseFactoryResolver;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Exception\ApiRequestException;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Request\CreateAgentRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteAgentRequest;
use Tourze\RAGFlowApiBundle\Request\GetAgentRequest;
use Tourze\RAGFlowApiBundle\Request\ListAgentsRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateAgentRequest;
use Tourze\RAGFlowApiBundle\Service\ChunkService;
use Tourze\RAGFlowApiBundle\Service\ConversationService;
use Tourze\RAGFlowApiBundle\Service\CurlUploadService;
use Tourze\RAGFlowApiBundle\Service\DatasetService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManager;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

class RAGFlowApiClient extends ApiClient implements RAGFlowApiClientInterface, RAGFlowInstanceManagerInterface
{
    private readonly ResponseFactoryResolver $responseFactoryResolver;

    private ?RAGFlowInstance $instance = null;

    private string $instanceName;

    public function __construct(
        private readonly RAGFlowInstanceManager $instanceManager,
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient,
        private readonly LockFactory $lockFactory,
        private readonly CacheInterface $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AsyncInsertService $asyncInsertService,
        private readonly LocalDataSyncService $localDataSyncService,
        private readonly DatasetRepository $datasetRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly CurlUploadService $curlUploadService,
    ) {
        $this->instanceName = 'default'; // 默认实例名称
        $this->responseFactoryResolver = new ResponseFactoryResolver();
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    protected function getLockFactory(): LockFactory
    {
        return $this->lockFactory;
    }

    protected function getCache(): CacheInterface
    {
        return $this->cache;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    protected function getAsyncInsertService(): AsyncInsertService
    {
        return $this->asyncInsertService;
    }

    protected function getRequestUrl(RequestInterface $request): string
    {
        return $this->getBaseUrl() . $request->getRequestPath();
    }

    protected function getRequestMethod(RequestInterface $request): string
    {
        return $request->getRequestMethod() ?? 'POST';
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getRequestOptions(RequestInterface $request): ?array
    {
        /** @var array<string, mixed> $options */
        $options = $request->getRequestOptions() ?? [];

        // 添加默认请求头
        $headers = $options['headers'] ?? [];
        if (!is_array($headers)) {
            $headers = [];
        }
        $options['headers'] = array_merge($headers, $this->getDefaultHeaders());

        $this->logger->info('ragflow-option', [
            'options' => $options,
        ]);

        return $options;
    }

    /**
     * @return ApiResponseDto<mixed>
     */
    protected function formatResponse(RequestInterface $request, ResponseInterface $response): ApiResponseDto
    {
        $content = $response->getContent(false);
        $this->logger->info('ragflow-response', [
            'content' => $content,
        ]);

        $data = json_decode($content, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ApiRequestException($request, $response, 'Invalid JSON response: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new ApiRequestException($request, $response, 'Invalid response format');
        }

        /** @var array<string, mixed> $data */
        $code = $data['code'] ?? null;
        if (!isset($data['code']) || 0 !== $code) {
            $message = isset($data['message']) && is_string($data['message']) ? $data['message'] : 'API request failed';
            $errorCode = isset($data['code']) && is_int($data['code']) ? $data['code'] : null;
            $errorDetails = json_encode($data);
            if (false === $errorDetails) {
                $errorDetails = 'Failed to encode error response';
            }
            throw new ApiRequestException($request, $response, $message, $errorCode, $errorDetails);
        }

        // 使用响应工厂创建DTO
        $factory = $this->responseFactoryResolver->resolve($request);

        return $factory->create($data);
    }

    public function getBaseUrl(): string
    {
        return $this->getInstance()->getApiUrl();
    }

    /**
     * @return array<string, string>
     */
    protected function getDefaultHeaders(): array
    {
        $instance = $this->getInstance();

        return [
            'Authorization' => 'Bearer ' . $instance->decryptApiKey($instance->getApiKey()),
            'Content-Type' => 'application/json',
        ];
    }

    public function getInstance(): RAGFlowInstance
    {
        if (null === $this->instance) {
            $this->instance = $this->instanceManager->getDefaultInstance();
        }

        return $this->instance;
    }

    public function setInstance(RAGFlowInstance $instance): void
    {
        $this->instance = $instance;
        $this->instanceName = $instance->getName();
    }

    public function getInstanceName(): string
    {
        return $this->instanceName;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function createInstance(array $config): RAGFlowInstance
    {
        // RAGFlowApiClient 本身是单实例的，直接返回当前实例
        return $this->getInstance();
    }

    public function getClient(string $instanceName): RAGFlowApiClientInterface
    {
        // RAGFlowApiClient 本身是单实例的，直接返回自己
        return $this;
    }

    public function getDefaultClient(): RAGFlowApiClientInterface
    {
        return $this;
    }

    public function getDefaultInstance(): RAGFlowInstance
    {
        return $this->getInstance();
    }

    public function checkHealth(string $instanceName): bool
    {
        return true;
    }

    /**
     * @return RAGFlowInstance[]
     */
    public function getActiveInstances(): array
    {
        return [$this->getInstance()];
    }

    public function datasets(): DatasetService
    {
        return new DatasetService($this, $this->localDataSyncService);
    }

    public function documents(): DocumentService
    {
        return new DocumentService(
            $this,
            $this->localDataSyncService,
            $this->datasetRepository,
            $this->curlUploadService
        );
    }

    public function chunks(): ChunkService
    {
        return new ChunkService(
            $this,
            $this->localDataSyncService,
            $this->documentRepository
        );
    }

    public function conversations(): ConversationService
    {
        return new ConversationService(
            $this,
            $this->localDataSyncService,
            $this->datasetRepository
        );
    }

    /**
     * 创建智能体
     *
     * @param array<string, mixed> $data
     * @return ApiResponseDto<AgentDataDto>
     */
    public function createAgent(array $data): ApiResponseDto
    {
        $request = new CreateAgentRequest($data);

        /** @var ApiResponseDto<AgentDataDto> */
        return $this->request($request);
    }

    /**
     * 更新智能体
     *
     * @param array<string, mixed> $data
     * @return ApiResponseDto<AgentDataDto>
     */
    public function updateAgent(string $agentId, array $data): ApiResponseDto
    {
        $request = new UpdateAgentRequest($agentId, $data);

        /** @var ApiResponseDto<AgentDataDto> */
        return $this->request($request);
    }

    /**
     * 删除智能体
     *
     * @return ApiResponseDto<array<string, mixed>>
     */
    public function deleteAgent(string $agentId): ApiResponseDto
    {
        $request = new DeleteAgentRequest($agentId);

        /** @var ApiResponseDto<array<string, mixed>> */
        return $this->request($request);
    }

    /**
     * 获取智能体列表
     *
     * @return ApiResponseDto<array<AgentDataDto>>
     */
    public function getAgentList(int $page = 1, int $size = 20): ApiResponseDto
    {
        $request = new ListAgentsRequest($page, $size);

        /** @var ApiResponseDto<array<AgentDataDto>> */
        return $this->request($request);
    }

    /**
     * 获取智能体详情
     *
     * @return ApiResponseDto<AgentDataDto>
     */
    public function getAgent(string $agentId): ApiResponseDto
    {
        $request = new GetAgentRequest($agentId);

        /** @var ApiResponseDto<AgentDataDto> */
        return $this->request($request);
    }
}
