<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Builder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Builder\DocumentBuilder;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

/**
 * @internal
 */
#[CoversClass(DocumentBuilder::class)]
#[RunTestsInSeparateProcesses]
class DocumentBuilderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // No setup needed
    }

    public function testGetDocument(): void
    {
        // 从容器中获取 DocumentBuilder 服务
        $builder = self::getService(DocumentBuilder::class);
        $document = $builder->getDocument();

        $this->assertInstanceOf(Document::class, $document);
    }

    public function testFromUploadBasic(): void
    {
        $uniqueSuffix = uniqid('', true);

        $instance = new RAGFlowInstance();
        $instance->setName('Builder Test Instance ' . $uniqueSuffix);
        $instance->setApiUrl('https://builder-test.example.com/api');
        $instance->setApiKey('builder-test-key-' . $uniqueSuffix);

        $dataset = new Dataset();
        $dataset->setName('Test Dataset ' . $uniqueSuffix);
        $dataset->setDescription('Test Description');
        $dataset->setRemoteId('dataset-123');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        // 创建临时测试文件
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        if (false === $tmpFile) {
            self::fail('Failed to create temporary file');
        }
        file_put_contents($tmpFile, 'test content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $file = new File();
        $file->setFileName('test.txt');
        $file->setSize(12);
        $file->setMimeType('text/plain');
        $this->persistAndFlush($file);

        $request = new Request();
        $request->request->set('display_name', 'Test Document');
        $request->request->set('description', 'Test Description');

        $builder = DocumentBuilder::fromUpload($dataset, $uploadedFile, $file, $request);
        $document = $builder->getDocument();

        $this->assertEquals('Test Document', $document->getName());
        $this->assertEquals('test.txt', $document->getFilename());
        $this->assertEquals('txt', $document->getType());
        $this->assertEquals(DocumentStatus::PENDING, $document->getStatus());
        $this->assertSame($dataset, $document->getDataset());

        // 清理
        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }

    public function testFromUploadWithoutDisplayName(): void
    {
        $uniqueSuffix = uniqid('', true);

        $instance = new RAGFlowInstance();
        $instance->setName('Builder Test 2 ' . $uniqueSuffix);
        $instance->setApiUrl('https://builder-test2.example.com/api');
        $instance->setApiKey('builder-test-key-2-' . $uniqueSuffix);

        $dataset = new Dataset();
        $dataset->setName('Test Dataset 2 ' . $uniqueSuffix);
        $dataset->setRemoteId('dataset-456');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        if (false === $tmpFile) {
            self::fail('Failed to create temporary file');
        }
        file_put_contents($tmpFile, 'test content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            'original_name.pdf',
            'application/pdf',
            null,
            true
        );

        $file = new File();
        $file->setFileName('original_name.pdf');
        $file->setSize(100);
        $file->setMimeType('application/pdf');
        $this->persistAndFlush($file);

        $request = new Request();

        $builder = DocumentBuilder::fromUpload($dataset, $uploadedFile, $file, $request);
        $document = $builder->getDocument();

        // 没有提供 display_name，应使用原始文件名
        $this->assertEquals('original_name.pdf', $document->getName());

        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }

    public function testFromUploadWithEmptyDisplayName(): void
    {
        $uniqueSuffix = uniqid('', true);

        $instance = new RAGFlowInstance();
        $instance->setName('Builder Test 3 ' . $uniqueSuffix);
        $instance->setApiUrl('https://builder-test3.example.com/api');
        $instance->setApiKey('builder-test-key-3-' . $uniqueSuffix);

        $dataset = new Dataset();
        $dataset->setName('Test Dataset 3 ' . $uniqueSuffix);
        $dataset->setRemoteId('dataset-789');
        $dataset->setRagFlowInstance($instance);

        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        if (false === $tmpFile) {
            self::fail('Failed to create temporary file');
        }
        file_put_contents($tmpFile, 'test');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            'fallback.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $file = new File();
        $file->setFileName('fallback.docx');
        $file->setSize(50);
        $file->setMimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->persistAndFlush($file);

        $request = new Request();
        $request->request->set('display_name', ''); // 空字符串

        $builder = DocumentBuilder::fromUpload($dataset, $uploadedFile, $file, $request);
        $document = $builder->getDocument();

        // 空字符串应回退到原始文件名
        $this->assertEquals('fallback.docx', $document->getName());

        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }
}
