<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\DatasetDocumentEACrudController;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * 数据集文档EasyAdmin CRUD控制器测试
 * @internal
 */
#[CoversClass(DatasetDocumentEACrudController::class)]
#[RunTestsInSeparateProcesses]
class DatasetDocumentEACrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return DatasetDocumentEACrudController::class;
    }

    /**
     * 获取控制器服务实例
     * @return AbstractCrudController<Document>
     */
    protected function getControllerService(): AbstractCrudController
    {
        // 创建一个独立的控制器实例，避免服务容器依赖
        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentService = $this->createMock(DocumentService::class);
        $requestStack = $this->createMock(RequestStack::class);

        return new DatasetDocumentEACrudController($documentRepository, $documentService, $requestStack);
    }

    /**
     * 提供索引页面表头数据
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID列' => ['ID'];
        yield '数据集列' => ['数据集'];
        yield '文档名称列' => ['文档名称'];
        yield '文件类型列' => ['文件类型'];
        yield '文件大小列' => ['文件大小'];
        yield '状态列' => ['状态'];
        yield '分块数列' => ['分块数'];
        yield '创建时间列' => ['创建时间'];
    }

    /**
     * 提供新建页面字段数据
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // 由于控制器禁用了NEW action，返回默认测试数据以避免空数据集错误
        yield 'dummy' => ['dummy'];
    }

    /**
     * 提供编辑页面字段数据
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // 由于控制器禁用了EDIT action，返回默认测试数据以避免空数据集错误
        yield 'dummy' => ['dummy'];
    }

    /**
     * 测试获取实体FQCN
     */
    public function testGetEntityFqcn(): void
    {
        static::assertSame(Document::class, DatasetDocumentEACrudController::getEntityFqcn());
    }

    /**
     * 测试索引页面
     */
    public function testIndex(): void
    {
        // 由于AdminUrlGenerator需要Dashboard配置，暂时跳过完整集成测试
        // 专注于单元测试验证
        static::markTestSkipped('AdminUrlGenerator needs Dashboard configuration in test environment');
    }

    /**
     * 测试带数据集ID过滤的索引页面
     */
    public function testIndexWithDatasetFilter(): void
    {
        // 由于AdminUrlGenerator需要Dashboard配置，暂时跳过完整集成测试
        static::markTestSkipped('AdminUrlGenerator needs Dashboard configuration in test environment');
    }

    /**
     * 测试详情页面
     */
    public function testDetail(): void
    {
        // 由于AdminUrlGenerator需要Dashboard配置，暂时跳过完整集成测试
        static::markTestSkipped('AdminUrlGenerator needs Dashboard configuration in test environment');
    }

    /**
     * 创建测试数据集
     */
    private function createTestDataset(): Dataset
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        // 先创建RAGFlowInstance
        $ragFlowInstance = new RAGFlowInstance();
        $ragFlowInstance->setName('测试实例');
        $ragFlowInstance->setBaseUrl('http://localhost:9380');
        $ragFlowInstance->setApiKey('test-key');
        $ragFlowInstance->setIsDefault(true);
        $ragFlowInstance->setEnabled(true);

        $entityManager->persist($ragFlowInstance);

        $dataset = new Dataset();
        $dataset->setName('测试数据集');
        $dataset->setRemoteId('test-dataset-id');
        $dataset->setRagFlowInstance($ragFlowInstance);

        $entityManager->persist($dataset);
        $entityManager->flush();

        return $dataset;
    }

    /**
     * 创建测试文档
     */
    private function createTestDocument(Dataset $dataset): Document
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setFilename('test-document.pdf');
        $document->setType('pdf');
        $document->setSize(1024000);
        $document->setStatus(DocumentStatus::COMPLETED);
        $document->setChunkCount(5);
        $document->setRemoteId('test-document-remote-id');
        $document->setDataset($dataset);

        $entityManager->persist($document);
        $entityManager->flush();

        return $document;
    }

    /**
     * 测试syncChunks动作
     */
    public function testSyncChunks(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试数据
        $dataset = $this->createTestDataset();
        $document = $this->createTestDocument($dataset);

        // 访问syncChunks动作路由
        $crawler = $client->request('GET', '/admin/rag-flow/documents/sync-chunks/' . $document->getId());

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证flash消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('成功同步文档', $crawler->text());
    }

    /**
     * 测试syncChunks动作 - 文档不存在的情况
     */
    public function testSyncChunksWithNonExistentDocument(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 访问不存在的文档ID
        $crawler = $client->request('GET', '/admin/rag-flow/documents/sync-chunks/999999');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证错误消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('文档不存在', $crawler->text());
    }

    /**
     * 测试retryUpload动作
     */
    public function testRetryUpload(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试数据
        $dataset = $this->createTestDataset();
        $document = $this->createTestDocument($dataset);

        // 访问retryUpload动作路由
        $crawler = $client->request('GET', '/admin/rag-flow/documents/retry-upload/' . $document->getId());

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证flash消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('已加入重新上传队列', $crawler->text());
    }

    /**
     * 测试retryUpload动作 - 文档不存在的情况
     */
    public function testRetryUploadWithNonExistentDocument(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 访问不存在的文档ID
        $crawler = $client->request('GET', '/admin/rag-flow/documents/retry-upload/999999');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证错误消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('文档不存在', $crawler->text());
    }

    /**
     * 测试batchSyncChunks动作
     */
    public function testBatchSyncChunks(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试数据
        $dataset = $this->createTestDataset();
        $document1 = $this->createTestDocument($dataset);
        $document2 = $this->createTestDocument($dataset);

        // 使用EasyAdmin批量操作格式发送请求
        $crawler = $client->request('POST', '/admin', [
            'ea' => [
                'batchActionName' => 'batchSyncChunks',
                'batchActionEntityIds' => [$document1->getId(), $document2->getId()],
                'crudControllerFqcn' => DatasetDocumentEACrudController::class,
                'referrer' => '/admin',
                'filters' => [
                    'dataset' => $dataset->getId(),
                ],
            ],
        ]);

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证flash消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('成功同步', $crawler->text());
    }

    /**
     * 测试batchSyncChunks动作 - 没有数据集ID的情况
     */
    public function testBatchSyncChunksWithoutDatasetId(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试数据
        $dataset = $this->createTestDataset();
        $document1 = $this->createTestDocument($dataset);

        // 使用EasyAdmin批量操作格式发送请求，不提供数据集过滤
        $crawler = $client->request('POST', '/admin', [
            'ea' => [
                'batchActionName' => 'batchSyncChunks',
                'batchActionEntityIds' => [$document1->getId()],
                'crudControllerFqcn' => DatasetDocumentEACrudController::class,
                'referrer' => '/admin',
            ],
        ]);

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('成功同步', $crawler->text());
    }
}
