<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\DTO;

use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\DTO\LegacyResponseWrapper;

/**
 * 测试Legacy响应包装器
 */
class LegacyResponseWrapperTest extends TestCase
{
    public function testWrapper(): void
    {
        $wrapper = new LegacyResponseWrapper();
        $this->assertInstanceOf(LegacyResponseWrapper::class, $wrapper);
    }
}