<?php

declare(strict_types=1);

namespace AIContentAuditBundle\DataFixtures;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use BizUserBundle\DataFixtures\BizUserFixtures;
use BizUserBundle\Entity\BizUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * 违规记录数据填充
 *
 * 创建各类型的违规记录测试数据
 */
class ViolationRecordFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // 常量定义引用名称
    public const VIOLATION_RECORD_REFERENCE_PREFIX = 'violation-record-';

    // 违规内容模板
    private const VIOLATION_CONTENT_TEMPLATES = [
        '用户尝试生成关于%s的内容，已被系统拦截',
        '检测到用户输入包含%s内容，违反平台规定',
        '用户生成的回答中涉及%s，系统判定为违规',
        '内容中包含敏感词"%s"，被标记为违规',
        '用户提问中含有%s相关描述，需要审核',
        '系统检测到回答中提及%s，已标记为违规',
        '生成内容涉及%s话题，被系统自动拦截',
        '用户试图获取关于%s的信息，违反使用规范',
        '内容模型生成了包含%s的回答，被管理员删除',
        '审核发现用户多次尝试询问%s相关问题'
    ];

    // 违规主题
    private const VIOLATION_SUBJECTS = [
        '黑客技术', '非法活动', '敏感政治话题', '歧视言论',
        '暴力内容', '不当言论', '成人内容', '侵犯隐私',
        '虚假信息', '极端思想', '欺诈行为', '个人攻击',
        '版权侵权', '未经授权数据', '恶意软件', '绕过系统限制'
    ];

    // 处理结果模板
    private const PROCESS_RESULTS = [
        '内容已删除，用户已收到警告',
        '违规内容已屏蔽，暂时限制用户功能',
        '根据审核规则，该内容已被删除',
        '违规内容已移除，记录用户违规历史',
        '首次违规，已发出警告并删除内容',
        '多次违规，账号已被限制使用一周',
        '严重违规，永久禁止使用AI生成功能',
        '内容已修改，移除违规部分',
        '违规情节较轻，用户已被告知规则',
        '系统自动处理，内容未公开展示'
    ];

    // 处理人员
    private const PROCESSORS = [
        'admin', 'moderator', 'system', 'ai_audit', 'content_review'
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_CN');

        // 创建40条违规记录
        for ($i = 1; $i <= 10; $i++) {
            // 随机选择用户（有80%是普通用户，20%是重复违规用户）
            if (mt_rand(1, 100) <= 80) {
                // 随机普通用户
                $userRef = BizUserFixtures::NORMAL_USER_REFERENCE_PREFIX . mt_rand(1, 20);
            } else {
                // 违规重复用户（选择1-5号用户）
                $userRef = BizUserFixtures::NORMAL_USER_REFERENCE_PREFIX . mt_rand(1, 5);
            }
            $user = $this->getReference($userRef, BizUser::class);

            // 创建违规记录
            $violationRecord = new ViolationRecord();
            $violationRecord->setUser($user);

            // 设置违规时间（1到30天内的随机时间）
            $randomDays = mt_rand(1, 30);
            $violationTime = new \DateTimeImmutable("-{$randomDays} days");
            $violationRecord->setViolationTime($violationTime);

            // 生成违规内容
            $violationSubject = self::VIOLATION_SUBJECTS[array_rand(self::VIOLATION_SUBJECTS)];
            $contentTemplate = self::VIOLATION_CONTENT_TEMPLATES[array_rand(self::VIOLATION_CONTENT_TEMPLATES)];
            $violationContent = sprintf($contentTemplate, $violationSubject);
            if (mt_rand(1, 10) > 7) {
                // 30%概率添加额外内容
                $violationContent .= "\n\n" . $faker->paragraph(mt_rand(2, 5));
            }
            $violationRecord->setViolationContent($violationContent);

            // 设置违规类型
            $violationType = $this->getViolationType($i);
            $violationRecord->setViolationType($violationType);

            // 设置处理结果和处理时间
            $processResult = self::PROCESS_RESULTS[array_rand(self::PROCESS_RESULTS)];
            $violationRecord->setProcessResult($processResult);

            // 处理时间通常在违规后的1小时到1天内
            $processHours = mt_rand(1, 24);
            $processTime = $violationTime->modify("+{$processHours} hours");
            $violationRecord->setProcessTime($processTime);

            // 设置处理人员
            if ($violationType === ViolationType::MACHINE_HIGH_RISK) {
                $violationRecord->setProcessedBy('system');
            } else {
                $violationRecord->setProcessedBy(self::PROCESSORS[array_rand(self::PROCESSORS)]);
            }

            $manager->persist($violationRecord);
            $this->addReference(self::VIOLATION_RECORD_REFERENCE_PREFIX . $i, $violationRecord);
        }

        $manager->flush();
    }

    /**
     * 根据索引获取违规类型
     *
     * 分布：40% 机器识别高风险，30% 人工审核删除，20% 用户举报，10% 重复违规
     */
    private function getViolationType(int $index): ViolationType
    {
        if ($index <= 16) {
            return ViolationType::MACHINE_HIGH_RISK;
        } elseif ($index <= 28) {
            return ViolationType::MANUAL_DELETE;
        } elseif ($index <= 36) {
            return ViolationType::USER_REPORT;
        } else {
            return ViolationType::REPEATED_VIOLATION;
        }
    }

    /**
     * 获取依赖关系
     */
    public function getDependencies(): array
    {
        return [
            BizUserFixtures::class
        ];
    }

    public static function getGroups(): array
    {
        return ['ai-content-audit'];
    }
}
