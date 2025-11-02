<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Context\DocumentRequestContext;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentActionService;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * 测试文档动作服务
 * @internal
 */
#[CoversClass(DocumentActionService::class)]
class DocumentActionServiceTest extends TestCase
{
    public function testService(): void
    {
        $service = new DocumentActionService(
            $this->createMock(DocumentRepository::class),
            $this->createMock(DocumentService::class),
            $this->createMock(DocumentRequestContext::class)
        );
        $this->assertInstanceOf(DocumentActionService::class, $service);
    }
}
