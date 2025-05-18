<?php

namespace AIContentAuditBundle\Tests\Entity;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use PHPUnit\Framework\TestCase;

class RiskKeywordTest extends TestCase
{
    private RiskKeyword $riskKeyword;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->riskKeyword = new RiskKeyword();
        $this->now = new \DateTimeImmutable();
    }

    /**
     * @dataProvider provideKeywordData
     */
    public function testKeywordAccessors(string $keyword): void
    {
        $this->riskKeyword->setKeyword($keyword);
        $this->assertEquals($keyword, $this->riskKeyword->getKeyword());
    }
    
    public function provideKeywordData(): array
    {
        return [
            'simple keyword' => ['bad'],
            'keyword with spaces' => ['very bad'],
            'multilanguage keyword' => ['坏词'],
            'special characters' => ['bad-word!']
        ];
    }
    
    /**
     * @dataProvider provideRiskLevelData
     */
    public function testRiskLevelAccessors(RiskLevel $riskLevel): void
    {
        $this->riskKeyword->setRiskLevel($riskLevel);
        $this->assertEquals($riskLevel, $this->riskKeyword->getRiskLevel());
    }
    
    public function provideRiskLevelData(): array
    {
        return [
            'low risk' => [RiskLevel::LOW_RISK],
            'medium risk' => [RiskLevel::MEDIUM_RISK],
            'high risk' => [RiskLevel::HIGH_RISK]
        ];
    }
    
    public function testUpdateTimeAccessors(): void
    {
        $updateTime = new \DateTimeImmutable('-1 day');
        $this->riskKeyword->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $this->riskKeyword->getUpdateTime());
    }
    
    /**
     * @dataProvider provideCategoryData
     */
    public function testCategoryAccessors(?string $category): void
    {
        $this->riskKeyword->setCategory($category);
        $this->assertEquals($category, $this->riskKeyword->getCategory());
    }
    
    public function provideCategoryData(): array
    {
        return [
            'null category' => [null],
            'empty category' => [''],
            'political category' => ['政治'],
            'adult content' => ['色情'],
            'violence' => ['暴力']
        ];
    }
    
    /**
     * @dataProvider provideDescriptionData
     */
    public function testDescriptionAccessors(?string $description): void
    {
        $this->riskKeyword->setDescription($description);
        $this->assertEquals($description, $this->riskKeyword->getDescription());
    }
    
    public function provideDescriptionData(): array
    {
        return [
            'null description' => [null],
            'empty description' => [''],
            'simple description' => ['这是一个危险词汇'],
            'long description' => ['这是一个包含敏感政治话题的词汇，应当被标记为高风险内容。用户使用此类词汇可能导致内容被屏蔽或账号被封禁。']
        ];
    }
    
    /**
     * @dataProvider provideAddedByData
     */
    public function testAddedByAccessors(?string $addedBy): void
    {
        $this->riskKeyword->setAddedBy($addedBy);
        $this->assertEquals($addedBy, $this->riskKeyword->getAddedBy());
    }
    
    public function provideAddedByData(): array
    {
        return [
            'null added by' => [null],
            'empty added by' => [''],
            'system added' => ['系统'],
            'admin added' => ['admin'],
            'moderator added' => ['moderator1']
        ];
    }
    
    public function testConstructor(): void
    {
        $riskKeyword = new RiskKeyword();
        $this->assertInstanceOf(\DateTimeImmutable::class, $riskKeyword->getUpdateTime());
    }
    
    public function testToString(): void
    {
        // 测试空关键词
        $emptyKeyword = new RiskKeyword();
        $this->assertEquals('', (string)$emptyKeyword);
        
        // 测试有值关键词
        $testKeyword = new RiskKeyword();
        $testKeyword->setKeyword('test');
        $this->assertEquals('test', (string)$testKeyword);
    }
} 