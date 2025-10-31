<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Service;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Service\DatasetManagementService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowFacadeService;
use Tourze\RAGFlowApiBundle\Service\RAGFlowUrlService;

/**
 * RAGFlow门面服务测试
 *
 * @internal
 */
#[CoversClass(RAGFlowFacadeService::class)]
#[RunTestsInSeparateProcesses]
final class RAGFlowFacadeServiceTest extends AbstractIntegrationTestCase
{
    private RAGFlowFacadeService $facadeService;

    /** @var DatasetManagementService&MockObject */
    private DatasetManagementService $datasetManagementService;

    /** @var RAGFlowUrlService&MockObject */
    private RAGFlowUrlService $urlService;

    protected function onSetUp(): void
    {
        $this->datasetManagementService = $this->createMock(DatasetManagementService::class);
        $this->urlService = $this->createMock(RAGFlowUrlService::class);
        self::getContainer()->set(DatasetManagementService::class, $this->datasetManagementService);
        self::getContainer()->set(RAGFlowUrlService::class, $this->urlService);
        $this->facadeService = self::getContainer()->get(RAGFlowFacadeService::class);
    }

    private function createTestDataset(): Dataset
    {
        $instance = new RAGFlowInstance();
        $instance->setName('Facade Test Instance');
        $instance->setApiUrl('https://facade-test.example.com/api');
        $instance->setApiKey('facade-test-key');
        $dataset = new Dataset();
        $dataset->setName('Facade Test Dataset');
        $dataset->setRemoteId('dataset-facade-123');
        $dataset->setRagFlowInstance($instance);
        $this->persistAndFlush($instance);
        $this->persistAndFlush($dataset);

        return $dataset;
    }

    public function testService创建(): void
    {
        $this->assertInstanceOf(RAGFlowFacadeService::class, $this->facadeService);
    }

    public function testGetDatasetManagementInfo返回完整信息(): void
    {
        $dataset = $this->createTestDataset();
        $this->datasetManagementService->expects($this->once())->method('getDatasetFullInfo')->with(1)->willReturn(['dataset' => $dataset, 'stats' => ['total' => 10, 'processed' => 5], 'can_delete' => true]);
        $this->urlService->expects($this->once())->method('generateDocumentManagementPath')->with(1)->willReturn('/dataset/1/documents');
        $this->urlService->expects($this->once())->method('generateDocumentManagementUrl')->with(1)->willReturn('https://example.com/dataset/1/documents');
        $this->urlService->expects($this->once())->method('generateKnowledgeGraphPath')->with(1)->willReturn('/dataset/1/knowledge-graph');
        $this->urlService->expects($this->once())->method('generateKnowledgeGraphUrl')->with(1)->willReturn('https://example.com/dataset/1/knowledge-graph');
        $this->urlService->expects($this->once())->method('generateDocumentUploadPath')->with(1)->willReturn('/dataset/1/upload');
        $this->urlService->expects($this->once())->method('generateDocumentUploadUrl')->with(1)->willReturn('https://example.com/dataset/1/upload');
        $this->urlService->expects($this->once())->method('getRouteAvailability')->willReturn(['route1' => true, 'route2' => false]);
        $info = $this->facadeService->getDatasetManagementInfo(1);
        $this->assertIsArray($info);
        $this->assertArrayHasKey('dataset', $info);
        $this->assertArrayHasKey('stats', $info);
        $this->assertArrayHasKey('can_delete', $info);
        $this->assertArrayHasKey('urls', $info);
        $this->assertArrayHasKey('route_status', $info);
        $this->assertTrue($info['can_delete']);
        $this->assertIsArray($info['urls']);
        $this->assertArrayHasKey('document_management_path', $info['urls']);
        $this->assertArrayHasKey('document_management_url', $info['urls']);
    }

    public function testValidateDatasetAccess委托给管理服务(): void
    {
        $dataset = $this->createTestDataset();
        $this->datasetManagementService->expects($this->once())->method('validateDatasetAccess')->with(1)->willReturn($dataset);
        $result = $this->facadeService->validateDatasetAccess(1);
        $this->assertSame($dataset, $result);
    }

    public function testGetDatasetBasicInfo返回基本信息(): void
    {
        $dataset = $this->createTestDataset();
        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);
        $this->datasetManagementService->expects($this->once())->method('validateDatasetAccess')->with($datasetId)->willReturn($dataset);
        $this->datasetManagementService->expects($this->once())->method('getDatasetDocumentStats')->with($datasetId)->willReturn(['total_documents' => 20, 'processed_documents' => 15, 'pending_documents' => 5]);
        $info = $this->facadeService->getDatasetBasicInfo($datasetId);
        $this->assertIsArray($info);
        $this->assertArrayHasKey('id', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('description', $info);
        $this->assertArrayHasKey('remote_id', $info);
        $this->assertArrayHasKey('document_count', $info);
        $this->assertArrayHasKey('processed_count', $info);
        $this->assertArrayHasKey('pending_count', $info);
        $this->assertSame($datasetId, $info['id']);
        $this->assertSame('Facade Test Dataset', $info['name']);
        $this->assertSame(20, $info['document_count']);
        $this->assertSame(15, $info['processed_count']);
        $this->assertSame(5, $info['pending_count']);
    }

