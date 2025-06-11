<?php

declare(strict_types=1);

namespace AIContentAuditBundle\DataFixtures;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use BizUserBundle\DataFixtures\BizUserFixtures;
use BizUserBundle\Entity\BizUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * 生成内容数据填充
 *
 * 创建AI生成内容测试数据，模拟各种风险等级和审核状态
 */
class GeneratedContentFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // 生成内容引用常量
    public const CONTENT_REFERENCE_PREFIX = 'generated-content-';

    // 输入提示模板
    private const INPUT_TEMPLATES = [
        '请生成一篇关于%s的文章',
        '我想了解关于%s的信息',
        '帮我写一个%s的介绍',
        '如何学习%s？',
        '详细讲解一下%s的原理',
        '给我一些关于%s的建议',
        '分析一下%s的优缺点',
        '请对%s做一个全面的介绍',
        '我需要一份关于%s的报告',
        '%s是什么？请详细解释'
    ];

    // 常见话题
    private const TOPICS = [
        '人工智能', '机器学习', '区块链', '云计算',
        '网络安全', '数据科学', '编程语言', '软件开发',
        '物联网', '加密货币', '大数据', '自动驾驶',
        '太空探索', '环境保护', '可再生能源', '气候变化',
        '健康生活', '营养学', '心理健康', '运动科学',
        '历史事件', '文化差异', '艺术欣赏', '音乐理论',
        '经济学', '投资策略', '创业指南', '职业发展',
        '烹饪技巧', '旅行目的地', '摄影技术', '时尚设计'
    ];

    // 敏感话题（用于生成高中风险内容）
    private const SENSITIVE_TOPICS = [
        '黑客技术', '投机赚钱', '灰色产业', '规避监管',
        '个人隐私', '政治立场', '宗教冲突', '种族问题',
        '武器制造', '极端思想', '地下交易', '避税方法',
        '激进言论', '社会矛盾', '暴力内容', '成人话题'
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_CN');

        // 创建100条内容记录
        for ($i = 1; $i <= 100; $i++) {
            // 随机选择用户（用户ID范围1-20）
            $userRef = BizUserFixtures::NORMAL_USER_REFERENCE_PREFIX . mt_rand(1, 20);
            $user = $this->getReference($userRef, BizUser::class);

            // 决定风险等级
            $riskLevel = $this->getRandomRiskLevel($i);

            // 生成输入和输出文本
            [$inputText, $outputText] = $this->generateContent($riskLevel, $faker);

            // 创建生成内容实体
            $content = new GeneratedContent();
            $content->setUser($user);
            $content->setInputText($inputText);
            $content->setOutputText($outputText);
            $content->setMachineAuditResult($riskLevel);

            // 设置机器审核时间（1到30天内的随机时间）
            $randomDays = mt_rand(1, 30);
            $machineAuditTime = new \DateTimeImmutable("-{$randomDays} days");
            $content->setMachineAuditTime($machineAuditTime);

            // 如果是中风险内容，有50%概率已经人工审核
            if ($riskLevel === RiskLevel::MEDIUM_RISK && mt_rand(0, 1) === 1) {
                $manualAuditResult = $this->getRandomManualAuditResult();
                $content->setManualAuditResult($manualAuditResult);

                // 人工审核时间在机器审核后1-3天
                $manualAuditTime = $machineAuditTime->modify('+' . mt_rand(1, 3) . ' days');
                $content->setManualAuditTime($manualAuditTime);
            }

            // 高风险内容自动审核为删除
            if ($riskLevel === RiskLevel::HIGH_RISK) {
                $content->setManualAuditResult(AuditResult::DELETE);
                $content->setManualAuditTime($machineAuditTime->modify('+1 hour'));
            }

            $manager->persist($content);
            $this->addReference(self::CONTENT_REFERENCE_PREFIX . $i, $content);
        }

        $manager->flush();
    }

    /**
     * 获取依赖关系
     */
    public function getDependencies(): array
    {
        return [
            BizUserFixtures::class,
            RiskKeywordFixtures::class
        ];
    }

    /**
     * 根据索引获取随机风险等级
     */
    private function getRandomRiskLevel(int $index): RiskLevel
    {
        // 通过索引控制风险分布：70%无风险，15%低风险，10%中风险，5%高风险
        if ($index <= 70) {
            return RiskLevel::NO_RISK;
        } elseif ($index <= 85) {
            return RiskLevel::LOW_RISK;
        } elseif ($index <= 95) {
            return RiskLevel::MEDIUM_RISK;
        } else {
            return RiskLevel::HIGH_RISK;
        }
    }

    /**
     * 获取随机人工审核结果
     */
    private function getRandomManualAuditResult(): AuditResult
    {
        $rand = mt_rand(1, 10);

        // 通过概率分布：通过60%，修改30%，删除10%
        if ($rand <= 6) {
            return AuditResult::PASS;
        } elseif ($rand <= 9) {
            return AuditResult::MODIFY;
        } else {
            return AuditResult::DELETE;
        }
    }

    /**
     * 生成内容文本
     */
    private function generateContent(RiskLevel $riskLevel, \Faker\Generator $faker): array
    {
        // 根据风险等级选择话题
        $topic = match ($riskLevel) {
            RiskLevel::HIGH_RISK, RiskLevel::MEDIUM_RISK => self::SENSITIVE_TOPICS[array_rand(self::SENSITIVE_TOPICS)],
            default => self::TOPICS[array_rand(self::TOPICS)]
        };

        // 生成输入提示
        $inputTemplate = self::INPUT_TEMPLATES[array_rand(self::INPUT_TEMPLATES)];
        $inputText = sprintf($inputTemplate, $topic);

        // 生成输出文本
        $paragraphs = $faker->paragraphs(mt_rand(3, 8));
        $outputText = implode("\n\n", $paragraphs);

        // 对于中高风险内容，插入关键词
        if ($riskLevel === RiskLevel::MEDIUM_RISK || $riskLevel === RiskLevel::HIGH_RISK) {
            // 获取相应风险等级的关键词
            $keywordIndices = range(1, 20); // 假设每个风险等级有20个关键词
            shuffle($keywordIndices);

            $keywordIndex = $keywordIndices[0];
            $keywordReference = match ($riskLevel) {
                RiskLevel::HIGH_RISK => RiskKeywordFixtures::KEYWORD_REFERENCE_PREFIX . $keywordIndex,
                RiskLevel::MEDIUM_RISK => RiskKeywordFixtures::KEYWORD_REFERENCE_PREFIX . (20 + $keywordIndex),
                default => null
            };

            if ($keywordReference) {
                try {
                    $keyword = $this->getReference($keywordReference, RiskKeyword::class);
                    // 在输出文本中插入关键词
                    $position = mt_rand(0, mb_strlen($outputText) - mb_strlen($keyword->getKeyword()));
                    $outputText = mb_substr($outputText, 0, $position) .
                        $keyword->getKeyword() .
                        mb_substr($outputText, $position);
                } catch (\Throwable $e) {
                    // 如果获取引用失败，不插入关键词
                }
            }
        }

        return [$inputText, $outputText];
    }

    public static function getGroups(): array
    {
        return ['ai-content-audit'];
    }
}
