<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\Document;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Helper\Document\DocumentBatchDeleter;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;
use Tourze\RAGFlowApiBundle\Service\DocumentService;

/**
 * @internal
 */
#[CoversClass(DocumentBatchDeleter::class)]
final class DocumentBatchDeleterTest extends TestCase
{
    /** @var DocumentRepository&MockObject */
    private DocumentRepository $documentRepository;

    /** @var DocumentService&MockObject */
    private DocumentService $documentService;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private DocumentBatchDeleter $deleter;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(DocumentRepository::class);
        $this->documentService = $this->createMock(DocumentService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->deleter = new DocumentBatchDeleter($this->documentRepository, $this->documentService, $this->entityManager);
    }

    public function testBatchDeleteSuccess(): void
    {
        $datasetId = 1;
        $documentIds = [1, 2, 3];
        $document1 = $this->createMock(Document::class);
        $document2 = $this->createMock(Document::class);
        $document3 = $this->createMock(Document::class);
        $dataset = $this->createMock(Dataset::class);
        $dataset->expects($this->once())->method('getId')->willReturn('1');
        $document1->expects($this->once())->method('getDataset')->willReturn($dataset);
        $document2->expects($this->once())->method('getDataset')->willReturn($dataset);
        $document3->expects($this->once())->method('getDataset')->willReturn($dataset);
        $this->documentRepository->expects($this->exactly(3))->method('find')->willReturnOnConsecutiveCalls($document1, $document2, $document3);
        $this->entityManager->expects($this->exactly(3))->method('remove');
        $this->entityManager->expects($this->exactly(3))->method('flush');
        $result = $this->deleter->batchDelete($datasetId, $documentIds);
        $this->assertEquals(3, $result['deleted_count']);
        $this->assertEmpty($result['errors']);
    }

    public function testBatchDeleteWithNotFoundDocument(): void
    {
        $datasetId = 1;
        $documentIds = [1, 2];
        $document1 = $this->createMock(Document::class);
        $dataset = $this->createMock(Dataset::class);
        $dataset->expects($this->once())->method('getId')->willReturn('1');
        $document1->expects($this->once())->method('getDataset')->willReturn($dataset);
        $this->documentRepository->expects($this->exactly(2))->method('find')->willReturnOnConsecutiveCalls($document1, null);
        $this->entityManager->expects($this->once())->method('remove');
        $result = $this->deleter->batchDelete($datasetId, $documentIds);
        $this->assertEquals(1, $result['deleted_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Document 2 not found', $result['errors'][0]);
    }

    public function testBatchDeleteWithWrongDataset(): void
    {
        $datasetId = 1;
        $documentIds = [1];
        $document = $this->createMock(Document::class);
        $wrongDataset = $this->createMock(Dataset::class);
        $wrongDataset->expects($this->once())->method('getId')->willReturn('2');
        // Wrong dataset
        $document->expects($this->once())->method('getDataset')->willReturn($wrongDataset);
        $this->documentRepository->expects($this->once())->method('find')->willReturn($document);
        $this->entityManager->expects($this->never())->method('remove');
        $result = $this->deleter->batchDelete($datasetId, $documentIds);
        $this->assertEquals(0, $result['deleted_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('not belongs to this dataset', $result['errors'][0]);
    }
}
