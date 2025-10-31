<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

/**
 * 文档状态枚举测试
 *
 * @internal
 */
#[CoversClass(DocumentStatus::class)]
#[RunTestsInSeparateProcesses]
class DocumentStatusTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 枚举测试不需要特殊的设置逻辑
    }

    /**
     * @return array<string, array{DocumentStatus, string}>
     */
    public static function provideLabelData(): array
    {
        return [
            'pending' => [DocumentStatus::PENDING, '待处理'],
            'uploading' => [DocumentStatus::UPLOADING, '上传中'],
            'uploaded' => [DocumentStatus::UPLOADED, '已上传'],
            'processing' => [DocumentStatus::PROCESSING, '处理中'],
            'completed' => [DocumentStatus::COMPLETED, '已完成'],
            'failed' => [DocumentStatus::FAILED, '失败'],
            'synced' => [DocumentStatus::SYNCED, '已同步'],
            'sync_failed' => [DocumentStatus::SYNC_FAILED, '同步失败'],
        ];
    }

    #[DataProvider('provideLabelData')]
    public function testGetLabel(DocumentStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->getLabel());
    }

    /**
     * @return array<string, array{DocumentStatus, string}>
     */
    public static function provideCssClassData(): array
    {
        return [
            'pending' => [DocumentStatus::PENDING, 'secondary'],
            'uploading' => [DocumentStatus::UPLOADING, 'warning'],
            'uploaded' => [DocumentStatus::UPLOADED, 'info'],
            'processing' => [DocumentStatus::PROCESSING, 'warning'],
            'completed' => [DocumentStatus::COMPLETED, 'success'],
            'failed' => [DocumentStatus::FAILED, 'danger'],
            'synced' => [DocumentStatus::SYNCED, 'success'],
            'sync_failed' => [DocumentStatus::SYNC_FAILED, 'danger'],
        ];
    }

    #[DataProvider('provideCssClassData')]
    public function testGetCssClass(DocumentStatus $status, string $expectedClass): void
    {
        $this->assertSame($expectedClass, $status->getCssClass());
    }

    /**
     * @return array<string, array{DocumentStatus, bool}>
     */
    public static function provideFailedStatusData(): array
    {
        return [
            'pending' => [DocumentStatus::PENDING, false],
            'uploading' => [DocumentStatus::UPLOADING, false],
            'uploaded' => [DocumentStatus::UPLOADED, false],
            'processing' => [DocumentStatus::PROCESSING, false],
            'completed' => [DocumentStatus::COMPLETED, false],
            'failed' => [DocumentStatus::FAILED, true],
            'synced' => [DocumentStatus::SYNCED, false],
            'sync_failed' => [DocumentStatus::SYNC_FAILED, true],
        ];
    }

    #[DataProvider('provideFailedStatusData')]
    public function testIsFailed(DocumentStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isFailed());
    }

    /**
     * @return array<string, array{DocumentStatus, bool}>
     */
    public static function provideProcessingStatusData(): array
    {
        return [
            'pending' => [DocumentStatus::PENDING, false],
            'uploading' => [DocumentStatus::UPLOADING, true],
            'uploaded' => [DocumentStatus::UPLOADED, false],
            'processing' => [DocumentStatus::PROCESSING, true],
            'completed' => [DocumentStatus::COMPLETED, false],
            'failed' => [DocumentStatus::FAILED, false],
            'synced' => [DocumentStatus::SYNCED, false],
            'sync_failed' => [DocumentStatus::SYNC_FAILED, false],
        ];
    }

    #[DataProvider('provideProcessingStatusData')]
    public function testIsProcessing(DocumentStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isProcessing());
    }

    /**
     * @return array<string, array{DocumentStatus, bool}>
     */
    public static function provideCompletedStatusData(): array
    {
        return [
            'pending' => [DocumentStatus::PENDING, false],
            'uploading' => [DocumentStatus::UPLOADING, false],
            'uploaded' => [DocumentStatus::UPLOADED, true],
            'processing' => [DocumentStatus::PROCESSING, false],
            'completed' => [DocumentStatus::COMPLETED, true],
            'failed' => [DocumentStatus::FAILED, false],
            'synced' => [DocumentStatus::SYNCED, false],
            'sync_failed' => [DocumentStatus::SYNC_FAILED, false],
        ];
    }

    #[DataProvider('provideCompletedStatusData')]
    public function testIsCompleted(DocumentStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isCompleted());
    }

    /**
     * @return array<string, array{DocumentStatus, bool}>
     */
    public static function provideRetryStatusData(): array
    {
        return [
            'pending' => [DocumentStatus::PENDING, false],
            'uploading' => [DocumentStatus::UPLOADING, false],
            'uploaded' => [DocumentStatus::UPLOADED, false],
            'processing' => [DocumentStatus::PROCESSING, false],
            'completed' => [DocumentStatus::COMPLETED, false],
            'failed' => [DocumentStatus::FAILED, true],
            'synced' => [DocumentStatus::SYNCED, false],
            'sync_failed' => [DocumentStatus::SYNC_FAILED, true],
        ];
    }

    #[DataProvider('provideRetryStatusData')]
    public function testNeedsRetry(DocumentStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->needsRetry());
    }

    public function testGetValues(): void
    {
        $values = DocumentStatus::getValues();
        $expectedValues = [
            'pending',
            'uploading',
            'uploaded',
            'processing',
            'completed',
            'failed',
            'synced',
            'sync_failed',
        ];

        $this->assertCount(8, $values);
        $this->assertSame($expectedValues, $values);

        // 枚举值已经是确定的字符串类型
    }

    public function testGetChoices(): void
    {
        $choices = DocumentStatus::getChoices();
        $expectedChoices = [
            '待处理' => 'pending',
            '上传中' => 'uploading',
            '已上传' => 'uploaded',
            '处理中' => 'processing',
            '已完成' => 'completed',
            '失败' => 'failed',
            '已同步' => 'synced',
            '同步失败' => 'sync_failed',
        ];

        $this->assertCount(8, $choices);
        $this->assertSame($expectedChoices, $choices);

        // 验证键是中文标签，值是英文状态值
        foreach ($choices as $label => $value) {
            // 验证标签和值匹配
            $status = DocumentStatus::from($value);
            $this->assertSame($label, $status->getLabel());
        }
    }

    public function testAllCasesHaveValues(): void
    {
        $cases = DocumentStatus::cases();
        $this->assertCount(8, $cases);

        foreach ($cases as $case) {
            $this->assertInstanceOf(DocumentStatus::class, $case);
            $this->assertNotEmpty($case->value);
        }
    }

    public function testEnumValues(): void
    {
        $this->assertSame('pending', DocumentStatus::PENDING->value);
        $this->assertSame('uploading', DocumentStatus::UPLOADING->value);
        $this->assertSame('uploaded', DocumentStatus::UPLOADED->value);
        $this->assertSame('processing', DocumentStatus::PROCESSING->value);
        $this->assertSame('completed', DocumentStatus::COMPLETED->value);
        $this->assertSame('failed', DocumentStatus::FAILED->value);
        $this->assertSame('synced', DocumentStatus::SYNCED->value);
        $this->assertSame('sync_failed', DocumentStatus::SYNC_FAILED->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(DocumentStatus::PENDING, DocumentStatus::from('pending'));
        $this->assertSame(DocumentStatus::UPLOADING, DocumentStatus::from('uploading'));
        $this->assertSame(DocumentStatus::UPLOADED, DocumentStatus::from('uploaded'));
        $this->assertSame(DocumentStatus::PROCESSING, DocumentStatus::from('processing'));
        $this->assertSame(DocumentStatus::COMPLETED, DocumentStatus::from('completed'));
        $this->assertSame(DocumentStatus::FAILED, DocumentStatus::from('failed'));
        $this->assertSame(DocumentStatus::SYNCED, DocumentStatus::from('synced'));
        $this->assertSame(DocumentStatus::SYNC_FAILED, DocumentStatus::from('sync_failed'));
    }

    public function testFromInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        DocumentStatus::from('invalid_status');
    }

    public function testTryFromValidValue(): void
    {
        $this->assertSame(DocumentStatus::PENDING, DocumentStatus::tryFrom('pending'));
        $this->assertSame(DocumentStatus::FAILED, DocumentStatus::tryFrom('failed'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(DocumentStatus::tryFrom('invalid_status'));
    }

    /**
     * @return array<string, array{int|string|null, DocumentStatus|null}>
     */
    public static function provideFromValueData(): array
    {
        return [
            // 数字兼容性测试
            'numeric 0' => [0, DocumentStatus::PENDING],
            'numeric 1' => [1, DocumentStatus::UPLOADED],
            'numeric 2' => [2, DocumentStatus::PROCESSING],
            'numeric 3' => [3, DocumentStatus::COMPLETED],
            'numeric 4' => [4, DocumentStatus::FAILED],
            // 字符串数字兼容性测试
            'string numeric 0' => ['0', DocumentStatus::PENDING],
            'string numeric 1' => ['1', DocumentStatus::UPLOADED],
            'string numeric 2' => ['2', DocumentStatus::PROCESSING],
            'string numeric 3' => ['3', DocumentStatus::COMPLETED],
            'string numeric 4' => ['4', DocumentStatus::FAILED],
            // 字符串状态值测试
            'string pending' => ['pending', DocumentStatus::PENDING],
            'string uploaded' => ['uploaded', DocumentStatus::UPLOADED],
            'string processing' => ['processing', DocumentStatus::PROCESSING],
            'string completed' => ['completed', DocumentStatus::COMPLETED],
            'string failed' => ['failed', DocumentStatus::FAILED],
            'string uploading' => ['uploading', DocumentStatus::UPLOADING],
            'string synced' => ['synced', DocumentStatus::SYNCED],
            'string sync_failed' => ['sync_failed', DocumentStatus::SYNC_FAILED],
            // 边界情况
            'null value' => [null, null],
            'invalid numeric' => [999, null],
            'invalid string' => ['invalid', null],
        ];
    }

    #[DataProvider('provideFromValueData')]
    public function testFromValueWithNumericCompatibility(int|string|null $input, ?DocumentStatus $expected): void
    {
        $result = DocumentStatus::fromValue($input);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{DocumentStatus, int}>
     */
    public static function provideToNumericData(): array
    {
        return [
            'pending' => [DocumentStatus::PENDING, 0],
            'uploading' => [DocumentStatus::UPLOADING, 0],  // 映射到待处理
            'uploaded' => [DocumentStatus::UPLOADED, 1],
            'processing' => [DocumentStatus::PROCESSING, 2],
            'completed' => [DocumentStatus::COMPLETED, 3],
            'failed' => [DocumentStatus::FAILED, 4],
            'synced' => [DocumentStatus::SYNCED, 3],         // 映射到已完成
            'sync_failed' => [DocumentStatus::SYNC_FAILED, 4], // 映射到失败
        ];
    }

    #[DataProvider('provideToNumericData')]
    public function testToNumeric(DocumentStatus $status, int $expected): void
    {
        $this->assertSame($expected, $status->toNumeric());
    }

    public function testFromValueToNumericRoundTrip(): void
    {
        // 测试数字状态码的往返转换
        $numericStatuses = [0, 1, 2, 3, 4];

        foreach ($numericStatuses as $numeric) {
            $status = DocumentStatus::fromValue($numeric);
            $this->assertNotNull($status, "Should be able to create status from numeric value {$numeric}");

            $backToNumeric = $status->toNumeric();
            $this->assertSame($numeric, $backToNumeric, "Round trip should preserve numeric value {$numeric}");
        }
    }

    public function testCompatibilityWithLegacyCode(): void
    {
        // 模拟从API获取的数据，状态值为1
        $apiData = ['status' => 1];

        $status = DocumentStatus::fromValue($apiData['status']);
        $this->assertSame(DocumentStatus::UPLOADED, $status);
        $this->assertSame('已上传', $status->getLabel());
        $this->assertSame('info', $status->getCssClass());

        // 确保可以转换回数字状态码
        $this->assertSame(1, $status->toNumeric());
    }
}
