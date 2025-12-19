<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\DatasetRepository;

/**
 * @internal
 */
#[CoversClass(DatasetRepository::class)]
#[RunTestsInSeparateProcesses]
class DatasetRepositoryTest extends AbstractRepositoryTestCase
{
    private static ?RAGFlowInstance $testInstance = null;
    private static int $datasetCounter = 0;

    protected function onSetUp(): void
    {
        // AbstractRepositoryTestCase 需要此方法
        // 初始化工作在 createNewEntity 和 createTestDataset 中完成
    }

    /**
     * @return ServiceEntityRepository<Dataset>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(DatasetRepository::class);
    }

    protected function createNewEntity(): object
    {
        // 确保 testInstance 存在
        if (null === self::$testInstance) {
            self::$testInstance = new RAGFlowInstance();
            self::$testInstance->setName('test-instance-' . uniqid());
            self::$testInstance->setApiUrl('https://test.example.com/api');
            self::$testInstance->setApiKey('test-key');
            self::$testInstance->setEnabled(true);
            self::getEntityManager()->persist(self::$testInstance);
            self::getEntityManager()->flush();
        }

        $dataset = new Dataset();
        $dataset->setRagFlowInstance(self::$testInstance);
        $dataset->setName('test-dataset-' . (++self::$datasetCounter) . '-' . uniqid());
        $dataset->setDescription('Test dataset for repository testing');

        return $dataset;
    }

    /**
     * 确保测试实例存在
     */
    private function ensureTestInstance(): void
    {
        if (null === self::$testInstance) {
            self::$testInstance = new RAGFlowInstance();
            self::$testInstance->setName('test-instance-' . uniqid());
            self::$testInstance->setApiUrl('https://test.example.com/api');
            self::$testInstance->setApiKey('test-key');
            self::$testInstance->setEnabled(true);
            $this->persistAndFlush(self::$testInstance);
        }
    }

    /**
     * 创建测试用的 Dataset，自动设置 RAGFlowInstance
     */
    private function createTestDataset(): Dataset
    {
        $this->ensureTestInstance();

        $dataset = new Dataset();
        $dataset->setRagFlowInstance(self::$testInstance);

        return $dataset;
    }

    public function testFindByRemoteId(): void
    {
        // 创建测试数据集
        $dataset = $this->createTestDataset();
        $dataset->setName('远程ID测试数据集');
        $dataset->setDescription('用于测试远程ID查找的数据集');
        $dataset->setRemoteId('remote-dataset-123');
        /** @var Dataset $persistedDataset */
        $persistedDataset = $this->persistAndFlush($dataset);

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试通过远程ID查找
        $foundDataset = $repository->findByRemoteId('remote-dataset-123');
        $this->assertNotNull($foundDataset);
        $this->assertEquals($persistedDataset->getId(), $foundDataset->getId());
        $this->assertEquals('remote-dataset-123', $foundDataset->getRemoteId());

        // 测试查找不存在的远程ID
        $notFound = $repository->findByRemoteId('non-existent-remote-id');
        $this->assertNull($notFound);
    }

    public function testFindEnabled(): void
    {
        // 创建启用的数据集
        $enabledDataset1 = $this->createTestDataset();
        $enabledDataset1->setName('启用数据集1');
        $enabledDataset1->setDescription('第一个启用的数据集');
        $enabledDataset1->setEnabled(true);
        $this->persistAndFlush($enabledDataset1);

        $enabledDataset2 = $this->createTestDataset();
        $enabledDataset2->setName('启用数据集2');
        $enabledDataset2->setDescription('第二个启用的数据集');
        $enabledDataset2->setEnabled(true);
        $this->persistAndFlush($enabledDataset2);

        // 创建禁用的数据集
        $disabledDataset = $this->createTestDataset();
        $disabledDataset->setName('禁用数据集');
        $disabledDataset->setDescription('被禁用的数据集');
        $disabledDataset->setEnabled(false);
        $this->persistAndFlush($disabledDataset);

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试查找启用的数据集
        $enabledDatasets = $repository->findEnabled();
        $this->assertGreaterThanOrEqual(2, count($enabledDatasets));

        // 验证结果中的数据集都是启用状态
        foreach ($enabledDatasets as $dataset) {
            $this->assertTrue($dataset->isEnabled());
        }
    }

