<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Exception;

use HttpClientBundle\Request\RequestInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ApiRequestException extends RAGFlowApiException
{
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        string $message = '',
        ?int $errorCode = null,
        ?string $errorDetails = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, $response, $message, $errorCode, $errorDetails, $previous);
    }
}
