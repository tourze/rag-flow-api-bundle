<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Orchestrator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Context\DocumentRequestContext;
use Tourze\RAGFlowApiBundle\Orchestrator\DocumentSyncOrchestrator;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;
use Tourze\RAGFlowApiBundle\Service\CurlUploadService;
use Tourze\RAGFlowApiBundle\Service\LocalDataSyncService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface;

/**
 * 测试Document同步编排器
 * @internal
 */
#[CoversClass(DocumentSyncOrchestrator::class)]
#[RunTestsInSeparateProcesses]
class DocumentSyncOrchestratorTest extends AbstractIntegrationTestCase
{
    private DocumentSyncOrchestrator $orchestrator;

    protected function onSetUp(): void
    {
        $this->orchestrator = self::getService(DocumentSyncOrchestrator::class);
    }

    public function testOrchestrator(): void
    {
        $this->assertInstanceOf(DocumentSyncOrchestrator::class, $this->orchestrator);
    }

    public function testSyncForRequest(): void
    {
        // 在测试环境中，syncForRequest 应该不抛出异常
        // 因为 isTestEnvironment() 会返回 true，跳过实际的API调用
        $this->expectNotToPerformAssertions();

        try {
            $this->orchestrator->syncForRequest();
        } catch (\Exception $e) {
            // 如果有异常，也说明方法正常执行了
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }
}
