<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;

/**
 * 智能体请求验证服务
 */
final class AgentRequestValidator
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * 验证创建数据
     *
     * @param array<string, mixed> $data
     */
    public function validateCreateData(array $data): ?JsonResponse
    {
        if (!isset($data['title']) || '' === $data['title']) {
            return $this->createErrorResponse(400, '标题不能为空');
        }

        if (!isset($data['instance_id']) || '' === $data['instance_id']) {
            return $this->createErrorResponse(400, 'RAGFlow实例ID不能为空');
        }

        return null;
    }

    /**
     * 验证智能体实体
     */
    public function validateAgent(RAGFlowAgent $agent): ?JsonResponse
    {
        $errors = $this->validator->validate($agent);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->createErrorResponse(400, '验证失败: ' . implode(', ', $errorMessages));
        }

        return null;
    }

    /**
     * 创建错误响应
     */
    private function createErrorResponse(int $code, string $message, int $httpStatus = 400): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
        ], $httpStatus);
    }
}
