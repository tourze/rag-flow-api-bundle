<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\DocumentDataUpdater;

/**
 * 文档数据更新器测试
 *
 * @internal
 */
#[CoversClass(DocumentDataUpdater::class)]
#[RunTestsInSeparateProcesses]
final class DocumentDataUpdaterTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 不需要额外的服务初始化
    }

    private function createTestDocument(): Document
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Updater Test Instance');
        $instance->setApiUrl('https://updater-test.example.com/api');
        $instance->setApiKey('updater-test-key');

        $dataset = new Dataset();
        $dataset->setName('Updater Test Dataset');
        $dataset->setRemoteId('dataset-updater-123');
        $dataset->setRagFlowInstance($instance);

        $document = new Document();
        $document->setName('Original Name');
        $document->setFilename('original.txt');
        $document->setType('txt');
        $document->setDataset($dataset);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);
        $this->persistAndFlush($document);

        return $document;
    }

    public function testService创建(): void
    {
        $document = $this->createTestDocument();
        $updater = new DocumentDataUpdater($document);

        $this->assertInstanceOf(DocumentDataUpdater::class, $updater);
    }

    public function testUpdateName可以更新文档名称(): void
    {
        $document = $this->createTestDocument();
        $updater = new DocumentDataUpdater($document);

        $result = $updater->updateName('New Document Name');

        $this->assertSame($updater, $result, '应该返回自身以支持链式调用');
        $this->assertSame('New Document Name', $document->getName());
    }

    public function testUpdateName传入null不会更新(): void
    {
        $document = $this->createTestDocument();
        $originalName = $document->getName();
        $updater = new DocumentDataUpdater($document);

        $result = $updater->updateName(null);

        $this->assertSame($updater, $result);
        $this->assertSame($originalName, $document->getName());
    }

    public function testUpdateSummary可以更新摘要(): void
    {
        $document = $this->createTestDocument();
        $updater = new DocumentDataUpdater($document);

        $result = $updater->updateSummary('This is a test summary');

        $this->assertSame($updater, $result);
        $this->assertSame('This is a test summary', $document->getSummary());
    }

    public function testUpdateSummary传入null不会更新(): void
    {
        $document = $this->createTestDocument();
        $document->setSummary('Original Summary');
        $this->persistAndFlush($document);

        $updater = new DocumentDataUpdater($document);
        $result = $updater->updateSummary(null);

        $this->assertSame($updater, $result);
        $this->assertSame('Original Summary', $document->getSummary());
    }

    public function testUpdateLanguage可以更新语言(): void
    {
        $document = $this->createTestDocument();
        $updater = new DocumentDataUpdater($document);

        $result = $updater->updateLanguage('en');

        $this->assertSame($updater, $result);
        $this->assertSame('en', $document->getLanguage());
    }

    public function testUpdateLanguage传入null不会更新(): void
    {
        $document = $this->createTestDocument();
        $document->setLanguage('zh');
        $this->persistAndFlush($document);

        $updater = new DocumentDataUpdater($document);
        $result = $updater->updateLanguage(null);

        $this->assertSame($updater, $result);
        $this->assertSame('zh', $document->getLanguage());
    }

    public function test链式调用可以同时更新多个字段(): void
    {
        $document = $this->createTestDocument();
        $updater = new DocumentDataUpdater($document);

        $updater
            ->updateName('Chained Name')
            ->updateSummary('Chained Summary')
            ->updateLanguage('ja')
        ;

        $this->assertSame('Chained Name', $document->getName());
        $this->assertSame('Chained Summary', $document->getSummary());
        $this->assertSame('ja', $document->getLanguage());
    }

    public function test链式调用混合null值(): void
    {
        $document = $this->createTestDocument();
        $document->setSummary('Existing Summary');
        $document->setLanguage('fr');
        $this->persistAndFlush($document);

        $updater = new DocumentDataUpdater($document);

        $updater
            ->updateName('Only Name Changed')
            ->updateSummary(null) // 不应更新
            ->updateLanguage(null) // 不应更新
        ;

        $this->assertSame('Only Name Changed', $document->getName());
        $this->assertSame('Existing Summary', $document->getSummary());
        $this->assertSame('fr', $document->getLanguage());
    }

    public function testServiceIsFinal(): void
    {
        $reflection = new \ReflectionClass(DocumentDataUpdater::class);
        $this->assertTrue($reflection->isFinal());
    }

    public function testConstructorInjectsDocument(): void
    {
        $reflection = new \ReflectionClass(DocumentDataUpdater::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters);

        $parameter = $parameters[0];
        $this->assertSame('document', $parameter->getName());

        $type = $parameter->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame(Document::class, $type->getName());
    }

    public function testDocumentPropertyIsReadonly(): void
    {
        $reflection = new \ReflectionClass(DocumentDataUpdater::class);
        $property = $reflection->getProperty('document');

        $this->assertTrue($property->isReadOnly());
    }
}
