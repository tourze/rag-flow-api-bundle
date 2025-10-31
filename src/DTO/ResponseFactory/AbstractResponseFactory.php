<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO\ResponseFactory;

use HttpClientBundle\Request\RequestInterface;
use Tourze\RAGFlowApiBundle\DTO\ApiResponseDto;

/**
 * 响应工厂抽象基类
 */
abstract class AbstractResponseFactory
{
    /**
     * 创建API响应DTO
     *
     * @param array<string, mixed> $payload API响应载荷
     * @return ApiResponseDto<mixed>
     */
    public function create(array $payload): ApiResponseDto
    {
        return ApiResponseDto::fromArray(
            $payload,
            fn(array $data) => $this->hydrate($data)
        );
    }

    /**
     * 水合器：将原始data字段转换为具体的数据结构
     *
     * @param array<string, mixed> $data 原始data字段数据
     * @return mixed 水合后的数据结构
     */
    abstract protected function hydrate(array $data);

    /**
     * 检查工厂是否支持指定的请求
     *
     * @param RequestInterface $request API请求
     */
    abstract public function supports(RequestInterface $request): bool;
}
