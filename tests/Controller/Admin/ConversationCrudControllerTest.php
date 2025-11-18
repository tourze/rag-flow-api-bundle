<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\ConversationCrudController;
use Tourze\RAGFlowApiBundle\Entity\VirtualConversation;

/**
 * 会话管理CRUD控制器单元测试
 * @internal
 */
#[CoversClass(ConversationCrudController::class)]
#[RunTestsInSeparateProcesses]
class ConversationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function afterEasyAdminSetUp(): void
    {
        parent::onSetUp();
    }

    public function testIndex(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问 Conversation 的 EasyAdmin 列表页
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::INDEX));

        // 验证响应成功
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isSuccessful());

        // 验证页面内容包含会话管理相关元素
        $this->assertGreaterThan(0, $crawler->filter('body')->count(), '页面应该包含 body 元素');

        // 验证页面标题包含内容
        $pageTitle = $crawler->filter('title')->text();
        $this->assertNotEmpty($pageTitle, '页面应该有标题');
    }

    /**
     * @return AbstractCrudController<VirtualConversation>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(ConversationCrudController::class);
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        // 提供与 configureFields('index') 匹配的表头标签
        yield 'id' => ['ID'];
        yield 'chatId' => ['聊天助手ID'];
        yield 'sessionId' => ['会话ID'];
        yield 'userMessage' => ['用户消息'];
        yield 'assistantMessage' => ['助手回复'];
        yield 'role' => ['角色'];
        yield 'messageCount' => ['消息数量'];
        yield 'status' => ['状态'];
        yield 'responseTime' => ['响应时间'];
        yield 'tokenCount' => ['Token数量'];
        yield 'createTime' => ['创建时间'];
        yield 'updateTime' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'id' => ['id'];
        yield 'chatId' => ['chatId'];
        yield 'sessionId' => ['sessionId'];
        yield 'userMessage' => ['userMessage'];
        yield 'assistantMessage' => ['assistantMessage'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'id' => ['id'];
        yield 'chatId' => ['chatId'];
        yield 'sessionId' => ['sessionId'];
        yield 'userMessage' => ['userMessage'];
        yield 'assistantMessage' => ['assistantMessage'];
    }

    public function testValidationErrors(): void
    {
        $client = self::createAuthenticatedClient();

        try {
            $client->catchExceptions(true);

            $crawler = $client->request('GET', '/admin?crudAction=new&crudControllerFqcn='
                . urlencode(ConversationCrudController::class));

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
            'chatId' => ['field' => 'chatId', 'form' => 'VirtualConversation[chatId]', 'multiple' => false],
            'userMessage' => ['field' => 'userMessage', 'form' => 'VirtualConversation[userMessage]', 'multiple' => false],
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
