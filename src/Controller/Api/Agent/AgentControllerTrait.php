<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Controller\Api\Agent;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 智能体控制器通用方法
 */
trait AgentControllerTrait
{
    /**
     * 解析请求数据
     */
    private function parseRequestData(Request $request): mixed
    {
        return json_decode($request->getContent(), true);
    }

    private function createErrorResponse(int $code, string $message, int $httpStatus = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
        ], $httpStatus);
    }

    /**
     * 格式化API结果
     *
     * @param array{success: bool, message?: string|null, data?: mixed} $result
     */
    private function formatApiResult(array $result): JsonResponse
    {
        $code = true === $result['success'] ? 0 : 500;
        $message = is_string($result['message'] ?? null) ? $result['message'] : '未知错误';

        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'data' => $result['data'] ?? null,
        ]);
    }
}
