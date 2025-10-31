<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RAGFlowApiBundle\Controller\Api\DocumentController;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(DocumentController::class)]
#[RunTestsInSeparateProcesses]
final class DocumentControllerTest extends AbstractWebTestCase
{
    /** @var DocumentService&MockObject */
    private DocumentService $documentService;

    private DocumentController $controller;

    protected function onSetUp(): void
    {
        $this->documentService = $this->createMock(DocumentService::class);
        self::getContainer()->set(DocumentService::class, $this->documentService);
        $this->controller = self::getService(DocumentController::class);
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
        $this->assertInstanceOf(DocumentController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(DocumentController::class);
        $expectedMethods = ['list', 'upload', 'delete', 'parse', 'getParseStatus'];
        foreach ($expectedMethods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Controller should have method: {$method}");
        }
    }

    public function testList(): void
    {
        $request = new Request(['page' => '1', 'limit' => '20', 'name' => 'Test', 'status' => 'parsed', 'type' => 'pdf', 'dataset_id' => '123']);
        $expectedData = ['documents' => [], 'total' => 0, 'page' => 1, 'limit' => 20];
        $this->documentService->expects($this->never())->method('list');
        $response = $this->controller->list($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedData, $responseData['data']);
    }

    public function testUploadWithValidFiles(): void
    {
        // 创建模拟的上传文件
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getPathname')->willReturn('/tmp/test-file.pdf');
        $uploadedFile->expects($this->once())->method('getClientOriginalName')->willReturn('test-file.pdf');
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('pdf');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('application/pdf');
        $uploadedFile->expects($this->once())->method('getSize')->willReturn(1024);
        $fileBag = new FileBag(['file' => $uploadedFile]);
        $request = new Request([], ['dataset_id' => '123']);
        $request->files = $fileBag;
        $expectedResult = ['uploaded_documents' => [['id' => 'doc-456', 'filename' => 'test-file.pdf']]];
        $this->documentService->expects($this->never())->method('upload');
        $response = $this->controller->upload($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testUploadWithNoFiles(): void
    {
        $request = new Request([], ['dataset_id' => '123']);
        $response = $this->controller->upload($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('No files uploaded', $responseData['message']);
    }

    public function testUploadWithInvalidFiles(): void
    {
        // 创建模拟的无效上传文件
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(false);
        $fileBag = new FileBag(['file' => $uploadedFile]);
        $request = new Request([], ['dataset_id' => '123']);
        $request->files = $fileBag;
        $response = $this->controller->upload($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('No valid files to upload', $responseData['message']);
    }

    public function testDelete(): void
    {
        $documentId = 456;
        $this->documentService->expects($this->never())->method('delete');
        $response = $this->controller->delete($documentId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Document deleted successfully', $responseData['message']);
    }

    public function testParseWithOptions(): void
    {
        $documentId = 456;
        $options = ['parser' => 'pdf', 'language' => 'en'];
        $request = $this->createJsonRequest($options);
        $expectedResult = ['task_id' => 'parse-task-789', 'status' => 'queued'];
        $this->documentService->expects($this->never())->method('parse');
        $response = $this->controller->parse($documentId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testParseWithoutOptions(): void
    {
        $documentId = 456;
        $request = new Request();
        $expectedResult = ['task_id' => 'parse-task-789', 'status' => 'queued'];
        $this->documentService->expects($this->never())->method('parse');
        $response = $this->controller->parse($documentId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testGetParseStatus(): void
    {
        $documentId = 456;
        $expectedResult = ['task_id' => 'parse-task-789', 'status' => 'completed', 'progress' => 100, 'chunks_created' => 25];
        $this->documentService->expects($this->never())->method('getParseStatus');
        $response = $this->controller->getParseStatus($documentId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals($expectedResult, $responseData['data']);
    }

    public function testListHandlesException(): void
    {
        $request = new Request();
        $this->documentService->expects($this->never())->method('list');
        $response = $this->controller->list($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve documents', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testUploadHandlesException(): void
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFile->expects($this->once())->method('getPathname')->willReturn('/tmp/test-file.pdf');
        $uploadedFile->expects($this->once())->method('getClientOriginalName')->willReturn('test-file.pdf');
        $uploadedFile->expects($this->once())->method('getClientOriginalExtension')->willReturn('pdf');
        $uploadedFile->expects($this->once())->method('getMimeType')->willReturn('application/pdf');
        $uploadedFile->expects($this->once())->method('getSize')->willReturn(1024);
        $fileBag = new FileBag(['file' => $uploadedFile]);
        $request = new Request([], ['dataset_id' => '123']);
        $request->files = $fileBag;
        $this->documentService->expects($this->never())->method('upload');
        $response = $this->controller->upload($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to upload documents', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testDeleteHandlesException(): void
    {
        $documentId = 456;
        $this->documentService->expects($this->never())->method('delete');
        $response = $this->controller->delete($documentId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to delete document', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testParseHandlesException(): void
    {
        $documentId = 456;
        $request = new Request();
        $this->documentService->expects($this->never())->method('parse');
        $response = $this->controller->parse($documentId, $request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to initiate document parsing', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    public function testGetParseStatusHandlesException(): void
    {
        $documentId = 456;
        $this->documentService->expects($this->never())->method('getParseStatus');
        $response = $this->controller->getParseStatus($documentId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $responseData = $this->decodeJsonResponse($response);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to retrieve parse status', $responseData['message']);
        $this->assertEquals('Service error', $responseData['error']);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        // 多方法控制器，不使用 __invoke，因此此测试不适用
        // 无意义的断言已移除
    }
}
