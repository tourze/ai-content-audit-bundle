<?php

namespace AIContentAuditBundle\Tests\Integration\Repository;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class ViolationRecordRepositoryTest extends KernelTestCase
{
    private ViolationRecordRepository $repository;
    private \Doctrine\ORM\EntityManagerInterface $entityManager;

    protected static function getKernelClass(): string
    {
        return \AIContentAuditBundle\Tests\TestKernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        // Create database schema
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
        
        $repository = $this->entityManager->getRepository(ViolationRecord::class);
        $this->assertInstanceOf(ViolationRecordRepository::class, $repository);
        $this->repository = $repository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->entityManager->close();
    }

    public function testFindByUser(): void
    {
        // Create test data
        $record1 = new ViolationRecord();
        $record1->setUser('user1');
        $record1->setViolationContent('User 1 violation 1');
        $record1->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record1->setProcessResult('已处理');
        $record1->setProcessedBy('admin');
        $record1->setViolationTime(new \DateTimeImmutable('-1 hour'));
        $record1->setProcessResult('已处理');
        $record1->setProcessedBy('admin');
        
        $record2 = new ViolationRecord();
        $record2->setUser('user2');
        $record2->setViolationContent('User 2 violation');
        $record2->setViolationType(ViolationType::MANUAL_DELETE);
        $record2->setViolationTime(new \DateTimeImmutable());
        $record2->setProcessResult('已处理');
        $record2->setProcessedBy('admin');
        
        $record3 = new ViolationRecord();
        $record3->setUser('user1');
        $record3->setViolationContent('User 1 violation 2');
        $record3->setViolationType(ViolationType::USER_REPORT);
        $record3->setViolationTime(new \DateTimeImmutable());
        $record3->setProcessResult('已处理');
        $record3->setProcessedBy('admin');
        
        $this->entityManager->persist($record1);
        $this->entityManager->persist($record2);
        $this->entityManager->persist($record3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByUser('user1');
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('User 1 violation 2', $results[0]->getViolationContent());
        $this->assertEquals('User 1 violation 1', $results[1]->getViolationContent());
    }

    public function testFindByViolationType(): void
    {
        // Create test data
        $record1 = new ViolationRecord();
        $record1->setUser('test');
        $record1->setViolationContent('Political content');
        $record1->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record1->setProcessResult('已处理');
        $record1->setProcessedBy('admin');
        
        $record2 = new ViolationRecord();
        $record2->setUser('test');
        $record2->setViolationContent('Violence content');
        $record2->setViolationType(ViolationType::MANUAL_DELETE);
        $record2->setProcessResult('已处理');
        $record2->setProcessedBy('admin');
        
        $record3 = new ViolationRecord();
        $record3->setUser('test');
        $record3->setViolationContent('More political content');
        $record3->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record3->setProcessResult('已处理');
        $record3->setProcessedBy('admin');
        
        $this->entityManager->persist($record1);
        $this->entityManager->persist($record2);
        $this->entityManager->persist($record3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByViolationType(ViolationType::MACHINE_HIGH_RISK);
        
        // Assert
        $this->assertCount(2, $results);
    }

    public function testCountByViolationType(): void
    {
        // Create test data - machine high risk
        for ($i = 0; $i < 3; $i++) {
            $record = new ViolationRecord();
            $record->setUser('test');
            $record->setViolationContent("Violation $i");
            $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
            $record->setProcessResult('内容已删除');
            $record->setProcessedBy('system');
            $this->entityManager->persist($record);
        }
        
        // manual delete
        for ($i = 0; $i < 2; $i++) {
            $record = new ViolationRecord();
            $record->setUser('test');
            $record->setViolationContent("Violation $i");
            $record->setViolationType(ViolationType::MANUAL_DELETE);
            $record->setProcessResult('内容已删除');
            $record->setProcessedBy('admin');
            $this->entityManager->persist($record);
        }
        
        // repeated violation
        for ($i = 0; $i < 4; $i++) {
            $record = new ViolationRecord();
            $record->setUser('test');
            $record->setViolationContent("Violation $i");
            $record->setViolationType(ViolationType::REPEATED_VIOLATION);
            $record->setProcessResult('用户已封禁');
            $record->setProcessedBy('system');
            $this->entityManager->persist($record);
        }
        
        // user report
        $record = new ViolationRecord();
        $record->setUser('test');
        $record->setViolationContent("Violation");
        $record->setViolationType(ViolationType::USER_REPORT);
        $record->setProcessResult('内容已审核');
        $record->setProcessedBy('admin');
        $this->entityManager->persist($record);
        
        $this->entityManager->flush();
        
        // Test
        $counts = $this->repository->countByViolationType();
        
        // Assert
        $this->assertEquals(3, $counts['机器识别高风险内容']);
        $this->assertEquals(2, $counts['人工审核删除']);
        $this->assertEquals(4, $counts['重复违规']);
        $this->assertEquals(1, $counts['用户举报']);
    }

    public function testFindByDateRange(): void
    {
        // Create test data
        $yesterday = new \DateTimeImmutable('-1 day');
        $today = new \DateTimeImmutable();
        $tomorrow = new \DateTimeImmutable('+1 day');
        
        $record1 = new ViolationRecord();
        $record1->setUser('test');
        $record1->setViolationContent('Yesterday violation');
        $record1->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record1->setProcessResult('已处理');
        $record1->setProcessedBy('admin');
        $record1->setViolationTime($yesterday);
        
        $record2 = new ViolationRecord();
        $record2->setUser('test');
        $record2->setViolationContent('Today violation');
        $record2->setViolationType(ViolationType::MANUAL_DELETE);
        $record2->setViolationTime($today);
        $record2->setProcessResult('已处理');
        $record2->setProcessedBy('admin');
        
        $record3 = new ViolationRecord();
        $record3->setUser('test');
        $record3->setViolationContent('Tomorrow violation');
        $record3->setViolationType(ViolationType::USER_REPORT);
        $record3->setViolationTime($tomorrow);
        $record3->setProcessResult('已处理');
        $record3->setProcessedBy('admin');
        
        $this->entityManager->persist($record1);
        $this->entityManager->persist($record2);
        $this->entityManager->persist($record3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByDateRange($yesterday, $today);
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Today violation', $results[0]->getViolationContent());
        $this->assertEquals('Yesterday violation', $results[1]->getViolationContent());
    }

    public function testCountByDateRange(): void
    {
        // Create test data
        $yesterday = new \DateTimeImmutable('-1 day');
        $today = new \DateTimeImmutable();
        $tomorrow = new \DateTimeImmutable('+1 day');
        
        for ($i = 0; $i < 3; $i++) {
            $record = new ViolationRecord();
            $record->setUser('test');
            $record->setViolationContent("Violation $i");
            $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
            $record->setViolationTime($today);
            $record->setProcessResult('已处理');
            $record->setProcessedBy('admin');
            $this->entityManager->persist($record);
        }
        
        $recordFuture = new ViolationRecord();
        $recordFuture->setUser('test');
        $recordFuture->setViolationContent('Future violation');
        $recordFuture->setViolationType(ViolationType::USER_REPORT);
        $recordFuture->setViolationTime($tomorrow);
        $recordFuture->setProcessResult('已处理');
        $recordFuture->setProcessedBy('admin');
        $this->entityManager->persist($recordFuture);
        
        $this->entityManager->flush();
        
        // Test
        $count = $this->repository->countByDateRange($yesterday, $today);
        
        // Assert
        $this->assertEquals(3, $count);
    }

    public function testCountByUser(): void
    {
        // Create test data
        for ($i = 0; $i < 5; $i++) {
            $record = new ViolationRecord();
            $record->setUser('user1');
            $record->setViolationContent("User1 violation $i");
            $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
            $record->setProcessResult('已处理');
            $record->setProcessedBy('admin');
            $this->entityManager->persist($record);
        }
        
        for ($i = 0; $i < 3; $i++) {
            $record = new ViolationRecord();
            $record->setUser('user2');
            $record->setViolationContent("User2 violation $i");
            $record->setViolationType(ViolationType::MANUAL_DELETE);
            $record->setProcessResult('已处理');
            $record->setProcessedBy('admin');
            $this->entityManager->persist($record);
        }
        
        $this->entityManager->flush();
        
        // Test
        $count1 = $this->repository->countByUser('user1');
        $count2 = $this->repository->countByUser('user2');
        
        // Assert
        $this->assertEquals(5, $count1);
        $this->assertEquals(3, $count2);
    }

    public function testFindRecent(): void
    {
        // Create test data
        
        // Old record (40 days ago)
        $oldRecord = new ViolationRecord();
        $oldRecord->setUser('test');
        $oldRecord->setViolationContent('Old violation');
        $oldRecord->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $oldRecord->setViolationTime(new \DateTimeImmutable('-40 days'));
        $oldRecord->setProcessResult('已处理');
        $oldRecord->setProcessedBy('admin');
        $this->entityManager->persist($oldRecord);
        
        // Recent records (within 30 days)
        for ($i = 1; $i <= 3; $i++) {
            $record = new ViolationRecord();
            $record->setUser('test');
            $record->setViolationContent("Recent violation $i");
            $record->setViolationType(ViolationType::MANUAL_DELETE);
            $record->setViolationTime(new \DateTimeImmutable("-{$i} days"));
            $record->setProcessResult('已处理');
            $record->setProcessedBy('admin');
            $this->entityManager->persist($record);
        }
        
        $this->entityManager->flush();
        
        // Test default 30 days
        $results = $this->repository->findRecent();
        $this->assertCount(3, $results);
        
        // Test custom 50 days
        $results = $this->repository->findRecent(50);
        $this->assertCount(4, $results);
    }

    public function testFindByProcessedBy(): void
    {
        // Create test data
        
        $record1 = new ViolationRecord();
        $record1->setUser('test');
        $record1->setViolationContent('Admin processed 1');
        $record1->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record1->setProcessResult('已处理');
        $record1->setProcessedBy('admin');
        $record1->setProcessedBy('admin');
        $record1->setProcessTime(new \DateTimeImmutable('-1 hour'));
        $record1->setProcessResult('Content deleted');
        
        $record2 = new ViolationRecord();
        $record2->setUser('test');
        $record2->setViolationContent('Manager processed');
        $record2->setViolationType(ViolationType::MANUAL_DELETE);
        $record2->setProcessedBy('manager');
        $record2->setProcessTime(new \DateTimeImmutable());
        $record2->setProcessResult('User warned');
        
        $record3 = new ViolationRecord();
        $record3->setUser('test');
        $record3->setViolationContent('Admin processed 2');
        $record3->setViolationType(ViolationType::USER_REPORT);
        $record3->setProcessedBy('admin');
        $record3->setProcessTime(new \DateTimeImmutable());
        $record3->setProcessResult('Account suspended');
        
        $this->entityManager->persist($record1);
        $this->entityManager->persist($record2);
        $this->entityManager->persist($record3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByProcessedBy('admin');
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Admin processed 2', $results[0]->getViolationContent());
        $this->assertEquals('Admin processed 1', $results[1]->getViolationContent());
    }
}