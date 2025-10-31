<?php

namespace AIContentAuditBundle\Tests\Repository;

use AIContentAuditBundle\Entity\ViolationRecord;
use AIContentAuditBundle\Enum\ViolationType;
use AIContentAuditBundle\Repository\ViolationRecordRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ViolationRecordRepository::class)]
#[RunTestsInSeparateProcesses]
final class ViolationRecordRepositoryTest extends AbstractRepositoryTestCase
{
    private ViolationRecordRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ViolationRecordRepository::class);
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

        $this->persistEntities([$record1, $record2, $record3]);

        // Test
        $results = $this->repository->findByUser('user1');

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('User 1 violation 2', $results[0]->getViolationContent());
        $this->assertEquals('User 1 violation 1', $results[1]->getViolationContent());
    }

    public function testFindByViolationType(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $uniqueId = uniqid();

        $record1 = new ViolationRecord();
        $record1->setUser('test_user_' . $uniqueId);
        $record1->setViolationContent('Political content - ' . $uniqueId);
        $record1->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record1->setProcessResult('已处理');
        $record1->setProcessedBy('admin');

        $record2 = new ViolationRecord();
        $record2->setUser('test_user_' . $uniqueId);
        $record2->setViolationContent('Violence content - ' . $uniqueId);
        $record2->setViolationType(ViolationType::MANUAL_DELETE);
        $record2->setProcessResult('已处理');
        $record2->setProcessedBy('admin');

        $record3 = new ViolationRecord();
        $record3->setUser('test_user_' . $uniqueId);
        $record3->setViolationContent('More political content - ' . $uniqueId);
        $record3->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record3->setProcessResult('已处理');
        $record3->setProcessedBy('admin');

        $this->persistEntities([$record1, $record2, $record3]);

        // Test
        $results = $this->repository->findByViolationType(ViolationType::MACHINE_HIGH_RISK);

        // Assert - should find at least our 2 test records
        $this->assertGreaterThanOrEqual(2, count($results));

        // Find our specific test records
        $foundRecord1 = false;
        $foundRecord3 = false;
        foreach ($results as $result) {
            if (str_contains($result->getViolationContent() ?? '', 'Political content - ' . $uniqueId)) {
                $foundRecord1 = true;
            }
            if (str_contains($result->getViolationContent() ?? '', 'More political content - ' . $uniqueId)) {
                $foundRecord3 = true;
            }
        }
        $this->assertTrue($foundRecord1, 'Should find our first test record');
        $this->assertTrue($foundRecord3, 'Should find our second test record');
    }

    public function testCountByViolationType(): void
    {
        $entities = [];
        $uniqueId = uniqid();

        // Create test data - machine high risk
        for ($i = 0; $i < 3; ++$i) {
            $record = new ViolationRecord();
            $record->setUser('test_user_' . $uniqueId);
            $record->setViolationContent("Violation {$i} - {$uniqueId}");
            $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
            $record->setProcessResult('内容已删除');
            $record->setProcessedBy('system');
            $entities[] = $record;
        }

        // manual delete
        for ($i = 0; $i < 2; ++$i) {
            $record = new ViolationRecord();
            $record->setUser('test_user_' . $uniqueId);
            $record->setViolationContent("Violation {$i} - {$uniqueId}");
            $record->setViolationType(ViolationType::MANUAL_DELETE);
            $record->setProcessResult('内容已删除');
            $record->setProcessedBy('admin');
            $entities[] = $record;
        }

        // repeated violation
        for ($i = 0; $i < 4; ++$i) {
            $record = new ViolationRecord();
            $record->setUser('test_user_' . $uniqueId);
            $record->setViolationContent("Violation {$i} - {$uniqueId}");
            $record->setViolationType(ViolationType::REPEATED_VIOLATION);
            $record->setProcessResult('用户已封禁');
            $record->setProcessedBy('system');
            $entities[] = $record;
        }

        // user report
        $record = new ViolationRecord();
        $record->setUser('test_user_' . $uniqueId);
        $record->setViolationContent("Violation - {$uniqueId}");
        $record->setViolationType(ViolationType::USER_REPORT);
        $record->setProcessResult('内容已审核');
        $record->setProcessedBy('admin');
        $entities[] = $record;

        $this->persistEntities($entities);

        // Test
        $counts = $this->repository->countByViolationType();

        // Assert - should find at least our test data plus any from fixtures
        $this->assertGreaterThanOrEqual(3, $counts['机器识别高风险内容']);
        $this->assertGreaterThanOrEqual(2, $counts['人工审核删除']);
        $this->assertGreaterThanOrEqual(4, $counts['重复违规']);
        $this->assertGreaterThanOrEqual(1, $counts['用户举报']);

        // Verify counts are reasonable (not too high)
        $this->assertLessThan(50, $counts['机器识别高风险内容'], 'Machine high risk count should be reasonable');
        $this->assertLessThan(50, $counts['人工审核删除'], 'Manual delete count should be reasonable');
        $this->assertLessThan(50, $counts['重复违规'], 'Repeated violation count should be reasonable');
        $this->assertLessThan(50, $counts['用户举报'], 'User report count should be reasonable');
    }

    public function testFindByDateRange(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $yesterday = new \DateTimeImmutable('-1 day');
        $today = new \DateTimeImmutable();
        $tomorrow = new \DateTimeImmutable('+1 day');
        $uniqueId = uniqid();

        $record1 = new ViolationRecord();
        $record1->setUser('test_user_' . $uniqueId);
        $record1->setViolationContent('Yesterday violation - ' . $uniqueId);
        $record1->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record1->setProcessResult('已处理');
        $record1->setProcessedBy('admin');
        $record1->setViolationTime($yesterday);

        $record2 = new ViolationRecord();
        $record2->setUser('test_user_' . $uniqueId);
        $record2->setViolationContent('Today violation - ' . $uniqueId);
        $record2->setViolationType(ViolationType::MANUAL_DELETE);
        $record2->setViolationTime($today);
        $record2->setProcessResult('已处理');
        $record2->setProcessedBy('admin');

        $record3 = new ViolationRecord();
        $record3->setUser('test_user_' . $uniqueId);
        $record3->setViolationContent('Tomorrow violation - ' . $uniqueId);
        $record3->setViolationType(ViolationType::USER_REPORT);
        $record3->setViolationTime($tomorrow);
        $record3->setProcessResult('已处理');
        $record3->setProcessedBy('admin');

        $this->persistEntities([$record1, $record2, $record3]);

        // Test
        $results = $this->repository->findByDateRange($yesterday, $today);

        // Assert - should find at least our 2 test records
        $this->assertGreaterThanOrEqual(2, count($results));

        // Find our specific test records
        $foundYesterday = false;
        $foundToday = false;
        foreach ($results as $result) {
            if (str_contains($result->getViolationContent() ?? '', 'Yesterday violation - ' . $uniqueId)) {
                $foundYesterday = true;
            }
            if (str_contains($result->getViolationContent() ?? '', 'Today violation - ' . $uniqueId)) {
                $foundToday = true;
            }
        }
        $this->assertTrue($foundYesterday, 'Should find our yesterday violation');
        $this->assertTrue($foundToday, 'Should find our today violation');
    }

    public function testCountByDateRange(): void
    {
        // Create test data
        $yesterday = new \DateTimeImmutable('-1 day');
        $today = new \DateTimeImmutable();
        $tomorrow = new \DateTimeImmutable('+1 day');

        $entities = [];

        for ($i = 0; $i < 3; ++$i) {
            $record = new ViolationRecord();
            $record->setUser('test');
            $record->setViolationContent("Violation {$i}");
            $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
            $record->setViolationTime($today);
            $record->setProcessResult('已处理');
            $record->setProcessedBy('admin');
            $entities[] = $record;
        }

        $recordFuture = new ViolationRecord();
        $recordFuture->setUser('test');
        $recordFuture->setViolationContent('Future violation');
        $recordFuture->setViolationType(ViolationType::USER_REPORT);
        $recordFuture->setViolationTime($tomorrow);
        $recordFuture->setProcessResult('已处理');
        $recordFuture->setProcessedBy('admin');
        $entities[] = $recordFuture;

        $this->persistEntities($entities);

        // Test
        $count = $this->repository->countByDateRange($yesterday, $today);

        // Assert - should find at least our test data plus any from fixtures
        $this->assertGreaterThanOrEqual(3, $count);
    }

    public function testCountByUser(): void
    {
        $entities = [];

        // Create test data
        for ($i = 0; $i < 5; ++$i) {
            $record = new ViolationRecord();
            $record->setUser('user1');
            $record->setViolationContent("User1 violation {$i}");
            $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
            $record->setProcessResult('已处理');
            $record->setProcessedBy('admin');
            $entities[] = $record;
        }

        for ($i = 0; $i < 3; ++$i) {
            $record = new ViolationRecord();
            $record->setUser('user2');
            $record->setViolationContent("User2 violation {$i}");
            $record->setViolationType(ViolationType::MANUAL_DELETE);
            $record->setProcessResult('已处理');
            $record->setProcessedBy('admin');
            $entities[] = $record;
        }

        $this->persistEntities($entities);

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
        $entities = [];

        // Old record (40 days ago)
        $oldRecord = new ViolationRecord();
        $oldRecord->setUser('test');
        $oldRecord->setViolationContent('Old violation');
        $oldRecord->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $oldRecord->setViolationTime(new \DateTimeImmutable('-40 days'));
        $oldRecord->setProcessResult('已处理');
        $oldRecord->setProcessedBy('admin');
        $entities[] = $oldRecord;

        // Recent records (within 30 days)
        for ($i = 1; $i <= 3; ++$i) {
            $record = new ViolationRecord();
            $record->setUser('test');
            $record->setViolationContent("Recent violation {$i}");
            $record->setViolationType(ViolationType::MANUAL_DELETE);
            $record->setViolationTime(new \DateTimeImmutable("-{$i} days"));
            $record->setProcessResult('已处理');
            $record->setProcessedBy('admin');
            $entities[] = $record;
        }

        $this->persistEntities($entities);

        // Test default 30 days - should find at least our test records
        $results = $this->repository->findRecent();
        $this->assertGreaterThanOrEqual(3, $results);

        // Test custom 50 days - should find at least our test records
        $results = $this->repository->findRecent(50);
        $this->assertGreaterThanOrEqual(4, $results);
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

        $this->persistEntities([$record1, $record2, $record3]);

        // Test
        $results = $this->repository->findByProcessedBy('admin');

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Admin processed 2', $results[0]->getViolationContent());
        $this->assertEquals('Admin processed 1', $results[1]->getViolationContent());
    }

    public function testSave(): void
    {
        $record = new ViolationRecord();
        $record->setUser('test_user');
        $record->setViolationContent('Test violation');
        $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record->setProcessResult('已处理');
        $record->setProcessedBy('admin');

        $this->repository->save($record);

        $this->assertNotNull($record->getId());

        $found = $this->repository->find($record->getId());
        $this->assertInstanceOf(ViolationRecord::class, $found);
        $this->assertEquals('test_user', $found->getUser());
    }

    public function testSaveWithoutFlush(): void
    {
        $record = new ViolationRecord();
        $record->setUser('test_user');
        $record->setViolationContent('Test violation');
        $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record->setProcessResult('已处理');
        $record->setProcessedBy('admin');

        $this->repository->save($record, false);
        $this->assertNull($record->getId());

        self::getEntityManager()->flush();
        $this->assertNotNull($record->getId());
    }

    public function testRemove(): void
    {
        $record = new ViolationRecord();
        $record->setUser('test_user');
        $record->setViolationContent('Test violation');
        $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record->setProcessResult('已处理');
        $record->setProcessedBy('admin');

        $persisted = $this->persistAndFlush($record);
        $this->assertInstanceOf(ViolationRecord::class, $persisted);
        $entityId = $persisted->getId();
        $this->assertNotNull($entityId);

        $this->repository->remove($persisted);

        $found = $this->repository->find($entityId);
        $this->assertNull($found);
    }

    public function testFindOneByWithOrderByClause(): void
    {
        $record1 = new ViolationRecord();
        $record1->setUser('test_user');
        $record1->setViolationContent('Z violation');
        $record1->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record1->setProcessResult('已处理');
        $record1->setProcessedBy('admin');

        $record2 = new ViolationRecord();
        $record2->setUser('test_user');
        $record2->setViolationContent('A violation');
        $record2->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record2->setProcessResult('已处理');
        $record2->setProcessedBy('admin');

        $this->persistEntities([$record1, $record2]);

        $result = $this->repository->findOneBy(['user' => 'test_user'], ['violationContent' => 'ASC']);

        $this->assertInstanceOf(ViolationRecord::class, $result);
        $this->assertEquals('A violation', $result->getViolationContent());
    }

    protected function getRepository(): ViolationRecordRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $record = new ViolationRecord();
        $record->setUser('test_user');
        $record->setViolationContent('Test violation');
        $record->setViolationType(ViolationType::MACHINE_HIGH_RISK);
        $record->setProcessResult('已处理');
        $record->setProcessedBy('admin');

        return $record;
    }
}
