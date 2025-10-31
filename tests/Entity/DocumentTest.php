<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

/**
 * Document实体测试
 *
 * @internal
 */
#[CoversClass(Document::class)]
class DocumentTest extends AbstractEntityTestCase
{
    protected function createEntity(): Document
    {
        $document = new Document();
        $document->setName('test-document');

        return $document;
    }

    public function testGetSetId(): void
    {
        $document = $this->createEntity();
        // ID通常由Doctrine自动设置，这里只测试getter存在
        $this->assertNull($document->getId());
    }

    public function testGetSetName(): void
    {
        $name = '测试文档';
        $document = $this->createEntity();
        $document->setName($name);
        self::assertSame($name, $document->getName());
    }

    public function testGetSetRemoteId(): void
    {
        $remoteId = 'remote-doc-456';
        $document = $this->createEntity();
        $document->setRemoteId($remoteId);
        self::assertSame($remoteId, $document->getRemoteId());

        $document->setRemoteId(null);
        self::assertNull($document->getRemoteId());
    }

    public function testGetSetFilename(): void
    {
        $filename = 'test.pdf';
        $document = $this->createEntity();
        $document->setFilename($filename);
        self::assertSame($filename, $document->getFilename());

        $document->setFilename(null);
        self::assertNull($document->getFilename());
    }

    public function testGetSetFilePath(): void
    {
        $filePath = '/path/to/document.pdf';
        $document = $this->createEntity();
        $document->setFilePath($filePath);
        self::assertSame($filePath, $document->getFilePath());

        $document->setFilePath(null);
        self::assertNull($document->getFilePath());
    }

    public function testGetSetType(): void
    {
        $type = 'pdf';
        $document = $this->createEntity();
        $document->setType($type);
        self::assertSame($type, $document->getType());

        $document->setType(null);
        self::assertNull($document->getType());
    }

    public function testGetSetMimeType(): void
    {
        $mimeType = 'application/pdf';
        $document = $this->createEntity();
        $document->setMimeType($mimeType);
        self::assertSame($mimeType, $document->getMimeType());

        $document->setMimeType(null);
        self::assertNull($document->getMimeType());
    }

    public function testGetSetSize(): void
    {
        $size = 1024;
        $document = $this->createEntity();
        $document->setSize($size);
        self::assertSame($size, $document->getSize());

        $document->setSize(null);
        self::assertNull($document->getSize());
    }

    public function testGetSetParseStatus(): void
    {
        $parseStatus = 'completed';
        $document = $this->createEntity();
        $document->setParseStatus($parseStatus);
        self::assertSame($parseStatus, $document->getParseStatus());

        $document->setParseStatus(null);
        self::assertNull($document->getParseStatus());
    }

    public function testGetSetProgress(): void
    {
        $progress = 75.5;
        $document = $this->createEntity();
        $document->setProgress($progress);
        self::assertSame($progress, $document->getProgress());

        $document->setProgress(null);
        self::assertNull($document->getProgress());
    }

    public function testGetSetProgressMsg(): void
    {
        $progressMsg = 'Processing document...';
        $document = $this->createEntity();
        $document->setProgressMsg($progressMsg);
        self::assertSame($progressMsg, $document->getProgressMsg());

        $document->setProgressMsg(null);
        self::assertNull($document->getProgressMsg());
    }

    public function testGetSetLanguage(): void
    {
        $language = 'zh-CN';
        $document = $this->createEntity();
        $document->setLanguage($language);
        self::assertSame($language, $document->getLanguage());

        $document->setLanguage(null);
        self::assertNull($document->getLanguage());
    }

    public function testGetSetSummary(): void
    {
        $summary = '这是一个文档摘要';
        $document = $this->createEntity();
        $document->setSummary($summary);
        self::assertSame($summary, $document->getSummary());

        $document->setSummary(null);
        self::assertNull($document->getSummary());
    }

    public function testGetSetChunkCount(): void
    {
        $chunkCount = 10;
        $document = $this->createEntity();
        $document->setChunkCount($chunkCount);
        self::assertSame($chunkCount, $document->getChunkCount());
    }

    public function testGetSetStatus(): void
    {
        $status = DocumentStatus::COMPLETED;
        $document = $this->createEntity();
        $document->setStatus($status);
        self::assertSame($status, $document->getStatus());
    }

    public function testDefaultStatus(): void
    {
        $document = $this->createEntity();
        self::assertSame(DocumentStatus::PENDING, $document->getStatus());
    }

    public function testGetSetDataset(): void
    {
        $document = $this->createEntity();
        $dataset = new Dataset();
        $dataset->setName('测试数据集');
        $document->setDataset($dataset);

        self::assertSame($dataset, $document->getDataset());
    }

    public function testGetSetRemoteCreateTime(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00');
        $document = $this->createEntity();
        $document->setRemoteCreateTime($time);
        self::assertSame($time, $document->getRemoteCreateTime());

        $document->setRemoteCreateTime(null);
        self::assertNull($document->getRemoteCreateTime());
    }

    public function testGetSetRemoteUpdateTime(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00');
        $document = $this->createEntity();
        $document->setRemoteUpdateTime($time);
        self::assertSame($time, $document->getRemoteUpdateTime());

        $document->setRemoteUpdateTime(null);
        self::assertNull($document->getRemoteUpdateTime());
    }

    public function testGetSetLastSyncTime(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 12:00:00');
        $document = $this->createEntity();
        $document->setLastSyncTime($time);
        self::assertSame($time, $document->getLastSyncTime());

        $document->setLastSyncTime(null);
        self::assertNull($document->getLastSyncTime());
    }

    public function testToString(): void
    {
        $name = '测试文档';
        $document = $this->createEntity();
        $document->setName($name);
        self::assertSame($name, (string) $document);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'Test Document'];
        yield 'remoteId' => ['remoteId', 'remote-doc-123'];
        yield 'filename' => ['filename', 'test.pdf'];
        yield 'type' => ['type', 'pdf'];
        yield 'mimeType' => ['mimeType', 'application/pdf'];
        yield 'size' => ['size', 1024];
        yield 'chunkCount' => ['chunkCount', 5];
        yield 'status' => ['status', DocumentStatus::COMPLETED];
        yield 'parseStatus' => ['parseStatus', 'completed'];
        yield 'progress' => ['progress', 75.5];
        yield 'progressMsg' => ['progressMsg', 'Processing...'];
        yield 'language' => ['language', 'zh-CN'];
        yield 'summary' => ['summary', 'Test summary'];
    }
}
