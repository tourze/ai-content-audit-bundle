<?php

declare(strict_types=1);

namespace AIContentAuditBundle\DataFixtures;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use BizUserBundle\DataFixtures\BizUserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * 举报数据填充
 *
 * 创建各种状态的举报测试数据
 */
class ReportFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // 常量定义引用名称
    public const REPORT_REFERENCE_PREFIX = 'report-';

    // 举报理由模板
    private const REPORT_REASONS = [
        '内容涉嫌违规，包含敏感词"%s"',
        '该内容存在%s问题，违反了社区规范',
        '用户生成的内容中包含%s，建议审核',
        '检测到不适当的%s内容，请管理员处理',
        '该回复含有%s，属于违规信息',
        '内容中的"%s"描述涉嫌违反平台规则',
        '举报用户发布的%s相关内容，违反社区准则',
        '该内容疑似%s，请尽快处理',
        '内容中出现"%s"等违规词汇，建议审核',
        '该回答含有%s要素，不符合平台规定'
    ];

    // 违规类型
    private const VIOLATION_TYPES = [
        '政治敏感', '暴力', '色情', '欺诈', '歧视',
        '侮辱', '隐私', '版权', '虚假信息', '极端言论'
    ];

    // 处理结果模板
    private const PROCESS_RESULTS = [
        '举报属实，已删除相关内容',
        '经审核违规情况确认，内容已修改',
        '已警告用户并要求修改内容',
        '举报内容部分属实，已进行相应处理',
        '违规情况属实，对用户进行了处罚',
        '经审核未发现明显违规，但已做标记',
        '举报不属实，内容符合社区规范',
        '内容在可接受范围内，不予处理',
        '已告知用户注意相关内容的表述',
        '已对内容进行脱敏处理'
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_CN');

        // 创建50条举报记录
        for ($i = 1; $i <= 50; $i++) {
            // 随机选择举报用户（1-20号普通用户）
            $reporterUserRef = BizUserFixtures::NORMAL_USER_REFERENCE_PREFIX . mt_rand(1, 20);
            $reporterUser = $this->getReference($reporterUserRef, UserInterface::class);

            // 随机选择被举报内容（1-100号生成内容）
            $contentRef = GeneratedContentFixtures::CONTENT_REFERENCE_PREFIX . mt_rand(1, 100);
            $content = $this->getReference($contentRef, GeneratedContent::class);

            // 创建举报记录
            $report = new Report();
            $report->setReporterUser($reporterUser);
            $report->setReportedContent($content);

            // 设置随机举报理由
            $violationType = self::VIOLATION_TYPES[array_rand(self::VIOLATION_TYPES)];
            $reportReasonTemplate = self::REPORT_REASONS[array_rand(self::REPORT_REASONS)];
            $report->setReportReason(sprintf($reportReasonTemplate, $violationType));

            // 设置举报时间（1到14天内的随机时间）
            $randomDays = mt_rand(1, 14);
            $reportTime = new \DateTimeImmutable("-{$randomDays} days");
            $report->setReportTime($reportTime);

            // 设置处理状态，按比例分配：40%待处理，20%处理中，40%已处理
            if ($i <= 20) {
                $report->setProcessStatus(ProcessStatus::PENDING);
            } elseif ($i <= 30) {
                $report->setProcessStatus(ProcessStatus::PROCESSING);
            } else {
                $report->setProcessStatus(ProcessStatus::COMPLETED);

                // 已处理的举报添加处理时间和处理结果
                $processTime = $reportTime->modify('+' . mt_rand(1, 3) . ' days');
                $report->setProcessTime($processTime);
                $report->setProcessResult(self::PROCESS_RESULTS[array_rand(self::PROCESS_RESULTS)]);
            }

            $manager->persist($report);
            $this->addReference(self::REPORT_REFERENCE_PREFIX . $i, $report);
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
            GeneratedContentFixtures::class
        ];
    }

    public static function getGroups(): array
    {
        return ['ai-content-audit'];
    }
}
