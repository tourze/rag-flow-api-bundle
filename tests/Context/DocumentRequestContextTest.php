<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\RAGFlowApiBundle\Context\DocumentRequestContext;

/**
 * 测试Document请求上下文
 * @internal
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

    public function testExtractDatasetId(): void
    {
        $request = new Request();
        $request->query->set('filters', ['dataset' => '123']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = new DocumentRequestContext($requestStack);
        $datasetId = $context->extractDatasetId();

        $this->assertSame(123, $datasetId);
    }

    public function testExtractDatasetIdReturnsNullWhenNoRequest(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $context = new DocumentRequestContext($requestStack);
        $datasetId = $context->extractDatasetId();

        $this->assertNull($datasetId);
    }

    public function testExtractDatasetIdHandlesArrayValue(): void
    {
        $request = new Request();
        $request->query->set('filters', ['dataset' => ['456']]);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = new DocumentRequestContext($requestStack);
        $datasetId = $context->extractDatasetId();

        $this->assertSame(456, $datasetId);
    }

    public function testResolveEntityId(): void
    {
        $request = new Request();
        $request->attributes->set('entityId', '789');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = new DocumentRequestContext($requestStack);
        $entityId = $context->resolveEntityId();

        $this->assertSame('789', $entityId);
    }

    public function testResolveEntityIdReturnsNullWhenNoRequest(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $context = new DocumentRequestContext($requestStack);
        $entityId = $context->resolveEntityId();

        $this->assertNull($entityId);
    }

    public function testResolveEntityIdConvertsIntToString(): void
    {
        $request = new Request();
        $request->attributes->set('entityId', 999);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $context = new DocumentRequestContext($requestStack);
        $entityId = $context->resolveEntityId();

        $this->assertSame('999', $entityId);
    }
}
