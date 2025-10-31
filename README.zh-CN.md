# RAGFlow API Bundle

[English](README.md) | [中文](README.zh-CN.md)

用于 RAGFlow API 集成的 Symfony Bundle，为 RAGFlow 的 AI 驱动文档处理和检索功能提供全面的 PHP 接口。

## 特性

- **多实例管理**: 支持配置管理多个 RAGFlow 实例
- **HTTP 客户端集成**: 基于强大的 http-client-bundle 构建，确保可靠的 API 通信
- **完整的 API 覆盖**: 全面覆盖 RAGFlow 的数据集、文档、文档块和对话 API
- **服务层架构**: 在原始 API 调用之上提供清洁的服务层抽象
- **类型安全**: PHP 8.1+ 严格类型和全面的 PHPDoc 注解
- **命令行工具**: 用于管理和测试的 Symfony 命令
- **EasyAdmin 集成**: 实例管理的管理界面

## 安装

```bash
composer require tourze/rag-flow-api-bundle
```

## 快速开始

### 1. 配置 RAGFlow 实例

```php
// 从容器获取实例管理器
$instanceManager = $container->get('Tourze\RAGFlowApiBundle\Service\RAGFlowInstanceManagerInterface');

// 创建新实例
$instance = $instanceManager->createInstance([
    'name' => 'production-ragflow',
    'api_url' => 'https://ragflow.example.com/api',
    'api_key' => 'your_api_key',
    'description' => '生产环境 RAGFlow 实例'
]);
```

### 2. 基本 API 使用

```php
// 获取默认客户端
$client = $instanceManager->getDefaultClient();

// 创建数据集
$dataset = $client->datasets()->create([
    'name' => 'knowledge_base',
    'language' => 'Chinese',
    'chunk_method' => 'manual',
    'permission' => 'me',
    'embedding_model' => 'BAAI/bge-large-zh-v1.5@BAAI'
]);

// 上传文档
$documents = $client->documents()->upload($dataset['id'], [
    'file1.pdf' => '/path/to/document.pdf',
    'file2.txt' => '/path/to/document.txt'
]);

// 解析文档
foreach ($documents['data'] as $document) {
    $client->documents()->parse($dataset['id'], $document['id']);
}
```

## API 服务

### 数据集管理

```php
$datasetService = $client->datasets();

// 创建数据集
$dataset = $datasetService->create([
    'name' => 'my_dataset',
    'language' => 'Chinese'
]);

// 列出数据集
$datasets = $datasetService->list(['page' => 1, 'page_size' => 10]);

// 更新数据集
$datasetService->update($datasetId, ['name' => 'updated_name']);

// 删除数据集
$datasetService->delete($datasetId);

// 获取知识图谱
$graph = $datasetService->getKnowledgeGraph($datasetId);
```

### 文档管理

```php
$documentService = $client->documents();

// 上传文档
$result = $documentService->upload($datasetId, [
    'document.pdf' => '/local/path/document.pdf'
]);

// 列出文档
$documents = $documentService->list($datasetId, ['status' => 'parsed']);

// 解析文档
$documentService->parse($datasetId, $documentId, ['run' => 'yes']);

// 获取解析状态
$status = $documentService->getParseStatus($datasetId, $documentId);

// 删除文档
$documentService->delete($datasetId, $documentId);
```

### 文档块管理

```php
$chunkService = $client->chunks();

// 检索文档块
$chunks = $chunkService->retrieve($datasetId, '搜索查询', [
    'similarity_threshold' => 0.3,
    'vector_similarity_weight' => 0.3,
    'top_k' => 1024
]);

// 添加文档块
$chunkService->add($datasetId, [
    ['content_with_weight' => '文档块内容', 'weight' => 0.7]
]);

// 更新文档块
$chunkService->update($datasetId, $chunkId, [
    'content_with_weight' => '更新的内容'
]);

// 删除文档块
$chunkService->delete($datasetId, $chunkId);
```

### 对话管理

```php
$conversationService = $client->conversations();

// 创建对话
$conversation = $conversationService->create(
    '我的对话',
    $datasetId,
    ['model' => 'gpt-4']
);

// 发送消息
$response = $conversationService->sendMessage(
    $conversation['id'],
    '你好，能告诉我关于这个数据集的信息吗？'
);

// 获取对话历史
$history = $conversationService->getHistory($conversation['id']);

// 聊天补全
$completion = $conversationService->chatCompletion(
    [
        ['role' => 'user', 'content' => '关于文档的问题？']
    ],
    $conversation['id']
);
```

## 命令行工具

### 实例管理

```bash
# 添加 RAGFlow 实例
php bin/console rag-flow:instance:add \
    --name=production \
    --url=https://ragflow.example.com/api \
    --key=your_api_key

# 列出实例
php bin/console rag-flow:instance:list

# 测试实例连接
php bin/console rag-flow:instance:test production

# 健康检查
php bin/console rag-flow:health:check
```

### 数据集操作

```bash
# 列出数据集
php bin/console rag-flow:dataset:list
```

## 配置

Bundle 启用时会自动注册服务。自定义配置：

```yaml
# config/packages/rag_flow_api.yaml
rag_flow_api:
    default_instance: 'production'
    instances:
        production:
            api_url: '%env(RAGFLOW_API_URL)%'
            api_key: '%env(RAGFLOW_API_KEY)%'
            timeout: 30
            enabled: true
```

## 环境变量

```bash
# .env
RAGFLOW_API_URL=https://ragflow.example.com/api
RAGFLOW_API_KEY=your_secret_api_key
```

## 错误处理

```php
use Tourze\RAGFlowApiBundle\Exception\RAGFlowApiException;

try {
    $dataset = $client->datasets()->create($config);
} catch (RAGFlowApiException $e) {
    // 处理 API 错误
    echo "API 错误: " . $e->getMessage();
    echo "响应代码: " . $e->getCode();
}
```

## 测试

运行测试套件：

```bash
./vendor/bin/phpunit packages/rag-flow-api-bundle/tests/
```

运行 PHPStan 分析：

```bash
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/rag-flow-api-bundle/src/
```

## 要求

- PHP 8.1+
- Symfony 7.3+
- 运行中的 RAGFlow 实例

## 许可证

MIT License

## 贡献

请提交 Pull Request 和 Issue 来帮助改进这个 Bundle。