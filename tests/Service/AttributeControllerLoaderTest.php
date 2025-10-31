<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Service\AttributeControllerLoader;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
final class AttributeControllerLoaderTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->markTestIncomplete('Test implementation pending');
    }
}
