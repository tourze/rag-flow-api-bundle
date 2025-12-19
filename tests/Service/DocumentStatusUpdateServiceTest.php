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
use Tourze\RAGFlowApiBundle\Service\DocumentStatusUpdateService;

/**
 * 文档状态更新服务集成测试
 *
 * 测试服务与真实 EntityManager 和数据库的交互
 *
 * @internal
 */
#[CoversClass(DocumentStatusUpdateService::class)]
#[RunTestsInSeparateProcesses]
class DocumentStatusUpdateServiceTest extends AbstractIntegrationTestCase
{
    private DocumentStatusUpdateService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(DocumentStatusUpdateService::class);
    }

    /**
     * 创建测试用的 Document 实体
     */
    private function createTestDocument(): Document
    {
        // 创建 RAGFlowInstance
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setApiUrl('https://test.example.com');
        $instance->setApiKey('test-key');

        // 创建 Dataset
        $dataset = new Dataset();
        $dataset->setName('Test Dataset');
        $dataset->setRagFlowInstance($instance);

        // 创建 Document
        $document = new Document();
        $document->setName('Test Document');
        $document->setDataset($dataset);

        // 持久化
        $em = self::getEntityManager();
        $em->persist($instance);
        $em->persist($dataset);
        $em->persist($document);
        $em->flush();

        return $document;
    }

    public function testUpdateDocumentFromParseStatusWithProgress(): void
    {
        $document = $this->createTestDocument();
        $parseStatus = ['progress' => 75.5, 'progress_msg' => '正在处理中', 'status' => 'processing'];

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        // 清除实体管理器缓存并重新加载
        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame(75.5, $refreshedDocument->getProgress());
        $this->assertSame('正在处理中', $refreshedDocument->getProgressMsg());
        $this->assertSame(DocumentStatus::PROCESSING, $refreshedDocument->getStatus());
    }

    public function testUpdateDocumentFromParseStatusWithIntegerProgress(): void
    {
        $document = $this->createTestDocument();
        $parseStatus = ['progress' => 50];

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame(50.0, $refreshedDocument->getProgress());
    }

    public function testUpdateDocumentFromParseStatusWithStringProgress(): void
    {
        $document = $this->createTestDocument();
        $parseStatus = ['progress' => '80.7'];

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame(80.7, $refreshedDocument->getProgress());
    }

    public function testUpdateDocumentFromParseStatusWithInvalidStatus(): void
    {
        $document = $this->createTestDocument();
        $parseStatus = ['status' => 'invalid_status'];

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame(DocumentStatus::PENDING, $refreshedDocument->getStatus());
    }

    public function testUpdateDocumentFromParseStatusWithEmptyArray(): void
    {
        $document = $this->createTestDocument();
        $originalProgress = $document->getProgress();
        $originalProgressMsg = $document->getProgressMsg();
        $originalStatus = $document->getStatus();

        $parseStatus = [];
        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        // 空数组不应该修改任何字段
        $this->assertSame($originalProgress, $refreshedDocument->getProgress());
        $this->assertSame($originalProgressMsg, $refreshedDocument->getProgressMsg());
        $this->assertSame($originalStatus, $refreshedDocument->getStatus());
    }

    public function testResetDocumentForRetry(): void
    {
        $document = $this->createTestDocument();
        $document->setRemoteId('remote-123');
        $document->setStatus(DocumentStatus::SYNC_FAILED);
        $document->setProgress(50.0);
        self::getEntityManager()->flush();

        $this->service->resetDocumentForRetry($document);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame(DocumentStatus::UPLOADING, $refreshedDocument->getStatus());
        $this->assertSame(0.0, $refreshedDocument->getProgress());
        $this->assertSame('准备重传...', $refreshedDocument->getProgressMsg());
        $this->assertNull($refreshedDocument->getRemoteId());
    }

    public function testMarkDocumentUploadedWithRemoteId(): void
    {
        $document = $this->createTestDocument();
        $remoteId = 'remote-doc-123';

        $this->service->markDocumentUploaded($document, $remoteId);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame($remoteId, $refreshedDocument->getRemoteId());
        $this->assertSame(DocumentStatus::UPLOADED, $refreshedDocument->getStatus());
        $this->assertSame('上传成功', $refreshedDocument->getProgressMsg());
        $this->assertInstanceOf(\DateTimeImmutable::class, $refreshedDocument->getLastSyncTime());
    }

    public function testMarkDocumentUploadedWithoutRemoteId(): void
    {
        $document = $this->createTestDocument();
        $document->setRemoteId('existing-remote-id');
        self::getEntityManager()->flush();

        $this->service->markDocumentUploaded($document);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        // remoteId 应该保持不变
        $this->assertSame('existing-remote-id', $refreshedDocument->getRemoteId());
        $this->assertSame(DocumentStatus::UPLOADED, $refreshedDocument->getStatus());
        $this->assertSame('上传成功', $refreshedDocument->getProgressMsg());
        $this->assertInstanceOf(\DateTimeImmutable::class, $refreshedDocument->getLastSyncTime());
    }

    public function testMarkDocumentUploadFailed(): void
    {
        $document = $this->createTestDocument();
        $reason = 'Network timeout';

        $this->service->markDocumentUploadFailed($document, $reason);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame(DocumentStatus::SYNC_FAILED, $refreshedDocument->getStatus());
        $this->assertSame("上传失败: {$reason}", $refreshedDocument->getProgressMsg());
    }

    public function testStartDocumentProcessing(): void
    {
        $document = $this->createTestDocument();

        $this->service->startDocumentProcessing($document);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame(DocumentStatus::PROCESSING, $refreshedDocument->getStatus());
        $this->assertSame(0.0, $refreshedDocument->getProgress());
        $this->assertSame('开始解析...', $refreshedDocument->getProgressMsg());
    }

    public function testStopDocumentProcessing(): void
    {
        $document = $this->createTestDocument();

        $this->service->stopDocumentProcessing($document);

        $em = self::getEntityManager();
        $em->clear();
        $refreshedDocument = $em->find(Document::class, $document->getId());

        $this->assertNotNull($refreshedDocument);
        $this->assertSame(DocumentStatus::COMPLETED, $refreshedDocument->getStatus());
        $this->assertSame('解析已取消', $refreshedDocument->getProgressMsg());
    }

    public function testServiceIsReadonly(): void
    {
        $reflection = new \ReflectionClass(DocumentStatusUpdateService::class);
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->hasProperty('entityManager'));
        $property = $reflection->getProperty('entityManager');
        $this->assertTrue($property->isReadOnly());
    }

    public function testConstructorInjectsEntityManager(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters);
        $parameter = $parameters[0];
        $this->assertSame('entityManager', $parameter->getName());
        $type = $parameter->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('Doctrine\ORM\EntityManagerInterface', $type->getName());
    }
}
