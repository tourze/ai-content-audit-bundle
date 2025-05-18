<?php

namespace AIContentAuditBundle\Tests\Service;

use AIContentAuditBundle\Entity\ViolationRecord;
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
    private EntityManagerInterface|MockObject $entityManager;
    private ViolationRecordRepository|MockObject $violationRepository;
    private LoggerInterface|MockObject $logger;
    private UserInterface|MockObject $user;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->violationRepository = $this->createMock(ViolationRecordRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->user = $this->getMockBuilder(UserInterface::class)
            ->addMethods(['getId'])
            ->getMockForAbstractClass();
        
        $this->service = new UserManagementService(
            $this->entityManager,
            $this->violationRepository,
            $this->logger
        );
        
        $this->user->method('getUserIdentifier')->willReturn('test_user');
        $this->user->method('getId')->willReturn(42);
    }
    
    public function testDisableUser_createsViolationRecord()
    {
        // 设置entityManager的期望行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) {
                return $record instanceof ViolationRecord
                    && $record->getProcessResult() === '账号已禁用'
                    && $record->getProcessedBy() === 'admin';
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->disableUser(
            $this->user,
            '多次违规，禁用账号',
            'admin'
        );
    }
    
    public function testEnableUser_createsViolationRecord()
    {
        // 设置entityManager的期望行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) {
                return $record instanceof ViolationRecord
                    && $record->getProcessResult() === '账号已恢复正常'
                    && $record->getProcessedBy() === 'admin';
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->enableUser(
            $this->user,
            '用户申诉通过，解除禁用',
            'admin'
        );
    }
    
    public function testReviewAppeal_withApprovedResult()
    {
        // 设置entityManager的期望行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) {
                return $record instanceof ViolationRecord
                    && $record->getProcessResult() === '申诉通过，账号已恢复'
                    && $record->getProcessedBy() === 'admin';
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->reviewAppeal(
            $this->user,
            '我保证遵守规则，请恢复我的账号',
            true,
            '申诉通过，账号已恢复',
            'admin'
        );
    }
    
    public function testReviewAppeal_withRejectedResult()
    {
        // 设置entityManager的期望行为
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($record) {
                return $record instanceof ViolationRecord
                    && $record->getProcessResult() === '申诉被拒绝，违规行为明显'
                    && $record->getProcessedBy() === 'admin';
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行方法
        $this->service->reviewAppeal(
            $this->user,
            '我没有违规，请恢复我的账号',
            false,
            '申诉被拒绝，违规行为明显',
            'admin'
        );
    }
    
    public function testGetUserViolationRecords_returnsViolationRecords()
    {
        // 创建测试违规记录
        $violationRecord1 = new ViolationRecord();
        $violationRecord2 = new ViolationRecord();
        $violationRecords = [$violationRecord1, $violationRecord2];
        
        // 设置violationRepository的期望行为
        $this->violationRepository->expects($this->once())
            ->method('findByUser')
            ->with(42)
            ->willReturn($violationRecords);
            
        // 执行方法
        $result = $this->service->getUserViolationRecords($this->user);
        
        // 断言结果
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame($violationRecords, $result);
    }
} 