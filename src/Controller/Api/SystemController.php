<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Request\HealthCheckRequest;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * 系统相关API Controller
 *
 * 提供系统健康检查和状态监控的RESTful API接口
 */
#[Route(path: '/api/v1/system', name: 'api_system_')]
final class SystemController extends AbstractController
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
    }

    private function getClient(): RAGFlowApiClient
    {
        $client = $this->instanceManager->getDefaultClient();
        assert($client instanceof RAGFlowApiClient);

        return $client;
    }

    /**
     * 健康检查API
     *
     * 检查RAGFlow服务的健康状态
     */
    #[Route(path: '/health', name: 'health_check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        try {
            $request = new HealthCheckRequest();
            $result = $this->getClient()->request($request);

            return new JsonResponse([
                'status' => 'ok',
                'message' => 'RAGFlow service is healthy',
                'data' => $result,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'RAGFlow service health check failed',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * 系统状态API
     *
     * 获取系统的运行状态信息
     */
    #[Route(path: '/status', name: 'status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        try {
            // 检查RAGFlow服务是否可用
            $request = new HealthCheckRequest();
            $healthResult = $this->getClient()->request($request);

            return new JsonResponse([
                'status' => 'running',
                'message' => 'System is operational',
                'services' => [
                    'ragflow' => [
                        'status' => 'healthy',
                        'data' => $healthResult,
                    ],
                ],
                'timestamp' => date('c'),
                'version' => '1.0.0',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'degraded',
                'message' => 'Some services are experiencing issues',
                'services' => [
                    'ragflow' => [
                        'status' => 'unhealthy',
                        'error' => $e->getMessage(),
                    ],
                ],
                'timestamp' => date('c'),
                'version' => '1.0.0',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
