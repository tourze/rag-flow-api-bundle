<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\System;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RAGFlowApiBundle\Client\RAGFlowApiClient;
use Tourze\RAGFlowApiBundle\Request\HealthCheckRequest;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * 系统状态API Controller
 *
 * 获取系统的运行状态信息
 */
final class StatusController extends AbstractController
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
    }

    #[Route(path: '/api/v1/system/status', name: 'api_system_status', methods: ['GET'])]
    public function __invoke(): JsonResponse
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

    private function getClient(): RAGFlowApiClient
    {
        $client = $this->instanceManager->getDefaultClient();
        assert($client instanceof RAGFlowApiClient);

        return $client;
    }
}
