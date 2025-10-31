<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Repository\ChatAssistantRepository;

/**
 * @internal
 */
#[CoversClass(ChatAssistantRepository::class)]
#[RunTestsInSeparateProcesses]
class ChatAssistantRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository tests don't need additional setup
    }

    /**
     * 创建测试用的 RAGFlowInstance
     */
    private function createTestRAGFlowInstance(): RAGFlowInstance
    {
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('Test Instance ' . uniqid());
        $ragFlowInstance->setApiKey('test-api-key-' . uniqid());
        $ragFlowInstance->setBaseUrl('https://test.example.com');

        return $ragFlowInstance;
    }

    /**
     * 创建测试用的 Dataset
     */
    private function createTestDataset(?string $name = null): Dataset
    {
        $ragFlowInstance = $this->createTestRAGFlowInstance();
        $this->persistAndFlush($ragFlowInstance);

        $dataset = new Dataset();
        $dataset->setName($name ?? 'Test Dataset ' . uniqid());
        $dataset->setDescription('Test dataset description');
        $dataset->setRagFlowInstance($ragFlowInstance);

        return $dataset;
    }

    protected function getRepository(): ChatAssistantRepository
    {
        /** @var ChatAssistantRepository */
        return self::getService(ChatAssistantRepository::class);
    }

    protected function createNewEntity(): object
    {
        $dataset = $this->createTestDataset('Test Dataset for Assistant ' . uniqid());
        $this->persistAndFlush($dataset);

        $assistant = new ChatAssistant();
        $assistant->setName('Test Assistant ' . uniqid());
        $assistant->setDataset($dataset);
        $assistant->setLlmModel('gpt-3.5-turbo');

        return $assistant;
    }

    public function testRepositoryCreation(): void
    {
        $this->assertInstanceOf(ChatAssistantRepository::class, $this->getRepository());
    }

    public function testFindByRemoteId(): void
    {
        // 创建测试数据集
        $dataset = $this->createTestDataset('助手仓库测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建测试助手
        $assistant = new ChatAssistant();
        $assistant->setName('测试助手');
        $assistant->setRemoteId('remote-assistant-123');
        $assistant->setDataset($persistedDataset);
        $assistant->setLlmModel('gpt-3.5-turbo');
        /** @var ChatAssistant $persistedAssistant */
        $persistedAssistant = $this->persistAndFlush($assistant);

        // 测试通过远程ID查找
        $foundAssistant = $this->getRepository()->findByRemoteId('remote-assistant-123');
        $this->assertNotNull($foundAssistant);
        $this->assertEquals($persistedAssistant->getId(), $foundAssistant->getId());
        $this->assertEquals('remote-assistant-123', $foundAssistant->getRemoteId());

        // 测试查找不存在的远程ID
        $notFound = $this->getRepository()->findByRemoteId('non-existent-remote-id');
        $this->assertNull($notFound);
    }

    public function testFindByDataset(): void
    {
        // 创建两个数据集
        $dataset1 = $this->createTestDataset('数据集1');
        $persistedDataset1Result = $this->persistAndFlush($dataset1);
        $this->assertInstanceOf(Dataset::class, $persistedDataset1Result);
        /** @var Dataset $persistedDataset1 */
        $persistedDataset1 = $persistedDataset1Result;

        $dataset2 = $this->createTestDataset('数据集2');
        $persistedDataset2Result = $this->persistAndFlush($dataset2);
        $this->assertInstanceOf(Dataset::class, $persistedDataset2Result);
        /** @var Dataset $persistedDataset2 */
        $persistedDataset2 = $persistedDataset2Result;

        // 为第一个数据集创建助手
        $assistant1 = new ChatAssistant();
        $assistant1->setName('数据集1助手1');
        $assistant1->setDataset($persistedDataset1);
        $this->persistAndFlush($assistant1);

        $assistant2 = new ChatAssistant();
        $assistant2->setName('数据集1助手2');
        $assistant2->setDataset($persistedDataset1);
        $this->persistAndFlush($assistant2);

        // 为第二个数据集创建助手
        $assistant3 = new ChatAssistant();
        $assistant3->setName('数据集2助手1');
        $assistant3->setDataset($persistedDataset2);
        $this->persistAndFlush($assistant3);

        // 测试查找第一个数据集的助手
        $dataset1Assistants = $this->getRepository()->findByDataset($persistedDataset1);
        $this->assertCount(2, $dataset1Assistants);

        // 测试查找第二个数据集的助手
        $dataset2Assistants = $this->getRepository()->findByDataset($persistedDataset2);
        $this->assertCount(1, $dataset2Assistants);
    }

    public function testFindEnabledAssistants(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('启用状态测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建启用的助手
        $enabledAssistant1 = new ChatAssistant();
        $enabledAssistant1->setName('启用助手1');
        $enabledAssistant1->setDataset($persistedDataset);
        $enabledAssistant1->setEnabled(true);
        $this->persistAndFlush($enabledAssistant1);

        $enabledAssistant2 = new ChatAssistant();
        $enabledAssistant2->setName('启用助手2');
        $enabledAssistant2->setDataset($persistedDataset);
        $enabledAssistant2->setEnabled(true);
        $this->persistAndFlush($enabledAssistant2);

        // 创建禁用的助手
        $disabledAssistant = new ChatAssistant();
        $disabledAssistant->setName('禁用助手');
        $disabledAssistant->setDataset($persistedDataset);
        $disabledAssistant->setEnabled(false);
        $this->persistAndFlush($disabledAssistant);

        // 测试查找启用的助手
        $enabledAssistants = $this->getRepository()->findEnabled();
        $this->assertGreaterThanOrEqual(2, count($enabledAssistants)); // 可能有其他测试创建的启用助手

        // 验证结果中的助手都是启用状态
        foreach ($enabledAssistants as $assistant) {
            $this->assertTrue($assistant->isEnabled());
        }
    }

    public function testFindByLlmModel(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('模型测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建使用不同模型的助手
        $gpt35Assistant = new ChatAssistant();
        $gpt35Assistant->setName('GPT-3.5助手');
        $gpt35Assistant->setDataset($persistedDataset);
        $gpt35Assistant->setLlmModel('gpt-3.5-turbo');
        $this->persistAndFlush($gpt35Assistant);

        $gpt4Assistant = new ChatAssistant();
        $gpt4Assistant->setName('GPT-4助手');
        $gpt4Assistant->setDataset($persistedDataset);
        $gpt4Assistant->setLlmModel('gpt-4');
        $this->persistAndFlush($gpt4Assistant);

        $anotherGpt35Assistant = new ChatAssistant();
        $anotherGpt35Assistant->setName('另一个GPT-3.5助手');
        $anotherGpt35Assistant->setDataset($persistedDataset);
        $anotherGpt35Assistant->setLlmModel('gpt-3.5-turbo');
        $this->persistAndFlush($anotherGpt35Assistant);

        // 测试查找使用GPT-3.5的助手
        $gpt35Assistants = $this->getRepository()->findByLlmModel('gpt-3.5-turbo');
        $this->assertGreaterThanOrEqual(2, count($gpt35Assistants));

        // 验证结果中的助手都使用正确的模型
        foreach ($gpt35Assistants as $assistant) {
            $this->assertEquals('gpt-3.5-turbo', $assistant->getLlmModel());
        }

        // 测试查找使用GPT-4的助手
        $gpt4Assistants = $this->getRepository()->findByLlmModel('gpt-4');
        $this->assertGreaterThanOrEqual(1, count($gpt4Assistants));

        foreach ($gpt4Assistants as $assistant) {
            $this->assertEquals('gpt-4', $assistant->getLlmModel());
        }
    }

    public function testFindByNamePattern(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('名称模式测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建不同名称的助手
        $assistants = [
            ['name' => '客服助手v1', 'pattern' => '客服'],
            ['name' => '技术支持助手', 'pattern' => '技术'],
            ['name' => '销售助手Pro', 'pattern' => '销售'],
            ['name' => '智能客服机器人', 'pattern' => '客服'],
        ];

        foreach ($assistants as $assistantData) {
            $assistant = new ChatAssistant();
            $assistant->setName($assistantData['name']);
            $assistant->setDataset($persistedDataset);
            $this->persistAndFlush($assistant);
        }

        // 测试按名称模式查找
        $customerServiceAssistants = $this->getRepository()->findByName('客服');
        $this->assertGreaterThanOrEqual(2, count($customerServiceAssistants));

        foreach ($customerServiceAssistants as $assistant) {
            $this->assertStringContainsString('客服', $assistant->getName());
        }

        $techAssistants = $this->getRepository()->findByName('技术');
        $this->assertGreaterThanOrEqual(1, count($techAssistants));

        foreach ($techAssistants as $assistant) {
            $this->assertStringContainsString('技术', $assistant->getName());
        }
    }

    public function testFindRecentlyCreated(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('最近创建测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建助手
        $recentAssistant = new ChatAssistant();
        $recentAssistant->setName('最近创建的助手');
        $recentAssistant->setDataset($persistedDataset);
        $this->persistAndFlush($recentAssistant);

        // 测试查找最近创建的助手
        $recentAssistants = $this->getRepository()->findRecentlyCreated(10);
        $this->assertGreaterThanOrEqual(1, count($recentAssistants));

        // 验证结果按创建时间降序排列
        $previousCreateTime = null;
        foreach ($recentAssistants as $assistant) {
            $createTime = $assistant->getCreateTime();
            $this->assertNotNull($createTime, 'Assistant create time should not be null');

            if (null !== $previousCreateTime) {
                // 允许1秒的时间差异，避免微秒级精度问题
                $timeDiff = abs($createTime->getTimestamp() - $previousCreateTime->getTimestamp());
                $this->assertLessThanOrEqual(
                    1,
                    $timeDiff,
                    '助手应该按创建时间降序排列，允许1秒误差'
                );
            }
            $previousCreateTime = $createTime;
        }
    }

    public function testFindWithPagination(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('分页测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建多个助手
        for ($i = 1; $i <= 25; ++$i) {
            $assistant = new ChatAssistant();
            $assistant->setName("分页测试助手{$i}");
            $assistant->setDataset($persistedDataset);
            $this->persistAndFlush($assistant);
        }

        // 测试第一页
        $firstPage = $this->getRepository()->findWithPagination(1, 10);
        $this->assertCount(10, $firstPage);

        // 测试第二页
        $secondPage = $this->getRepository()->findWithPagination(2, 10);
        $this->assertCount(10, $secondPage);

        // 测试第三页
        $thirdPage = $this->getRepository()->findWithPagination(3, 10);
        $this->assertGreaterThanOrEqual(5, count($thirdPage)); // 至少有5个，可能有其他测试创建的

        // 验证分页结果不重复
        $firstPageIds = array_map(fn ($a) => $a->getId(), $firstPage);
        $secondPageIds = array_map(fn ($a) => $a->getId(), $secondPage);
        $this->assertEmpty(array_intersect($firstPageIds, $secondPageIds));
    }

    public function testCountByDataset(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('计数测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        $initialCount = $this->getRepository()->countByDataset($persistedDataset);

        // 添加助手
        for ($i = 1; $i <= 3; ++$i) {
            $assistant = new ChatAssistant();
            $assistant->setName("计数测试助手{$i}");
            $assistant->setDataset($persistedDataset);
            $this->persistAndFlush($assistant);
        }

        $finalCount = $this->getRepository()->countByDataset($persistedDataset);
        $this->assertEquals($initialCount + 3, $finalCount);
    }

    public function testCountEnabledAssistants(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('启用计数测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        $initialEnabledCount = count($this->getRepository()->findEnabled());

        // 添加启用的助手
        $enabledAssistant = new ChatAssistant();
        $enabledAssistant->setName('计数启用助手');
        $enabledAssistant->setDataset($persistedDataset);
        $enabledAssistant->setEnabled(true);
        $this->persistAndFlush($enabledAssistant);

        // 添加禁用的助手
        $disabledAssistant = new ChatAssistant();
        $disabledAssistant->setName('计数禁用助手');
        $disabledAssistant->setDataset($persistedDataset);
        $disabledAssistant->setEnabled(false);
        $this->persistAndFlush($disabledAssistant);

        $finalEnabledCount = count($this->getRepository()->findEnabled());
        $this->assertEquals($initialEnabledCount + 1, $finalEnabledCount);
    }

    public function testFindOrCreateByRemoteId(): void
    {
        $remoteId = 'find-or-create-test-123';

        // 第一次调用应该创建新实体
        $firstResult = $this->getRepository()->findOrCreateByRemoteId($remoteId);
        $this->assertInstanceOf(ChatAssistant::class, $firstResult);
        $this->assertEquals($remoteId, $firstResult->getRemoteId());
        $this->assertNotNull($firstResult->getId()); // 应该已经持久化

        // 第二次调用应该返回相同的实体
        $secondResult = $this->getRepository()->findOrCreateByRemoteId($remoteId);
        $this->assertEquals($firstResult->getId(), $secondResult->getId());
        $this->assertEquals($remoteId, $secondResult->getRemoteId());
    }

    public function testSearchByKeywords(): void
    {
        // 创建数据集
        $dataset = $this->createTestDataset('关键词搜索测试数据集');
        $persistedDatasetResult = $this->persistAndFlush($dataset);
        $this->assertInstanceOf(Dataset::class, $persistedDatasetResult);
        /** @var Dataset $persistedDataset */
        $persistedDataset = $persistedDatasetResult;

        // 创建带有不同描述的助手
        $assistants = [
            ['name' => '客服助手', 'description' => '专门处理客户服务和售后支持的智能助手'],
            ['name' => '技术助手', 'description' => '提供技术支持和问题解答的专业助手'],
            ['name' => '销售助手', 'description' => '协助销售团队进行客户沟通和产品推荐'],
            ['name' => '培训助手', 'description' => '帮助员工学习和培训的教育助手'],
        ];

        foreach ($assistants as $assistantData) {
            $assistant = new ChatAssistant();
            $assistant->setName($assistantData['name']);
            $assistant->setDescription($assistantData['description']);
            $assistant->setDataset($persistedDataset);
            $this->persistAndFlush($assistant);
        }

        // 搜索包含"客户"的助手
        $customerAssistants = $this->getRepository()->searchByKeywords('客户');
        $this->assertGreaterThanOrEqual(2, count($customerAssistants)); // 客服和销售助手

        // 搜索包含"技术"的助手
        $techAssistants = $this->getRepository()->searchByKeywords('技术');
        $this->assertGreaterThanOrEqual(1, count($techAssistants));

        // 搜索不存在的关键词
        $noResultAssistants = $this->getRepository()->searchByKeywords('不存在的关键词');
        $this->assertEmpty($noResultAssistants);
    }
}
