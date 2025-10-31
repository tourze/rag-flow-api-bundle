<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Orchestrator;

use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Orchestrator\DocumentSyncOrchestrator;

/**
 * 测试Document同步编排器
 */
class DocumentSyncOrchestratorTest extends TestCase
{
    public function testOrchestrator(): void
    {
        $orchestrator = new DocumentSyncOrchestrator();
        $this->assertInstanceOf(DocumentSyncOrchestrator::class, $orchestrator);
    }
}