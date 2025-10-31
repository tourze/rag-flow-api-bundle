<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Context;

use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Context\DocumentRequestContext;

/**
 * 测试Document请求上下文
 */
class DocumentRequestContextTest extends TestCase
{
    public function testContext(): void
    {
        $context = new DocumentRequestContext();
        $this->assertInstanceOf(DocumentRequestContext::class, $context);
    }
}