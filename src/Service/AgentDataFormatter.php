<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;

/**
 * 智能体数据格式化服务
 */
final class AgentDataFormatter
{
    /**
     * 格式化智能体列表数据
     *
     * @param RAGFlowAgent[] $agents
     * @return array<array<string, mixed>>
     */
    public function formatAgentsForList(array $agents): array
    {
        $data = [];
        foreach ($agents as $agent) {
            $data[] = $this->formatSingleAgentForList($agent);
        }

        return $data;
    }

    /**
     * 格式化单个智能体数据
     *
     * @return array<string, mixed>
     */
    public function formatSingleAgentForList(RAGFlowAgent $agent): array
    {
        return [
            'id' => $agent->getId(),
            'title' => $agent->getTitle(),
            'description' => $agent->getDescription(),
            'status' => $agent->getStatus(),
            'remote_id' => $agent->getRemoteId(),
            'instance_name' => $agent->getRagFlowInstance()->getName(),
            'create_time' => $agent->getCreateTime()?->format('Y-m-d H:i:s'),
            'last_sync_time' => $agent->getLastSyncTime()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 格式化智能体详情数据
     *
     * @return array<string, mixed>
     */
    public function formatAgentDetail(RAGFlowAgent $agent): array
    {
        return [
            'id' => $agent->getId(),
            'title' => $agent->getTitle(),
            'description' => $agent->getDescription(),
            'dsl' => $agent->getDsl(),
            'status' => $agent->getStatus(),
            'remote_id' => $agent->getRemoteId(),
            'instance' => [
                'id' => $agent->getRagFlowInstance()->getId(),
                'name' => $agent->getRagFlowInstance()->getName(),
            ],
            'create_time' => $agent->getCreateTime()?->format('Y-m-d H:i:s'),
            'update_time' => $agent->getUpdateTime()?->format('Y-m-d H:i:s'),
            'last_sync_time' => $agent->getLastSyncTime()?->format('Y-m-d H:i:s'),
            'sync_error_message' => $agent->getSyncErrorMessage(),
        ];
    }
}
