<?php

namespace AIContentAuditBundle\Tests\Entity;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(RiskKeyword::class)]
final class RiskKeywordTest extends AbstractEntityTestCase
{
    protected function createEntity(): RiskKeyword
    {
        return new RiskKeyword();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'keyword' => ['keyword', 'test_keyword'],
            'riskLevel' => ['riskLevel', RiskLevel::LOW_RISK],
            'updateTime' => ['updateTime', new \DateTimeImmutable()],
            'category' => ['category', 'test_category'],
            'description' => ['description', 'test_description'],
            'addedBy' => ['addedBy', 'test_user'],
        ];
    }

    #[DataProvider('provideKeywordData')]
    public function testKeywordAccessors(string $keyword): void
    {
        $entity = $this->createEntity();
        $entity->setKeyword($keyword);
        $this->assertEquals($keyword, $entity->getKeyword());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideKeywordData(): array
    {
        return [
            'simple keyword' => ['bad'],
            'keyword with spaces' => ['very bad'],
            'multilanguage keyword' => ['坏词'],
            'special characters' => ['bad-word!'],
        ];
    }

    #[DataProvider('provideRiskLevelData')]
    public function testRiskLevelAccessors(RiskLevel $riskLevel): void
    {
        $entity = $this->createEntity();
        $entity->setRiskLevel($riskLevel);
        $this->assertEquals($riskLevel, $entity->getRiskLevel());
    }

    /**
     * @return array<string, array{0: RiskLevel}>
     */
    public static function provideRiskLevelData(): array
    {
        return [
            'low risk' => [RiskLevel::LOW_RISK],
            'medium risk' => [RiskLevel::MEDIUM_RISK],
            'high risk' => [RiskLevel::HIGH_RISK],
        ];
    }

    public function testUpdateTimeAccessors(): void
    {
        $entity = $this->createEntity();
        $updateTime = new \DateTimeImmutable('-1 day');
        $entity->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $entity->getUpdateTime());
    }

    #[DataProvider('provideCategoryData')]
    public function testCategoryAccessors(?string $category): void
    {
        $entity = $this->createEntity();
        $entity->setCategory($category);
        $this->assertEquals($category, $entity->getCategory());
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function provideCategoryData(): array
    {
        return [
            'null category' => [null],
            'empty category' => [''],
            'political category' => ['政治'],
            'adult content' => ['色情'],
            'violence' => ['暴力'],
        ];
    }

    #[DataProvider('provideDescriptionData')]
    public function testDescriptionAccessors(?string $description): void
    {
        $entity = $this->createEntity();
        $entity->setDescription($description);
        $this->assertEquals($description, $entity->getDescription());
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function provideDescriptionData(): array
    {
        return [
            'null description' => [null],
            'empty description' => [''],
            'simple description' => ['这是一个危险词汇'],
            'long description' => ['这是一个包含敏感政治话题的词汇，应当被标记为高风险内容。用户使用此类词汇可能导致内容被屏蔽或账号被封禁。'],
        ];
    }

    #[DataProvider('provideAddedByData')]
    public function testAddedByAccessors(?string $addedBy): void
    {
        $entity = $this->createEntity();
        $entity->setAddedBy($addedBy);
        $this->assertEquals($addedBy, $entity->getAddedBy());
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function provideAddedByData(): array
    {
        return [
            'null added by' => [null],
            'empty added by' => [''],
            'system added' => ['系统'],
            'admin added' => ['admin'],
            'moderator added' => ['moderator1'],
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
        $this->assertEquals('', (string) $emptyKeyword);

        // 测试有值关键词
        $testKeyword = new RiskKeyword();
        $testKeyword->setKeyword('test');
        $this->assertEquals('test', (string) $testKeyword);
    }
}
