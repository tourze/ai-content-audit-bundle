<?php

namespace AIContentAuditBundle\Tests\Repository;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViolationRecordRepositoryTest extends TestCase
{
    private ViolationRecordRepository $repository;
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
        
        $this->managerRegistry->method('getManagerForClass')
            ->willReturn($this->entityManager);
            
        $this->repository = new ViolationRecordRepository($this->managerRegistry);
        
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('groupBy')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }
    
    public function testFindByUser()
    {
        $userId = 123;
        $expectedRecords = [
            $this->createViolationRecord(1, ViolationType::USER_REPORT),
            $this->createViolationRecord(2, ViolationType::MANUAL_DELETE)
        ];
        
        $repositoryMock = $this->getMockBuilder(ViolationRecordRepository::class)
            ->setConstructorArgs([$this->managerRegistry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
            
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('v')
            ->willReturn($this->queryBuilder);
            
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedRecords);
            
        $result = $repositoryMock->findByUser($userId);
        
        $this->assertEquals($expectedRecords, $result);
        $this->assertCount(2, $result);
    }
    
    /**
     * 创建测试用的ViolationRecord实例
     */
    private function createViolationRecord(int $id, ViolationType $type): ViolationRecord
    {
        $record = new ViolationRecord();
        $record->setViolationType($type);
        $record->setViolationContent('测试违规内容');
        $record->setProcessResult('测试处理结果');
        $record->setViolationTime(new \DateTimeImmutable());
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass($record);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($record, $id);
        
        return $record;
    }
} 