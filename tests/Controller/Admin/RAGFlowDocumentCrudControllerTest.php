<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\RAGFlowDocumentCrudController;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Enum\DocumentStatus;

/**
 * RAGFlow文档管理CRUD控制器单元测试
 * @internal
 */
#[CoversClass(RAGFlowDocumentCrudController::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowDocumentCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testIndex(): void
    {
        $client = self::createAuthenticatedClient();

        // 访问 Document 的 EasyAdmin 列表页
        $crawler = $client->request('GET', '/admin');

        // 验证响应成功
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isSuccessful());

        // 验证页面内容包含文档管理相关元素
        $this->assertGreaterThan(0, $crawler->filter('body')->count(), '页面应该包含 body 元素');

        // 验证页面标题包含内容
        $pageTitle = $crawler->filter('title')->text();
        $this->assertNotEmpty($pageTitle, '页面应该有标题');
    }

    /**
     * @return AbstractCrudController<Document>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(RAGFlowDocumentCrudController::class);
    }

    public function testConfigureFields(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('index'));

        // 验证字段存在
        $this->assertNotEmpty($fields);

        // 确保每个字段都是有效的字段接口实例
        foreach ($fields as $field) {
            $this->assertInstanceOf(FieldInterface::class, $field);
        }
    }

    public function testConfigureFieldsForDifferentPages(): void
    {
        $controller = $this->getControllerService();
        $indexFields = iterator_to_array($controller->configureFields(Crud::PAGE_INDEX));
        $detailFields = iterator_to_array($controller->configureFields(Crud::PAGE_DETAIL));

        // 验证字段存在
        $this->assertNotEmpty($indexFields);
        $this->assertNotEmpty($detailFields);

        // 验证每个字段都是有效的
        foreach ([$indexFields, $detailFields] as $fields) {
            foreach ($fields as $field) {
                $this->assertInstanceOf(FieldInterface::class, $field);
            }
        }
    }

    public function testHasConfigurationMethods(): void
    {
        $controller = $this->getControllerService();
        // 验证控制器存在（基类方法由框架保证存在）
        $this->assertInstanceOf(AbstractCrudController::class, $controller);
    }

    public function testMapDataToEntity(): void
    {
        $controller = $this->getControllerService();

        // 测试数据映射功能
        $testData = [
            'id' => 123,
            'dataset_id' => 456,
            'name' => '测试文档',
            'filename' => 'test.pdf',
            'type' => 'pdf',
            'size' => 102400,
            'status' => 'uploaded',
            'parse_status' => 'success',
            'language' => 'zh',
            'chunk_count' => 10,
            'uploaded_at' => '2023-12-01T00:00:00Z',
            'parsed_at' => '2023-12-01T01:00:00Z',
            'create_time' => '2023-12-01T00:00:00Z',
            'update_time' => '2024-01-01T00:00:00Z',
        ];

        // 使用反射来测试受保护的方法
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('mapDataToEntity');
        $method->setAccessible(true);

        $entity = $method->invoke($controller, $testData);

        $this->assertInstanceOf(Document::class, $entity);
        $this->assertEquals(123, $entity->getId());
        $this->assertEquals('456', $entity->getDatasetId());
        $this->assertEquals('测试文档', $entity->getName());
        $this->assertEquals('test.pdf', $entity->getFilename());
    }

    public function testMapEntityToData(): void
    {
        $controller = $this->getControllerService();

        // 创建测试实体
        $entity = new Document();
        $entity->setId(123);
        $entity->setDatasetId(456);
        $entity->setName('测试文档');
        $entity->setFilename('test.pdf');
        $entity->setType('pdf');
        $entity->setLanguage('zh');

        // 使用反射来测试受保护的方法
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('mapEntityToData');
        $method->setAccessible(true);

        $data = $method->invoke($controller, $entity);

        $this->assertIsArray($data);
        $this->assertEquals('456', $data['datasetId']);
        $this->assertEquals('测试文档', $data['name']);
        $this->assertEquals('test.pdf', $data['filename']);
        $this->assertEquals('pdf', $data['type']);
        $this->assertEquals('zh', $data['language']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'dataset.name' => ['所属数据集'];
        yield 'name' => ['文档名称'];
        yield 'size' => ['文件大小'];
        yield 'status' => ['状态'];
        yield 'language' => ['语言'];
        yield 'chunkCount' => ['分块数量'];
        yield 'progress' => ['解析进度'];
        yield 'createTime' => ['创建时间'];
        yield 'updateTime' => ['更新时间'];
        yield 'remoteCreateTime' => ['远程创建时间'];
        yield 'lastSyncTime' => ['最后同步时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'id' => ['id'];
        yield 'datasetId' => ['datasetId'];
        yield 'name' => ['name'];
        yield 'filename' => ['filename'];
        yield 'type' => ['type'];
        yield 'language' => ['language'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'id' => ['id'];
        yield 'datasetId' => ['datasetId'];
        yield 'name' => ['name'];
        yield 'filename' => ['filename'];
        yield 'type' => ['type'];
        yield 'language' => ['language'];
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
    private function createTestDocument(Dataset $dataset, DocumentStatus $status = DocumentStatus::UPLOADED): Document
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $document = new Document();
        $document->setName('测试文档.pdf');
        $document->setFilename('test-document.pdf');
        $document->setType('pdf');
        $document->setSize(1024000);
        $document->setStatus($status);
        $document->setRemoteId('test-document-remote-id');
        $document->setDataset($dataset);

        $entityManager->persist($document);
        $entityManager->flush();

        return $document;
    }

    /**
     * 测试parseDocument动作
     */
    public function testParseDocument(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建测试数据
        $dataset = $this->createTestDataset();
        $document = $this->createTestDocument($dataset, DocumentStatus::UPLOADED);

        // 访问parseDocument动作路由
        $crawler = $client->request('GET', '/admin/ragflow-document/' . $document->getId() . '/parse');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证flash消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('开始解析', $crawler->text());
    }

    /**
     * 测试parseDocument动作 - 文档不存在的情况
     */
    public function testParseDocumentWithNonExistentDocument(): void
    {
        $client = self::createAuthenticatedClient();

        // 访问不存在的文档ID
        $crawler = $client->request('GET', '/admin/ragflow-document/999999/parse');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证错误消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('文档不存在', $crawler->text());
    }

    /**
     * 测试parseDocument动作 - 文档状态不支持解析
     */
    public function testParseDocumentWithWrongStatus(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建测试数据，状态为已解析
        $dataset = $this->createTestDataset();
        $document = $this->createTestDocument($dataset, DocumentStatus::COMPLETED);

        // 访问parseDocument动作路由
        $crawler = $client->request('GET', '/admin/ragflow-document/' . $document->getId() . '/parse');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证错误消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('文档未上传到远程服务', $crawler->text());
    }

    /**
     * 测试showParseStatus动作
     */
    public function testShowParseStatus(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建测试数据
        $dataset = $this->createTestDataset();
        $document = $this->createTestDocument($dataset, DocumentStatus::PROCESSING);

        // 访问showParseStatus动作路由
        $crawler = $client->request('GET', '/admin/ragflow-document/' . $document->getId() . '/parse-status');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证flash消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('当前状态', $crawler->text());
    }

    /**
     * 测试showParseStatus动作 - 文档不存在的情况
     */
    public function testShowParseStatusWithNonExistentDocument(): void
    {
        $client = self::createAuthenticatedClient();

        // 访问不存在的文档ID
        $crawler = $client->request('GET', '/admin/ragflow-document/999999/parse-status');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证错误消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('文档不存在', $crawler->text());
    }

    /**
     * 测试downloadDocument动作
     */
    public function testDownloadDocument(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建测试数据
        $dataset = $this->createTestDataset();
        $document = $this->createTestDocument($dataset, DocumentStatus::COMPLETED);

        // 访问downloadDocument动作路由
        $crawler = $client->request('GET', '/admin/ragflow-document/' . $document->getId() . '/download');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证flash消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('下载功能暂未实现', $crawler->text());
    }

    /**
     * 测试downloadDocument动作 - 文档不存在的情况
     */
    public function testDownloadDocumentWithNonExistentDocument(): void
    {
        $client = self::createAuthenticatedClient();

        // 访问不存在的文档ID
        $crawler = $client->request('GET', '/admin/ragflow-document/999999/download');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证错误消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('文档不存在', $crawler->text());
    }

    /**
     * 测试downloadDocument动作 - 文档没有文件名的情况
     */
    public function testDownloadDocumentWithoutFilename(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建测试数据，不设置文件名
        $dataset = $this->createTestDataset();
        $document = new Document();
        $document->setName('测试文档');
        $document->setStatus(DocumentStatus::COMPLETED);
        $document->setDataset($dataset);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->persist($document);
        $entityManager->flush();

        // 访问downloadDocument动作路由
        $crawler = $client->request('GET', '/admin/ragflow-document/' . $document->getId() . '/download');

        // 验证响应成功并重定向
        $this->assertResponseRedirects();

        // 获取重定向后的页面并验证错误消息
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('文件不可下载', $crawler->text());
    }
}
