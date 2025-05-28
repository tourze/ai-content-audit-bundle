<?php

namespace AIContentAuditBundle\Tests\Repository;

use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReportRepositoryTest extends TestCase
{
    private ReportRepository $repository;
    private MockObject $entityManager;
    private MockObject $queryBuilder;
    private MockObject $query;
    private MockObject $managerRegistry;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        
        // 设置ManagerRegistry返回EntityManager
        $this->managerRegistry->method('getManagerForClass')
            ->willReturn($this->entityManager);
            
        $this->repository = new ReportRepository($this->managerRegistry);
        
        // 设置QueryBuilder的默认行为
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('groupBy')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }
    
    public function testFindPendingReports()
    {
        $expectedReports = [
            $this->createReport(1, ProcessStatus::PENDING),
            $this->createReport(2, ProcessStatus::PENDING)
        ];
        
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('r.processStatus = :status')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('status', ProcessStatus::PENDING)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.reportTime', 'ASC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedReports);
            
        $result = $repositoryMock->findPendingReports();
        
        $this->assertEquals($expectedReports, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindProcessingReports()
    {
        $expectedReports = [
            $this->createReport(1, ProcessStatus::PROCESSING),
            $this->createReport(2, ProcessStatus::PROCESSING)
        ];
        
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('r.processStatus = :status')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('status', ProcessStatus::PROCESSING)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.reportTime', 'ASC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedReports);
            
        $result = $repositoryMock->findProcessingReports();
        
        $this->assertEquals($expectedReports, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindCompletedReports()
    {
        $expectedReports = [
            $this->createReport(1, ProcessStatus::COMPLETED),
            $this->createReport(2, ProcessStatus::COMPLETED)
        ];
        
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('r.processStatus = :status')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('status', ProcessStatus::COMPLETED)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.processTime', 'DESC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedReports);
            
        $result = $repositoryMock->findCompletedReports();
        
        $this->assertEquals($expectedReports, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindByReporterUser()
    {
        $userId = 123;
        $expectedReports = [
            $this->createReport(1, ProcessStatus::PENDING),
            $this->createReport(2, ProcessStatus::COMPLETED)
        ];
        
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('r.reporterUser = :userId')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.reportTime', 'DESC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedReports);
            
        $result = $repositoryMock->findByReporterUser($userId);
        
        $this->assertEquals($expectedReports, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindByReportedContent()
    {
        $contentId = 456;
        $expectedReports = [
            $this->createReport(1, ProcessStatus::PENDING),
            $this->createReport(2, ProcessStatus::PROCESSING)
        ];
        
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('r.reportedContent = :contentId')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('contentId', $contentId)
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.reportTime', 'DESC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedReports);
            
        $result = $repositoryMock->findByReportedContent($contentId);
        
        $this->assertEquals($expectedReports, $result);
        $this->assertCount(2, $result);
    }
    
    public function testCountByStatus()
    {
        $queryResults = [
            ['status' => ProcessStatus::PENDING, 'count' => 5],
            ['status' => ProcessStatus::PROCESSING, 'count' => 3],
            ['status' => ProcessStatus::COMPLETED, 'count' => 12]
        ];
        
        $expectedCounts = [
            '待审核' => 5,
            '审核中' => 3,
            '已处理' => 12
        ];
        
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('r.processStatus as status, COUNT(r.id) as count')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('r.processStatus')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResults);
            
        $result = $repositoryMock->countByStatus();
        
        $this->assertEquals($expectedCounts, $result);
    }
    
    public function testFindByDateRange()
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');
        $expectedReports = [
            $this->createReport(1, ProcessStatus::PENDING),
            $this->createReport(2, ProcessStatus::COMPLETED)
        ];
        
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
            
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.reportTime', 'DESC')
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedReports);
            
        $result = $repositoryMock->findByDateRange($startDate, $endDate);
        
        $this->assertEquals($expectedReports, $result);
        $this->assertCount(2, $result);
    }
    
    public function testFindPendingReports_withEmptyResult()
    {
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
            
        $result = $repositoryMock->findPendingReports();
        
        $this->assertEquals([], $result);
        $this->assertCount(0, $result);
    }
    
    public function testCountByStatus_withEmptyResult()
    {
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
            
        $result = $repositoryMock->countByStatus();
        
        $this->assertEquals([], $result);
    }
    
    public function testFindByReporterUser_withStringUserId()
    {
        $userId = 'user123';
        $expectedReports = [
            $this->createReport(1, ProcessStatus::PENDING)
        ];
        
        $repositoryMock = $this->getMockBuilder(ReportRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
            
        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('userId', $userId)
            ->willReturnSelf();
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedReports);
            
        $result = $repositoryMock->findByReporterUser($userId);
        
        $this->assertEquals($expectedReports, $result);
    }
    
    /**
     * 创建测试用的Report实例
     */
    private function createReport(int $id, ProcessStatus $status): Report
    {
        $report = new Report();
        $report->setReportReason('Test reason ' . $id);
        $report->setProcessStatus($status);
        $report->setReportTime(new \DateTimeImmutable());
        
        if ($status === ProcessStatus::COMPLETED) {
            $report->setProcessTime(new \DateTimeImmutable());
            $report->setProcessResult('Test result ' . $id);
        }
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($report);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($report, $id);
        
        return $report;
    }
} 