<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Orchestrator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Orchestrator\DocumentSyncOrchestrator;

/**
 * 测试Document同步编排器
 * @internal
 */
#[CoversClass(DocumentSyncOrchestrator::class)]
class DocumentSyncOrchestratorTest extends TestCase
{
    public function testOrchestrator(): void
    {
        $orchestrator = new DocumentSyncOrchestrator();
        $this->assertInstanceOf(DocumentSyncOrchestrator::class, $orchestrator);
    }
}
