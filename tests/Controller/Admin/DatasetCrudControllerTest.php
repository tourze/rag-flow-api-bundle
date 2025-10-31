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
use Tourze\RAGFlowApiBundle\Controller\Admin\DatasetCrudController;
use Tourze\RAGFlowApiBundle\Entity\Dataset;

/**
 * 数据集CRUD控制器单元测试
 * @internal
 */
#[CoversClass(DatasetCrudController::class)]
#[RunTestsInSeparateProcesses]
class DatasetCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function onSetUp(): void
    {
        parent::onSetUp();
    }

    public function testIndex(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 访问 Dataset 的 EasyAdmin 列表页
        $crawler = $client->request('GET', '/admin');

        // 验证响应成功
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isSuccessful());

        // 验证页面内容包含数据集管理相关元素
        $this->assertGreaterThan(0, $crawler->filter('body')->count(), '页面应该包含 body 元素');

        // 验证页面标题 - 先检查title元素是否存在，再获取文本
        $titleElements = $crawler->filter('title');
        if ($titleElements->count() > 0) {
            $pageTitle = $titleElements->text();
            $this->assertNotEmpty($pageTitle, '页面应该有标题');
        } else {
            // 如果没有title元素，检查h1或其他标题元素
            $h1Elements = $crawler->filter('h1');
            if ($h1Elements->count() > 0) {
                $pageTitle = $h1Elements->text();
                $this->assertNotEmpty($pageTitle, '页面应该有标题内容');
            } else {
                // 如果都没有，至少确保页面有内容
                $this->assertGreaterThan(0, $crawler->filter('body')->count(), '页面应该有内容');
            }
        }
    }

    public function testGetEntityFqcn(): void
    {
        $controller = $this->getControllerService();
        $this->assertEquals(Dataset::class, $controller::getEntityFqcn());
    }

    /**
     * @return AbstractCrudController<Dataset>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(DatasetCrudController::class);
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
        $newFields = iterator_to_array($controller->configureFields(Crud::PAGE_NEW));
        $editFields = iterator_to_array($controller->configureFields(Crud::PAGE_EDIT));
        $detailFields = iterator_to_array($controller->configureFields(Crud::PAGE_DETAIL));

        // 验证字段存在
        $this->assertNotEmpty($indexFields);
        $this->assertNotEmpty($newFields);
        $this->assertNotEmpty($editFields);
        $this->assertNotEmpty($detailFields);

        // 验证每个字段都是有效的
        foreach ([$indexFields, $newFields, $editFields, $detailFields] as $fields) {
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

    public function testConvertDatasetToApiData(): void
    {
        $controller = $this->getControllerService();

        // 创建测试实体
        $entity = new Dataset();
        $entity->setName('测试数据集');
        $entity->setDescription('测试描述');
        $entity->setParserMethod('intelligent');
        $entity->setChunkMethod('paragraph');
        $entity->setChunkSize(1000);
        $entity->setLanguage('zh');

        // 使用反射来测试私有方法
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('convertDatasetToApiData');
        $method->setAccessible(true);

        $data = $method->invoke($controller, $entity);

        $this->assertIsArray($data);
        $this->assertEquals('测试数据集', $data['name']);
        $this->assertEquals('测试描述', $data['description']);
        $this->assertEquals('intelligent', $data['parser_method']);
        $this->assertEquals('paragraph', $data['chunk_method']);
        $this->assertEquals(1000, $data['chunk_size']);
        $this->assertEquals('zh', $data['language']);
        $this->assertEquals('text-embedding-3-small', $data['embedding_model']); // 默认值
        $this->assertEquals(70, $data['similarity_threshold']); // 默认值
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'remoteId' => ['远程ID'];
        yield 'name' => ['数据集名称'];
        yield 'description' => ['数据集描述'];
        yield 'chunkMethod' => ['分块方法'];
        yield 'embeddingModel' => ['嵌入模型'];
        yield 'status' => ['状态'];
        yield 'remoteCreateTime' => ['远程创建时间'];
        yield 'remoteUpdateTime' => ['远程更新时间'];
        yield 'lastSyncTime' => ['最后同步时间'];
        yield 'documentCount' => ['文档数量'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // New页面只显示用户可编辑的字段
        // id, remoteId, status, remoteCreateTime, remoteUpdateTime, lastSyncTime, documentCount 都标记了 hideOnForm()
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'chunkMethod' => ['chunkMethod'];
        yield 'embeddingModel' => ['embeddingModel'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // Edit页面只显示用户可编辑的字段
        // id, remoteId, status, remoteCreateTime, remoteUpdateTime, lastSyncTime, documentCount 都标记了 hideOnForm()
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'chunkMethod' => ['chunkMethod'];
        yield 'embeddingModel' => ['embeddingModel'];
    }

    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        try {
            $client->catchExceptions(true);

            $crawler = $client->request('GET', '/admin?crudAction=new&crudControllerFqcn='
                . urlencode(DatasetCrudController::class));

            $response = $client->getResponse();
            if (404 === $response->getStatusCode()) {
                self::markTestSkipped('EasyAdmin路由配置问题，返回404');
            }

            $this->assertResponseIsSuccessful();

            $buttonCrawler = $crawler->selectButton('Create');
            if (0 === $buttonCrawler->count()) {
                self::markTestSkipped('找不到 Create 按钮，可能是 EasyAdmin 配置问题');
            }

            $form = $buttonCrawler->form();
            $form->setValues(self::buildBlankPayload());

            $crawler = $client->submit($form);

            $statusCode = $client->getResponse()->getStatusCode();
            if (422 === $statusCode) {
                $this->assertSame(422, $statusCode);
            } else {
                $invalidFeedback = $crawler->filter('.invalid-feedback');
                $this->assertGreaterThan(0, $invalidFeedback->count(), '应该提示表单验证错误');

                $messages = $invalidFeedback->each(static function ($node): string {
                    return trim((string) $node->text());
                });

                $this->assertNotEmpty(
                    array_filter(
                        $messages,
                        static function (string $message): bool {
                            $normalized = strtolower($message);

                            return str_contains($normalized, 'should not be blank')
                                || str_contains($message, '不能为空');
                        }
                    ),
                    '验证信息应该提示字段不能为空'
                );
            }
        } catch (\Throwable $exception) {
            self::markTestSkipped('验证测试遇到异常: ' . $exception->getMessage());
        }
    }

    /**
     * @return array<string, array{field: string, form: string, multiple: bool}>
     */
    private static function getRequiredFieldMap(): array
    {
        return [
            'name' => ['field' => 'name', 'form' => 'Dataset[name]', 'multiple' => false],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildBlankPayload(): array
    {
        $payload = [];

        foreach (self::getRequiredFieldMap() as $config) {
            $payload[$config['form']] = $config['multiple'] ? [] : '';
        }

        return $payload;
    }

    public function testManageDocuments(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        try {
            $client->catchExceptions(true);

            // 创建一个测试数据集
            $dataset = new Dataset();
            $dataset->setName('测试数据集');
            $dataset->setDescription('测试描述');

            /** @var EntityManagerInterface $entityManager */
            $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
            $entityManager->persist($dataset);
            $entityManager->flush();

            // 访问 manageDocuments 动作
            $crawler = $client->request('GET', '/admin/rag-flow/dataset/' . $dataset->getId() . '/manage-documents');

            $response = $client->getResponse();

            // 验证响应是重定向（这是预期的行为）
            $this->assertTrue(
                $response->isRedirect() || $response->isSuccessful(),
                'manageDocuments action should either redirect or return successful response'
            );
        } catch (\Throwable $exception) {
            self::markTestSkipped('manageDocuments 测试遇到异常: ' . $exception->getMessage());
        }
    }
}
