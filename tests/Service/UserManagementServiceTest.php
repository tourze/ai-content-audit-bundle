<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use AIContentAuditBundle\Service\UserManagementService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserManagementServiceTest extends TestCase
{
    private UserManagementService $service;
    private MockObject $entityManager;
    private MockObject $violationRecordRepository;
    private MockObject $logger;
    private UserInterface $user;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->violationRecordRepository = $this->createMock(ViolationRecordRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // 创建一个简单的User实现来避免Mock问题
        $this->user = new class implements UserInterface {
            public function getId(): int { return 123; }
            public function getUserIdentifier(): string { return 'test_user'; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function eraseCredentials(): void { }
        };
        
        // 设置EntityManager返回Repository
        $this->entityManager->method('getRepository')
            ->with(ViolationRecord::class)
            ->willReturn($this->violationRecordRepository);
        
        $this->service = new UserManagementService(
            $this->entityManager,
            $this->violationRecordRepository,
            $this->logger
        );
    }
    
    public function testDisableUser()
    {
        $reason = '多次违规发布不当内容';
        $operator = 'admin';
        
        // 设置logger期望 - 应该记录警告和信息日志
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('禁用用户账号', [
                'userId' => 123,
                'reason' => $reason,
                'operator' => $operator
            ]);
            
        $this->logger->expects($this->once())
            ->method('info')
            ->with('用户账号已禁用', $this->callback(function ($context) {
                // 在测试环境中，violationId可能为null，因为实体还没有真正保存
                return $context['userId'] === 'test_user' && array_key_exists('violationId', $context);
            }));
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) use ($reason, $operator) {
                return $record instanceof ViolationRecord
                    && $record->getViolationContent() === $reason
                    && $record->getViolationType() === ViolationType::REPEATED_VIOLATION
                    && $record->getProcessResult() === '账号已禁用'
                    && $record->getProcessedBy() === $operator;
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->disableUser($this->user, $reason, $operator);
    }
    
    public function testEnableUser()
    {
        $reason = '申诉成功，恢复账号';
        $operator = 'admin';
        
        // 设置logger期望 - 应该记录两次信息日志
        $this->logger->expects($this->exactly(2))
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) use ($reason, $operator) {
                return $record instanceof ViolationRecord
                    && $record->getViolationContent() === '账号解禁: ' . $reason
                    && $record->getViolationType() === ViolationType::USER_REPORT
                    && $record->getProcessResult() === '账号已恢复正常'
                    && $record->getProcessedBy() === $operator;
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->enableUser($this->user, $reason, $operator);
    }
    
    public function testReviewAppeal_withApprovedResult()
    {
        $appealContent = '我认为我的内容没有违规';
        $approved = true;
        $result = '申诉成功，恢复内容';
        $operator = 'admin';
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info')
            ->with('开始复核用户申诉', [
                'userId' => 123,
                'approved' => true,
                'operator' => $operator
            ]);
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) use ($appealContent, $result, $operator) {
                return $record instanceof ViolationRecord
                    && $record->getViolationContent() === '用户申诉: ' . $appealContent
                    && $record->getViolationType() === ViolationType::MANUAL_DELETE
                    && $record->getProcessResult() === $result
                    && $record->getProcessedBy() === $operator;
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->reviewAppeal($this->user, $appealContent, $approved, $result, $operator);
    }
    
    public function testReviewAppeal_withRejectedResult()
    {
        $appealContent = '我认为处罚过重';
        $approved = false;
        $result = '申诉被驳回，维持原处罚';
        $operator = 'admin';
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info')
            ->with('开始复核用户申诉', [
                'userId' => 123,
                'approved' => false,
                'operator' => $operator
            ]);
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist');
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->reviewAppeal($this->user, $appealContent, $approved, $result, $operator);
    }
    
    public function testGetUserViolationRecords()
    {
        $expectedRecords = [
            new ViolationRecord(),
            new ViolationRecord(),
            new ViolationRecord()
        ];
        
        $this->violationRecordRepository->expects($this->once())
            ->method('findByUser')
            ->with(123)
            ->willReturn($expectedRecords);
            
        $result = $this->service->getUserViolationRecords($this->user);
        
        $this->assertEquals($expectedRecords, $result);
        $this->assertCount(3, $result);
    }
    
    public function testDisableUser_withEmptyReason()
    {
        $reason = '';
        $operator = 'admin';
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('warning');
            
        $this->logger->expects($this->once())
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) {
                return $record instanceof ViolationRecord
                    && $record->getViolationContent() === '';
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->disableUser($this->user, $reason, $operator);
    }
    
    public function testEnableUser_withLongReason()
    {
        $reason = str_repeat('很长的解禁原因 ', 50);
        $operator = 'admin';
        
        // 设置logger期望
        $this->logger->expects($this->exactly(2))
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) use ($reason) {
                return $record instanceof ViolationRecord
                    && $record->getViolationContent() === '账号解禁: ' . $reason;
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->enableUser($this->user, $reason, $operator);
    }
    
    public function testReviewAppeal_withEmptyAppealContent()
    {
        $appealContent = '';
        $approved = false;
        $result = '申诉内容为空，驳回申诉';
        $operator = 'admin';
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('info');
            
        // 设置entityManager期望
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) {
                return $record instanceof ViolationRecord
                    && $record->getViolationContent() === '用户申诉: ';
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->reviewAppeal($this->user, $appealContent, $approved, $result, $operator);
    }
    
    public function testGetUserViolationRecords_withNoRecords()
    {
        $this->violationRecordRepository->expects($this->once())
            ->method('findByUser')
            ->with(123)
            ->willReturn([]);
            
        $result = $this->service->getUserViolationRecords($this->user);
        
        $this->assertEquals([], $result);
        $this->assertCount(0, $result);
    }
    
    /**
     * 测试异常处理 - EntityManager异常
     */
    public function testDisableUser_withEntityManagerException()
    {
        $reason = '违规内容';
        $operator = 'admin';
        
        // 设置logger期望
        $this->logger->expects($this->once())
            ->method('warning');
            
        // 设置EntityManager抛出异常
        $this->entityManager->method('persist')
            ->willThrowException(new \Exception('Database error'));
            
        // 执行方法，应该抛出异常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');
        
        $this->service->disableUser($this->user, $reason, $operator);
    }
    
    /**
     * 测试异常处理 - Repository异常
     */
    public function testGetUserViolationRecords_withRepositoryException()
    {
        $this->violationRecordRepository->expects($this->once())
            ->method('findByUser')
            ->with(123)
            ->willThrowException(new \Exception('Query error'));
            
        // 执行方法，应该抛出异常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Query error');
        
        $this->service->getUserViolationRecords($this->user);
    }
} 