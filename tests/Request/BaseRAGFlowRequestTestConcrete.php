<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use Tourze\RAGFlowApiBundle\Request\BaseRAGFlowRequest;

/**
 * 测试用的具体实现类
 *
 * @internal
 */
class BaseRAGFlowRequestTestConcrete extends BaseRAGFlowRequest
{
    public function getRequestPath(): string
    {
        return '/test';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        return [];
    }
}
