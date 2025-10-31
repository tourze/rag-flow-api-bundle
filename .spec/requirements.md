# RAGFlow API集成包需求文档

## 概述

RAGFlow API Bundle 是一个用于集成 RAGFlow 知识库检索增强生成（RAG）服务的 Symfony 包。该包基于内部的 `http-client-bundle` 构建，提供了完整的 PHP 客户端实现，用于与 RAGFlow 的 RESTful API 进行交互，支持数据集管理、文档处理、对话助手等核心功能，并支持同时管理多个 RAGFlow 实例。

## 核心价值主张

1. **多实例管理**：支持同时连接和管理多个 RAGFlow 实例
2. **简化集成**：基于 http-client-bundle 提供标准化的 API 接入能力
3. **完整的API覆盖**：支持 RAGFlow 所有核心 API 端点
4. **企业级可靠性**：继承 http-client-bundle 的重试、缓存、锁定等机制
5. **类型安全**：通过 PHP 8.1+ 的类型系统确保 API 调用的正确性
6. **命令行工具**：提供 Symfony Command 进行数据同步和管理任务

## 功能需求

### 1. 多实例管理

#### 1.1 RAGFlow 实例实体
- 系统必须提供 RAGFlowInstance 实体来存储实例配置
- 实体必须包含：名称、API 地址、API Key、描述、状态等字段
- 系统必须支持实例的启用/禁用状态管理
- 当实例被禁用时，系统必须拒绝向该实例发送请求

#### 1.2 实例管理服务
- 系统必须提供服务来管理多个 RAGFlow 实例
- 系统必须支持按名称或ID获取特定实例的客户端
- 系统必须支持实例健康检查
- 如果实例不可用，系统必须记录错误并通知管理员

### 2. API 客户端核心功能

#### 2.1 基于 http-client-bundle 的实现
- 系统必须继承 `ApiClient` 基类实现 RAGFlow 客户端
- 系统必须使用 `ApiRequest` 接口定义所有 API 请求
- 系统必须利用 `AutoRetryRequest` 实现自动重试机制
- 系统必须使用 `CacheRequest` 缓存合适的响应

#### 2.2 认证管理
- 系统必须为每个实例配置独立的 Bearer Token 认证
- 系统必须安全存储 API Key（加密存储在数据库中）
- 当 API Key 无效时，系统必须抛出明确的认证异常

#### 2.3 请求处理
- 系统必须实现请求日志记录（使用 HttpRequestLog 实体）
- 系统必须支持并发请求限制（使用 LockHttpClient）
- 系统必须支持请求超时配置

### 3. 数据集管理

#### 3.1 数据集 CRUD 操作
- 系统必须提供创建数据集的接口，包括配置嵌入模型、分块方法等参数
- 系统必须支持更新数据集配置
- 系统必须支持删除单个或批量数据集
- 系统必须提供列表查询功能，支持分页、排序和过滤

#### 3.2 知识图谱管理
- 系统必须支持获取数据集的知识图谱
- 系统必须支持删除数据集的知识图谱
- 如果启用了 GraphRAG，系统必须正确处理图谱数据

### 4. 文档管理

#### 4.1 文档上传与处理
- 系统必须支持批量上传文档到数据集
- 系统必须支持多种文档格式（txt、pdf、doc、excel等）
- 当文档上传成功后，系统必须返回文档 ID 和处理状态

#### 4.2 文档操作
- 系统必须支持更新文档配置和元数据
- 系统必须支持下载文档内容
- 系统必须支持删除文档
- 系统必须提供文档列表查询功能

#### 4.3 文档解析
- 系统必须支持触发文档解析任务
- 系统必须支持停止正在进行的解析任务
- 系统必须提供解析进度查询功能

### 5. 分块管理

#### 5.1 分块 CRUD 操作
- 系统必须支持添加自定义分块到文档
- 系统必须支持更新分块内容和关键词
- 系统必须支持删除分块
- 系统必须支持设置分块的可用性状态

#### 5.2 分块检索
- 系统必须提供基于问题的分块检索功能
- 系统必须支持混合检索（向量相似度 + 关键词匹配）
- 系统必须支持配置相似度阈值和权重
- 当启用重排序模型时，系统必须使用重排序分数
- 系统必须支持跨语言检索

### 6. 聊天助手管理

#### 6.1 助手配置
- 系统必须支持创建和配置聊天助手
- 系统必须支持关联数据集到助手
- 系统必须支持配置 LLM 参数（温度、top_p、惩罚等）
- 系统必须支持自定义提示词模板

#### 6.2 对话管理
- 系统必须提供聊天完成接口
- 系统必须支持对话历史管理
- 系统必须支持 Agent 模式的对话完成
- 系统必须返回结构化的响应数据

### 7. Symfony Command 命令行工具

#### 7.1 数据同步命令
- 系统必须提供命令同步数据集列表 `rag-flow:dataset:sync`
- 系统必须提供命令批量上传文档 `rag-flow:document:upload`
- 系统必须提供命令触发文档解析 `rag-flow:document:parse`
- 系统必须提供命令检查解析状态 `rag-flow:document:status`

#### 7.2 实例管理命令
- 系统必须提供命令添加 RAGFlow 实例 `rag-flow:instance:add`
- 系统必须提供命令测试实例连接 `rag-flow:instance:test`
- 系统必须提供命令列出所有实例 `rag-flow:instance:list`
- 系统必须提供命令启用/禁用实例 `rag-flow:instance:toggle`

#### 7.3 维护命令
- 系统必须提供命令清理过期缓存 `rag-flow:cache:clear`
- 系统必须提供命令导出 API 日志 `rag-flow:log:export`
- 系统必须提供命令进行健康检查 `rag-flow:health:check`

