<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Service\DocumentActionService;

/**
 * 测试文档动作服务
 */
#[CoversClass(DocumentActionService::class)]
class DocumentActionServiceTest extends TestCase
{
    public function testService(): void
    {
        $service = new DocumentActionService(
            $this->createMock(\Tourze\RAGFlowApiBundle\Repository\DocumentRepository::class),
            $this->createMock(\Tourze\RAGFlowApiBundle\Service\DocumentService::class),
            $this->createMock(\Tourze\RAGFlowApiBundle\Context\DocumentRequestContext::class)
        );
        $this->assertInstanceOf(DocumentActionService::class, $service);
    }
}