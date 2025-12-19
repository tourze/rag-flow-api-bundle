<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Service\DocumentService;
use Tourze\RAGFlowApiBundle\Service\DocumentUploadService;

/**
 * @internal
 */
#[CoversClass(DocumentUploadService::class)]
#[RunTestsInSeparateProcesses]
final class DocumentUploadServiceTest extends AbstractIntegrationTestCase
{
    private DocumentUploadService $uploadService;

    protected function onSetUp(): void
    {
        $this->uploadService = self::getService(DocumentUploadService::class);
    }

    public function test服务创建成功(): void
    {
        $this->assertInstanceOf(DocumentUploadService::class, $this->uploadService);
    }

    public function testExtractUploadedFiles(): void
    {
        // 创建临时测试文件
        $tempFile1 = tempnam(sys_get_temp_dir(), 'test1');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test2');
        file_put_contents($tempFile1, 'test content 1');
        file_put_contents($tempFile2, 'test content 2');

        try {
            $uploadedFile1 = new UploadedFile($tempFile1, 'test1.txt', 'text/plain', null, true);
            $uploadedFile2 = new UploadedFile($tempFile2, 'test2.txt', 'text/plain', null, true);

            $request = new Request();
            $request->files->set('files', [$uploadedFile1, $uploadedFile2]);

            $files = $this->uploadService->extractUploadedFiles($request);

            $this->assertCount(2, $files);
            $this->assertInstanceOf(UploadedFile::class, $files[0]);
            $this->assertInstanceOf(UploadedFile::class, $files[1]);
        } finally {
            if (file_exists($tempFile1)) {
                unlink($tempFile1);
            }
            if (file_exists($tempFile2)) {
                unlink($tempFile2);
            }
        }
    }

