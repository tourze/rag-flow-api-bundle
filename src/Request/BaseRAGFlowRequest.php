<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Request;

use HttpClientBundle\Request\ApiRequest;

abstract class BaseRAGFlowRequest extends ApiRequest
{
    public function getRequestMethod(): ?string
    {
        return 'POST';
    }
}
