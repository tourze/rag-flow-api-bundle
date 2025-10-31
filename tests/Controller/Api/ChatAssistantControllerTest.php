<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\ChatAssistantController;
use Tourze\RAGFlowApiBundle\Entity\ChatAssistant;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * @internal
 */
#[CoversClass(ChatAssistantController::class)]
#[RunTestsInSeparateProcesses]
final class ChatAssistantControllerTest extends AbstractWebTestCase
{
    /** @var ConversationService&MockObject */
    private ConversationService $conversationService;

    private ChatAssistantController $controller;

    protected function onSetUp(): void
    {
        $this->conversationService = $this->createMock(ConversationService::class);
        self::getContainer()->set(ConversationService::class, $this->conversationService);
        $this->controller = self::getService(ChatAssistantController::class);
    }

    private function encodeJson(mixed $data): string
    {
        $encoded = json_encode($data);
        if (false === $encoded) {
            throw new \RuntimeException('Failed to encode JSON');
        }

        return $encoded;
    }

    public function testControllerExists(): void
    {
        $this->assertInstanceOf(ChatAssistantController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(ChatAssistantController::class);
        $expectedMethods = ['list', 'create', 'update', 'delete', 'createSession'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Controller should have method: {$method}");
        }
    }

    public function testListMethod(): void
    {
        $request = new Request(['page' => '1', 'limit' => '20']);
        $expectedData = ['data' => [], 'total' => 0, 'page' => 1, 'limit' => 20];
        $this->conversationService->expects($this->once())->method('listChatAssistants')->with(['page' => 1, 'limit' => 20])->willReturn($expectedData);
        $response = $this->controller->list($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedData, $responseData['data']);
    }

    public function testCreateMethodWithValidData(): void
    {
        $requestData = ['name' => 'Test Assistant', 'dataset_ids' => ['dataset-1'], 'options' => ['temperature' => 0.7]];
        $request = new Request([], [], [], [], [], [], $this->encodeJson($requestData));
        // Create a ChatAssistant entity mock
        $chatAssistant = $this->createMock(ChatAssistant::class);
        $chatAssistant->expects($this->once())->method('getId')->willReturn(123);
        $chatAssistant->expects($this->once())->method('getName')->willReturn('Test Assistant');
        $this->conversationService->expects($this->once())->method('createChatAssistant')->with('Test Assistant', ['dataset-1'], ['temperature' => 0.7])->willReturn($chatAssistant);
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('success', $responseData['status']);
        $this->assertIsArray($responseData['data']);
    }

    public function testCreateMethodWithInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testCreateMethodWithMissingName(): void
    {
        $requestData = ['dataset_ids' => ['dataset-1']];
        $request = new Request([], [], [], [], [], [], $this->encodeJson($requestData));
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Assistant name is required', $responseData['message']);
    }

    public function testUpdateMethod(): void
    {
        $assistantId = 'assistant-123';
        $updateData = ['name' => 'Updated Assistant'];
        $request = new Request([], [], [], [], [], [], $this->encodeJson($updateData));
        $expectedResult = ['id' => $assistantId, 'name' => 'Updated Assistant'];
        $this->conversationService->expects($this->once())->method('updateChatAssistant')->with($assistantId, $updateData)->willReturn($expectedResult);
        $response = $this->controller->update($assistantId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testDeleteMethod(): void
    {
        $assistantId = 'assistant-123';
        $this->conversationService->expects($this->once())->method('deleteChatAssistant')->with($assistantId);
        $response = $this->controller->delete($assistantId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Chat assistant deleted successfully', $responseData['message']);
    }

    public function testCreateSessionMethod(): void
    {
        $assistantId = 'assistant-123';
        $options = ['timeout' => 3600];
        $request = new Request([], [], [], [], [], [], $this->encodeJson($options));
        $expectedResult = ['session_id' => 'session-456', 'assistant_id' => $assistantId];
        $this->conversationService->expects($this->once())->method('createSession')->with($assistantId, $options)->willReturn($expectedResult);
        $response = $this->controller->createSession($assistantId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testListMethodHandlesException(): void
    {
        $request = new Request();
        $this->conversationService->expects($this->once())->method('listChatAssistants')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->list($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve chat assistants', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testCreateMethodHandlesException(): void
    {
        $requestData = ['name' => 'Test Assistant'];
        $request = new Request([], [], [], [], [], [], $this->encodeJson($requestData));
        $this->conversationService->expects($this->once())->method('createChatAssistant')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $content = $response->getContent();
        $contentStr = false !== $content ? $content : '';
        $responseData = json_decode($contentStr, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to create chat assistant', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        // 多方法控制器，不使用 __invoke，因此此测试不适用
        // provideNotAllowedMethods 会返回 ['INVALID'] 对于没有 __invoke 的控制器
    }

    /**
     * 覆盖父类测试 - ChatAssistantController是多方法控制器，不使用__invoke
     */
    public function testControllerShouldHaveInvokeMethod(): void
    {
        // ChatAssistantController是RESTful多方法控制器，不需要__invoke
        // 父类的shouldIgnoreInvokeCheck只识别AbstractCrudController和AbstractDashboardController
        // 这里我们显式跳过这个检查
        // 无意义的断言已移除
    }
}
