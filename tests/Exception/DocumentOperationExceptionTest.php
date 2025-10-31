<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RAGFlowApiBundle\Exception\DocumentOperationException;

/**
 * 文档操作异常测试
 *
 * @internal
 */
#[CoversClass(DocumentOperationException::class)]
class DocumentOperationExceptionTest extends AbstractExceptionTestCase
{
    public function testConstructor(): void
    {
        $message = 'Test error message';
        $code = 500;
        $documentId = 'doc123';
        $operation = 'upload';

        $exception = new DocumentOperationException(
            $message,
            $code,
            null,
            $documentId,
            $operation
        );

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($documentId, $exception->getDocumentId());
        $this->assertSame($operation, $exception->getOperation());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithDefaults(): void
    {
        $exception = new DocumentOperationException();

        $this->assertSame('Document operation failed', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertNull($exception->getDocumentId());
        $this->assertNull($exception->getOperation());
    }

    public function testConstructorWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new DocumentOperationException(
            'Test message',
            500,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testUploadFailed(): void
    {
        $documentName = 'test.pdf';
        $reason = 'File too large';

        $exception = DocumentOperationException::uploadFailed($documentName, $reason);

        $this->assertSame("文档上传失败: {$documentName}. 原因: {$reason}", $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame('upload', $exception->getOperation());
        $this->assertNull($exception->getDocumentId());
        $this->assertNull($exception->getPrevious());
    }

    public function testUploadFailedWithPrevious(): void
    {
        $documentName = 'test.pdf';
        $reason = 'Network error';
        $previous = new \Exception('Connection failed');

        $exception = DocumentOperationException::uploadFailed($documentName, $reason, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testSyncFailed(): void
    {
        $documentId = 'doc123';
        $reason = 'API timeout';

        $exception = DocumentOperationException::syncFailed($documentId, $reason);

        $this->assertSame("文档同步失败: ID {$documentId}. 原因: {$reason}", $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame('sync', $exception->getOperation());
        $this->assertSame($documentId, $exception->getDocumentId());
        $this->assertNull($exception->getPrevious());
    }

    public function testSyncFailedWithPrevious(): void
    {
        $documentId = 'doc123';
        $reason = 'Server error';
        $previous = new \Exception('HTTP 500');

        $exception = DocumentOperationException::syncFailed($documentId, $reason, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testParseFailed(): void
    {
        $documentId = 'doc123';
        $reason = 'Invalid format';

        $exception = DocumentOperationException::parseFailed($documentId, $reason);

        $this->assertSame("文档解析失败: ID {$documentId}. 原因: {$reason}", $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame('parse', $exception->getOperation());
        $this->assertSame($documentId, $exception->getDocumentId());
        $this->assertNull($exception->getPrevious());
    }

    public function testParseFailedWithPrevious(): void
    {
        $documentId = 'doc123';
        $reason = 'Corrupted file';
        $previous = new \Exception('Parse error');

        $exception = DocumentOperationException::parseFailed($documentId, $reason, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDatasetNotFound(): void
    {
        $datasetId = 'dataset123';

        $exception = DocumentOperationException::datasetNotFound($datasetId);

        $this->assertSame("数据集不存在: ID {$datasetId}", $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
        $this->assertSame('dataset_lookup', $exception->getOperation());
        $this->assertNull($exception->getDocumentId());
        $this->assertNull($exception->getPrevious());
    }

    public function testDocumentNotFound(): void
    {
        $documentId = 'doc123';

        $exception = DocumentOperationException::documentNotFound($documentId);

        $this->assertSame("文档不存在: ID {$documentId}", $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
        $this->assertSame('document_lookup', $exception->getOperation());
        $this->assertSame($documentId, $exception->getDocumentId());
        $this->assertNull($exception->getPrevious());
    }

    public function testUnsupportedFileType(): void
    {
        $fileType = 'exe';

        $exception = DocumentOperationException::unsupportedFileType($fileType);

        $this->assertSame("不支持的文件类型: {$fileType}", $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame('file_validation', $exception->getOperation());
        $this->assertNull($exception->getDocumentId());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * @return array<string, array{string, string, int, ?string, string}>
     */
    public static function provideFactoryMethods(): array
    {
        return [
            'uploadFailed' => ['uploadFailed', 'test.pdf', 500, null, 'upload'],
            'syncFailed' => ['syncFailed', 'doc123', 500, 'doc123', 'sync'],
            'parseFailed' => ['parseFailed', 'doc123', 500, 'doc123', 'parse'],
            'datasetNotFound' => ['datasetNotFound', 'dataset123', 404, null, 'dataset_lookup'],
            'documentNotFound' => ['documentNotFound', 'doc123', 404, 'doc123', 'document_lookup'],
            'unsupportedFileType' => ['unsupportedFileType', 'exe', 400, null, 'file_validation'],
        ];
    }

    #[DataProvider('provideFactoryMethods')]
    public function testFactoryMethodsReturnCorrectType(
        string $method,
        string $parameter,
        int $expectedCode,
        ?string $expectedDocumentId,
        string $expectedOperation,
    ): void {
        if ('uploadFailed' === $method) {
            $exception = DocumentOperationException::uploadFailed($parameter, 'test reason');
        } elseif ('syncFailed' === $method) {
            $exception = DocumentOperationException::syncFailed($parameter, 'test reason');
        } elseif ('parseFailed' === $method) {
            $exception = DocumentOperationException::parseFailed($parameter, 'test reason');
        } elseif ('datasetNotFound' === $method) {
            $exception = DocumentOperationException::datasetNotFound($parameter);
        } elseif ('documentNotFound' === $method) {
            $exception = DocumentOperationException::documentNotFound($parameter);
        } elseif ('unsupportedFileType' === $method) {
            $exception = DocumentOperationException::unsupportedFileType($parameter);
        } else {
            self::fail("Unknown method: {$method}");
        }

        $this->assertInstanceOf(DocumentOperationException::class, $exception);
        $this->assertSame($expectedCode, $exception->getCode());
        $this->assertSame($expectedDocumentId, $exception->getDocumentId());
        $this->assertSame($expectedOperation, $exception->getOperation());
        $this->assertNotEmpty($exception->getMessage());
    }

    public function testExtendsException(): void
    {
        $exception = new DocumentOperationException();
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
