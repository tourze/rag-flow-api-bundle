<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Exception;

use HttpClientBundle\Request\RequestInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class RAGFlowApiException extends \Exception
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response,
        string $message = '',
        private readonly ?int $errorCode = null,
        private readonly ?string $errorDetails = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function getErrorDetails(): ?string
    {
        return $this->errorDetails;
    }
}
