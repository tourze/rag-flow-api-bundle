<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * @internal
 */
#[CoversClass(DocumentRepository::class)]
#[RunTestsInSeparateProcesses]
class DocumentRepositoryTest extends AbstractRepositoryTestCase
{
    private DocumentRepository $repository;

    protected function createNewEntity(): object
    {
        // 创建RAGFlow实例
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例');
        $ragFlowInstance->setApiUrl('http://localhost:9380');
        $ragFlowInstance->setApiKey('test-key');
        $this->getEntityManagerInstance()->persist($ragFlowInstance);

        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('测试数据集');
        $dataset->setDescription('用于测试的数据集');
        $dataset->setRagFlowInstance($ragFlowInstance);
        $this->getEntityManagerInstance()->persist($dataset);

        // 创建文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($dataset);
        $document->setStatus(DocumentStatus::UPLOADED);
        $document->setType('pdf');
        $document->setSize(1024);

        return $document;
    }

    protected function getRepository(): DocumentRepository
    {
        if (!isset($this->repository)) {
            $this->repository = self::getService(DocumentRepository::class);
        }

        return $this->repository;
    }

    protected function onSetUp(): void
    {
        // 初始化repository
        $this->repository = self::getService(DocumentRepository::class);
    }

    /**
     * 创建测试用的RAGFlow实例和数据集
     */
    private function createTestDataset(string $name, ?string $description = null): Dataset
    {
        // 创建RAGFlow实例
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例');
        $ragFlowInstance->setApiUrl('http://localhost:9380');
        $ragFlowInstance->setApiKey('test-key');
        $this->persistAndFlush($ragFlowInstance);

        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName($name);
        $dataset->setDescription($description ?? "用于测试的数据集: {$name}");
        $dataset->setRagFlowInstance($ragFlowInstance);
        $persistedDataset = $this->persistAndFlush($dataset);

        $this->assertInstanceOf(Dataset::class, $persistedDataset);

        return $persistedDataset;
    }

    public function testRepositoryCreation(): void
    {
        $this->assertInstanceOf(DocumentRepository::class, $this->repository);
    }

