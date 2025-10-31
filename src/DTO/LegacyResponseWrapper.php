<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO;

use Tourze\RAGFlowApiBundle\DTO\ApiResponseDto;

/**
 * 向后兼容响应包装器
 *
 * 提供传统数组访问方式的兼容性支持
 */
final class LegacyResponseWrapper
{
    /**
     * 将DTO转换为传统数组格式
     *
     * @param ApiResponseDto<mixed> $dto
     * @return array<string, mixed>
     */
    public static function toArray(ApiResponseDto $dto): array
    {
        return $dto->toLegacyArray();
    }

    /**
     * 检查响应是否成功（向后兼容方法）
     *
     * @param ApiResponseDto<mixed> $dto
     * @return bool
     */
    public static function isSuccess(ApiResponseDto $dto): bool
    {
        return 0 === $dto->getCode();
    }

    /**
     * 获取响应消息（向后兼容方法）
     *
     * @param ApiResponseDto<mixed> $dto
     * @return string
     */
    public static function getMessage(ApiResponseDto $dto): string
    {
        return $dto->getMessage();
    }

    /**
     * 获取响应数据（向后兼容方法）
     *
     * @template T
     * @param ApiResponseDto<T> $dto
     * @return T
     */
    public static function getData(ApiResponseDto $dto)
    {
        return $dto->getData();
    }

    /**
     * 模拟传统的响应结构检查
     *
     * @param ApiResponseDto<mixed> $dto
     * @param int $expectedCode
     * @return bool
     */
    public static function hasCode(ApiResponseDto $dto, int $expectedCode): bool
    {
        return $dto->getCode() === $expectedCode;
    }
}
