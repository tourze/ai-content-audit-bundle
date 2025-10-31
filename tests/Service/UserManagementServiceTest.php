<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use AIContentAuditBundle\Service\UserManagementService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(UserManagementService::class)]
#[RunTestsInSeparateProcesses]
final class UserManagementServiceTest extends AbstractIntegrationTestCase
{
    private UserManagementService $service;

    private ViolationRecordRepository $violationRecordRepository;

    protected function onSetUp(): void
    {
        $this->service = self::getService(UserManagementService::class);
        $this->violationRecordRepository = self::getService(ViolationRecordRepository::class);
    }

    public function testServiceExists(): void
    {
        // 验证服务可以正确获取
        $this->assertInstanceOf(UserManagementService::class, $this->service);
    }

    public function testDisableUser(): void
    {
        $userId = 'test_user_123';
        $reason = '违规发布内容';
        $operator = 'admin_001';

        // 执行禁用用户操作
        $this->service->disableUser($userId, $reason, $operator);

        // 验证违规记录是否正确创建
        $violationRecords = $this->violationRecordRepository
            ->findBy(['user' => $userId])
        ;

        $this->assertNotEmpty($violationRecords);
        $record = $violationRecords[0];

        $this->assertEquals($userId, $record->getUser());
        $this->assertEquals($reason, $record->getViolationContent());
        $this->assertEquals(ViolationType::REPEATED_VIOLATION, $record->getViolationType());
        $this->assertEquals('账号已禁用', $record->getProcessResult());
        $this->assertEquals($operator, $record->getProcessedBy());
        $this->assertNotNull($record->getId());
    }

    public function testEnableUser(): void
    {
        $userId = 'test_user_456';
        $reason = '申诉成功，恢复账号';
        $operator = 'admin_002';

        // 执行解禁用户操作
        $this->service->enableUser($userId, $reason, $operator);

        // 验证解禁记录是否正确创建
        $violationRecords = $this->violationRecordRepository
            ->findBy(['user' => $userId])
        ;

        $this->assertNotEmpty($violationRecords);
        $record = $violationRecords[0];

        $this->assertEquals($userId, $record->getUser());
        $this->assertEquals('账号解禁: ' . $reason, $record->getViolationContent());
        $this->assertEquals(ViolationType::USER_REPORT, $record->getViolationType());
        $this->assertEquals('账号已恢复正常', $record->getProcessResult());
        $this->assertEquals($operator, $record->getProcessedBy());
        $this->assertNotNull($record->getId());
    }

    public function testReviewAppeal(): void
    {
        $userId = 'test_user_789';
        $appealContent = '我的内容是正当的，请求恢复';
        $approved = true;
        $result = '申诉通过，恢复正常';
        $operator = 'admin_003';

        // 执行申诉复核操作
        $this->service->reviewAppeal($userId, $appealContent, $approved, $result, $operator);

        // 验证申诉记录是否正确创建
        $violationRecords = $this->violationRecordRepository
            ->findBy(['user' => $userId])
        ;

        $this->assertNotEmpty($violationRecords);
        $record = $violationRecords[0];

        $this->assertEquals($userId, $record->getUser());
        $this->assertEquals('用户申诉: ' . $appealContent, $record->getViolationContent());
        $this->assertEquals(ViolationType::MANUAL_DELETE, $record->getViolationType());
        $this->assertEquals($result, $record->getProcessResult());
        $this->assertEquals($operator, $record->getProcessedBy());
        $this->assertNotNull($record->getId());
    }

    public function testReviewAppealRejected(): void
    {
        $userId = 'test_user_rejected';
        $appealContent = '请求恢复账号';
        $approved = false;
        $result = '申诉被驳回，维持原处理结果';
        $operator = 'admin_004';

        // 执行申诉复核操作（拒绝）
        $this->service->reviewAppeal($userId, $appealContent, $approved, $result, $operator);

        // 验证申诉记录是否正确创建
        $violationRecords = $this->violationRecordRepository
            ->findBy(['user' => $userId])
        ;

        $this->assertNotEmpty($violationRecords);
        $record = $violationRecords[0];

        $this->assertEquals($result, $record->getProcessResult());
        $this->assertEquals($operator, $record->getProcessedBy());
    }

    public function testGetUserViolationRecords(): void
    {
        $userId = 'test_user_violations';
        $operator = 'admin_005';

        // 创建多个违规记录
        $this->service->disableUser($userId, '第一次违规', $operator);
        $this->service->enableUser($userId, '申诉成功', $operator);
        $this->service->disableUser($userId, '第二次违规', $operator);

        // 获取用户违规记录
        $violationRecords = $this->service->getUserViolationRecords($userId);

        // 验证记录数量和内容
        $this->assertCount(3, $violationRecords);

        // 验证每条记录都属于指定用户
        foreach ($violationRecords as $record) {
            $this->assertEquals($userId, $record->getUser());
            $this->assertEquals($operator, $record->getProcessedBy());
        }
    }

    public function testGetUserViolationRecordsWithNoRecords(): void
    {
        $userId = 'non_existent_user';

        // 获取不存在用户的违规记录
        $violationRecords = $this->service->getUserViolationRecords($userId);

        // 应该返回空数组
        $this->assertEmpty($violationRecords);
    }

    public function testDisableUserWithStringUserId(): void
    {
        $userId = 'user_string_id';
        $reason = '字符串用户ID测试';
        $operator = 'admin_string';

        // 执行禁用用户操作
        $this->service->disableUser($userId, $reason, $operator);

        // 验证记录是否正确创建
        $violationRecords = $this->violationRecordRepository
            ->findBy(['user' => $userId])
        ;

        $this->assertNotEmpty($violationRecords);
        $this->assertEquals($userId, $violationRecords[0]->getUser());
    }

    public function testDisableUserWithIntegerUserId(): void
    {
        $userId = 12345;
        $reason = '数字用户ID测试';
        $operator = 'admin_int';

        // 执行禁用用户操作
        $this->service->disableUser($userId, $reason, $operator);

        // 验证记录是否正确创建
        $violationRecords = $this->violationRecordRepository
            ->findBy(['user' => $userId])
        ;

        $this->assertNotEmpty($violationRecords);
        $this->assertEquals($userId, $violationRecords[0]->getUser());
    }
}
