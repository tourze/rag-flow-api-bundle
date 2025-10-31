<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Service\DocumentStatusUpdateService;

/**
 * DocumentStatusUpdateService集成测试
 *
 * 测试服务在处理API返回数据时的兼容性
 *
 * @internal
 */
#[CoversClass(DocumentStatusUpdateService::class)]
#[RunTestsInSeparateProcesses]
class DocumentStatusUpdateServiceIntegrationTest extends AbstractIntegrationTestCase
{
    private DocumentStatusUpdateService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(DocumentStatusUpdateService::class);
    }

    /**
     * @return array<string, array{array<string, mixed>, DocumentStatus}>
     */
    public static function provideParseStatusData(): array
    {
        return [
            // API返回数字状态码
            'api_numeric_status_0' => [
                ['status' => 0, 'progress' => 0.0, 'progress_msg' => 'Starting...'],
                DocumentStatus::PENDING,
            ],
            'api_numeric_status_1' => [
                ['status' => 1, 'progress' => 100.0, 'progress_msg' => 'Uploaded'],
                DocumentStatus::UPLOADED,
            ],
            'api_numeric_status_2' => [
                ['status' => 2, 'progress' => 50.0, 'progress_msg' => 'Processing...'],
                DocumentStatus::PROCESSING,
            ],
            'api_numeric_status_3' => [
                ['status' => 3, 'progress' => 100.0, 'progress_msg' => 'Completed'],
                DocumentStatus::COMPLETED,
            ],
            'api_numeric_status_4' => [
                ['status' => 4, 'progress' => 0.0, 'progress_msg' => 'Failed'],
                DocumentStatus::FAILED,
            ],

            // API返回字符串数字状态码
            'api_string_numeric_status_0' => [
                ['status' => '0', 'progress' => 0.0, 'progress_msg' => 'Pending'],
                DocumentStatus::PENDING,
            ],
            'api_string_numeric_status_1' => [
                ['status' => '1', 'progress' => 100.0, 'progress_msg' => 'Uploaded'],
                DocumentStatus::UPLOADED,
            ],

            // API返回字符串状态码
            'api_string_status_pending' => [
                ['status' => 'pending', 'progress' => 0.0, 'progress_msg' => 'Pending'],
                DocumentStatus::PENDING,
            ],
            'api_string_status_uploading' => [
                ['status' => 'uploading', 'progress' => 25.0, 'progress_msg' => 'Uploading...'],
                DocumentStatus::UPLOADING,
            ],
            'api_string_status_uploaded' => [
                ['status' => 'uploaded', 'progress' => 100.0, 'progress_msg' => 'Upload complete'],
                DocumentStatus::UPLOADED,
            ],
            'api_string_status_processing' => [
                ['status' => 'processing', 'progress' => 75.0, 'progress_msg' => 'Processing...'],
                DocumentStatus::PROCESSING,
            ],
            'api_string_status_completed' => [
                ['status' => 'completed', 'progress' => 100.0, 'progress_msg' => 'All done'],
                DocumentStatus::COMPLETED,
            ],
            'api_string_status_failed' => [
                ['status' => 'failed', 'progress' => 0.0, 'progress_msg' => 'Processing failed'],
                DocumentStatus::FAILED,
            ],
            'api_string_status_synced' => [
                ['status' => 'synced', 'progress' => 100.0, 'progress_msg' => 'Synced'],
                DocumentStatus::SYNCED,
            ],
            'api_string_status_sync_failed' => [
                ['status' => 'sync_failed', 'progress' => 0.0, 'progress_msg' => 'Sync failed'],
                DocumentStatus::SYNC_FAILED,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parseStatus
     */
    #[DataProvider('provideParseStatusData')]
    public function testUpdateDocumentFromParseStatus(array $parseStatus, DocumentStatus $expectedStatus): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::PENDING); // 设置初始状态

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        $this->assertSame($expectedStatus, $document->getStatus());
        $this->assertSame((float) $parseStatus['progress'], $document->getProgress());
        $this->assertSame($parseStatus['progress_msg'], $document->getProgressMsg());
    }

    public function testUpdateDocumentFromParseStatusWithInvalidStatus(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::PENDING);

        // 无效状态值应该回退到PENDING
        $parseStatus = [
            'status' => 'invalid_status',
            'progress' => 50.0,
            'progress_msg' => 'Some progress',
        ];

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        // 无效状态应该回退到PENDING
        $this->assertSame(DocumentStatus::PENDING, $document->getStatus());
        $this->assertSame(50.0, $document->getProgress());
        $this->assertSame('Some progress', $document->getProgressMsg());
    }

    public function testUpdateDocumentFromParseStatusWithMissingFields(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::UPLOADING);
        $document->setProgress(25.0);
        $document->setProgressMsg('Previous message');

        // 只有部分字段的API响应
        $parseStatus = [
            'status' => 2, // 只更新状态
        ];

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        // 状态应该更新
        $this->assertSame(DocumentStatus::PROCESSING, $document->getStatus());
        // 其他字段保持不变
        $this->assertSame(25.0, $document->getProgress());
        $this->assertSame('Previous message', $document->getProgressMsg());
    }

    public function testUpdateDocumentFromParseStatusWithInvalidProgress(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setProgress(10.0);

        // 无效的progress值
        $parseStatus = [
            'status' => 1,
            'progress' => 'not_a_number',
            'progress_msg' => 'Updated message',
        ];

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());
        // progress保持原值，因为无效
        $this->assertSame(10.0, $document->getProgress());
        $this->assertSame('Updated message', $document->getProgressMsg());
    }

    public function testUpdateDocumentFromParseStatusWithInvalidProgressMsg(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::PENDING);
        $document->setProgressMsg('Original message');

        // 无效的progress_msg值
        $parseStatus = [
            'status' => 1,
            'progress' => 100.0,
            'progress_msg' => ['not', 'a', 'string'],
        ];

        $this->service->updateDocumentFromParseStatus($document, $parseStatus);

        $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());
        $this->assertSame(100.0, $document->getProgress());
        // progress_msg保持原值，因为无效
        $this->assertSame('Original message', $document->getProgressMsg());
    }

    public function testResetDocumentForRetry(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::FAILED);
        $document->setProgress(100.0);
        $document->setProgressMsg('Failed');
        $document->setRemoteId('old-remote-id');

        $this->service->resetDocumentForRetry($document);

        $this->assertSame(DocumentStatus::UPLOADING, $document->getStatus());
        $this->assertSame(0.0, $document->getProgress());
        $this->assertSame('准备重传...', $document->getProgressMsg());
        $this->assertNull($document->getRemoteId());
    }

    public function testMarkDocumentUploaded(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::UPLOADING);

        $this->service->markDocumentUploaded($document, 'new-remote-id');

        $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());
        $this->assertSame('new-remote-id', $document->getRemoteId());
        $this->assertSame('上传成功', $document->getProgressMsg());
        $this->assertInstanceOf(\DateTimeImmutable::class, $document->getLastSyncTime());
    }

    public function testMarkDocumentUploadedWithoutRemoteId(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::UPLOADING);
        $document->setRemoteId('existing-remote-id');

        $this->service->markDocumentUploaded($document);

        $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());
        $this->assertSame('existing-remote-id', $document->getRemoteId()); // 保持不变
        $this->assertSame('上传成功', $document->getProgressMsg());
    }

    public function testMarkDocumentUploadFailed(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::UPLOADING);

        $this->service->markDocumentUploadFailed($document, 'Network error');

        $this->assertSame(DocumentStatus::SYNC_FAILED, $document->getStatus());
        $this->assertSame('上传失败: Network error', $document->getProgressMsg());
    }

    public function testStartDocumentProcessing(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::UPLOADED);

        $this->service->startDocumentProcessing($document);

        $this->assertSame(DocumentStatus::PROCESSING, $document->getStatus());
        $this->assertSame(0.0, $document->getProgress());
        $this->assertSame('开始解析...', $document->getProgressMsg());
    }

    public function testStopDocumentProcessing(): void
    {
        $document = new Document();
        $document->setName('Test Document');
        $document->setStatus(DocumentStatus::PROCESSING);

        $this->service->stopDocumentProcessing($document);

        $this->assertSame(DocumentStatus::COMPLETED, $document->getStatus());
        $this->assertSame('解析已取消', $document->getProgressMsg());
    }

    public function testRealWorldAPIIntegrationScenario(): void
    {
        $document = new Document();
        $document->setName('Real World API Test');
        $document->setStatus(DocumentStatus::PENDING);

        // 模拟真实API调用序列

        // 1. 开始上传
        $this->service->resetDocumentForRetry($document);
        $this->assertSame(DocumentStatus::UPLOADING, $document->getStatus());

        // 2. 上传成功，API返回数字状态码1
        $apiResponse1 = [
            'status' => 1,
            'progress' => 1.0,
            'progress_msg' => 'Upload successful',
        ];
        $this->service->updateDocumentFromParseStatus($document, $apiResponse1);
        $this->assertSame(DocumentStatus::UPLOADED, $document->getStatus());

        // 3. 开始处理，API返回字符串状态码
        $apiResponse2 = [
            'status' => 'processing',
            'progress' => 0.5,
            'progress_msg' => 'Parsing document...',
        ];
        $this->service->updateDocumentFromParseStatus($document, $apiResponse2);
        $this->assertSame(DocumentStatus::PROCESSING, $document->getStatus());
        $this->assertSame(0.5, $document->getProgress());

        // 4. 处理完成，API返回字符串数字状态码
        $apiResponse3 = [
            'status' => '3',
            'progress' => 1.0,
            'progress_msg' => 'Processing complete',
        ];
        $this->service->updateDocumentFromParseStatus($document, $apiResponse3);
        $this->assertSame(DocumentStatus::COMPLETED, $document->getStatus());
        $this->assertSame(1.0, $document->getProgress());
        $this->assertSame('Processing complete', $document->getProgressMsg());
    }
}
