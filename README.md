# AI Content Audit Bundle

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

A comprehensive Symfony bundle for auditing AI-generated content with automated risk detection 
and violation management.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Documentation](#documentation)
  - [Admin Interface](#admin-interface)
  - [Advanced Usage](#advanced-usage)
  - [Configuration Reference](#configuration-reference)
  - [Entities](#entities)
  - [Enums](#enums)
  - [Events](#events)
- [Dependencies](#dependencies)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Machine Audit**: Automated content analysis with risk level detection (high/medium/low)
- **Manual Audit**: Support for manual review workflow
- **Risk Keywords**: Configurable risk keywords management with categories
- **Violation Records**: Track and manage content violations
- **Report System**: User reporting functionality with processing workflow
- **Statistics**: Real-time content audit statistics
- **Admin Interface**: EasyAdmin integration for content management

## Installation

```bash
composer require tourze/ai-content-audit-bundle
```

## Quick Start

### Configuration

Enable the bundle in your `config/bundles.php`:

```php
return [
    // ...
    AIContentAuditBundle\AIContentAuditBundle::class => ['all' => true],
];
```

Configure the bundle in `config/packages/ai_content_audit.yaml`:

```yaml
ai_content_audit:
    # Default configuration
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

### Basic Usage

### Content Auditing

```php
use AIContentAuditBundle\Service\ContentAuditService;

class YourService
{
    public function __construct(
        private ContentAuditService $auditService
    ) {}
    
    public function processContent(string $input, string $output, int $userId): void
    {
        // Perform machine audit
        $content = $this->auditService->machineAudit($input, $output, $userId);
        
        // Check if manual review is needed
        if ($content->needsManualAudit()) {
            // Handle manual review process
        }
    }
}
```

### Managing Risk Keywords

```php
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;

// Add a new risk keyword
$keyword = new RiskKeyword();
$keyword->setKeyword('prohibited term');
$keyword->setRiskLevel(RiskLevel::HIGH_RISK);
$keyword->setCategory('Politics');
$keyword->setAddedBy('admin');

$entityManager->persist($keyword);
$entityManager->flush();
```

### Handling Reports

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

### Statistics

```php
use AIContentAuditBundle\Service\StatisticsService;

// Get audit statistics
$stats = $statisticsService->getStatistics();
// Returns: [
//     'total' => 1000,
//     'passed' => 800,
//     'blocked' => 150,
//     'pending' => 50,
//     'risk_distribution' => ['high' => 50, 'medium' => 100, 'low' => 850]
// ]
```

## Documentation

### Admin Interface

This bundle provides EasyAdmin integration for content management:

### Generated Content Management
- View and manage all AI-generated content
- Filter content by risk level and audit status
- Perform manual audits on medium-risk content

### Risk Keyword Management  
- Add, edit, and delete risk keywords
- Categorize keywords by risk level
- Bulk import/export keyword lists

### Report Management
- Review user reports on generated content
- Process reports and take appropriate actions
- Track report resolution status

### Violation Records
- Monitor user violation history
- View automated enforcement actions
- Generate compliance reports

The admin interface is automatically registered when EasyAdminBundle is installed.

### Advanced Usage

### Custom Risk Detection

Create custom risk detection rules:

```php
use AIContentAuditBundle\Service\ContentAuditService;

class CustomRiskDetector
{
    public function detectCustomRisks(string $content): RiskLevel
    {
        // Implement your custom risk detection logic
        if (preg_match('/custom-pattern/', $content)) {
            return RiskLevel::HIGH_RISK;
        }
        
        return RiskLevel::NO_RISK;
    }
}
```

### Event Listeners

Listen to audit events:

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
        // Handle audit completion
    }
}
```

### Batch Processing

Process multiple content items:

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

### Custom Audit Results

Extend audit results with custom logic:

```php
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;

class CustomAuditHandler
{
    public function handleCustomResult(GeneratedContent $content): void
    {
        if ($content->hasCustomFlag()) {
            $content->setManualAuditResult(AuditResult::MODIFY);
            // Apply custom modifications
        }
    }
}
```

### Configuration Reference

Complete configuration options:

```yaml
ai_content_audit:
    machine_audit:
        enabled: true
        auto_review_threshold: 'medium_risk'
        batch_size: 100
        
    manual_audit:
        enabled: true
        require_for_high_risk: true
        approval_timeout: 3600  # seconds
        
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

The bundle provides EasyAdmin CRUD controllers for managing:

- Generated Content
- Pending Content (requiring manual review)
- Risk Keywords
- Reports
- Violation Records

Access the admin interface at `/admin` after configuration.

### Entities

### GeneratedContent
Stores AI-generated content with audit results.

### RiskKeyword
Manages prohibited keywords with risk levels and categories.

### Report
Handles user reports on content.

### ViolationRecord
Tracks content violations and actions taken.

### Enums

- **RiskLevel**: `HIGH_RISK`, `MEDIUM_RISK`, `LOW_RISK`
- **AuditResult**: `PASS`, `BLOCK`, `DELETE`
- **ProcessStatus**: `PENDING`, `PROCESSING`, `COMPLETED`
- **ViolationType**: `MACHINE_HIGH_RISK`, `MANUAL_DELETE`, `USER_REPORT`, `REPEATED_VIOLATION`

### Events

The bundle dispatches the following events:

- `ai_content_audit.content_audited`: When content audit is completed
- `ai_content_audit.violation_detected`: When a violation is detected
- `ai_content_audit.report_submitted`: When a user report is submitted

## Dependencies

This bundle requires:

- **PHP**: 8.1 or higher
- **Symfony**: 6.4 or higher
- **Doctrine ORM**: 3.0 or higher
- **EasyAdmin Bundle**: 4.0 or higher
- **Required Extensions**: SPL, Date, Mbstring, Random

### Optional Dependencies

- **Faker**: For generating test data fixtures
- **KnpMenuBundle**: For menu integration

## Testing

Run the test suite:

```bash
# Run all tests
./vendor/bin/phpunit packages/ai-content-audit-bundle/tests

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage packages/ai-content-audit-bundle/tests
```

## Contributing

Please refer to the main project's contributing guidelines.

## License

This bundle is part of the tourze monorepo and follows the project's licensing terms.

