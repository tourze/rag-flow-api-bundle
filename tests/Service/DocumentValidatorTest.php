<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\DocumentValidator;

/**
 * 文档验证服务测试
 *
 * @internal
 */
#[CoversClass(DocumentValidator::class)]
#[RunTestsInSeparateProcesses]
final class DocumentValidatorTest extends AbstractIntegrationTestCase
{
    private DocumentValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = self::getContainer()->get(DocumentValidator::class);
    }

    private function createTestDatasetWithInstance(): Dataset
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Validator Test Instance');
        $instance->setApiUrl('https://validator-test.example.com/api');
        $instance->setApiKey('validator-test-key');

        $dataset = new Dataset();
        $dataset->setName('Validator Test Dataset');
        $dataset->setRemoteId('dataset-validator-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        return $dataset;
    }

    private function createTestDocument(Dataset $dataset, string $name): Document
    {
        $document = new Document();
        $document->setName($name);
        $document->setFilename("{$name}.txt");
        $document->setType('txt');
        $document->setDataset($dataset);

        $this->persistAndFlush($document);

        return $document;
    }

    public function testService创建(): void
    {
        $this->assertInstanceOf(DocumentValidator::class, $this->validator);
    }

    public function testFindDataset可以找到存在的数据集(): void
    {
        $dataset = $this->createTestDatasetWithInstance();
        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);

        $found = $this->validator->findDataset($datasetId);

        $this->assertInstanceOf(Dataset::class, $found);
        $this->assertSame($datasetId, $found->getId());
        $this->assertSame('Validator Test Dataset', $found->getName());
    }

    public function testFindDataset对不存在的数据集返回null(): void
    {
        $result = $this->validator->findDataset(999999);

        $this->assertNull($result);
    }

    public function testFindDocumentInDataset可以找到属于数据集的文档(): void
    {
        $dataset = $this->createTestDatasetWithInstance();
        $document = $this->createTestDocument($dataset, 'InDataset Test Doc');

        $documentId = $document->getId();
        $this->assertNotNull($documentId);

        $found = $this->validator->findDocumentInDataset($documentId, $dataset);

        $this->assertInstanceOf(Document::class, $found);
        $this->assertSame($documentId, $found->getId());
        $this->assertSame('InDataset Test Doc', $found->getName());
    }

    public function testFindDocumentInDataset对不存在的文档返回null(): void
    {
        $dataset = $this->createTestDatasetWithInstance();

        $result = $this->validator->findDocumentInDataset(999999, $dataset);

        $this->assertNull($result);
    }

    public function testFindDocumentInDataset对不属于数据集的文档返回null(): void
    {
        $dataset1 = $this->createTestDatasetWithInstance();

        // 创建第二个数据集
        $instance2 = new RAGFlowInstance();
        $instance2->setName('Second Instance');
        $instance2->setApiUrl('https://second.example.com/api');
        $instance2->setApiKey('second-key');

        $dataset2 = new Dataset();
        $dataset2->setName('Second Dataset');
        $dataset2->setRemoteId('dataset-second-789');
        $dataset2->setRagFlowInstance($instance2);

        $this->persistAndFlush($instance2);
        $this->persistAndFlush($dataset2);

        $document = $this->createTestDocument($dataset2, 'Doc in Dataset2');
        $documentId = $document->getId();
        $this->assertNotNull($documentId);

        // 尝试在 dataset1 中查找属于 dataset2 的文档
        $result = $this->validator->findDocumentInDataset($documentId, $dataset1);

        $this->assertNull($result);
    }

    public function testValidateDocumentForChunkSync当文档有remoteId时返回null(): void
    {
        $dataset = $this->createTestDatasetWithInstance();
        $document = $this->createTestDocument($dataset, 'Chunk Sync Test');
        $document->setRemoteId('remote-doc-123');
        $this->persistAndFlush($document);

        $result = $this->validator->validateDocumentForChunkSync($document);

        $this->assertNull($result);
    }

    public function testValidateDocumentForChunkSync当文档无remoteId时返回错误响应(): void
    {
        $dataset = $this->createTestDatasetWithInstance();
        $document = $this->createTestDocument($dataset, 'No Remote ID');

        $result = $this->validator->validateDocumentForChunkSync($document);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(400, $result->getStatusCode());

        $content = $result->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('文档尚未上传到RAGFlow', $data['error']);
    }

    public function testValidateDocumentForChunkSync当remoteId为空字符串时返回错误响应(): void
    {
        $dataset = $this->createTestDatasetWithInstance();
        $document = $this->createTestDocument($dataset, 'Empty Remote ID');
        $document->setRemoteId('');
        $this->persistAndFlush($document);

        $result = $this->validator->validateDocumentForChunkSync($document);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(400, $result->getStatusCode());
    }

    public function testCanSyncDataset当有有效remoteId时返回true(): void
    {
        $dataset = $this->createTestDatasetWithInstance();

        $result = $this->validator->canSyncDataset($dataset);

        $this->assertTrue($result);
    }

    public function testCanSyncDataset当remoteId为null时返回false(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('No Remote Instance');
        $instance->setApiUrl('https://no-remote.example.com/api');
        $instance->setApiKey('no-remote-key');

        $dataset = new Dataset();
        $dataset->setName('No Remote Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $result = $this->validator->canSyncDataset($dataset);

        $this->assertFalse($result);
    }

    public function testCanSyncDataset当remoteId为空字符串时返回false(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Empty Remote Instance');
        $instance->setApiUrl('https://empty-remote.example.com/api');
        $instance->setApiKey('empty-remote-key');

        $dataset = new Dataset();
        $dataset->setName('Empty Remote Dataset');
        $dataset->setRemoteId('');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $result = $this->validator->canSyncDataset($dataset);

        $this->assertFalse($result);
    }

    public function testCanSyncDocument当有有效remoteId时返回true(): void
    {
        $dataset = $this->createTestDatasetWithInstance();
        $document = $this->createTestDocument($dataset, 'Can Sync Doc');
        $document->setRemoteId('remote-doc-sync-123');
        $this->persistAndFlush($document);

        $result = $this->validator->canSyncDocument($document);

        $this->assertTrue($result);
    }

    public function testCanSyncDocument当remoteId为null时返回false(): void
    {
        $dataset = $this->createTestDatasetWithInstance();
        $document = $this->createTestDocument($dataset, 'No Remote Doc');

        $result = $this->validator->canSyncDocument($document);

        $this->assertFalse($result);
    }

    public function testCanSyncDocument当remoteId为空字符串时返回false(): void
    {
        $dataset = $this->createTestDatasetWithInstance();
        $document = $this->createTestDocument($dataset, 'Empty Remote Doc');
        $document->setRemoteId('');
        $this->persistAndFlush($document);

        $result = $this->validator->canSyncDocument($document);

        $this->assertFalse($result);
    }

    public function testServiceIsFinal(): void
    {
        $reflection = new \ReflectionClass(DocumentValidator::class);
        $this->assertTrue($reflection->isFinal());
    }

    public function testConstructorInjectsDependencies(): void
    {
        $reflection = new \ReflectionClass(DocumentValidator::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertSame('datasetRepository', $parameters[0]->getName());
        $this->assertSame('documentRepository', $parameters[1]->getName());
    }
}
