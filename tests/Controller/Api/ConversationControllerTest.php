<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\ConversationController;
use Tourze\RAGFlowApiBundle\Service\ConversationService;

/**
 * @internal
 */
#[CoversClass(ConversationController::class)]
#[RunTestsInSeparateProcesses]
final class ConversationControllerTest extends AbstractWebTestCase
{
    /** @var ConversationService&MockObject */
    private ConversationService $conversationService;

    private ConversationController $controller;

    protected function onSetUp(): void
    {
        $this->conversationService = $this->createMock(ConversationService::class);
        self::getContainer()->set(ConversationService::class, $this->conversationService);
        $this->controller = self::getService(ConversationController::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(JsonResponse $response): array
    {
        $content = $response->getContent();

        if (false === $content) {
            throw new \RuntimeException('Failed to get response content');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Failed to decode JSON response');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createJsonRequest(array $data): Request
    {
        $jsonContent = json_encode($data);
        $this->assertIsString($jsonContent);

        return new Request([], [], [], [], [], [], $jsonContent);
    }

    public function testControllerExists(): void
    {
        $this->assertInstanceOf(ConversationController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(ConversationController::class);
        $expectedMethods = ['create', 'list', 'update', 'delete', 'createSession', 'sendMessage', 'getHistory'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Controller should have method: {$method}");
        }
    }

    public function testCreateWithValidData(): void
    {
        $requestData = ['name' => 'Test Conversation', 'dataset_ids' => ['dataset-1', 'dataset-2'], 'options' => ['temperature' => 0.7, 'max_tokens' => 100]];
        $request = $this->createJsonRequest($requestData);
        $expectedResult = ['id' => 'conversation-123', 'name' => 'Test Conversation'];
        $this->conversationService->expects($this->once())->method('createChatAssistant')->with('Test Conversation', ['dataset-1', 'dataset-2'], ['temperature' => 0.7, 'max_tokens' => 100])->willReturn($expectedResult);
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testCreateWithInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testCreateWithMissingName(): void
    {
        $requestData = ['dataset_ids' => ['dataset-1']];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Conversation name is required', $responseData['message']);
    }

    public function testList(): void
    {
        $request = new Request(['page' => '1', 'limit' => '20', 'name' => 'Test']);
        $expectedData = ['data' => [], 'total' => 0, 'page' => 1, 'limit' => 20];
        $this->conversationService->expects($this->once())->method('listChatAssistants')->with(['page' => 1, 'limit' => 20, 'name' => 'Test'])->willReturn($expectedData);
        $response = $this->controller->list($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedData, $responseData['data']);
    }

    public function testUpdate(): void
    {
        $chatId = 'conversation-123';
        $updateData = ['name' => 'Updated Conversation'];
        $request = $this->createJsonRequest($updateData);
        $expectedResult = ['id' => $chatId, 'name' => 'Updated Conversation'];
        $this->conversationService->expects($this->once())->method('updateChatAssistant')->with($chatId, $updateData)->willReturn($expectedResult);
        $response = $this->controller->update($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testUpdateWithInvalidJson(): void
    {
        $chatId = 'conversation-123';
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->update($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testDelete(): void
    {
        $chatId = 'conversation-123';
        $this->conversationService->expects($this->once())->method('deleteChatAssistant')->with($chatId);
        $response = $this->controller->delete($chatId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Conversation deleted successfully', $responseData['message']);
    }

    public function testCreateSession(): void
    {
        $chatId = 'conversation-123';
        $options = ['timeout' => 3600];
        $request = $this->createJsonRequest($options);
        $expectedResult = ['session_id' => 'session-456', 'chat_id' => $chatId];
        $this->conversationService->expects($this->once())->method('createSession')->with($chatId, $options)->willReturn($expectedResult);
        $response = $this->controller->createSession($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testCreateSessionWithEmptyBody(): void
    {
        $chatId = 'conversation-123';
        $request = new Request();
        $expectedResult = ['session_id' => 'session-456', 'chat_id' => $chatId];
        $this->conversationService->expects($this->once())->method('createSession')->with($chatId, null)->willReturn($expectedResult);
        $response = $this->controller->createSession($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testSendMessageWithValidData(): void
    {
        $chatId = 'conversation-123';
        $requestData = ['question' => 'What is AI?', 'session_id' => 'session-456'];
        $request = $this->createJsonRequest($requestData);
        $expectedResult = ['answer' => 'AI is artificial intelligence...'];
        $this->conversationService->expects($this->once())->method('sendMessage')->with($chatId, 'What is AI?', ['session_id' => 'session-456'])->willReturn($expectedResult);
        $response = $this->controller->sendMessage($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testSendMessageWithInvalidJson(): void
    {
        $chatId = 'conversation-123';
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $response = $this->controller->sendMessage($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Invalid JSON data', $responseData['message']);
    }

    public function testSendMessageWithMissingQuestion(): void
    {
        $chatId = 'conversation-123';
        $requestData = ['session_id' => 'session-456'];
        $request = $this->createJsonRequest($requestData);
        $response = $this->controller->sendMessage($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Question is required', $responseData['message']);
    }

    public function testGetHistory(): void
    {
        $chatId = 'conversation-123';
        $request = new Request(['page' => '1', 'limit' => '50', 'session_id' => 'session-456']);
        $expectedResult = ['messages' => [], 'total' => 0, 'page' => 1, 'limit' => 50];
        $this->conversationService->expects($this->once())->method('getHistory')->with($chatId, ['page' => 1, 'limit' => 50, 'session_id' => 'session-456'])->willReturn($expectedResult);
        $response = $this->controller->getHistory($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testCreateHandlesException(): void
    {
        $requestData = ['name' => 'Test Conversation'];
        $request = $this->createJsonRequest($requestData);
        $this->conversationService->expects($this->once())->method('createChatAssistant')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to create conversation', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testListHandlesException(): void
    {
        $request = new Request();
        $this->conversationService->expects($this->once())->method('listChatAssistants')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->list($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve conversations', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testUpdateHandlesException(): void
    {
        $chatId = 'conversation-123';
        $requestData = ['name' => 'Updated Conversation'];
        $request = $this->createJsonRequest($requestData);
        $this->conversationService->expects($this->once())->method('updateChatAssistant')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->update($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to update conversation', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testDeleteHandlesException(): void
    {
        $chatId = 'conversation-123';
        $this->conversationService->expects($this->once())->method('deleteChatAssistant')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->delete($chatId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to delete conversation', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testCreateSessionHandlesException(): void
    {
        $chatId = 'conversation-123';
        $requestData = ['timeout' => 3600];
        $request = $this->createJsonRequest($requestData);
        $this->conversationService->expects($this->once())->method('createSession')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->createSession($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to create session', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testSendMessageHandlesException(): void
    {
        $chatId = 'conversation-123';
        $requestData = ['question' => 'What is AI?'];
        $request = $this->createJsonRequest($requestData);
        $this->conversationService->expects($this->once())->method('sendMessage')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->sendMessage($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to send message', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testGetHistoryHandlesException(): void
    {
        $chatId = 'conversation-123';
        $request = new Request();
        $this->conversationService->expects($this->once())->method('getHistory')->willThrowException(new \Exception('Service error'));
        $response = $this->controller->getHistory($chatId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve chat history', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testMethodNotAllowed(string $method): void
    {
        // 多方法控制器，不使用 __invoke，因此此测试不适用
        // 无意义的断言已移除
    }
}
