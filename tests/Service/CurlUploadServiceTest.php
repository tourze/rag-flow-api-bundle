<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\CurlUploadService;

/**
 * @internal
 */
#[CoversClass(CurlUploadService::class)]
#[RunTestsInSeparateProcesses]
class CurlUploadServiceTest extends AbstractIntegrationTestCase
{
    private CurlUploadService $curlUploadService;

    protected function onSetUp(): void
    {
        $this->curlUploadService = self::getService(CurlUploadService::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(CurlUploadService::class, $this->curlUploadService);
    }

    public function testUploadDocumentWithInvalidFile(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Curl Test Instance');
        $instance->setApiUrl('https://curl-test.example.com/api');
        $instance->setApiKey('curl-test-key');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('文件不存在');

        $this->curlUploadService->uploadDocument(
            $instance,
            'dataset-123',
            '/non/existent/file.txt',
            'file.txt'
        );
    }

    public function testUploadDocumentWithValidFileButNoApi(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Curl Test 2');
        $instance->setApiUrl('https://invalid-api.example.com/api');
        $instance->setApiKey('test-key');

        // 创建临时测试文件
        $tmpFile = tempnam(sys_get_temp_dir(), 'curl_test_');
        if (false === $tmpFile) {
            self::fail('Failed to create temporary file');
        }
        file_put_contents($tmpFile, 'test content for curl upload');

        try {
            $this->curlUploadService->uploadDocument(
                $instance,
                'dataset-456',
                $tmpFile,
                'test_upload.txt'
            );
            self::fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Curl请求失败', $e->getMessage());
        } finally {
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }
}