    public function testFindByNamePattern(): void
    {
        // 创建不同名称的数据集
        $datasets = [
            ['name' => '客户服务知识库', 'pattern' => '客户'],
            ['name' => '技术文档集合', 'pattern' => '技术'],
            ['name' => '产品介绍资料', 'pattern' => '产品'],
            ['name' => '客户案例库', 'pattern' => '客户'],
        ];

        foreach ($datasets as $datasetData) {
            $dataset = $this->createTestDataset();
            $dataset->setName($datasetData['name']);
            $dataset->setDescription("用于{$datasetData['pattern']}相关的数据集");
            $this->persistAndFlush($dataset);
        }

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试按名称模式查找
        $customerDatasets = $repository->findByNamePattern('客户');
        $this->assertGreaterThanOrEqual(2, count($customerDatasets));

        foreach ($customerDatasets as $dataset) {
            $this->assertStringContainsString('客户', $dataset->getName());
        }

        $techDatasets = $repository->findByNamePattern('技术');
        $this->assertGreaterThanOrEqual(1, count($techDatasets));

        foreach ($techDatasets as $dataset) {
            $this->assertStringContainsString('技术', $dataset->getName());
        }
    }

    public function testFindRecentlyCreated(): void
    {
        // 创建数据集
        $recentDataset = $this->createTestDataset();
        $recentDataset->setName('最近创建的数据集');
        $recentDataset->setDescription('用于测试最近创建查询的数据集');
        $this->persistAndFlush($recentDataset);

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试查找最近创建的数据集
        $recentDatasets = $repository->findRecentlyCreated(10);
        $this->assertGreaterThanOrEqual(1, count($recentDatasets));

        // 验证我们创建的数据集在结果中
        $found = false;
        foreach ($recentDatasets as $dataset) {
            if ($dataset->getId() === $recentDataset->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, '最近创建的数据集应该在结果中');

        // 验证结果按创建时间降序排列（使用 >= 因为快速创建可能有相同时间戳）
        $previousCreateTime = null;
        foreach ($recentDatasets as $dataset) {
            $currentCreateTime = $dataset->getCreateTime();
            if (null !== $previousCreateTime && null !== $currentCreateTime) {
                $this->assertGreaterThanOrEqual(
                    $currentCreateTime->getTimestamp(),
                    $previousCreateTime->getTimestamp(),
                    '数据集应该按创建时间降序排列'
                );
            }
            $previousCreateTime = $currentCreateTime;
        }
    }

    public function testFindWithPagination(): void
    {
        // 创建多个数据集
        for ($i = 1; $i <= 25; ++$i) {
            $dataset = $this->createTestDataset();
            $dataset->setName("分页测试数据集{$i}");
            $dataset->setDescription("用于测试分页的第{$i}个数据集");
            $this->persistAndFlush($dataset);
        }

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试第一页
        $firstPage = $repository->findWithPagination(1, 10);
        $this->assertCount(10, $firstPage);

        // 测试第二页
        $secondPage = $repository->findWithPagination(2, 10);
        $this->assertCount(10, $secondPage);

        // 测试第三页
        $thirdPage = $repository->findWithPagination(3, 10);
        $this->assertGreaterThanOrEqual(5, count($thirdPage)); // 至少有5个，可能有其他测试创建的

        // 验证分页结果不重复
        $firstPageIds = array_map(fn ($d) => $d->getId(), $firstPage);
        $secondPageIds = array_map(fn ($d) => $d->getId(), $secondPage);
        $this->assertEmpty(array_intersect($firstPageIds, $secondPageIds));
    }

    public function testCountTotalDatasets(): void
    {
        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $initialCount = $repository->countTotalDatasets();

        // 添加数据集
        for ($i = 1; $i <= 3; ++$i) {
            $dataset = $this->createTestDataset();
            $dataset->setName("计数测试数据集{$i}");
            $dataset->setDescription("用于测试计数的第{$i}个数据集");
            $this->persistAndFlush($dataset);
        }

        $finalCount = $repository->countTotalDatasets();
        $this->assertEquals($initialCount + 3, $finalCount);
    }

    public function testCountEnabledDatasets(): void
    {
        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $initialEnabledCount = $repository->countEnabledDatasets();

        // 添加启用的数据集
        $enabledDataset = $this->createTestDataset();
        $enabledDataset->setName('计数启用数据集');
        $enabledDataset->setDescription('用于测试启用状态计数的数据集');
        $enabledDataset->setEnabled(true);
        $this->persistAndFlush($enabledDataset);

        // 添加禁用的数据集
        $disabledDataset = $this->createTestDataset();
        $disabledDataset->setName('计数禁用数据集');
        $disabledDataset->setDescription('用于测试禁用状态计数的数据集');
        $disabledDataset->setEnabled(false);
        $this->persistAndFlush($disabledDataset);

        $finalEnabledCount = $repository->countEnabledDatasets();
        $this->assertEquals($initialEnabledCount + 1, $finalEnabledCount);
    }

    public function testFindOrCreateByRemoteId(): void
    {
        $remoteId = 'find-or-create-dataset-123';

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 确保测试实例存在
        $this->ensureTestInstance();

        // 第一次调用应该创建新实体
        $firstResult = $repository->findOrCreateByRemoteId($remoteId, self::$testInstance);
        $this->assertInstanceOf(Dataset::class, $firstResult);
        $this->assertEquals($remoteId, $firstResult->getRemoteId());
        $this->assertNotNull($firstResult->getId()); // 应该已经持久化

        // 第二次调用应该返回相同的实体
        $secondResult = $repository->findOrCreateByRemoteId($remoteId, self::$testInstance);
        $this->assertEquals($firstResult->getId(), $secondResult->getId());
        $this->assertEquals($remoteId, $secondResult->getRemoteId());
    }

    public function testSearchByKeywords(): void
    {
        // 创建带有不同描述的数据集
        $datasets = [
            ['name' => '客户服务知识库', 'description' => '包含客户服务相关的文档和FAQ'],
            ['name' => '技术文档集', 'description' => '提供技术支持和开发指南的文档集合'],
            ['name' => '销售资料库', 'description' => '协助销售团队的产品介绍和案例资料'],
            ['name' => '培训材料', 'description' => '帮助员工学习和培训的教育资源'],
        ];

        foreach ($datasets as $datasetData) {
            $dataset = $this->createTestDataset();
            $dataset->setName($datasetData['name']);
            $dataset->setDescription($datasetData['description']);
            $this->persistAndFlush($dataset);
        }

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 搜索包含"客户"的数据集
        $customerDatasets = $repository->searchByKeywords('客户');
        $this->assertGreaterThanOrEqual(1, count($customerDatasets));

        // 搜索包含"技术"的数据集
        $techDatasets = $repository->searchByKeywords('技术');
        $this->assertGreaterThanOrEqual(1, count($techDatasets));

        // 搜索不存在的关键词
        $noResultDatasets = $repository->searchByKeywords('不存在的关键词');
        $this->assertEmpty($noResultDatasets);
    }

    public function testFindWithDocumentStats(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset();
        $dataset->setName('文档统计测试数据集');
        $dataset->setDescription('用于测试文档统计的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 为数据集添加文档
        for ($i = 1; $i <= 5; ++$i) {
            $document = new Document();
            $document->setName("统计测试文档{$i}.pdf");
            $document->setDataset($persistedDataset);
            $this->persistAndFlush($document);
        }

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试带文档统计的查找
        $datasetsWithStats = $repository->findWithDocumentStats();
        $this->assertNotEmpty($datasetsWithStats);

        // 验证我们的测试数据集在结果中
        // 注意：getId() 返回字符串，但统计结果中的 id 是整数
        $testDatasetFound = false;
        $expectedId = (int) $persistedDataset->getId();
        foreach ($datasetsWithStats as $datasetStats) {
            if ($datasetStats['id'] === $expectedId) {
                $testDatasetFound = true;
                $this->assertGreaterThanOrEqual(5, $datasetStats['document_count']);
                break;
            }
        }
        $this->assertTrue($testDatasetFound, '测试数据集应该在统计结果中找到');
    }

    public function testFindByChunkConfig(): void
    {
        // 创建不同块配置的数据集
        $config1 = [
            'chunk_method' => 'naive',
            'chunk_size' => 512,
            'chunk_overlap' => 64,
        ];

        $dataset1 = $this->createTestDataset();
        $dataset1->setName('块配置测试数据集1');
        $dataset1->setDescription('使用naive方法的数据集');
        $dataset1->setChunkConfig($config1);
        $this->persistAndFlush($dataset1);

        $config2 = [
            'chunk_method' => 'manual',
            'chunk_size' => 1024,
            'chunk_overlap' => 128,
        ];

        $dataset2 = $this->createTestDataset();
        $dataset2->setName('块配置测试数据集2');
        $dataset2->setDescription('使用manual方法的数据集');
        $dataset2->setChunkConfig($config2);
        $this->persistAndFlush($dataset2);

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试按块方法查找
        $naiveDatasets = $repository->findByChunkConfig('chunk_method', 'naive');
        $this->assertGreaterThanOrEqual(1, count($naiveDatasets));

        foreach ($naiveDatasets as $dataset) {
            $chunkConfig = $dataset->getChunkConfig();
            $this->assertIsArray($chunkConfig);
            $this->assertArrayHasKey('chunk_method', $chunkConfig);
            $this->assertEquals('naive', $chunkConfig['chunk_method']);
        }

        // 测试按块大小查找
        $largeChunkDatasets = $repository->findByChunkConfig('chunk_size', 1024);
        $this->assertGreaterThanOrEqual(1, count($largeChunkDatasets));

        foreach ($largeChunkDatasets as $dataset) {
            $chunkConfig = $dataset->getChunkConfig();
            $this->assertIsArray($chunkConfig);
            $this->assertArrayHasKey('chunk_size', $chunkConfig);
            $this->assertEquals(1024, $chunkConfig['chunk_size']);
        }
    }

    public function testFindEmptyDatasets(): void
    {
        // 创建空数据集（没有文档）
        $emptyDataset = $this->createTestDataset();
        $emptyDataset->setName('空数据集');
        $emptyDataset->setDescription('没有任何文档的数据集');
        /** @var Dataset $persistedEmptyDataset */
        $persistedEmptyDataset = $this->persistAndFlush($emptyDataset);

        // 创建有文档的数据集
        $datasetWithDocs = $this->createTestDataset();
        $datasetWithDocs->setName('有文档的数据集');
        $datasetWithDocs->setDescription('包含文档的数据集');
        $persistedDatasetWithDocsResult = $this->persistAndFlush($datasetWithDocs);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetWithDocsResult);
        /** @var Dataset $persistedDatasetWithDocs */
        $persistedDatasetWithDocs = $persistedDatasetWithDocsResult;

        // 为第二个数据集添加文档
        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setDataset($persistedDatasetWithDocs);
        $this->persistAndFlush($document);

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试查找空数据集
        $emptyDatasets = $repository->findEmptyDatasets();
        $this->assertNotEmpty($emptyDatasets);

        // 验证空数据集在结果中
        $emptyDatasetFound = false;
        foreach ($emptyDatasets as $dataset) {
            if ($dataset->getId() === $persistedEmptyDataset->getId()) {
                $emptyDatasetFound = true;
                break;
            }
        }
        $this->assertTrue($emptyDatasetFound, '空数据集应该在结果中找到');
    }

    public function testFindDatasetsWithChatAssistants(): void
    {
        // 创建数据集
        $datasetWithAssistant = $this->createTestDataset();
        $datasetWithAssistant->setName('有助手的数据集');
        $datasetWithAssistant->setDescription('包含聊天助手的数据集');
        $persistedDatasetWithAssistantResult = $this->persistAndFlush($datasetWithAssistant);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetWithAssistantResult);
        /** @var Dataset $persistedDatasetWithAssistant */
        $persistedDatasetWithAssistant = $persistedDatasetWithAssistantResult;

        $datasetWithoutAssistant = $this->createTestDataset();
        $datasetWithoutAssistant->setName('无助手的数据集');
        $datasetWithoutAssistant->setDescription('没有聊天助手的数据集');
        $this->persistAndFlush($datasetWithoutAssistant);

        // 为第一个数据集创建聊天助手
        $chatAssistant = new ChatAssistant();
        $chatAssistant->setName('测试聊天助手');
        $chatAssistant->setDataset($persistedDatasetWithAssistant);
        $this->persistAndFlush($chatAssistant);

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();

        // 测试查找有聊天助手的数据集
        $datasetsWithAssistants = $repository->findDatasetsWithChatAssistants();
        $this->assertNotEmpty($datasetsWithAssistants);

        // 验证有助手的数据集在结果中
        $datasetFound = false;
        foreach ($datasetsWithAssistants as $dataset) {
            if ($dataset->getId() === $persistedDatasetWithAssistant->getId()) {
                $datasetFound = true;
                break;
            }
        }
        $this->assertTrue($datasetFound, '有助手的数据集应该在结果中找到');
    }

