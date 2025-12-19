<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\RAGFlowApiBundle\Entity\Document;
use Tourze\RAGFlowApiBundle\Util\DocumentDataUpdater;

/**
 * DocumentDataUpdater 测试
 *
 * @internal
 */
#[CoversClass(DocumentDataUpdater::class)]
class DocumentDataUpdaterTest extends TestCase
{
    private Document $document;
    private DocumentDataUpdater $updater;

    protected function setUp(): void
    {
        $this->document = new Document();
        $this->updater = new DocumentDataUpdater($this->document);
    }

    public function testUpdateNameWithValue(): void
    {
        $result = $this->updater->updateName('新文档名');

        $this->assertSame($this->updater, $result);
        $this->assertSame('新文档名', $this->document->getName());
    }

    public function testUpdateNameWithNull(): void
    {
        $this->document->setName('原始名称');
        $this->updater->updateName(null);

        $this->assertSame('原始名称', $this->document->getName());
    }

    public function testUpdateSummaryWithValue(): void
    {
        $result = $this->updater->updateSummary('文档摘要');

        $this->assertSame($this->updater, $result);
        $this->assertSame('文档摘要', $this->document->getSummary());
    }

    public function testUpdateSummaryWithNull(): void
    {
        $this->document->setSummary('原始摘要');
        $this->updater->updateSummary(null);

        $this->assertSame('原始摘要', $this->document->getSummary());
    }

    public function testUpdateLanguageWithValue(): void
    {
        $result = $this->updater->updateLanguage('zh');

        $this->assertSame($this->updater, $result);
        $this->assertSame('zh', $this->document->getLanguage());
    }

    public function testUpdateLanguageWithNull(): void
    {
        $this->document->setLanguage('en');
        $this->updater->updateLanguage(null);

        $this->assertSame('en', $this->document->getLanguage());
    }

    public function testChainedUpdates(): void
    {
        $this->updater
            ->updateName('链式测试文档')
            ->updateSummary('这是链式调用的摘要')
            ->updateLanguage('ja');

        $this->assertSame('链式测试文档', $this->document->getName());
        $this->assertSame('这是链式调用的摘要', $this->document->getSummary());
        $this->assertSame('ja', $this->document->getLanguage());
    }

    public function testPartialChainedUpdates(): void
    {
        $this->document->setName('原名称');
        $this->document->setSummary('原摘要');
        $this->document->setLanguage('fr');

        $this->updater
            ->updateName('新名称')
            ->updateSummary(null)
            ->updateLanguage('de');

        $this->assertSame('新名称', $this->document->getName());
        $this->assertSame('原摘要', $this->document->getSummary());
        $this->assertSame('de', $this->document->getLanguage());
    }
}
