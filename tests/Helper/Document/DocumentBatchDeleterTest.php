<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Helper\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RAGFlowApiBundle\Entity\Dataset;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;
use Tourze\RAGFlowApiBundle\Helper\Document\DocumentBatchDeleter;
use Tourze\RAGFlowApiBundle\Repository\DocumentRepository;

/**
 * @internal
 */
#[CoversClass(DocumentBatchDeleter::class)]
#[RunTestsInSeparateProcesses]
final class DocumentBatchDeleterTest extends AbstractIntegrationTestCase
{
    private DocumentBatchDeleter $deleter;
    private DocumentRepository $documentRepository;

    protected function onSetUp(): void
    {
        $this->deleter = self::getService(DocumentBatchDeleter::class);
        $this->documentRepository = self::getService(DocumentRepository::class);
    }

    public function testBatchDeleteSuccess(): void
    {
        $em = self::getEntityManager();

        // Create instance
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setBaseUrl('http://test.example.com');
        $instance->setApiKey('test-api-key');
        $em->persist($instance);

        // Create dataset
        $dataset = new Dataset();
        $dataset->setName('Test Dataset');
        $dataset->setRagFlowInstance($instance);
        $em->persist($dataset);

        // Create documents
        $doc1 = new Document();
        $doc1->setName('Document 1');
        $doc1->setDataset($dataset);
        $em->persist($doc1);

        $doc2 = new Document();
        $doc2->setName('Document 2');
        $doc2->setDataset($dataset);
        $em->persist($doc2);

        $doc3 = new Document();
        $doc3->setName('Document 3');
        $doc3->setDataset($dataset);
        $em->persist($doc3);

        $em->flush();
        $em->clear();

        $datasetId = $dataset->getId();
        $documentIds = [$doc1->getId(), $doc2->getId(), $doc3->getId()];

        $result = $this->deleter->batchDelete($datasetId, $documentIds);

        $this->assertEquals(3, $result['deleted_count']);
        $this->assertEmpty($result['errors']);
    }

    public function testBatchDeleteWithNotFoundDocument(): void
    {
        $em = self::getEntityManager();

        // Create instance
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setBaseUrl('http://test.example.com');
        $instance->setApiKey('test-api-key');
        $em->persist($instance);

        // Create dataset
        $dataset = new Dataset();
        $dataset->setName('Test Dataset');
        $dataset->setRagFlowInstance($instance);
        $em->persist($dataset);

        // Create one document
        $doc = new Document();
        $doc->setName('Document 1');
        $doc->setDataset($dataset);
        $em->persist($doc);

        $em->flush();
        $em->clear();

        $datasetId = $dataset->getId();
        $documentIds = [$doc->getId(), 99999]; // 99999 doesn't exist

        $result = $this->deleter->batchDelete($datasetId, $documentIds);

        $this->assertEquals(1, $result['deleted_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('not found', $result['errors'][0]);
    }

    public function testBatchDeleteWithWrongDataset(): void
    {
        $em = self::getEntityManager();

        // Create instance
        $instance = new RAGFlowInstance();
        $instance->setName('Test Instance');
        $instance->setBaseUrl('http://test.example.com');
        $instance->setApiKey('test-api-key');
        $em->persist($instance);

        // Create two datasets
        $dataset1 = new Dataset();
        $dataset1->setName('Test Dataset 1');
        $dataset1->setRagFlowInstance($instance);
        $em->persist($dataset1);

        $dataset2 = new Dataset();
        $dataset2->setName('Test Dataset 2');
        $dataset2->setRagFlowInstance($instance);
        $em->persist($dataset2);

        // Create document in dataset2
        $doc = new Document();
        $doc->setName('Document 1');
        $doc->setDataset($dataset2);
        $em->persist($doc);

        $em->flush();
        $em->clear();

        // Try to delete from dataset1
        $result = $this->deleter->batchDelete($dataset1->getId(), [$doc->getId()]);

        $this->assertEquals(0, $result['deleted_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('not belongs to this dataset', $result['errors'][0]);
    }
}
