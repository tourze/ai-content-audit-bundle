<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Service\StatisticsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(StatisticsService::class)]
#[RunTestsInSeparateProcesses]
final class StatisticsServiceTest extends AbstractIntegrationTestCase
{
    private StatisticsService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(StatisticsService::class);
    }

    public function testServiceExists(): void
    {
        // 验证服务可以正确获取
        $this->assertInstanceOf(StatisticsService::class, $this->service);
    }

    public function testGetAuditEfficiencyStatistics(): void
    {
        // 创建测试数据 - 有人工审核结果的内容
        $content1 = new GeneratedContent();
        $content1->setUser('test_user_1');
        $content1->setInputText('Test input 1');
        $content1->setOutputText('Test output 1');
        $content1->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable('-2 days 10:00:00'));
        $content1->setManualAuditResult(AuditResult::PASS);
        $content1->setManualAuditTime(new \DateTimeImmutable('-2 days 11:00:00'));
        self::getEntityManager()->persist($content1);

        $content2 = new GeneratedContent();
        $content2->setUser('test_user_2');
        $content2->setInputText('Test input 2');
        $content2->setOutputText('Test output 2');
        $content2->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable('-1 days 14:00:00'));
        $content2->setManualAuditResult(AuditResult::DELETE);
        $content2->setManualAuditTime(new \DateTimeImmutable('-1 days 15:00:00'));
        self::getEntityManager()->persist($content2);

        // 创建一个没有人工审核的内容（应该被排除）
        $content3 = new GeneratedContent();
        $content3->setUser('test_user_3');
        $content3->setInputText('Test input 3');
        $content3->setOutputText('Test output 3');
        $content3->setMachineAuditResult(RiskLevel::NO_RISK);
        $content3->setMachineAuditTime(new \DateTimeImmutable('-1 days'));
        self::getEntityManager()->persist($content3);

        self::getEntityManager()->flush();

        // 测试统计方法
        $statistics = $this->service->getAuditEfficiencyStatistics();

        // 验证返回结构
        $this->assertArrayHasKey('auditCount', $statistics);
        $this->assertArrayHasKey('avgAuditTimeSeconds', $statistics);
        $this->assertArrayHasKey('auditResults', $statistics);

        // 验证审核数量（至少包含我们创建的2条记录）
        $this->assertGreaterThanOrEqual(2, $statistics['auditCount']);

        // 验证平均审核时间为正数（具体值可能受其他数据影响）
        $this->assertGreaterThan(0, $statistics['avgAuditTimeSeconds']);

        // 验证审核结果统计（至少包含我们创建的数据）
        $auditResults = $statistics['auditResults'];
        self::assertIsArray($auditResults);
        $this->assertArrayHasKey('通过', $auditResults);
        $this->assertArrayHasKey('删除', $auditResults);
        $this->assertGreaterThanOrEqual(1, $auditResults['通过']);
        $this->assertGreaterThanOrEqual(1, $auditResults['删除']);
    }

    public function testGetKeywordStatistics(): void
    {
        // 创建测试风险关键词数据
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('test_keyword_1');
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);
        $keyword1->setCategory('category_1');
        $keyword1->setAddedBy('admin');
        $keyword1->setUpdateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($keyword1);

        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('test_keyword_2');
        $keyword2->setRiskLevel(RiskLevel::LOW_RISK);
        $keyword2->setCategory('category_1');
        $keyword2->setAddedBy('admin');
        $keyword2->setUpdateTime(new \DateTimeImmutable('-1 hour'));
        self::getEntityManager()->persist($keyword2);

        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('test_keyword_3');
        $keyword3->setRiskLevel(RiskLevel::HIGH_RISK);
        $keyword3->setCategory('category_2');
        $keyword3->setAddedBy('admin');
        $keyword3->setUpdateTime(new \DateTimeImmutable('-2 hours'));
        self::getEntityManager()->persist($keyword3);

        self::getEntityManager()->flush();

        // 测试统计方法
        $statistics = $this->service->getKeywordStatistics();

        // 验证返回结构
        $this->assertArrayHasKey('keywordByRiskLevel', $statistics);
        $this->assertArrayHasKey('keywordByCategory', $statistics);
        $this->assertArrayHasKey('recentKeywords', $statistics);

        // 验证风险等级统计（至少包含我们创建的数据）
        $riskLevelStats = $statistics['keywordByRiskLevel'];
        self::assertIsArray($riskLevelStats);
        $this->assertArrayHasKey('高风险', $riskLevelStats);
        $this->assertArrayHasKey('低风险', $riskLevelStats);
        $this->assertGreaterThanOrEqual(2, $riskLevelStats['高风险']);
        $this->assertGreaterThanOrEqual(1, $riskLevelStats['低风险']);

        // 验证分类统计（至少包含我们创建的数据）
        $categoryStats = $statistics['keywordByCategory'];
        self::assertIsArray($categoryStats);
        $this->assertArrayHasKey('category_1', $categoryStats);
        $this->assertArrayHasKey('category_2', $categoryStats);
        $this->assertGreaterThanOrEqual(2, $categoryStats['category_1']);
        $this->assertGreaterThanOrEqual(1, $categoryStats['category_2']);

        // 验证最近更新的关键词
        $recentKeywords = $statistics['recentKeywords'];
        $this->assertIsArray($recentKeywords);
        $this->assertGreaterThanOrEqual(3, count($recentKeywords));
        // 验证返回的关键词都是有效对象
        foreach (array_slice($recentKeywords, 0, 3) as $keyword) {
            $this->assertInstanceOf(RiskKeyword::class, $keyword);
            $this->assertNotEmpty($keyword->getKeyword());
        }
    }
}
