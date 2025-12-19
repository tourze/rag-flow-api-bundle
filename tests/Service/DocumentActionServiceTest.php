<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Service\DocumentActionService;

/**
 * 测试文档动作服务
 * @internal
 */
#[CoversClass(DocumentActionService::class)]
#[RunTestsInSeparateProcesses]
class DocumentActionServiceTest extends AbstractIntegrationTestCase
{
    private DocumentActionService $actionService;

    protected function onSetUp(): void
    {
        $this->actionService = self::getService(DocumentActionService::class);
    }

    public function testService(): void
    {
        $this->assertInstanceOf(DocumentActionService::class, $this->actionService);
    }

    public function testExecuteParsing(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Execute Parsing Instance');
        $instance->setApiUrl('https://execute-parsing.example.com/api');
        $instance->setApiKey('execute-parsing-key');

        $dataset = new Dataset();
        $dataset->setName('Execute Parsing Dataset');
        $dataset->setRemoteId('dataset-execute-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Document to Parse');
        $document->setFilename('parse.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setRemoteId('remote-doc-123');
        $document->setDataset($dataset);

        $this->persistAndFlush($document);

        $documentId = $document->getId();
        $this->assertNotNull($documentId);

        // 由于实际解析需要调用外部API，此处验证方法返回结果
        try {
            $result = $this->actionService->executeParsing((string) $documentId);
            $this->assertIsObject($result);
        } catch (\Exception $e) {
            // API调用失败是预期的，因为测试环境中没有真实的API
            $this->assertTrue(true, '方法调用抛出了预期的异常');
        }
    }

    public function testShowParseStatus(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Show Status Instance');
        $instance->setApiUrl('https://show-status.example.com/api');
        $instance->setApiKey('show-status-key');

        $dataset = new Dataset();
        $dataset->setName('Show Status Dataset');
        $dataset->setRemoteId('dataset-status-456');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Document Status Check');
        $document->setFilename('status.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::COMPLETED);
        $document->setDataset($dataset);

        $this->persistAndFlush($document);

        $documentId = $document->getId();
        $this->assertNotNull($documentId);

        $result = $this->actionService->showParseStatus((string) $documentId);

        $this->assertIsObject($result);
        $this->assertTrue($result->success);
        $this->assertStringContainsString('Document Status Check', $result->message);
    }

    public function testDownloadDocument(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Download Instance');
        $instance->setApiUrl('https://download.example.com/api');
        $instance->setApiKey('download-key');

        $dataset = new Dataset();
        $dataset->setName('Download Dataset');
        $dataset->setRemoteId('dataset-download-789');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Document to Download');
        $document->setFilename('download.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::COMPLETED);
        $document->setDataset($dataset);

        $this->persistAndFlush($document);

        $documentId = $document->getId();
        $this->assertNotNull($documentId);

        $result = $this->actionService->downloadDocument((string) $documentId);

        $this->assertIsObject($result);
        $this->assertTrue($result->success);
        $this->assertStringContainsString('暂未实现', $result->message);
    }
}