    public function testFindByRemoteId(): void
    {
        // 创建测试数据集
        $dataset = new Dataset();
        $dataset->setName('文档仓库测试数据集');
        $dataset->setDescription('用于测试文档仓库的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建测试文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setRemoteId('remote-document-456');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::UPLOADED);
        /** @var Document $persistedDocument */
        $persistedDocument = $this->persistAndFlush($document);

        // 测试通过远程ID查找
        $foundDocument = $this->repository->findByRemoteId('remote-document-456');
        $this->assertNotNull($foundDocument);
        $this->assertEquals($persistedDocument->getId(), $foundDocument->getId());
        $this->assertEquals('remote-document-456', $foundDocument->getRemoteId());

        // 测试查找不存在的远程ID
        $notFound = $this->repository->findByRemoteId('non-existent-remote-id');
        $this->assertNull($notFound);
    }

    public function testFindByDataset(): void
    {
        // 创建两个数据集
        $dataset1 = new Dataset();
        $dataset1->setName('文档数据集1');
        $dataset1->setDescription('第一个文档数据集');
        $persistedDataset1Result = $this->persistAndFlush($dataset1);
        $this->assertInstanceOf(Dataset::class, $persistedDataset1Result);
        /** @var Dataset $persistedDataset1 */
        $persistedDataset1 = $persistedDataset1Result;

        $dataset2 = new Dataset();
        $dataset2->setName('文档数据集2');
        $dataset2->setDescription('第二个文档数据集');
        $persistedDataset2Result = $this->persistAndFlush($dataset2);
        $this->assertInstanceOf(Dataset::class, $persistedDataset2Result);
        /** @var Dataset $persistedDataset2 */
        $persistedDataset2 = $persistedDataset2Result;

        // 为第一个数据集创建文档
        $doc1 = new Document();
        $doc1->setName('数据集1文档1.pdf');
        $doc1->setDataset($persistedDataset1);
        $this->persistAndFlush($doc1);

        $doc2 = new Document();
        $doc2->setName('数据集1文档2.docx');
        $doc2->setDataset($persistedDataset1);
        $this->persistAndFlush($doc2);

        // 为第二个数据集创建文档
        $doc3 = new Document();
        $doc3->setName('数据集2文档1.txt');
        $doc3->setDataset($persistedDataset2);
        $this->persistAndFlush($doc3);

        // 测试查找第一个数据集的文档
        $dataset1Documents = $this->repository->findByDataset($persistedDataset1);
        $this->assertCount(2, $dataset1Documents);

        // 测试查找第二个数据集的文档
        $dataset2Documents = $this->repository->findByDataset($persistedDataset2);
        $this->assertCount(1, $dataset2Documents);
    }

    public function testFindByStatus(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('状态测试数据集');
        $dataset->setDescription('用于测试文档状态的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同状态的文档
        $statuses = [
            DocumentStatus::PENDING,
            DocumentStatus::UPLOADING,
            DocumentStatus::UPLOADED,
            DocumentStatus::PROCESSING,
            DocumentStatus::COMPLETED,
            DocumentStatus::FAILED,
            DocumentStatus::SYNC_FAILED,
        ];

        foreach ($statuses as $i => $status) {
            $document = new Document();
            $document->setName("状态测试文档{$i}.pdf");
            $document->setDataset($persistedDataset);
            $document->setStatus($status);
            $this->persistAndFlush($document);
        }

        // 测试查找特定状态的文档
        $uploadedDocuments = $this->repository->findByStatus(DocumentStatus::UPLOADED);
        $this->assertGreaterThanOrEqual(1, count($uploadedDocuments));

        foreach ($uploadedDocuments as $document) {
            $this->assertEquals(DocumentStatus::UPLOADED, $document->getStatus());
        }

        $completedDocuments = $this->repository->findByStatus(DocumentStatus::COMPLETED);
        $this->assertGreaterThanOrEqual(1, count($completedDocuments));

        foreach ($completedDocuments as $document) {
            $this->assertEquals(DocumentStatus::COMPLETED, $document->getStatus());
        }
    }

    public function testFindFailedDocuments(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('失败文档测试数据集');
        $dataset->setDescription('用于测试失败文档查询的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建失败状态的文档
        $failedDoc = new Document();
        $failedDoc->setName('失败文档1.pdf');
        $failedDoc->setDataset($persistedDataset);
        $failedDoc->setStatus(DocumentStatus::FAILED);
        $this->persistAndFlush($failedDoc);

        $syncFailedDoc = new Document();
        $syncFailedDoc->setName('同步失败文档.docx');
        $syncFailedDoc->setDataset($persistedDataset);
        $syncFailedDoc->setStatus(DocumentStatus::SYNC_FAILED);
        $this->persistAndFlush($syncFailedDoc);

        // 创建成功状态的文档作为对比
        $successDoc = new Document();
        $successDoc->setName('成功文档.txt');
        $successDoc->setDataset($persistedDataset);
        $successDoc->setStatus(DocumentStatus::COMPLETED);
        $this->persistAndFlush($successDoc);

        // 测试查找失败的文档
        $failedDocuments = $this->repository->findFailedDocuments();
        $this->assertGreaterThanOrEqual(2, count($failedDocuments));

        foreach ($failedDocuments as $document) {
            $this->assertTrue(
                in_array($document->getStatus(), [DocumentStatus::FAILED, DocumentStatus::SYNC_FAILED], true)
            );
        }
    }

    public function testFindByFileType(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('文件类型测试数据集');
        $dataset->setDescription('用于测试文件类型筛选的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同类型的文档
        $fileTypes = [
            ['name' => 'PDF文档.pdf', 'type' => 'pdf'],
            ['name' => 'Word文档.docx', 'type' => 'docx'],
            ['name' => '文本文档.txt', 'type' => 'txt'],
            ['name' => '另一个PDF.pdf', 'type' => 'pdf'],
        ];

        foreach ($fileTypes as $fileData) {
            $document = new Document();
            $document->setName($fileData['name']);
            $document->setType($fileData['type']);
            $document->setDataset($persistedDataset);
            $this->persistAndFlush($document);
        }

        // 测试查找PDF文档
        $pdfDocuments = $this->repository->findByFileType('pdf');
        $this->assertGreaterThanOrEqual(2, count($pdfDocuments));

        foreach ($pdfDocuments as $document) {
            $this->assertEquals('pdf', $document->getType());
        }

        // 测试查找DOCX文档
        $docxDocuments = $this->repository->findByFileType('docx');
        $this->assertGreaterThanOrEqual(1, count($docxDocuments));

        foreach ($docxDocuments as $document) {
            $this->assertEquals('docx', $document->getType());
        }
    }

    public function testFindWithProgress(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('进度测试数据集');
        $dataset->setDescription('用于测试文档进度查询的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同进度的文档
        $progressData = [
            ['name' => '进度0文档.pdf', 'progress' => 0.0],
            ['name' => '进度50文档.docx', 'progress' => 50.0],
            ['name' => '进度75文档.txt', 'progress' => 75.0],
            ['name' => '进度100文档.md', 'progress' => 100.0],
        ];

        foreach ($progressData as $data) {
            $document = new Document();
            $document->setName($data['name']);
            $document->setProgress($data['progress']);
            $document->setDataset($persistedDataset);
            $this->persistAndFlush($document);
        }

        // 测试查找进度大于50%的文档
        $highProgressDocs = $this->repository->findWithProgress(50.0);
        $this->assertGreaterThanOrEqual(2, count($highProgressDocs)); // 75% 和 100%

        foreach ($highProgressDocs as $document) {
            $this->assertGreaterThanOrEqual(50.0, $document->getProgress());
        }

        // 测试查找已完成的文档
        $completedDocs = $this->repository->findWithProgress(100.0);
        $this->assertGreaterThanOrEqual(1, count($completedDocs));

        foreach ($completedDocs as $document) {
            $this->assertGreaterThanOrEqual(100.0, $document->getProgress());
        }
    }

    public function testFindByNamePattern(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('名称模式测试数据集');
        $dataset->setDescription('用于测试名称模式匹配的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同名称的文档
        $documents = [
            '用户手册.pdf',
            '技术文档_v1.docx',
            '产品介绍.pptx',
            '用户指南.txt',
            '技术规范书.pdf',
        ];

        foreach ($documents as $docName) {
            $document = new Document();
            $document->setName($docName);
            $document->setDataset($persistedDataset);
            $this->persistAndFlush($document);
        }

        // 搜索包含"用户"的文档
        $userDocs = $this->repository->findByNamePattern('用户');
        $this->assertGreaterThanOrEqual(2, count($userDocs));

        foreach ($userDocs as $document) {
            $this->assertStringContainsString('用户', $document->getName());
        }

        // 搜索包含"技术"的文档
        $techDocs = $this->repository->findByNamePattern('技术');
        $this->assertGreaterThanOrEqual(2, count($techDocs));

        foreach ($techDocs as $document) {
            $this->assertStringContainsString('技术', $document->getName());
        }
    }

    public function testFindRecentlyUpdated(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('最近更新测试数据集');
        $dataset->setDescription('用于测试最近更新查询的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建文档
        $recentDocument = new Document();
        $recentDocument->setName('最近更新的文档.pdf');
        $recentDocument->setDataset($persistedDataset);
        $this->persistAndFlush($recentDocument);

        // 测试查找最近更新的文档
        $recentDocuments = $this->repository->findRecentlyUpdated(10);
        $this->assertGreaterThanOrEqual(1, count($recentDocuments));

        // 验证结果按更新时间降序排列
        $previousUpdateTime = null;
        foreach ($recentDocuments as $document) {
            if (null !== $previousUpdateTime) {
                $this->assertGreaterThanOrEqual(
                    $document->getUpdateTime(),
                    $previousUpdateTime,
                    '文档应该按更新时间降序排列'
                );
            }
            $previousUpdateTime = $document->getUpdateTime();
        }
    }

    public function testFindLargeDocuments(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('大文件测试数据集');
        $dataset->setDescription('用于测试大文件查询的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同大小的文档
        $sizes = [
            ['name' => '小文档.txt', 'size' => 1024], // 1KB
            ['name' => '中等文档.pdf', 'size' => 1024 * 1024], // 1MB
            ['name' => '大文档.docx', 'size' => 10 * 1024 * 1024], // 10MB
            ['name' => '超大文档.pdf', 'size' => 50 * 1024 * 1024], // 50MB
        ];

        foreach ($sizes as $sizeData) {
            $document = new Document();
            $document->setName($sizeData['name']);
            $document->setSize($sizeData['size']);
            $document->setDataset($persistedDataset);
            $this->persistAndFlush($document);
        }

        // 测试查找大于5MB的文档
        $minSize = 5 * 1024 * 1024; // 5MB
        $largeDocuments = $this->repository->findLargeDocuments($minSize);
        $this->assertGreaterThanOrEqual(2, count($largeDocuments)); // 10MB 和 50MB

        foreach ($largeDocuments as $document) {
            $this->assertGreaterThan($minSize, $document->getSize());
        }
    }

    public function testCountByStatus(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('状态计数测试数据集');
        $dataset->setDescription('用于测试状态计数的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        $initialUploadedCount = $this->repository->countByStatus(DocumentStatus::UPLOADED);

        // 添加已上传状态的文档
        for ($i = 1; $i <= 3; ++$i) {
            $document = new Document();
            $document->setName("上传文档{$i}.pdf");
            $document->setDataset($persistedDataset);
            $document->setStatus(DocumentStatus::UPLOADED);
            $this->persistAndFlush($document);
        }

        // 添加其他状态的文档
        $otherDocument = new Document();
        $otherDocument->setName('处理中文档.docx');
        $otherDocument->setDataset($persistedDataset);
        $otherDocument->setStatus(DocumentStatus::PROCESSING);
        $this->persistAndFlush($otherDocument);

        $finalUploadedCount = $this->repository->countByStatus(DocumentStatus::UPLOADED);
        $this->assertEquals($initialUploadedCount + 3, $finalUploadedCount);
    }

    public function testCountByDataset(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('数据集计数测试');
        $dataset->setDescription('用于测试数据集文档计数的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        $initialCount = $this->repository->countByDataset($persistedDataset);

        // 添加文档
        for ($i = 1; $i <= 5; ++$i) {
            $document = new Document();
            $document->setName("计数测试文档{$i}.pdf");
            $document->setDataset($persistedDataset);
            $this->persistAndFlush($document);
        }

        $finalCount = $this->repository->countByDataset($persistedDataset);
        $this->assertEquals($initialCount + 5, $finalCount);
    }

    public function testFindOrCreateByRemoteId(): void
    {
        $remoteId = 'find-or-create-doc-test-789';

        // 第一次调用应该创建新实体
        $firstResult = $this->repository->findOrCreateByRemoteId($remoteId);
        $this->assertInstanceOf(Document::class, $firstResult);
        $this->assertEquals($remoteId, $firstResult->getRemoteId());
        $this->assertNotNull($firstResult->getId()); // 应该已经持久化

        // 第二次调用应该返回相同的实体
        $secondResult = $this->repository->findOrCreateByRemoteId($remoteId);
        $this->assertEquals($firstResult->getId(), $secondResult->getId());
        $this->assertEquals($remoteId, $secondResult->getRemoteId());
    }

    public function testGetStatusStatistics(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('统计测试数据集');
        $dataset->setDescription('用于测试状态统计的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同状态的文档
        $statuses = [
            DocumentStatus::PENDING->value => 2,
            DocumentStatus::UPLOADED->value => 3,
            DocumentStatus::PROCESSING->value => 1,
            DocumentStatus::COMPLETED->value => 4,
            DocumentStatus::FAILED->value => 1,
        ];

        foreach ($statuses as $statusValue => $count) {
            $status = DocumentStatus::from($statusValue);
            for ($i = 1; $i <= $count; ++$i) {
                $document = new Document();
                $document->setName("统计测试_{$statusValue}_{$i}.pdf");
                $document->setDataset($persistedDataset);
                $document->setStatus($status);
                $this->persistAndFlush($document);
            }
        }

        // 获取状态统计
        $statistics = $this->repository->getStatusStatistics();

        $this->assertIsArray($statistics);

        // 验证统计数据包含我们创建的文档
        foreach ($statuses as $statusValue => $expectedCount) {
            if (isset($statistics[$statusValue])) {
                $this->assertGreaterThanOrEqual($expectedCount, $statistics[$statusValue]);
            }
        }
    }

    public function testFindDocumentsNeedingRetry(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('重试测试数据集');
        $dataset->setDescription('用于测试重试查询的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建需要重试的文档
        $retryDoc1 = new Document();
        $retryDoc1->setName('重试文档1.pdf');
        $retryDoc1->setDataset($persistedDataset);
        $retryDoc1->setStatus(DocumentStatus::SYNC_FAILED);
        $retryDoc1->setFilePath('/tmp/retry-doc1.pdf');
        $this->persistAndFlush($retryDoc1);

        $retryDoc2 = new Document();
        $retryDoc2->setName('重试文档2.docx');
        $retryDoc2->setDataset($persistedDataset);
        $retryDoc2->setStatus(DocumentStatus::FAILED);
        $retryDoc2->setFilePath('/tmp/retry-doc2.docx');
        $this->persistAndFlush($retryDoc2);

        // 创建不需要重试的文档
        $successDoc = new Document();
        $successDoc->setName('成功文档.txt');
        $successDoc->setDataset($persistedDataset);
        $successDoc->setStatus(DocumentStatus::COMPLETED);
        $this->persistAndFlush($successDoc);

        // 查找需要重试的文档
        $retryDocuments = $this->repository->findDocumentsNeedingRetry();
        $this->assertGreaterThanOrEqual(2, count($retryDocuments));

        foreach ($retryDocuments as $document) {
            $this->assertTrue(
                in_array($document->getStatus(), [DocumentStatus::SYNC_FAILED, DocumentStatus::FAILED], true)
            );
            $this->assertNotNull($document->getFilePath());
        }
    }

    public function testCountPendingByDataset(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('待处理计数测试数据集', '用于测试待处理文档计数的数据集');
        /** @var Dataset $dataset */
        $dataset = $dataset;

        // 记录初始待处理文档数量
        $initialPendingCount = $this->repository->countPendingByDataset($dataset);

        // 创建不同状态的文档
        $pendingDoc = new Document();
        $pendingDoc->setName('待处理文档1.pdf');
        $pendingDoc->setDataset($dataset);
        $pendingDoc->setStatus(DocumentStatus::PENDING);
        $this->persistAndFlush($pendingDoc);

        $parsingDoc = new Document();
        $parsingDoc->setName('解析中文档2.docx');
        $parsingDoc->setDataset($dataset);
        $parsingDoc->setStatus(DocumentStatus::PENDING); // 暂时使用PENDING状态
        $this->persistAndFlush($parsingDoc);

        $failedDoc = new Document();
        $failedDoc->setName('失败文档3.txt');
        $failedDoc->setDataset($dataset);
        $failedDoc->setStatus(DocumentStatus::FAILED);
        $this->persistAndFlush($failedDoc);

        // 创建已处理状态的文档（不应被计入待处理）
        $processedDoc = new Document();
        $processedDoc->setName('已完成文档4.md');
        $processedDoc->setDataset($dataset);
        $processedDoc->setStatus(DocumentStatus::COMPLETED);
        $this->persistAndFlush($processedDoc);

        // 验证待处理文档数量增加（只计算我们实际创建的2个PENDING + 1个FAILED = 3个）
        $finalPendingCount = $this->repository->countPendingByDataset($dataset);
        $this->assertEquals($initialPendingCount + 3, $finalPendingCount); // 2个PENDING + 1个FAILED
    }

    public function testCountProcessedByDataset(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('已处理计数测试数据集', '用于测试已处理文档计数的数据集');
        /** @var Dataset $dataset */
        $dataset = $dataset;

        // 记录初始已处理文档数量
        $initialProcessedCount = $this->repository->countProcessedByDataset($dataset);

        // 创建已处理状态的文档（使用'parsed'状态）
        $processedDoc1 = new Document();
        $processedDoc1->setName('已处理文档1.pdf');
        $processedDoc1->setDataset($dataset);
        $processedDoc1->setStatus('parsed'); // Repository期望的字符串状态
        $this->persistAndFlush($processedDoc1);

        $processedDoc2 = new Document();
        $processedDoc2->setName('已处理文档2.docx');
        $processedDoc2->setDataset($dataset);
        $processedDoc2->setStatus('parsed');
        $this->persistAndFlush($processedDoc2);

        // 创建未处理状态的文档（不应被计入已处理）
        $unprocessedDoc = new Document();
        $unprocessedDoc->setName('未处理文档.txt');
        $unprocessedDoc->setDataset($dataset);
        $unprocessedDoc->setStatus(DocumentStatus::PENDING);
        $this->persistAndFlush($unprocessedDoc);

        // 验证已处理文档数量增加
        $finalProcessedCount = $this->repository->countProcessedByDataset($dataset);
        $this->assertEquals($initialProcessedCount + 2, $finalProcessedCount);
    }

    public function testFindPendingSync(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('同步测试数据集');
        $dataset->setDescription('用于测试待同步文档查询的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建测试时间点
        $testTime = new \DateTimeImmutable('2024-01-01 00:00:00');

        // 创建需要同步的文档（lastSyncTime为空）
        $needsSyncDoc1 = new Document();
        $needsSyncDoc1->setName('需要同步文档1.pdf');
        $needsSyncDoc1->setDataset($persistedDataset);
        $needsSyncDoc1->setLastSyncTime(null);
        $this->persistAndFlush($needsSyncDoc1);

        // 创建需要同步的文档（lastSyncTime早于测试时间）
        $needsSyncDoc2 = new Document();
        $needsSyncDoc2->setName('需要同步文档2.docx');
        $needsSyncDoc2->setDataset($persistedDataset);
        $needsSyncDoc2->setLastSyncTime(new \DateTimeImmutable('2023-12-01 00:00:00'));
        $this->persistAndFlush($needsSyncDoc2);

        // 创建不需要同步的文档（lastSyncTime晚于测试时间）
        $syncedDoc = new Document();
        $syncedDoc->setName('已同步文档.txt');
        $syncedDoc->setDataset($persistedDataset);
        $syncedDoc->setLastSyncTime(new \DateTimeImmutable('2024-02-01 00:00:00'));
        $this->persistAndFlush($syncedDoc);

        // 查找需要同步的文档
        $pendingSyncDocs = $this->repository->findPendingSync($testTime);
        $this->assertGreaterThanOrEqual(2, count($pendingSyncDocs));

        // 验证结果按lastSyncTime升序排列（null值的排在前面）
        $previousSyncTime = null;
        foreach ($pendingSyncDocs as $document) {
            $currentSyncTime = $document->getLastSyncTime();
            if (null !== $previousSyncTime && null !== $currentSyncTime) {
                $this->assertLessThanOrEqual(
                    $currentSyncTime->getTimestamp(),
                    $previousSyncTime->getTimestamp(),
                    '文档应该按lastSyncTime升序排列'
                );
            }
            $previousSyncTime = $currentSyncTime;
        }
    }

    public function testFindWithFilters(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('筛选测试数据集');
        $dataset->setDescription('用于测试文档筛选查询的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同类型、状态、名称的文档
        $documents = [
            ['name' => '用户手册.pdf', 'type' => 'pdf', 'status' => DocumentStatus::COMPLETED],
            ['name' => '技术文档.docx', 'type' => 'docx', 'status' => DocumentStatus::PROCESSING],
            ['name' => '用户指南.txt', 'type' => 'txt', 'status' => DocumentStatus::COMPLETED],
            ['name' => '产品介绍.pptx', 'type' => 'pptx', 'status' => DocumentStatus::FAILED],
            ['name' => '开发手册.pdf', 'type' => 'pdf', 'status' => DocumentStatus::PENDING],
        ];

        $persistedDocuments = [];
        foreach ($documents as $docData) {
            $document = new Document();
            $document->setName($docData['name']);
            $document->setType($docData['type']);
            $document->setStatus($docData['status']);
            $document->setDataset($persistedDataset);
            $persistedDoc = $this->persistAndFlush($document);
            $persistedDocuments[] = $persistedDoc;
        }

        // 测试无筛选条件的基本分页查询
        $basicResult = $this->repository->findWithFilters();
        $this->assertIsArray($basicResult);
        $this->assertArrayHasKey('items', $basicResult);
        $this->assertArrayHasKey('total', $basicResult);
        $this->assertIsArray($basicResult['items']);
        $this->assertIsInt($basicResult['total']);
        $this->assertLessThanOrEqual(20, count($basicResult['items'])); // 默认limit为20

        // 测试按名称筛选
        $nameFilterResult = $this->repository->findWithFilters(['name' => '用户']);
        $this->assertGreaterThanOrEqual(2, $nameFilterResult['total']); // 应该找到包含"用户"的文档
        foreach ($nameFilterResult['items'] as $document) {
            $this->assertStringContainsString('用户', $document->getName());
        }

        // 测试按状态筛选
        $statusFilterResult = $this->repository->findWithFilters(['status' => DocumentStatus::COMPLETED->value]);
        foreach ($statusFilterResult['items'] as $document) {
            $this->assertEquals(DocumentStatus::COMPLETED, $document->getStatus());
        }

        // 测试按类型筛选
        $typeFilterResult = $this->repository->findWithFilters(['type' => 'pdf']);
        foreach ($typeFilterResult['items'] as $document) {
            $this->assertEquals('pdf', $document->getType());
        }

        // 测试按数据集筛选
        $datasetFilterResult = $this->repository->findWithFilters(['dataset_id' => $persistedDataset->getId()]);
        $this->assertGreaterThanOrEqual(5, $datasetFilterResult['total']); // 至少包含我们创建的5个文档
        foreach ($datasetFilterResult['items'] as $document) {
            $this->assertNotNull($document->getDataset());
            $this->assertEquals($persistedDataset->getId(), $document->getDataset()->getId());
        }

        // 测试组合筛选条件
        $combinedFilterResult = $this->repository->findWithFilters([
            'name' => '手册',
            'type' => 'pdf',
        ]);
        foreach ($combinedFilterResult['items'] as $document) {
            $this->assertStringContainsString('手册', $document->getName());
            $this->assertEquals('pdf', $document->getType());
        }

        // 测试分页功能
        $page1Result = $this->repository->findWithFilters([], 1, 2);
        $page2Result = $this->repository->findWithFilters([], 2, 2);

        $this->assertLessThanOrEqual(2, count($page1Result['items']));
        $this->assertLessThanOrEqual(2, count($page2Result['items']));

        // 验证分页结果的排序（按更新时间降序）
        $allItems = array_merge($page1Result['items'], $page2Result['items']);
        $previousUpdateTime = null;
        foreach ($allItems as $document) {
            if (null !== $previousUpdateTime) {
                $this->assertGreaterThanOrEqual(
                    $document->getUpdateTime(),
                    $previousUpdateTime,
                    '文档应该按更新时间降序排列'
                );
            }
            $previousUpdateTime = $document->getUpdateTime();
        }
    }

    public function testSave(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('保存测试数据集');
        $dataset->setDescription('用于测试文档保存的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 测试保存新文档
        $newDocument = new Document();
        $newDocument->setName('保存测试文档.pdf');
        $newDocument->setDataset($persistedDataset);
        $newDocument->setStatus(DocumentStatus::UPLOADED);
        $newDocument->setType('pdf');
        $newDocument->setSize(1024 * 1024); // 1MB

        // 确保文档还没有ID
        $this->assertNull($newDocument->getId());

        // 保存文档
        $this->repository->save($newDocument);

        // 验证文档已被持久化并分配了ID
        $this->assertNotNull($newDocument->getId());

        // 验证可以从数据库重新检索
        $em = $this->getEntityManagerInstance();
        $savedDocument = $em->find(Document::class, $newDocument->getId());
        $this->assertInstanceOf(Document::class, $savedDocument);
        $this->assertEquals('保存测试文档.pdf', $savedDocument->getName());
        $this->assertEquals('pdf', $savedDocument->getType());
        $this->assertEquals(DocumentStatus::UPLOADED, $savedDocument->getStatus());
        $this->assertEquals(1024 * 1024, $savedDocument->getSize());
        $this->assertNotNull($savedDocument->getDataset());
        $this->assertEquals($persistedDataset->getId(), $savedDocument->getDataset()->getId());

        // 测试更新现有文档
        $newDocument->setName('更新后的文档名称.pdf');
        $newDocument->setStatus(DocumentStatus::PROCESSING);
        $newDocument->setSize(2 * 1024 * 1024); // 2MB

        $this->repository->save($newDocument);

        // 清理EntityManager缓存并重新加载文档
        $em->clear();
        $updatedDocument = $em->find(Document::class, $newDocument->getId());
        $this->assertInstanceOf(Document::class, $updatedDocument);
        $this->assertEquals('更新后的文档名称.pdf', $updatedDocument->getName());
        $this->assertEquals(DocumentStatus::PROCESSING, $updatedDocument->getStatus());
        $this->assertEquals(2 * 1024 * 1024, $updatedDocument->getSize());
    }

    public function testRemove(): void
    {
        // 创建数据集
        $dataset = new Dataset();
        $dataset->setName('删除测试数据集');
        $dataset->setDescription('用于测试文档删除的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建要删除的文档
        $document = new Document();
        $document->setName('待删除文档.pdf');
        $document->setDataset($persistedDataset);
        $document->setStatus(DocumentStatus::UPLOADED);
        $persistedDocument = $this->persistAndFlush($document);
        $this->assertInstanceOf(Document::class, $persistedDocument);

        $documentId = $persistedDocument->getId();
        $this->assertNotNull($documentId);

        // 验证文档存在于数据库中
        $em = $this->getEntityManagerInstance();
        $existingDocument = $em->find(Document::class, $documentId);
        $this->assertInstanceOf(Document::class, $existingDocument);

        // 删除文档
        $this->repository->remove($persistedDocument);

        // 验证文档已从数据库中删除
        $deletedDocument = $em->find(Document::class, $documentId);
        $this->assertNull($deletedDocument);

        // 测试删除未持久化的文档（应该不会抛出异常）
        $nonPersistedDocument = new Document();
        $nonPersistedDocument->setName('未持久化的文档.pdf');

        try {
            $this->repository->remove($nonPersistedDocument);
            // 删除未持久化的文档不应该抛出异常 - 测试通过即证明功能正常
        } catch (\Exception $e) {
            self::fail('删除未持久化的文档时抛出了意外异常: ' . $e->getMessage());
        }
    }
}
