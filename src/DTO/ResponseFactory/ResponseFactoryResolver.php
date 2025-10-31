<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO\ResponseFactory;

use HttpClientBundle\Request\RequestInterface;
use Tourze\RAGFlowApiBundle\Request\CreateAgentRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteAgentRequest;
use Tourze\RAGFlowApiBundle\Request\GetAgentRequest;
use Tourze\RAGFlowApiBundle\Request\ListAgentsRequest;
use Tourze\RAGFlowApiBundle\Request\RequestInterface as RAGFlowRequestInterface;
use Tourze\RAGFlowApiBundle\Request\UpdateAgentRequest;

/**
 * 响应工厂解析器
 */
final class ResponseFactoryResolver
{
    private AgentResponseFactory $agentResponseFactory;

    public function __construct()
    {
        $this->agentResponseFactory = new AgentResponseFactory();
    }

    /**
     * 根据请求类型解析对应的响应工厂
     */
    public function resolve(\HttpClientBundle\Request\RequestInterface $request): AbstractResponseFactory
    {
        // Agent相关请求
        if ($request instanceof CreateAgentRequest
            || $request instanceof UpdateAgentRequest
            || $request instanceof DeleteAgentRequest
            || $request instanceof GetAgentRequest
            || $request instanceof ListAgentsRequest
        ) {
            $this->agentResponseFactory->setCurrentRequest($request);

            return $this->agentResponseFactory;
        }

        // 默认返回通用工厂（保持向后兼容）
        return new class extends AbstractResponseFactory {
            public function supports(\HttpClientBundle\Request\RequestInterface $request): bool
            {
                return true; // 通用工厂支持所有请求
            }

            protected function hydrate(array $data)
            {
                // 通用工厂直接返回原始数据，保持向后兼容
                return $data;
            }
        };
    }
}