    public function testGetDocumentManagementUrl验证并返回URL(): void
    {
        $dataset = $this->createTestDataset();
        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);
        $this->datasetManagementService->expects($this->once())->method('validateDatasetAccess')->with($datasetId)->willReturn($dataset);
        $this->urlService->expects($this->once())->method('generateDocumentManagementUrl')->with($datasetId, ['foo' => 'bar'])->willReturn('https://example.com/dataset/1/documents?foo=bar');
        $url = $this->facadeService->getDocumentManagementUrl($datasetId, ['foo' => 'bar']);
        $this->assertSame('https://example.com/dataset/1/documents?foo=bar', $url);
    }

    public function testGetKnowledgeGraphUrl验证并返回URL(): void
    {
        $dataset = $this->createTestDataset();
        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);
        $this->datasetManagementService->expects($this->once())->method('validateDatasetAccess')->with($datasetId)->willReturn($dataset);
        $this->urlService->expects($this->once())->method('generateKnowledgeGraphUrl')->with($datasetId, [])->willReturn('https://example.com/dataset/1/graph');
        $url = $this->facadeService->getKnowledgeGraphUrl($datasetId);
        $this->assertSame('https://example.com/dataset/1/graph', $url);
    }

    public function testRequiresRouteConfiguration委托给URL服务(): void
    {
        $this->urlService->expects($this->once())->method('requiresRouteConfiguration')->willReturn(true);
        $result = $this->facadeService->requiresRouteConfiguration();
        $this->assertTrue($result);
    }

    public function testGetRouteConfigurationAdvice当所有路由已配置(): void
    {
        $this->urlService->expects($this->once())->method('getRouteAvailability')->willReturn(['route1' => true, 'route2' => true]);
        $advice = $this->facadeService->getRouteConfigurationAdvice();
        $this->assertIsArray($advice);
        $this->assertArrayHasKey('status', $advice);
        $this->assertSame('ok', $advice['status']);
        $this->assertFalse($advice['action_required']);
    }

    public function testGetRouteConfigurationAdvice当缺少路由时返回建议(): void
    {
        $this->urlService->expects($this->once())->method('getRouteAvailability')->willReturn(['route1' => true, 'route2' => false, 'route3' => false]);
        $advice = $this->facadeService->getRouteConfigurationAdvice();
        $this->assertIsArray($advice);
        $this->assertArrayHasKey('status', $advice);
        $this->assertSame('missing_routes', $advice['status']);
        $this->assertTrue($advice['action_required']);
        $this->assertArrayHasKey('missing_routes', $advice);
        $this->assertArrayHasKey('suggested_config', $advice);
        $this->assertCount(2, $advice['missing_routes']);
    }

    public function testGetMultipleDatasetsInfo批量获取信息(): void
    {
        $dataset = $this->createTestDataset();
        $datasetId = $dataset->getId();
        $this->assertNotNull($datasetId);
        $this->datasetManagementService->expects($this->exactly(2))->method('validateDatasetAccess')->willReturnCallback(function (int $id) use ($dataset, $datasetId) {
            if ($id === $datasetId) {
                return $dataset;
            }
            throw new \InvalidArgumentException('数据集不存在');
        });
        $this->datasetManagementService->expects($this->once())->method('getDatasetDocumentStats')->willReturn(['total_documents' => 10, 'processed_documents' => 8, 'pending_documents' => 2]);
        $results = $this->facadeService->getMultipleDatasetsInfo([$datasetId, 999]);
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey($datasetId, $results);
        $this->assertArrayHasKey(999, $results);
        $this->assertArrayHasKey('name', $results[$datasetId]);
        $this->assertArrayHasKey('error', $results[999]);
        $this->assertFalse($results[999]['exists']);
    }

    public function testGetSystemOverview返回系统概览(): void
    {
        $this->urlService->expects($this->once())->method('requiresRouteConfiguration')->willReturn(false);
        $this->urlService->expects($this->once())->method('getRouteAvailability')->willReturn(['route1' => true, 'route2' => true]);
        $overview = $this->facadeService->getSystemOverview();
        $this->assertIsArray($overview);
        $this->assertArrayHasKey('service_available', $overview);
        $this->assertArrayHasKey('route_configuration_required', $overview);
        $this->assertArrayHasKey('available_routes', $overview);
        $this->assertArrayHasKey('service_version', $overview);
        $this->assertArrayHasKey('features', $overview);
        $this->assertTrue($overview['service_available']);
        $this->assertFalse($overview['route_configuration_required']);
    }

    public function testExtractDatasetIdFromAdminContext验证方法签名(): void
    {
        // AdminContext 和 EntityDto 都是 final 类，不能 mock
        // 我们只验证方法签名和基本逻辑
        $reflection = new \ReflectionClass(RAGFlowFacadeService::class);
        $method = $reflection->getMethod('extractDatasetIdFromAdminContext');
        $this->assertTrue($method->isPublic());
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('context', $parameters[0]->getName());
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testServiceIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(RAGFlowFacadeService::class);
        $this->assertFalse($reflection->isFinal(), 'RAGFlowFacadeService 不是 final 以允许扩展');
    }

    public function testConstructorInjectsDependencies(): void
    {
        $reflection = new \ReflectionClass(RAGFlowFacadeService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertSame('datasetService', $parameters[0]->getName());
        $this->assertSame('urlService', $parameters[1]->getName());
    }
}
