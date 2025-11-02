<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\RAGFlowApiBundle\Context\DocumentRequestContext;

/**
 * 测试Document请求上下文
 */
#[CoversClass(DocumentRequestContext::class)]
class DocumentRequestContextTest extends TestCase
{
    public function testContext(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $context = new DocumentRequestContext($requestStack);
        $this->assertInstanceOf(DocumentRequestContext::class, $context);
    }
}