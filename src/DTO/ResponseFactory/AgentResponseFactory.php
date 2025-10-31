<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\DTO\ResponseFactory;

use HttpClientBundle\Request\RequestInterface;
use Tourze\RAGFlowApiBundle\DTO\AgentDataDto;
use Tourze\RAGFlowApiBundle\Request\CreateAgentRequest;
use Tourze\RAGFlowApiBundle\Request\DeleteAgentRequest;
use Tourze\RAGFlowApiBundle\Request\GetAgentRequest;
use Tourze\RAGFlowApiBundle\Request\ListAgentsRequest;
use Tourze\RAGFlowApiBundle\Request\UpdateAgentRequest;

/**
 * Agent相关响应工厂
 */
final class AgentResponseFactory extends AbstractResponseFactory
{
    public function supports(RequestInterface $request): bool
    {
        return $request instanceof CreateAgentRequest
            || $request instanceof UpdateAgentRequest
            || $request instanceof DeleteAgentRequest
            || $request instanceof GetAgentRequest
            || $request instanceof ListAgentsRequest;
    }

    /**
     * 水合器：将原始data字段转换为Agent相关数据结构
     *
     * @param array<string, mixed> $data
     * @return AgentDataDto|array<AgentDataDto>|array<string, mixed>
     */
    protected function hydrate(array $data)
    {
        // 对于列表请求，data通常是数组
        if ($this->currentRequest instanceof ListAgentsRequest) {
            return array_map(
                static fn (array $agentData) => AgentDataDto::fromArray($agentData),
                $data
            );
        }

        // 对于单个Agent操作，data通常是单个对象
        if ($data !== []) {
            return AgentDataDto::fromArray($data);
        }

        // 对于删除操作，可能返回空数组或其他结构
        return $data;
    }

    private ?RequestInterface $currentRequest = null;

    /**
     * 设置当前请求上下文
     */
    public function setCurrentRequest(RequestInterface $request): void
    {
        $this->currentRequest = $request;
    }
}