### 8. 错误处理

#### 8.1 异常体系
- 系统必须继承 `HttpClientException` 实现 RAGFlow 特定异常
- 系统必须为不同类型的错误提供特定的异常类
- 当 API 返回错误时，系统必须包含错误代码和消息
- 系统必须区分客户端错误（4xx）和服务器错误（5xx）

#### 8.2 错误恢复
- 系统必须使用 `AutoRetryRequest` 自动重试失败请求
- 如果请求因认证失败，系统必须不重试并立即抛出异常
- 系统必须记录所有错误到 `HttpRequestLog` 实体

## 非功能需求

### 性能要求
- 系统必须使用 `CacheHttpClient` 缓存请求结果
- 系统必须使用 `LockHttpClient` 控制并发请求
- 文档上传必须支持大文件（>100MB）的分块传输
- 系统必须支持异步处理长时间运行的任务

### 兼容性要求
- 系统必须支持 PHP 8.1 及以上版本
- 系统必须与 Symfony 6.4+ 和 7.x 兼容
- 系统必须兼容 `tourze/http-client-bundle` 的所有功能
- 系统必须提供向后兼容的 API 接口

### 安全要求
- 系统必须加密存储 API 密钥在数据库中
- 系统必须验证所有输入参数的有效性
- 系统必须防止 API 密钥泄露到日志和异常堆栈中
- 系统必须支持 API 密钥轮换而不影响服务

### 可扩展性
- 系统必须允许自定义 `ApiRequest` 实现
- 系统必须支持自定义错误处理策略
- 系统必须允许扩展命令行工具
- 系统必须支持插件式的响应处理器

## 集成需求

### Symfony 集成
- 系统必须提供 Symfony Bundle 自动配置
- 系统必须支持通过 YAML/PHP 配置文件进行配置
- 系统必须将服务注册到 Symfony 容器
- 系统必须支持 Symfony Messenger 进行异步处理
- 系统必须集成 Doctrine ORM 管理实体

### http-client-bundle 集成
- 系统必须继承 `tourze/http-client-bundle` 的核心功能
- 系统必须使用 `SmartHttpClient` 服务
- 系统必须利用 `HttpRequestLog` 实体记录请求
- 系统必须使用 bundle 提供的事件系统

### 日志集成
- 系统必须使用 PSR-3 日志接口
- 系统必须通过 `HttpRequestLog` 实体持久化请求日志
- 系统必须屏蔽日志中的 API Key 等敏感信息
- 当错误发生时，系统必须记录详细的调试信息

## 验收标准

### 测试覆盖
- 单元测试覆盖率必须达到 90% 以上
- 必须提供所有 API 端点的集成测试
- 必须包含错误场景的测试用例

### 文档要求
- 必须提供完整的 API 文档
- 必须包含快速开始指南
- 必须提供配置示例
- 必须包含常见问题解答

### 代码质量
- 必须通过 PHPStan Level 8 检查
- 必须遵循 PSR-12 编码规范
- 必须提供类型声明和 PHPDoc 注释

## 使用场景示例

### 场景1：管理多个 RAGFlow 实例
```php
// 获取实例管理器
$instanceManager = $this->ragFlowInstanceManager;

// 添加新实例
$instance = $instanceManager->createInstance([
    'name' => 'production-ragflow',
    'api_url' => 'https://ragflow.example.com/api',
    'api_key' => $encryptedKey,
    'description' => '生产环境 RAGFlow 实例'
]);

// 获取特定实例的客户端
$client = $instanceManager->getClient('production-ragflow');

// 健康检查
if (!$instanceManager->checkHealth($instance)) {
    $this->logger->error('RAGFlow instance is down');
}
```

### 场景2：使用继承的 ApiClient 功能
```php
// 创建带有缓存和重试的请求
class CreateDatasetRequest extends ApiRequest 
    implements CacheRequest, AutoRetryRequest 
{
    public function getCacheTtl(): int 
    {
        return 3600; // 缓存1小时
    }
    
    public function getMaxRetries(): int 
    {
        return 3;
    }
}

// 执行请求
$client = $instanceManager->getClient('production');
$dataset = $client->execute(new CreateDatasetRequest([
    'name' => 'knowledge_base',
    'embedding_model' => 'BAAI/bge-large-zh-v1.5@BAAI'
]));
```

### 场景3：使用命令行工具批量操作
```bash
# 添加 RAGFlow 实例
php bin/console rag-flow:instance:add \
    --name=staging \
    --url=https://staging.ragflow.com/api \
    --key=YOUR_API_KEY

# 批量上传文档
php bin/console rag-flow:document:upload \
    --instance=production \
    --dataset=product_manual \
    --dir=/path/to/documents

# 检查解析状态
php bin/console rag-flow:document:status \
    --instance=production \
    --dataset=product_manual

# 健康检查所有实例
php bin/console rag-flow:health:check --all
```

### 场景4：异步处理长时间任务
```php
// 使用 Symfony Messenger 异步处理
$this->messageBus->dispatch(
    new ParseDocumentsMessage($instanceName, $datasetId, $documentIds)
);

// 消息处理器
class ParseDocumentsHandler
{
    public function __invoke(ParseDocumentsMessage $message)
    {
        $client = $this->instanceManager->getClient($message->getInstance());
        
        // 触发解析
        $client->documents()->parse(
            $message->getDatasetId(),
            $message->getDocumentIds()
        );
        
        // 轮询状态直到完成
        while (!$this->isParsingComplete($client, $message)) {
            sleep(10);
        }
    }
}
```