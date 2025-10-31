<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Request\HealthCheckRequest;

/**
 * @internal
 */
#[CoversClass(HealthCheckRequest::class)]
class HealthCheckRequestTest extends TestCase
{
    public function testRequestPath(): void
    {
        $request = new HealthCheckRequest();
        $this->assertEquals('/api/v1/health', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $request = new HealthCheckRequest();
        $this->assertEquals('GET', $request->getRequestMethod());
    }

    public function testRequestOptions(): void
    {
        $request = new HealthCheckRequest();
        $options = $request->getRequestOptions();

        $this->assertNull($options);
    }

    public function testStringRepresentation(): void
    {
        $request = new HealthCheckRequest();
        $this->assertStringContainsString('HealthCheckRequest', (string) $request);
    }
}
