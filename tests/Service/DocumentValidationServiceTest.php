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
use Tourze\RAGFlowApiBundle\Service\DocumentValidationService;

/**
 * @internal
 */
#[CoversClass(DocumentValidationService::class)]
#[RunTestsInSeparateProcesses]
final class DocumentValidationServiceTest extends AbstractIntegrationTestCase
{
    private DocumentValidationService $validationService;

    protected function onSetUp(): void
    {
        $this->validationService = self::getService(DocumentValidationService::class);
    }

    public function test服务创建成功(): void
    {
        $this->assertInstanceOf(DocumentValidationService::class, $this->validationService);
    }

    public function test验证数据集存在时返回实体(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Validation Instance');
        $instance->setApiUrl('https://validation.example.com/api');
        $instance->setApiKey('validation-key');

        $dataset = new Dataset();
        $dataset->setName('Valid Dataset');
        $dataset->setRemoteId('dataset-valid-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);

        $result = $this->validationService->validateAndGetDataset($datasetId);

        $this->assertInstanceOf(Dataset::class, $result);
        $this->assertSame($datasetId, $result->getId());
        $this->assertSame('Valid Dataset', $result->getName());
    }

    public function test数据集不存在抛出异常(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dataset not found');

        $this->validationService->validateAndGetDataset(99999);
    }

    public function test文档归属正确时返回实体(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Document Instance');
        $instance->setApiUrl('https://document.example.com/api');
        $instance->setApiKey('document-key');

        $dataset = new Dataset();
        $dataset->setName('Document Dataset');
        $dataset->setRemoteId('dataset-doc-456');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Test Document');
        $document->setFilename('test.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setDataset($dataset);

        $this->persistAndFlush($document);

        $datasetId = $dataset->getId();
        $documentId = $document->getId();
        $this->assertNotNull($datasetId);
        $this->assertNotNull($documentId);

        $result = $this->validationService->validateAndGetDocument($datasetId, $documentId);

        $this->assertInstanceOf(Document::class, $result);
        $this->assertSame($documentId, $result->getId());
        $this->assertSame('Test Document', $result->getName());
    }

    public function test文档归属错误时抛出异常(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Multi Dataset Instance');
        $instance->setApiUrl('https://multi.example.com/api');
        $instance->setApiKey('multi-key');

        $dataset1 = new Dataset();
        $dataset1->setName('Dataset 1');
        $dataset1->setRemoteId('dataset-1');
        $dataset1->setRagFlowInstance($instance);

        $dataset2 = new Dataset();
        $dataset2->setName('Dataset 2');
        $dataset2->setRemoteId('dataset-2');
        $dataset2->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset1);
        $this->persistAndFlush($dataset2);

        $document = new Document();
        $document->setName('Document in Dataset 2');
        $document->setFilename('doc2.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setDataset($dataset2);

        $this->persistAndFlush($document);

        $dataset1Id = $dataset1->getId();
        $documentId = $document->getId();
        $this->assertNotNull($dataset1Id);
        $this->assertNotNull($documentId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Document not found or not belongs to this dataset');

        // 尝试用dataset1的ID验证属于dataset2的文档
        $this->validationService->validateAndGetDocument($dataset1Id, $documentId);
    }

    public function test查找验证返回null当文档不存在(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Find Instance');
        $instance->setApiUrl('https://find.example.com/api');
        $instance->setApiKey('find-key');

        $dataset = new Dataset();
        $dataset->setName('Find Dataset');
        $dataset->setRemoteId('dataset-find-789');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);

        $result = $this->validationService->findAndValidateDocument($datasetId, 99999);

        $this->assertNull($result);
    }

    public function test查找验证返回null当归属错误(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Ownership Instance');
        $instance->setApiUrl('https://ownership.example.com/api');
        $instance->setApiKey('ownership-key');

        $dataset1 = new Dataset();
        $dataset1->setName('Owner Dataset');
        $dataset1->setRemoteId('dataset-owner-1');
        $dataset1->setRagFlowInstance($instance);

        $dataset2 = new Dataset();
        $dataset2->setName('Other Dataset');
        $dataset2->setRemoteId('dataset-owner-2');
        $dataset2->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset1);
        $this->persistAndFlush($dataset2);

        $document = new Document();
        $document->setName('Document Owned By Dataset2');
        $document->setFilename('owned.txt');
        $document->setType('txt');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setDataset($dataset2);

        $this->persistAndFlush($document);

        $dataset1Id = $dataset1->getId();
        $documentId = $document->getId();
        $this->assertNotNull($dataset1Id);
        $this->assertNotNull($documentId);

        $result = $this->validationService->findAndValidateDocument($dataset1Id, $documentId);

        $this->assertNull($result);
    }

    public function test远程ID判定(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('RemoteId Instance');
        $instance->setApiUrl('https://remoteid.example.com/api');
        $instance->setApiKey('remoteid-key');

        $dataset = new Dataset();
        $dataset->setName('RemoteId Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        // 文档有有效remoteId
        $docWithRemoteId = new Document();
        $docWithRemoteId->setName('Doc With RemoteId');
        $docWithRemoteId->setFilename('with-remote.txt');
        $docWithRemoteId->setType('txt');
        $docWithRemoteId->setRemoteId('remote-123');
        $docWithRemoteId->setDataset($dataset);

        // 文档没有remoteId
        $docWithoutRemoteId = new Document();
        $docWithoutRemoteId->setName('Doc Without RemoteId');
        $docWithoutRemoteId->setFilename('without-remote.txt');
        $docWithoutRemoteId->setType('txt');
        $docWithoutRemoteId->setDataset($dataset);

        // 文档remoteId为空字符串
        $docWithEmptyRemoteId = new Document();
        $docWithEmptyRemoteId->setName('Doc With Empty RemoteId');
        $docWithEmptyRemoteId->setFilename('empty-remote.txt');
        $docWithEmptyRemoteId->setType('txt');
        $docWithEmptyRemoteId->setRemoteId('');
        $docWithEmptyRemoteId->setDataset($dataset);

        $this->persistAndFlush($docWithRemoteId);
        $this->persistAndFlush($docWithoutRemoteId);
        $this->persistAndFlush($docWithEmptyRemoteId);

        $this->assertTrue($this->validationService->hasValidRemoteId($docWithRemoteId));
        $this->assertFalse($this->validationService->hasValidRemoteId($docWithoutRemoteId));
        $this->assertFalse($this->validationService->hasValidRemoteId($docWithEmptyRemoteId));

        $this->assertTrue($this->validationService->isValidRemoteId('remote-123'));
        $this->assertFalse($this->validationService->isValidRemoteId(null));
        $this->assertFalse($this->validationService->isValidRemoteId(''));
    }

    public function test验证文档可以解析时remoteId存在(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Parsing Instance');
        $instance->setApiUrl('https://parsing.example.com/api');
        $instance->setApiKey('parsing-key');

        $dataset = new Dataset();
        $dataset->setName('Parsing Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Doc Ready To Parse');
        $document->setFilename('parse.txt');
        $document->setType('txt');
        $document->setRemoteId('remote-parse-123');
        $document->setDataset($dataset);

        $this->persistAndFlush($document);

        // 不应抛出异常
        $this->validationService->validateDocumentForParsing($document);
        // 无意义的断言已移除
    }

    public function test验证文档解析时remoteId缺失抛出异常(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('No Remote Instance');
        $instance->setApiUrl('https://noremote.example.com/api');
        $instance->setApiKey('noremote-key');

        $dataset = new Dataset();
        $dataset->setName('No Remote Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $document = new Document();
        $document->setName('Doc Without RemoteId');
        $document->setFilename('no-remote.txt');
        $document->setType('txt');
        $document->setDataset($dataset);

        $this->persistAndFlush($document);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Document not uploaded to RAGFlow yet');

        $this->validationService->validateDocumentForParsing($document);
    }
}
