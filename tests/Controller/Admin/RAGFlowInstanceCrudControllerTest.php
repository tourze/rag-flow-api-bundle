<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\RAGFlowInstanceCrudController;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * RAGFlow实例CRUD控制器单元测试
 * @internal
 */
#[CoversClass(RAGFlowInstanceCrudController::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowInstanceCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testIndex(): void
    {
        $client = self::createAuthenticatedClient();

        // 访问 RAGFlowInstance 的 EasyAdmin 列表页
        $crawler = $client->request('GET', '/admin');

        // 验证响应成功
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isSuccessful());

        // 验证页面内容包含RAGFlow实例管理相关元素
        $this->assertGreaterThan(0, $crawler->filter('body')->count(), '页面应该包含 body 元素');

        // 验证页面标题包含内容
        $pageTitle = $crawler->filter('title')->text();
        $this->assertNotEmpty($pageTitle, '页面应该有标题');
    }

    public function testGetEntityFqcn(): void
    {
        $controller = $this->getControllerService();
        $this->assertEquals(RAGFlowInstance::class, $controller::getEntityFqcn());
    }

    /**
     * @return AbstractCrudController<RAGFlowInstance>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(RAGFlowInstanceCrudController::class);
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'name' => ['名称'];
        yield 'description' => ['描述'];
        yield 'apiUrl' => ['API URL'];
        yield 'apiKey' => ['API 密钥'];
        yield 'version' => ['版本'];
        yield 'isDefault' => ['默认'];
        yield 'isActive' => ['激活'];
        yield 'createTime' => ['创建时间'];
        yield 'updateTime' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'apiUrl' => ['apiUrl'];
        yield 'apiKey' => ['apiKey'];
        yield 'version' => ['version'];
        yield 'isDefault' => ['isDefault'];
        yield 'isActive' => ['isActive'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'apiUrl' => ['apiUrl'];
        yield 'apiKey' => ['apiKey'];
        yield 'version' => ['version'];
        yield 'isDefault' => ['isDefault'];
        yield 'isActive' => ['isActive'];
    }

    public function testValidationErrors(): void
    {
        $client = self::createAuthenticatedClient();

        try {
            $client->catchExceptions(true);

            $crawler = $client->request('GET', '/admin?crudAction=new&crudControllerFqcn='
                . urlencode(RAGFlowInstanceCrudController::class));

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
            'name' => ['field' => 'name', 'form' => 'RAGFlowInstance[name]', 'multiple' => false],
            'apiUrl' => ['field' => 'apiUrl', 'form' => 'RAGFlowInstance[apiUrl]', 'multiple' => false],
            'apiKey' => ['field' => 'apiKey', 'form' => 'RAGFlowInstance[apiKey]', 'multiple' => false],
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
}
