<?php

declare(strict_types=1);

namespace Tourze\RAGFlowApiBundle\Service;

use Tourze\RAGFlowApiBundle\Entity\RAGFlowAgent;
use Tourze\RAGFlowApiBundle\Entity\RAGFlowInstance;

/**
 * 智能体工厂服务
 */
final class AgentFactory
{
    /**
     * 从请求数据创建智能体
     *
     * @param array<string, mixed> $data
     */
    public function createFromData(array $data, RAGFlowInstance $instance): RAGFlowAgent
    {
        $agent = new RAGFlowAgent();

        $title = $data['title'] ?? '';
        $agent->setTitle(is_string($title) ? $title : '');

        $description = $data['description'] ?? null;
        $agent->setDescription(is_string($description) ? $description : null);

        $dsl = $data['dsl'] ?? [];
        /** @var array<string, mixed> $validDsl */
        $validDsl = is_array($dsl) ? $dsl : [];
        $agent->setDsl($validDsl);

        $agent->setRagFlowInstance($instance);
        $agent->setStatus('draft');

        return $agent;
    }

    /**
     * 更新智能体字段
     *
     * @param array<string, mixed> $data
     */
    public function updateFields(RAGFlowAgent $agent, array $data): void
    {
        if (isset($data['title'])) {
            $title = $data['title'];
            $agent->setTitle(is_string($title) ? $title : '');
        }

        if (isset($data['description'])) {
            $description = $data['description'];
            $agent->setDescription(is_string($description) ? $description : null);
        }

        if (isset($data['dsl'])) {
            $dsl = $data['dsl'];
            /** @var array<string, mixed> $validDsl */
            $validDsl = is_array($dsl) ? $dsl : [];
            $agent->setDsl($validDsl);
        }
    }
}
