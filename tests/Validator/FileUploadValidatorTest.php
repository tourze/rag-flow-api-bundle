<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\RAGFlowApiBundle\Validator\FileUploadValidator;

/**
 * @internal
 */
#[CoversClass(FileUploadValidator::class)]
final class FileUploadValidatorTest extends TestCase
{
    private FileUploadValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FileUploadValidator();
    }

    public function test验证有效PDF文件(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('pdf');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('application/pdf');
        $this->validator->validateUploadedFile($uploadedFile);
        // 如果没有抛出异常，则测试通过
        // 如果没有抛出异常，则测试通过
    }

    public function test验证有效DOCX文件(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('docx');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->validator->validateUploadedFile($uploadedFile);
        // 如果没有抛出异常，则测试通过
        // 如果没有抛出异常，则测试通过
    }

    public function test验证有效TXT文件(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('txt');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('text/plain');
        $this->validator->validateUploadedFile($uploadedFile);
        // 如果没有抛出异常，则测试通过
        // 如果没有抛出异常，则测试通过
    }

    public function test拒绝无效文件(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(false);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file upload');
        $this->validator->validateUploadedFile($uploadedFile);
    }

    public function test拒绝不支持的文件扩展名(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('exe');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported file type: exe');
        $this->validator->validateUploadedFile($uploadedFile);
    }

    public function test拒绝不支持的MIME类型(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('pdf');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('application/x-executable');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported MIME type');
        $this->validator->validateUploadedFile($uploadedFile);
    }

    public function test接受大小写混合扩展名(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('PDF');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('application/pdf');
        $this->validator->validateUploadedFile($uploadedFile);
        // 如果没有抛出异常，则测试通过
        // 如果没有抛出异常，则测试通过
    }

    public function test允许空MIME类型(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('pdf');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn(null);
        $this->validator->validateUploadedFile($uploadedFile);
        // 如果没有抛出异常，则测试通过
        // 如果没有抛出异常，则测试通过
    }

    public function test验证支持的图片格式(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('jpg');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');
        $this->validator->validateUploadedFile($uploadedFile);
        // 如果没有抛出异常，则测试通过
        // 如果没有抛出异常，则测试通过
    }

    public function test验证支持的表格格式(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('xlsx');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->validator->validateUploadedFile($uploadedFile);
        // 如果没有抛出异常，则测试通过
        // 如果没有抛出异常，则测试通过
    }
}
