<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\UploadDocumentRequest;

/**
 * @internal
 */
#[CoversClass(UploadDocumentRequest::class)]
class UploadDocumentRequestTest extends RequestTestCase
{
    public function testRequestPath(): void
    {
        $files = [0 => 'dummy.pdf'];
        $request = new UploadDocumentRequest('dataset-123', $files);
        $this->assertEquals('/api/v1/datasets/dataset-123/documents', $request->getRequestPath());
    }

    public function testRequestPathWithSpecialCharacters(): void
    {
        $files = [0 => 'dummy.pdf'];
        $request = new UploadDocumentRequest('dataset-test_123', $files);
        $this->assertEquals('/api/v1/datasets/dataset-test_123/documents', $request->getRequestPath());
    }

    public function testRequestMethod(): void
    {
        $files = [0 => 'dummy.pdf'];
        $request = new UploadDocumentRequest('dataset-123', $files);
        $this->assertEquals('POST', $request->getRequestMethod());
    }

    public function testRequestOptionsWithSingleFile(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_file');
        file_put_contents($testFile, 'test content');

        try {
            $files = [0 => $testFile];
            $request = new UploadDocumentRequest('dataset-123', $files);

            $options = $request->getRequestOptions();
            $this->assertIsArray($options);
            $this->assertArrayHasKey('multipart', $options);
            $this->assertIsArray($options['multipart']);
            $this->assertCount(1, $options['multipart']);

            $multipart = $options['multipart'][0];
            $this->assertIsArray($multipart);
            $this->assertEquals('file[0]', $multipart['name']);
            $this->assertEquals(basename($testFile), $multipart['filename']);
            $this->assertIsResource($multipart['contents']);
        } finally {
            unlink($testFile);
        }
    }

    public function testRequestOptionsWithMultipleFiles(): void
    {
        $testFile1 = tempnam(sys_get_temp_dir(), 'test_file1');
        $testFile2 = tempnam(sys_get_temp_dir(), 'test_file2');
        file_put_contents($testFile1, 'test content 1');
        file_put_contents($testFile2, 'test content 2');

        try {
            $files = [0 => $testFile1, 1 => $testFile2];
            $request = new UploadDocumentRequest('dataset-123', $files);

            $options = $request->getRequestOptions();
            $this->assertIsArray($options);
            $this->assertArrayHasKey('multipart', $options);
            $this->assertIsArray($options['multipart']);
            $this->assertCount(2, $options['multipart']);

            $this->assertIsArray($options['multipart'][0]);
            $this->assertIsArray($options['multipart'][1]);
            $this->assertEquals('file[0]', $options['multipart'][0]['name']);
            $this->assertEquals('file[1]', $options['multipart'][1]['name']);
            $this->assertEquals(basename($testFile1), $options['multipart'][0]['filename']);
            $this->assertEquals(basename($testFile2), $options['multipart'][1]['filename']);
            $this->assertIsResource($options['multipart'][0]['contents']);
            $this->assertIsResource($options['multipart'][1]['contents']);
        } finally {
            unlink($testFile1);
            unlink($testFile2);
        }
    }

    public function testRequestOptionsWithEmptyFiles(): void
    {
        $files = [];
        $request = new UploadDocumentRequest('dataset-123', $files);

        $options = $request->getRequestOptions();
        $this->assertIsArray($options, 'Request options should be an array');
        $this->assertArrayHasKey('multipart', $options);
        $this->assertCount(0, $options['multipart']);
    }

    public function testStringRepresentation(): void
    {
        $files = [0 => '/path/to/file1.pdf', 1 => '/path/to/file2.docx'];
        $request = new UploadDocumentRequest('dataset-123', $files);
        $stringRepresentation = (string) $request;
        $this->assertStringContainsString('UploadDocumentRequest', $stringRepresentation);
        $this->assertStringContainsString('dataset-123', $stringRepresentation);
        $this->assertStringContainsString('files', $stringRepresentation);
        $this->assertStringContainsString('dataset-123', $stringRepresentation);
    }

    public function testDifferentDatasetIds(): void
    {
        $testFile = tempnam(sys_get_temp_dir(), 'test_file');
        file_put_contents($testFile, 'test content');

        try {
            $files = [0 => $testFile];
            $testCases = [
                'simple-id' => '/api/v1/datasets/simple-id/documents',
                'complex_id-123' => '/api/v1/datasets/complex_id-123/documents',
                'id-with-numbers-456' => '/api/v1/datasets/id-with-numbers-456/documents',
            ];

            foreach ($testCases as $datasetId => $expectedPath) {
                $request = new UploadDocumentRequest($datasetId, $files);
                $this->assertEquals($expectedPath, $request->getRequestPath());
                $this->assertEquals('POST', $request->getRequestMethod());
            }
        } finally {
            unlink($testFile);
        }
    }

    public function testGenerateLogData(): void
    {
        $files = [0 => '/path/to/file1.pdf', 1 => '/path/to/file2.docx'];
        $request = new UploadDocumentRequest('dataset-123', $files);

        $logData = $request->generateLogData();

        $this->assertIsArray($logData);
        $this->assertEquals(UploadDocumentRequest::class, $logData['_className']);
        $this->assertEquals('/api/v1/datasets/dataset-123/documents', $logData['path']);
        $this->assertEquals('POST', $logData['method']);
        $this->assertEquals(['/path/to/file1.pdf', '/path/to/file2.docx'], $logData['files']);
        $this->assertEquals(2, $logData['fileCount']);
    }
}