    public function testProcessFileUploads(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Process Upload Instance');
        $instance->setApiUrl('https://process-upload.example.com/api');
        $instance->setApiKey('process-upload-key');

        // Dataset没有remoteId，这样会在同步时失败
        $dataset = new Dataset();
        $dataset->setName('Process Upload Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        // 创建两个无效的上传文件（模拟上传错误）
        $invalidUpload1 = new UploadedFile('/nonexistent/file1.txt', 'invalid1.txt', 'text/plain', UPLOAD_ERR_NO_FILE, true);
        $invalidUpload2 = new UploadedFile('/nonexistent/file2.txt', 'invalid2.txt', 'text/plain', UPLOAD_ERR_NO_FILE, true);

        $uploadedFiles = [$invalidUpload1, $invalidUpload2];

        [$documents, $errors] = $this->uploadService->processFileUploads($dataset, $uploadedFiles);

        // 应该有错误记录
        $this->assertEmpty($documents);
        $this->assertNotEmpty($errors);
        $this->assertCount(2, $errors);
        $this->assertStringContainsString('Invalid file upload', $errors[0]);
        $this->assertStringContainsString('Invalid file upload', $errors[1]);
    }

    public function test提取多个文件成功(): void
    {
        // 创建临时测试文件
        $tempFile1 = tempnam(sys_get_temp_dir(), 'test1');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test2');
        file_put_contents($tempFile1, 'test content 1');
        file_put_contents($tempFile2, 'test content 2');

        try {
            $uploadedFile1 = new UploadedFile($tempFile1, 'test1.txt', 'text/plain', null, true);
            $uploadedFile2 = new UploadedFile($tempFile2, 'test2.txt', 'text/plain', null, true);

            $request = new Request();
            $request->files->set('files', [$uploadedFile1, $uploadedFile2]);

            $files = $this->uploadService->extractUploadedFiles($request);

            $this->assertCount(2, $files);
            $this->assertInstanceOf(UploadedFile::class, $files[0]);
            $this->assertInstanceOf(UploadedFile::class, $files[1]);
        } finally {
            if (file_exists($tempFile1)) {
                unlink($tempFile1);
            }
            if (file_exists($tempFile2)) {
                unlink($tempFile2);
            }
        }
    }

    public function test提取单个文件成功(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        try {
            $uploadedFile = new UploadedFile($tempFile, 'single.txt', 'text/plain', null, true);

            $request = new Request();
            $request->files->set('file', $uploadedFile);

            $files = $this->uploadService->extractUploadedFiles($request);

            $this->assertCount(1, $files);
            $this->assertInstanceOf(UploadedFile::class, $files[0]);
            $this->assertSame('single.txt', $files[0]->getClientOriginalName());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function test没有文件上传抛出异常(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No files uploaded');

        $request = new Request();
        $this->uploadService->extractUploadedFiles($request);
    }

    public function test批量上传包含无效文件仍能汇总错误(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Upload Test Instance');
        $instance->setApiUrl('https://test.example.com/api');
        $instance->setApiKey('test-key');

        // Dataset没有remoteId，这样会在同步时失败
        $dataset = new Dataset();
        $dataset->setName('Upload Test Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        // 创建两个无效的上传文件（模拟上传错误）
        $invalidUpload1 = new UploadedFile('/nonexistent/file1.txt', 'invalid1.txt', 'text/plain', UPLOAD_ERR_NO_FILE, true);
        $invalidUpload2 = new UploadedFile('/nonexistent/file2.txt', 'invalid2.txt', 'text/plain', UPLOAD_ERR_NO_FILE, true);

        $uploadedFiles = [$invalidUpload1, $invalidUpload2];

        [$documents, $errors] = $this->uploadService->processFileUploads($dataset, $uploadedFiles);

        // 应该有错误记录
        $this->assertEmpty($documents);
        $this->assertNotEmpty($errors);
        $this->assertCount(2, $errors);
        $this->assertStringContainsString('Invalid file upload', $errors[0]);
        $this->assertStringContainsString('Invalid file upload', $errors[1]);
    }

    public function test验证文件扩展名支持(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Extension Instance');
        $instance->setApiUrl('https://extension.example.com/api');
        $instance->setApiKey('extension-key');

        $dataset = new Dataset();
        $dataset->setName('Extension Dataset');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $tempFile1 = tempnam(sys_get_temp_dir(), 'pdf');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($tempFile1, 'pdf content');
        file_put_contents($tempFile2, 'txt content');

        try {
            // 测试支持的文件类型
            $pdfUpload = new UploadedFile($tempFile1, 'document.pdf', 'application/pdf', null, true);
            $txtUpload = new UploadedFile($tempFile2, 'document.txt', 'text/plain', null, true);

            // 提取文件
            $request = new Request();
            $request->files->set('files', [$pdfUpload, $txtUpload]);

            $files = $this->uploadService->extractUploadedFiles($request);

            $this->assertCount(2, $files);
        } finally {
            if (file_exists($tempFile1)) {
                unlink($tempFile1);
            }
            if (file_exists($tempFile2)) {
                unlink($tempFile2);
            }
        }
    }

    public function test不支持的文件类型抛出异常(): void
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Type Test Instance');
        $instance->setApiUrl('https://type.example.com/api');
        $instance->setApiKey('type-key');

        $dataset = new Dataset();
        $dataset->setName('Type Test Dataset');
        $dataset->setRemoteId('dataset-type-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $tempFile = tempnam(sys_get_temp_dir(), 'unsupported');
        file_put_contents($tempFile, 'binary content');

        try {
            // 使用不支持的文件扩展名
            $uploadedFile = new UploadedFile($tempFile, 'unsupported.exe', 'application/x-msdownload', null, true);

            [$documents, $errors] = $this->uploadService->processFileUploads($dataset, [$uploadedFile]);

            $this->assertEmpty($documents);
            $this->assertNotEmpty($errors);
            $this->assertStringContainsString('Unsupported file type', $errors[0]);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
