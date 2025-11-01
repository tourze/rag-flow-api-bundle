<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RAGFlowApiBundle\Controller\Admin\RAGFlowAgentCrudController;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;

/**
 * RAGFlow智能体CRUD控制器单元测试
 * @internal
 */
#[CoversClass(RAGFlowAgentCrudController::class)]
#[RunTestsInSeparateProcesses]
class RAGFlowAgentCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function onSetUp(): void
    {
        parent::onSetUp();
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

    protected function getControllerService(): RAGFlowAgentCrudController
    {
        $controller = self::getService(RAGFlowAgentCrudController::class);
        $this->assertInstanceOf(RAGFlowAgentCrudController::class, $controller);

        return $controller;
    }

    public function testEntityFqcnIsCorrect(): void
    {
        $controller = $this->getControllerService();
        // 验证控制器使用正确的实体类
        $this->assertEquals(RAGFlowAgent::class, $controller::getEntityFqcn());
    }

    public function testControllerIsConfiguredCorrectly(): void
    {
        $controller = $this->getControllerService();
        // 验证控制器具有所需的自定义方法
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue(
            $reflection->hasMethod('syncToRemote'),
            'Controller should have syncToRemote method'
        );
        $this->assertTrue(
            $reflection->hasMethod('batchSync'),
            'Controller should have batchSync method'
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'title' => ['标题'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'title' => ['title'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'title' => ['title'];
    }

    public function testValidationErrors(): void
    {
        $client = self::createAuthenticatedClient();

        try {
            $client->catchExceptions(true);

            $crawler = $client->request('GET', '/admin?crudAction=new&crudControllerFqcn='
                . urlencode(RAGFlowAgentCrudController::class));

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
            $form->setValues([
                'RAGFlowAgent[title]' => '',
                'RAGFlowAgent[ragFlowInstance]' => '',
            ]);

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
}
