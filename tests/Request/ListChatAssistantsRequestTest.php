<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Tests\Request;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\RAGFlowApiBundle\Request\ListChatAssistantsRequest;

/**
 * @internal
 */
#[CoversClass(ListChatAssistantsRequest::class)]
final class ListChatAssistantsRequestTest extends RequestTestCase
{
    public function testPlaceholder(): void
    {
        self::markTestIncomplete('Test implementation pending');
    }
}