    public function testGetDatasetUsageStats(): void
    {
        // 创建测试数据集
        $dataset = $this->createTestDataset();
        $dataset->setName('使用统计测试数据集');
        $dataset->setDescription('用于测试使用统计的数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 添加文档和助手
        $document = new Document();
        $document->setName('统计文档.pdf');
        $document->setDataset($persistedDataset);
        $this->persistAndFlush($document);

        $chatAssistant = new ChatAssistant();
        $chatAssistant->setName('统计助手');
        $chatAssistant->setDataset($persistedDataset);
        $this->persistAndFlush($chatAssistant);

        // 测试获取使用统计
        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $stats = $repository->getDatasetUsageStats($persistedDataset);

        $this->assertArrayHasKey('document_count', $stats);
        $this->assertArrayHasKey('assistant_count', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['document_count']);
        $this->assertGreaterThanOrEqual(1, $stats['assistant_count']);
    }

    public function testFindByInstance(): void
    {
        // 创建第二个 instance
        $anotherInstance = new RAGFlowInstance();
        $anotherInstance->setName('another-instance-' . uniqid());
        $anotherInstance->setApiUrl('https://another.example.com/api');
        $anotherInstance->setApiKey('another-key');
        $anotherInstance->setEnabled(true);
        $this->persistAndFlush($anotherInstance);

        // 为 testInstance 创建数据集
        $dataset1 = $this->createTestDataset();
        $dataset1->setName('Instance 1 Dataset 1');
        $dataset1->setDescription('Dataset for test instance');
        $this->persistAndFlush($dataset1);

        // 为 anotherInstance 创建数据集
        $dataset2 = new Dataset();
        $dataset2->setRagFlowInstance($anotherInstance);
        $dataset2->setName('Instance 2 Dataset 1');
        $dataset2->setDescription('Dataset for another instance');
        $this->persistAndFlush($dataset2);

        // 测试按实例查找
        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $testInstanceDatasets = $repository->findByInstance(self::$testInstance);
        $this->assertNotEmpty($testInstanceDatasets);

        // 验证所有返回的数据集都属于正确的实例
        foreach ($testInstanceDatasets as $dataset) {
            $this->assertEquals(self::$testInstance->getId(), $dataset->getRagFlowInstance()->getId());
        }
    }

    public function testFindByStatus(): void
    {
        // 创建不同状态的数据集
        $dataset1 = $this->createTestDataset();
        $dataset1->setName('Active Dataset');
        $dataset1->setDescription('Active status dataset');
        $dataset1->setStatus('active');
        $this->persistAndFlush($dataset1);

        $dataset2 = $this->createTestDataset();
        $dataset2->setName('Inactive Dataset');
        $dataset2->setDescription('Inactive status dataset');
        $dataset2->setStatus('inactive');
        $this->persistAndFlush($dataset2);

        // 测试按状态查找
        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $activeDatasets = $repository->findByStatus('active');
        $this->assertNotEmpty($activeDatasets);

        // 验证所有返回的数据集都是 active 状态
        foreach ($activeDatasets as $dataset) {
            $this->assertEquals('active', $dataset->getStatus());
        }
    }

    public function testFindPendingSync(): void
    {
        // 创建一个需要同步的数据集（lastSyncTime 为 null 或很久以前）
        $dataset = $this->createTestDataset();
        $dataset->setName('Pending Sync Dataset');
        $dataset->setDescription('Dataset that needs sync');
        // lastSyncTime 默认为 null，所以应该被查找到
        $this->persistAndFlush($dataset);

        // 测试查找需要同步的数据集
        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $since = new \DateTimeImmutable('-1 hour');
        $pendingDatasets = $repository->findPendingSync($since);

        $this->assertNotEmpty($pendingDatasets);

        // 验证我们创建的数据集在结果中
        $found = false;
        foreach ($pendingDatasets as $pendingDataset) {
            if ($pendingDataset->getId() === $dataset->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindWithFilters(): void
    {
        // 创建测试数据集
        $dataset1 = $this->createTestDataset();
        $dataset1->setName('Filter Test Dataset 1');
        $dataset1->setDescription('Dataset for filter testing');
        $dataset1->setStatus('active');
        $this->persistAndFlush($dataset1);

        $dataset2 = $this->createTestDataset();
        $dataset2->setName('Another Dataset');
        $dataset2->setDescription('Another dataset');
        $dataset2->setStatus('inactive');
        $this->persistAndFlush($dataset2);

        // 测试按名称过滤
        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $result = $repository->findWithFilters(['name' => 'Filter Test'], 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertGreaterThanOrEqual(1, $result['total']);

        // 验证返回的数据集名称包含过滤词
        foreach ($result['items'] as $dataset) {
            $this->assertStringContainsString('Filter Test', $dataset->getName());
        }
    }

    public function testSave(): void
    {
        $dataset = $this->createTestDataset();
        $dataset->setName('Save Test Dataset');
        $dataset->setDescription('Dataset for save testing');

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $repository->save($dataset);

        // 验证已保存
        $this->assertNotNull($dataset->getId());

        // 验证可以重新查找到
        $found = $repository->find($dataset->getId());
        $this->assertInstanceOf(Dataset::class, $found);
        $this->assertEquals('Save Test Dataset', $found->getName());
    }

    public function testRemove(): void
    {
        $dataset = $this->createTestDataset();
        $dataset->setName('Remove Test Dataset');
        $dataset->setDescription('Dataset for remove testing');
        $this->persistAndFlush($dataset);

        $id = $dataset->getId();
        $this->assertNotNull($id);

        /** @var DatasetRepository $repository */
        $repository = $this->getRepository();
        $repository->remove($dataset);

        // 验证已删除
        $found = $repository->find($id);
        $this->assertNull($found);
    }
}
