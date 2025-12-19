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
 * 健康检查API Controller
 *
 * 检查RAGFlow服务的健康状态
 */
final class HealthCheckController extends AbstractController
{
    public function __construct(
        private readonly RAGFlowInstanceManagerInterface $instanceManager,
    ) {
    }

    #[Route(path: '/api/v1/system/health', name: 'api_system_health_check', methods: ['GET'])]
    public function __invoke(): JsonResponse
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

    private function getClient(): RAGFlowApiClient
    {
        $client = $this->instanceManager->getDefaultClient();
        assert($client instanceof RAGFlowApiClient);

        return $client;
    }
}
