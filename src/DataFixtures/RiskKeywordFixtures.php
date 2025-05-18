<?php

declare(strict_types=1);

namespace AIContentAuditBundle\DataFixtures;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * 风险关键词数据填充
 *
 * 创建不同风险等级的关键词数据
 */
class RiskKeywordFixtures extends Fixture implements FixtureGroupInterface
{
    // 风险关键词类别
    private const CATEGORIES = [
        '政治敏感',
        '暴力内容',
        '色情内容',
        '违法行为',
        '侮辱言论',
        '歧视内容',
        '个人隐私',
        '商业机密',
        '有害信息',
        '诈骗内容'
    ];

    // 添加人员
    private const ADDED_BY = [
        'admin',
        'moderator',
        'system',
        'ai_detection'
    ];

    // 高风险关键词
    private const HIGH_RISK_KEYWORDS = [
        '恐怖袭击', '制作炸弹', '非法枪支', '贩卖军火', '人口贩卖',
        '虐待儿童', '毒品制造', '贩卖毒品', '洗钱方法', '逃税攻略',
        '黑客攻击', '盗取密码', '破解银行', '非法入侵', '网络攻击',
        '绕过安检', '伪造证件', '假币制作', '骗取补贴', '非法采矿'
    ];

    // 中风险关键词
    private const MEDIUM_RISK_KEYWORDS = [
        '赌博网站', '代理投注', '博彩平台', '地下钱庄', '非法集资',
        '侮辱言论', '人身攻击', '挑拨离间', '歧视言论', '散布谣言',
        '盗版软件', '激活工具', '破解补丁', '绕过验证', '跳过付费',
        '隐私数据', '个人信息', '未经同意', '侵犯权益', '偷拍视频',
        '负面评价', '商业抹黑', '恶意差评', '虚假宣传', '夸大功效'
    ];

    // 低风险关键词
    private const LOW_RISK_KEYWORDS = [
        '有偿服务', '快速解决', '灰色地带', '监管漏洞', '钻空子',
        '负面情绪', '不满言论', '过激表达', '情绪宣泄', '抱怨投诉',
        '擦边球', '模糊界限', '边缘试探', '暗示内容', '引导消费',
        '未经证实', '道听途说', '小道消息', '传言', '据说',
        '内部资料', '非公开信息', '保密内容', '限制传播', '仅供参考'
    ];

    // 所有关键词引用的前缀
    public const KEYWORD_REFERENCE_PREFIX = 'keyword-';

    public function load(ObjectManager $manager): void
    {
        $keywordCount = 0;

        // 创建高风险关键词
        foreach (self::HIGH_RISK_KEYWORDS as $keyword) {
            $keywordEntity = $this->createKeyword($keyword, RiskLevel::HIGH_RISK);
            $manager->persist($keywordEntity);
            $this->addReference(self::KEYWORD_REFERENCE_PREFIX . ++$keywordCount, $keywordEntity);
        }

        // 创建中风险关键词
        foreach (self::MEDIUM_RISK_KEYWORDS as $keyword) {
            $keywordEntity = $this->createKeyword($keyword, RiskLevel::MEDIUM_RISK);
            $manager->persist($keywordEntity);
            $this->addReference(self::KEYWORD_REFERENCE_PREFIX . ++$keywordCount, $keywordEntity);
        }

        // 创建低风险关键词
        foreach (self::LOW_RISK_KEYWORDS as $keyword) {
            $keywordEntity = $this->createKeyword($keyword, RiskLevel::LOW_RISK);
            $manager->persist($keywordEntity);
            $this->addReference(self::KEYWORD_REFERENCE_PREFIX . ++$keywordCount, $keywordEntity);
        }

        // 生成更多随机关键词 (每个风险等级额外20个)
        $this->generateRandomKeywords($manager, RiskLevel::HIGH_RISK, 20, $keywordCount);
        $this->generateRandomKeywords($manager, RiskLevel::MEDIUM_RISK, 20, $keywordCount);
        $this->generateRandomKeywords($manager, RiskLevel::LOW_RISK, 20, $keywordCount);

        $manager->flush();
    }

    /**
     * 创建关键词实体
     */
    private function createKeyword(string $keyword, RiskLevel $riskLevel): RiskKeyword
    {
        $keywordEntity = new RiskKeyword();
        $keywordEntity->setKeyword($keyword);
        $keywordEntity->setRiskLevel($riskLevel);
        $keywordEntity->setUpdateTime(new \DateTimeImmutable());

        // 随机设置分类和添加人
        $keywordEntity->setCategory(self::CATEGORIES[array_rand(self::CATEGORIES)]);
        $keywordEntity->setAddedBy(self::ADDED_BY[array_rand(self::ADDED_BY)]);

        // 生成说明
        $keywordEntity->setDescription($this->generateDescription($keyword, $riskLevel));

        return $keywordEntity;
    }

    /**
     * 生成随机关键词数据
     */
    private function generateRandomKeywords(ObjectManager $manager, RiskLevel $riskLevel, int $count, int &$keywordCount): void
    {
        $prefix = match ($riskLevel) {
            RiskLevel::HIGH_RISK => ['严重', '危险', '极端', '非法', '恶意'],
            RiskLevel::MEDIUM_RISK => ['不当', '敏感', '争议', '问题', '可疑'],
            RiskLevel::LOW_RISK => ['轻微', '边缘', '潜在', '可能', '疑似']
        };

        $suffix = ['内容', '言论', '表述', '信息', '描述', '行为', '操作', '活动'];

        for ($i = 1; $i <= $count; $i++) {
            $randomKeyword = $prefix[array_rand($prefix)] .
                mt_rand(1, 99) .
                $suffix[array_rand($suffix)];

            $keywordEntity = $this->createKeyword($randomKeyword, $riskLevel);
            $manager->persist($keywordEntity);
            $this->addReference(self::KEYWORD_REFERENCE_PREFIX . ++$keywordCount, $keywordEntity);
        }
    }

    /**
     * 生成关键词描述
     */
    private function generateDescription(string $keyword, RiskLevel $riskLevel): string
    {
        $riskDescription = match ($riskLevel) {
            RiskLevel::HIGH_RISK => '高风险关键词，需要立即处理。',
            RiskLevel::MEDIUM_RISK => '中等风险关键词，需要人工审核。',
            RiskLevel::LOW_RISK => '低风险关键词，可能需要关注。'
        };

        return "关键词「{$keyword}」是{$riskDescription}可能涉及不适当内容，需要按照内容审核指南进行评估。";
    }

    public static function getGroups(): array
    {
        return ['ai-content-audit'];
    }
}
