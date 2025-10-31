# AI 内容审核包

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)  
[![Latest Version](https://img.shields.io/packagist/v/tourze/ai-content-audit-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/ai-content-audit-bundle)  
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/ai-content-audit-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/ai-content-audit-bundle)
[![Build](https://img.shields.io/github/actions/workflow/status/tourze/monorepo/.github/workflows/test.yml?style=flat-square)]
(https://github.com/tourze/monorepo/actions)
[![Coverage](https://img.shields.io/codecov/c/github/tourze/monorepo/master.svg?style=flat-square)]
(https://codecov.io/gh/tourze/monorepo)

一个功能全面的 Symfony 包，用于审核 AI 生成的内容，具备自动风险检测和违规管理功能。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [快速开始](#快速开始)
- [详细文档](#详细文档)
  - [管理界面](#管理界面)
  - [高级用法](#高级用法)
  - [配置参考](#配置参考)
  - [实体](#实体)
  - [枚举类型](#枚举类型)
  - [事件](#事件)
- [依赖要求](#依赖要求)
- [测试](#测试)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- **机器审核**：自动内容分析，支持风险等级检测（高/中/低风险）
- **人工审核**：支持人工审核工作流程
- **风险关键词**：可配置的风险关键词管理，支持分类
- **违规记录**：跟踪和管理内容违规
- **举报系统**：用户举报功能，带处理工作流
- **统计分析**：实时内容审核统计
- **管理界面**：集成 EasyAdmin 进行内容管理

## 安装

```bash
composer require tourze/ai-content-audit-bundle
```

## 快速开始

### 配置

在 `config/bundles.php` 中启用包：

```php
return [
    // ...
    AIContentAuditBundle\AIContentAuditBundle::class => ['all' => true],
];
```

在 `config/packages/ai_content_audit.yaml` 中配置包：

```yaml
ai_content_audit:
    # 默认配置
    machine_audit:
        enabled: true
        auto_review_threshold: 'medium_risk' # high_risk, medium_risk, low_risk
    
    manual_audit:
        enabled: true
        require_for_high_risk: true
    
    violation_handling:
        auto_delete_high_risk: true
        user_suspension_threshold: 3
```

### 基本用法

### 内容审核

```php
use AIContentAuditBundle\Service\ContentAuditService;

class YourService
{
    public function __construct(
        private ContentAuditService $auditService
    ) {}
    
    public function processContent(string $input, string $output, int $userId): void
    {
        // 执行机器审核
        $content = $this->auditService->machineAudit($input, $output, $userId);
        
        // 检查是否需要人工审核
        if ($content->needsManualAudit()) {
            // 处理人工审核流程
        }
    }
}
```

### 管理风险关键词

```php
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;

// 添加新的风险关键词
$keyword = new RiskKeyword();
$keyword->setKeyword('违禁词');
$keyword->setRiskLevel(RiskLevel::HIGH_RISK);
$keyword->setCategory('政治');
$keyword->setAddedBy('admin');

$entityManager->persist($keyword);
$entityManager->flush();
```

### 处理举报

```php
use AIContentAuditBundle\Service\ReportService;

class ReportController
{
    public function report(
        ReportService $reportService,
        int $contentId,
        string $reason
    ): void {
        $reportService->createReport(
            $contentId,
            $reason,
            $this->getUser()->getId()
        );
    }
}
```

### 统计数据

```php
use AIContentAuditBundle\Service\StatisticsService;

// 获取审核统计
$stats = $statisticsService->getStatistics();
// 返回: [
//     'total' => 1000,
//     'passed' => 800,
//     'blocked' => 150,
//     'pending' => 50,
//     'risk_distribution' => ['high' => 50, 'medium' => 100, 'low' => 850]
// ]
```

## 详细文档

### 管理界面

该包提供了 EasyAdmin CRUD 控制器用于管理：

- 生成的内容
- 待审核内容（需要人工审核）
- 风险关键词
- 举报
- 违规记录

配置后可通过 `/admin` 访问管理界面。

### 高级用法

### 自定义风险检测

创建自定义风险检测规则：

```php
use AIContentAuditBundle\Service\ContentAuditService;

class CustomRiskDetector
{
    public function detectCustomRisks(string $content): RiskLevel
    {
        // 实现您的自定义风险检测逻辑
        if (preg_match('/自定义模式/', $content)) {
            return RiskLevel::HIGH_RISK;
        }
        
        return RiskLevel::NO_RISK;
    }
}
```

### 事件监听器

监听审核事件：

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AIContentAuditBundle\Event\ContentAuditedEvent;

class AuditEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'ai_content_audit.content_audited' => 'onContentAudited',
        ];
    }
    
    public function onContentAudited(ContentAuditedEvent $event): void
    {
        $content = $event->getContent();
        // 处理审核完成事件
    }
}
```

### 批量处理

批量处理多个内容项：

```php
use AIContentAuditBundle\Service\ContentAuditService;

class BatchProcessor
{
    public function processBatch(array $contentItems): void
    {
        foreach ($contentItems as $item) {
            $this->auditService->machineAudit(
                $item['input'],
                $item['output'],
                $item['userId']
            );
        }
    }
}
```

### 自定义审核结果

使用自定义逻辑扩展审核结果：

```php
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;

class CustomAuditHandler
{
    public function handleCustomResult(GeneratedContent $content): void
    {
        if ($content->hasCustomFlag()) {
            $content->setManualAuditResult(AuditResult::MODIFY);
            // 应用自定义修改
        }
    }
}
```

### 配置参考

完整配置选项：

```yaml
ai_content_audit:
    machine_audit:
        enabled: true
        auto_review_threshold: 'medium_risk'
        batch_size: 100
        
    manual_audit:
        enabled: true
        require_for_high_risk: true
        approval_timeout: 3600  # 秒
        
    violation_handling:
        auto_delete_high_risk: true
        user_suspension_threshold: 3
        escalation_rules:
            - threshold: 5
              action: 'temporary_ban'
            - threshold: 10
              action: 'permanent_ban'
              
    notifications:
        enabled: true
        channels: ['email', 'webhook']
        webhook_url: 'https://your-app.com/webhook'
```

### 实体

### GeneratedContent（生成内容）
存储 AI 生成的内容及审核结果。

### RiskKeyword（风险关键词）
管理违禁关键词，包含风险等级和分类。

### Report（举报）
处理用户对内容的举报。

### ViolationRecord（违规记录）
跟踪内容违规和采取的措施。

### 枚举类型

- **RiskLevel（风险等级）**：
  - `HIGH_RISK`（高风险）、`MEDIUM_RISK`（中风险）、`LOW_RISK`（低风险）
- **AuditResult（审核结果）**：`PASS`（通过）、`BLOCK`（屏蔽）、`DELETE`（删除）
- **ProcessStatus（处理状态）**：
  - `PENDING`（待处理）、`PROCESSING`（处理中）、`COMPLETED`（已完成）
- **ViolationType（违规类型）**：
  - `MACHINE_HIGH_RISK`（机器识别高风险）、`MANUAL_DELETE`（人工删除）
  - `USER_REPORT`（用户举报）、`REPEATED_VIOLATION`（重复违规）

### 事件

该包会分发以下事件：

- `ai_content_audit.content_audited`：内容审核完成时
- `ai_content_audit.violation_detected`：检测到违规时
- `ai_content_audit.report_submitted`：用户提交举报时

## 依赖要求

该包需要：

- **PHP**：8.1 或更高版本
- **Symfony**：6.4 或更高版本
- **Doctrine ORM**：3.0 或更高版本
- **EasyAdmin Bundle**：4.0 或更高版本
- **必需扩展**：SPL、Date、Mbstring、Random

### 可选依赖

- **Faker**：用于生成测试数据
- **KnpMenuBundle**：用于菜单集成

## 测试

运行测试套件：

```bash
# 运行所有测试
./vendor/bin/phpunit packages/ai-content-audit-bundle/tests

# 运行并生成覆盖率报告
./vendor/bin/phpunit --coverage-html coverage packages/ai-content-audit-bundle/tests
```

## 贡献

请参考主项目的贡献指南。

## 许可证

此包是 tourze monorepo 的一部分，遵循项目的许可条款。