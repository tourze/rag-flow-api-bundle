<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\RAGFlowDocumentCrudController;
use Tourze\RAGFlowApiBundle\Entity\Document;

/**
 * RAGFlow文档管理CRUD控制器单元测试
 * @internal
 */
#[CoversClass(RAGFlowDocumentCrudController::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowDocumentCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
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
     * 测试 parseDocument 动作存在
     *
     * 由于需要真实的 RAGFlow API 连接，只验证方法存在性
     *
     * @see RAGFlowDocumentCrudController::parseDocument()
     */
    public function testParseDocument(): void
    {
        $controller = $this->getControllerService();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue(
            $reflection->hasMethod('parseDocument'),
            'Controller should have parseDocument method'
        );

        $method = $reflection->getMethod('parseDocument');
        $this->assertTrue($method->isPublic(), 'parseDocument should be public');
    }

    /**
     * 测试 showParseStatus 动作存在
     *
     * 由于需要真实的 RAGFlow API 连接，只验证方法存在性
     *
     * @see RAGFlowDocumentCrudController::showParseStatus()
     */
    public function testShowParseStatus(): void
    {
        $controller = $this->getControllerService();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue(
            $reflection->hasMethod('showParseStatus'),
            'Controller should have showParseStatus method'
        );

        $method = $reflection->getMethod('showParseStatus');
        $this->assertTrue($method->isPublic(), 'showParseStatus should be public');
    }

    /**
     * 测试 downloadDocument 动作存在
     *
     * 由于需要真实的 RAGFlow API 连接，只验证方法存在性
     *
     * @see RAGFlowDocumentCrudController::downloadDocument()
     */
    public function testDownloadDocument(): void
    {
        $controller = $this->getControllerService();
        $reflection = new \ReflectionClass($controller);

        $this->assertTrue(
            $reflection->hasMethod('downloadDocument'),
            'Controller should have downloadDocument method'
        );

        $method = $reflection->getMethod('downloadDocument');
        $this->assertTrue($method->isPublic(), 'downloadDocument should be public');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'dataset.remoteId' => ['远程ID'];
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
     * 文档是从远程同步的，没有 New 页面
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'dummy' => ['dummy'];
    }

    /**
     * 文档是从远程同步的，没有 Edit 页面
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'dummy' => ['dummy'];
    }
}
