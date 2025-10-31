<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Service\DocumentStatusUpdateService;

/**
 * 文档状态更新服务测试
 *
 * @internal
 */
#[CoversClass(DocumentStatusUpdateService::class)]
#[RunTestsInSeparateProcesses]
class DocumentStatusUpdateServiceTest extends AbstractIntegrationTestCase
{
    private DocumentStatusUpdateService $service;

    private Document&MockObject $document;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    protected function onSetUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->document = $this->createMock(Document::class);
        self::getContainer()->set(EntityManagerInterface::class, $this->entityManager);
        $this->service = self::getService(DocumentStatusUpdateService::class);
    }

    public function testUpdateDocumentFromParseStatusWithProgress(): void
    {
        $parseStatus = ['progress' => 75.5, 'progress_msg' => '正在处理中', 'status' => 'processing'];
        $this->document->expects($this->once())->method('setProgress')->with(75.5);
        $this->document->expects($this->once())->method('setProgressMsg')->with('正在处理中');
        $this->document->expects($this->once())->method('setStatus')->with(DocumentStatus::PROCESSING);
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->updateDocumentFromParseStatus($this->document, $parseStatus);
    }

    public function testUpdateDocumentFromParseStatusWithIntegerProgress(): void
    {
        $parseStatus = ['progress' => 50];
        $this->document->expects($this->once())->method('setProgress')->with(50.0);
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->updateDocumentFromParseStatus($this->document, $parseStatus);
    }

    public function testUpdateDocumentFromParseStatusWithStringProgress(): void
    {
        $parseStatus = ['progress' => '80.7'];
        $this->document->expects($this->once())->method('setProgress')->with(80.7);
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->updateDocumentFromParseStatus($this->document, $parseStatus);
    }

    public function testUpdateDocumentFromParseStatusWithInvalidStatus(): void
    {
        $parseStatus = ['status' => 'invalid_status'];
        $this->document->expects($this->once())->method('setStatus')->with(DocumentStatus::PENDING);
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->updateDocumentFromParseStatus($this->document, $parseStatus);
    }

    public function testUpdateDocumentFromParseStatusWithEmptyArray(): void
    {
        $parseStatus = [];
        $this->document->expects($this->never())->method('setProgress');
        $this->document->expects($this->never())->method('setProgressMsg');
        $this->document->expects($this->never())->method('setStatus');
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->updateDocumentFromParseStatus($this->document, $parseStatus);
    }

    public function testResetDocumentForRetry(): void
    {
        $this->document->expects($this->once())->method('setStatus')->with(DocumentStatus::UPLOADING);
        $this->document->expects($this->once())->method('setProgress')->with(0.0);
        $this->document->expects($this->once())->method('setProgressMsg')->with('准备重传...');
        $this->document->expects($this->once())->method('setRemoteId')->with(null);
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->resetDocumentForRetry($this->document);
    }

    public function testMarkDocumentUploadedWithRemoteId(): void
    {
        $remoteId = 'remote-doc-123';
        $this->document->expects($this->once())->method('setRemoteId')->with($remoteId);
        $this->document->expects($this->once())->method('setStatus')->with(DocumentStatus::UPLOADED);
        $this->document->expects($this->once())->method('setProgressMsg')->with('上传成功');
        $this->document->expects($this->once())->method('setLastSyncTime')->with(self::isInstanceOf(\DateTimeImmutable::class));
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->markDocumentUploaded($this->document, $remoteId);
    }

    public function testMarkDocumentUploadedWithoutRemoteId(): void
    {
        $this->document->expects($this->never())->method('setRemoteId');
        $this->document->expects($this->once())->method('setStatus')->with(DocumentStatus::UPLOADED);
        $this->document->expects($this->once())->method('setProgressMsg')->with('上传成功');
        $this->document->expects($this->once())->method('setLastSyncTime')->with(self::isInstanceOf(\DateTimeImmutable::class));
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->markDocumentUploaded($this->document);
    }

    public function testMarkDocumentUploadFailed(): void
    {
        $reason = 'Network timeout';
        $this->document->expects($this->once())->method('setStatus')->with(DocumentStatus::SYNC_FAILED);
        $this->document->expects($this->once())->method('setProgressMsg')->with("上传失败: {$reason}");
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->markDocumentUploadFailed($this->document, $reason);
    }

    public function testStartDocumentProcessing(): void
    {
        $this->document->expects($this->once())->method('setStatus')->with(DocumentStatus::PROCESSING);
        $this->document->expects($this->once())->method('setProgress')->with(0.0);
        $this->document->expects($this->once())->method('setProgressMsg')->with('开始解析...');
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->startDocumentProcessing($this->document);
    }

    public function testStopDocumentProcessing(): void
    {
        $this->document->expects($this->once())->method('setStatus')->with(DocumentStatus::COMPLETED);
        $this->document->expects($this->once())->method('setProgressMsg')->with('解析已取消');
        $this->entityManager->expects($this->once())->method('flush');
        $this->service->stopDocumentProcessing($this->document);
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
        $this->assertSame(EntityManagerInterface::class, $type->getName());
    }
}
