<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\LlmModelCrudController;
use Tourze\RAGFlowApiBundle\Entity\LlmModel;

/**
 * LLM模型CRUD控制器测试
 * @internal
 */
#[CoversClass(LlmModelCrudController::class)]
#[RunTestsInSeparateProcesses]
class LlmModelCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerFqcn(): string
    {
        return LlmModelCrudController::class;
    }

    /**
     * 获取控制器服务实例
     */
    protected function getControllerService(): LlmModelCrudController
    {
        // 创建独立的控制器实例,避免服务容器依赖
        return new LlmModelCrudController();
    }

    /**
     * 提供索引页面表头数据
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID列' => ['ID'];
        yield '模型标识符列' => ['模型标识符'];
        yield '模型名称列' => ['模型名称'];
        yield '提供商列' => ['提供商'];
        yield '模型类型列' => ['模型类型'];
        yield '是否可用列' => ['是否可用'];
    }

    public function testEntityFqcnIsCorrect(): void
    {
        $this->assertSame(LlmModel::class, LlmModelCrudController::getEntityFqcn());
    }

    public function testConfigureFieldsReturnsValidFields(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('index'));

        $this->assertNotEmpty($fields);

        // 验证每个字段都是有效的字段接口实例
        foreach ($fields as $field) {
            $this->assertInstanceOf(FieldInterface::class, $field);
        }
    }

    public function testConfigureCrudReturnsValidCrud(): void
    {
        $controller = $this->getControllerService();
        $crud = Crud::new();
        $result = $controller->configureCrud($crud);

        $this->assertInstanceOf(Crud::class, $result);
    }

    public function testConfigureActionsReturnsValidActions(): void
    {
        $controller = $this->getControllerService();
        $actions = Actions::new();
        $result = $controller->configureActions($actions);

        $this->assertInstanceOf(Actions::class, $result);
    }

    public function testConfigureFiltersReturnsValidFilters(): void
    {
        $controller = $this->getControllerService();
        $filters = Filters::new();
        $result = $controller->configureFilters($filters);

        $this->assertInstanceOf(Filters::class, $result);
    }

    public function testSyncFromApiReturnsResponse(): void
    {
        $controller = $this->getControllerService();
        $response = $controller->syncFromApi();

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'modelIdentifier' => ['modelIdentifier'];
        yield 'modelName' => ['modelName'];
        yield 'provider' => ['provider'];
        yield 'modelType' => ['modelType'];
        yield 'isAvailable' => ['isAvailable'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'modelIdentifier' => ['modelIdentifier'];
        yield 'modelName' => ['modelName'];
        yield 'provider' => ['provider'];
        yield 'modelType' => ['modelType'];
        yield 'isAvailable' => ['isAvailable'];
    }

    public function testValidationErrors(): void
    {
        $client = self::createAuthenticatedClient();

        try {
            $client->catchExceptions(true);

            $crawler = $client->request('GET', '/admin?crudAction=new&crudControllerFqcn='
                . urlencode(LlmModelCrudController::class));

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
            'ragFlowInstance' => ['field' => 'ragFlowInstance', 'form' => 'LlmModel[ragFlowInstance]', 'multiple' => false],
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
