<?php

namespace AIContentAuditBundle\Tests\Repository;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\ReportRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ReportRepository::class)]
#[RunTestsInSeparateProcesses]
final class ReportRepositoryTest extends AbstractRepositoryTestCase
{
    private ReportRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getService(ReportRepository::class);
        self::assertInstanceOf(ReportRepository::class, $repository);
        $this->repository = $repository;
    }

    private function createGeneratedContent(): GeneratedContent
    {
        $content = new GeneratedContent();
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setUser('test_user');
        $content->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $result = $this->persistAndFlush($content);
        $this->assertInstanceOf(GeneratedContent::class, $result);

        return $result;
    }

    public function testFindPendingReports(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $content = $this->createGeneratedContent();
        $uniqueId = uniqid();

        $report1 = new Report();
        $report1->setReportReason('Test reason 1 - ' . $uniqueId);
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable('-2 hours'));
        $report1->setReporterUser('reporter_' . $uniqueId);
        $report1->setReportedContent($content);

        $report2 = new Report();
        $report2->setReportReason('Test reason 2 - ' . $uniqueId);
        $report2->setProcessStatus(ProcessStatus::PROCESSING);
        $report2->setReportTime(new \DateTimeImmutable('-1 hour'));
        $report2->setReporterUser('reporter_' . $uniqueId);
        $report2->setReportedContent($content);

        $report3 = new Report();
        $report3->setReportReason('Test reason 3 - ' . $uniqueId);
        $report3->setProcessStatus(ProcessStatus::PENDING);
        $report3->setReportTime(new \DateTimeImmutable());
        $report3->setReporterUser('reporter_' . $uniqueId);
        $report3->setReportedContent($content);

        $this->persistEntities([$report1, $report2, $report3]);

        // Test
        $results = $this->repository->findPendingReports();

        // Assert - should find at least our 2 test reports
        $this->assertGreaterThanOrEqual(2, count($results));

        // Find our specific test reports
        $foundReport1 = false;
        $foundReport3 = false;
        foreach ($results as $result) {
            if (str_contains($result->getReportReason() ?? '', 'Test reason 1 - ' . $uniqueId)) {
                $foundReport1 = true;
            }
            if (str_contains($result->getReportReason() ?? '', 'Test reason 3 - ' . $uniqueId)) {
                $foundReport3 = true;
            }
        }
        $this->assertTrue($foundReport1, 'Should find our first pending report');
        $this->assertTrue($foundReport3, 'Should find our third pending report');
    }

    public function testFindProcessingReports(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $content = $this->createGeneratedContent();
        $uniqueId = uniqid();

        $report1 = new Report();
        $report1->setReportReason('Processing 1 - ' . $uniqueId);
        $report1->setProcessStatus(ProcessStatus::PROCESSING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('reporter_' . $uniqueId);
        $report1->setReportedContent($content);

        $report2 = new Report();
        $report2->setReportReason('Pending - ' . $uniqueId);
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('reporter_' . $uniqueId);
        $report2->setReportedContent($content);

        $this->persistEntities([$report1, $report2]);

        // Test
        $results = $this->repository->findProcessingReports();

        // Assert - should find at least our test report
        $this->assertGreaterThanOrEqual(1, count($results));

        // Find our specific test report
        $foundOurReport = false;
        foreach ($results as $result) {
            if (str_contains($result->getReportReason() ?? '', 'Processing 1 - ' . $uniqueId)) {
                $foundOurReport = true;
                break;
            }
        }
        $this->assertTrue($foundOurReport, 'Should find our processing report');
    }

    public function testFindCompletedReports(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $content = $this->createGeneratedContent();
        $uniqueId = uniqid();

        $report1 = new Report();
        $report1->setReportReason('Completed 1 - ' . $uniqueId);
        $report1->setProcessStatus(ProcessStatus::COMPLETED);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setProcessTime(new \DateTimeImmutable());
        $report1->setReporterUser('reporter_' . $uniqueId);
        $report1->setReportedContent($content);

        $report2 = new Report();
        $report2->setReportReason('Pending - ' . $uniqueId);
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('reporter_' . $uniqueId);
        $report2->setReportedContent($content);

        $this->persistEntities([$report1, $report2]);

        // Test
        $results = $this->repository->findCompletedReports();

        // Assert - should find at least our test report
        $this->assertGreaterThanOrEqual(1, count($results));

        // Find our specific test report
        $foundOurReport = false;
        foreach ($results as $result) {
            if (str_contains($result->getReportReason() ?? '', 'Completed 1 - ' . $uniqueId)) {
                $foundOurReport = true;
                break;
            }
        }
        $this->assertTrue($foundOurReport, 'Should find our completed report');
    }

    public function testFindByReporterUser(): void
    {
        // Create test data
        $content = $this->createGeneratedContent();

        $report1 = new Report();
        $report1->setReportReason('User 1 report');
        $report1->setReporterUser('user1');
        $report1->setReportedContent($content);
        $report1->setReportTime(new \DateTimeImmutable('-1 hour'));

        $report2 = new Report();
        $report2->setReportReason('User 2 report');
        $report2->setReporterUser('user2');
        $report2->setReportedContent($content);
        $report2->setReportTime(new \DateTimeImmutable());

        $report3 = new Report();
        $report3->setReportReason('Another User 1 report');
        $report3->setReporterUser('user1');
        $report3->setReportedContent($content);
        $report3->setReportTime(new \DateTimeImmutable());

        $this->persistEntities([$report1, $report2, $report3]);

        // Test
        $results = $this->repository->findByReporterUser('user1');

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Another User 1 report', $results[0]->getReportReason());
        $this->assertEquals('User 1 report', $results[1]->getReportReason());
    }

    public function testFindByReportedContent(): void
    {
        // Create test data
        $content1 = $this->createGeneratedContent();
        $content2 = $this->createGeneratedContent();

        $report1 = new Report();
        $report1->setReportReason('Report for content 1');
        $report1->setReportedContent($content1);
        $report1->setReporterUser('reporter');
        $report1->setReportTime(new \DateTimeImmutable());

        $report2 = new Report();
        $report2->setReportReason('Report for content 2');
        $report2->setReportedContent($content2);
        $report2->setReporterUser('reporter');
        $report2->setReportTime(new \DateTimeImmutable());

        $this->persistEntities([$report1, $report2]);

        // Test
        $contentId = $content1->getId();
        $this->assertNotNull($contentId);
        $results = $this->repository->findByReportedContent($contentId);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Report for content 1', $results[0]->getReportReason());
    }

    public function testCountByStatus(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $content = $this->createGeneratedContent();
        $uniqueId = uniqid();

        $entities = [];

        // pending
        for ($i = 0; $i < 3; ++$i) {
            $report = new Report();
            $report->setReportReason("Pending {$i} - " . $uniqueId);
            $report->setProcessStatus(ProcessStatus::PENDING);
            $report->setReportTime(new \DateTimeImmutable());
            $report->setReporterUser('reporter_' . $uniqueId);
            $report->setReportedContent($content);
            $entities[] = $report;
        }

        // processing
        for ($i = 0; $i < 2; ++$i) {
            $report = new Report();
            $report->setReportReason("Processing {$i} - " . $uniqueId);
            $report->setProcessStatus(ProcessStatus::PROCESSING);
            $report->setReportTime(new \DateTimeImmutable());
            $report->setReporterUser('reporter_' . $uniqueId);
            $report->setReportedContent($content);
            $entities[] = $report;
        }

        // completed
        for ($i = 0; $i < 5; ++$i) {
            $report = new Report();
            $report->setReportReason("Completed {$i} - " . $uniqueId);
            $report->setProcessStatus(ProcessStatus::COMPLETED);
            $report->setReportTime(new \DateTimeImmutable());
            $report->setProcessTime(new \DateTimeImmutable());
            $report->setReporterUser('reporter_' . $uniqueId);
            $report->setReportedContent($content);
            $entities[] = $report;
        }

        $this->persistEntities($entities);

        // Test
        $counts = $this->repository->countByStatus();

        // Assert - should find at least our test reports plus any from fixtures
        $this->assertGreaterThanOrEqual(3, $counts['待审核']);
        $this->assertGreaterThanOrEqual(2, $counts['审核中']);
        $this->assertGreaterThanOrEqual(5, $counts['已处理']);

        // Also verify the counts are reasonable (not too high)
        $this->assertLessThan(50, $counts['待审核'], 'Pending count should be reasonable');
        $this->assertLessThan(50, $counts['审核中'], 'Processing count should be reasonable');
        $this->assertLessThan(100, $counts['已处理'], 'Completed count should be reasonable');
    }

    public function testFindByDateRange(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $content = $this->createGeneratedContent();
        $uniqueId = uniqid();

        $yesterday = new \DateTimeImmutable('-1 day');
        $today = new \DateTimeImmutable();
        $tomorrow = new \DateTimeImmutable('+1 day');

        $report1 = new Report();
        $report1->setReportReason('Yesterday report - ' . $uniqueId);
        $report1->setReportTime($yesterday);
        $report1->setReporterUser('reporter_' . $uniqueId);
        $report1->setReportedContent($content);

        $report2 = new Report();
        $report2->setReportReason('Today report - ' . $uniqueId);
        $report2->setReportTime($today);
        $report2->setReporterUser('reporter_' . $uniqueId);
        $report2->setReportedContent($content);

        $report3 = new Report();
        $report3->setReportReason('Tomorrow report - ' . $uniqueId);
        $report3->setReportTime($tomorrow);
        $report3->setReporterUser('reporter_' . $uniqueId);
        $report3->setReportedContent($content);

        $this->persistEntities([$report1, $report2, $report3]);

        // Test
        $results = $this->repository->findByDateRange($yesterday, $today);

        // Assert - should find at least our 2 test reports
        $this->assertGreaterThanOrEqual(2, count($results));

        // Find our specific test reports
        $foundYesterday = false;
        $foundToday = false;
        foreach ($results as $result) {
            if (str_contains($result->getReportReason() ?? '', 'Yesterday report - ' . $uniqueId)) {
                $foundYesterday = true;
            }
            if (str_contains($result->getReportReason() ?? '', 'Today report - ' . $uniqueId)) {
                $foundToday = true;
            }
        }
        $this->assertTrue($foundYesterday, 'Should find our yesterday report');
        $this->assertTrue($foundToday, 'Should find our today report');
    }

    public function testSave(): void
    {
        $content = $this->createGeneratedContent();

        $report = new Report();
        $report->setReportReason('Test report');
        $report->setProcessStatus(ProcessStatus::PENDING);
        $report->setReportTime(new \DateTimeImmutable());
        $report->setReporterUser('test_user');
        $report->setReportedContent($content);

        $this->repository->save($report);

        $this->assertNotNull($report->getId());

        $found = $this->repository->find($report->getId());
        $this->assertInstanceOf(Report::class, $found);
        $this->assertEquals('test_user', $found->getReporterUser());
    }

    public function testSaveWithoutFlush(): void
    {
        $content = $this->createGeneratedContent();

        $report = new Report();
        $report->setReportReason('Test report');
        $report->setProcessStatus(ProcessStatus::PENDING);
        $report->setReportTime(new \DateTimeImmutable());
        $report->setReporterUser('test_user');
        $report->setReportedContent($content);

        $this->repository->save($report, false);
        $this->assertNull($report->getId());

        self::getEntityManager()->flush();
        $this->assertNotNull($report->getId());
    }

    public function testRemove(): void
    {
        $content = $this->createGeneratedContent();

        $report = new Report();
        $report->setReportReason('Test report');
        $report->setProcessStatus(ProcessStatus::PENDING);
        $report->setReportTime(new \DateTimeImmutable());
        $report->setReporterUser('test_user');
        $report->setReportedContent($content);

        $persisted = $this->persistAndFlush($report);
        $this->assertInstanceOf(Report::class, $persisted);
        $entityId = $persisted->getId();
        $this->assertNotNull($entityId);

        $this->repository->remove($persisted);

        $found = $this->repository->find($entityId);
        $this->assertNull($found);
    }

    public function testFindOneByWithOrderByClause(): void
    {
        $content = $this->createGeneratedContent();

        $report1 = new Report();
        $report1->setReportReason('Z report');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('test_user');
        $report1->setReportedContent($content);

        $report2 = new Report();
        $report2->setReportReason('A report');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('test_user');
        $report2->setReportedContent($content);

        $this->persistEntities([$report1, $report2]);

        $result = $this->repository->findOneBy(['reporterUser' => 'test_user'], ['reportReason' => 'ASC']);

        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals('A report', $result->getReportReason());
    }

    public function testFindByWithNullProcessTime(): void
    {
        $content = $this->createGeneratedContent();
        $uniqueId = uniqid();

        $report1 = new Report();
        $report1->setReportReason('Unprocessed report - ' . $uniqueId);
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('user1_' . $uniqueId);
        $report1->setReportedContent($content);
        $report1->setProcessTime(null);

        $report2 = new Report();
        $report2->setReportReason('Processed report - ' . $uniqueId);
        $report2->setProcessStatus(ProcessStatus::COMPLETED);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('user2_' . $uniqueId);
        $report2->setReportedContent($content);
        $report2->setProcessTime(new \DateTimeImmutable());

        $this->persistEntities([$report1, $report2]);

        $results = $this->repository->findBy(['processTime' => null]);

        $this->assertGreaterThanOrEqual(1, count($results));

        // Find our specific test report
        $foundOurReport = false;
        foreach ($results as $result) {
            if (str_contains($result->getReportReason() ?? '', 'Unprocessed report - ' . $uniqueId)) {
                $foundOurReport = true;
                break;
            }
        }
        $this->assertTrue($foundOurReport, 'Should find our unprocessed report');
    }

    public function testCountWithNullProcessTime(): void
    {
        $content = $this->createGeneratedContent();

        $report1 = new Report();
        $report1->setReportReason('Unprocessed report 1');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('user1');
        $report1->setReportedContent($content);
        $report1->setProcessTime(null);

        $report2 = new Report();
        $report2->setReportReason('Unprocessed report 2');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('user2');
        $report2->setReportedContent($content);
        $report2->setProcessTime(null);

        $this->persistEntities([$report1, $report2]);

        $count = $this->repository->count(['processTime' => null]);

        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testFindByWithNullProcessResult(): void
    {
        $content = $this->createGeneratedContent();
        $uniqueId = uniqid();

        $report1 = new Report();
        $report1->setReportReason('No result report - ' . $uniqueId);
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('user1_' . $uniqueId);
        $report1->setReportedContent($content);
        $report1->setProcessResult(null);

        $report2 = new Report();
        $report2->setReportReason('Has result report - ' . $uniqueId);
        $report2->setProcessStatus(ProcessStatus::COMPLETED);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('user2_' . $uniqueId);
        $report2->setReportedContent($content);
        $report2->setProcessResult('已处理');

        $this->persistEntities([$report1, $report2]);

        $results = $this->repository->findBy(['processResult' => null]);

        $this->assertGreaterThanOrEqual(1, count($results));

        // Find our specific test report
        $foundOurReport = false;
        foreach ($results as $result) {
            if (str_contains($result->getReportReason() ?? '', 'No result report - ' . $uniqueId)) {
                $foundOurReport = true;
                break;
            }
        }
        $this->assertTrue($foundOurReport, 'Should find our report with null process result');
    }

    public function testCountWithNullProcessResult(): void
    {
        $content = $this->createGeneratedContent();

        $report = new Report();
        $report->setReportReason('Pending report');
        $report->setProcessStatus(ProcessStatus::PENDING);
        $report->setReportTime(new \DateTimeImmutable());
        $report->setReporterUser('user1');
        $report->setReportedContent($content);
        $report->setProcessResult(null);

        $this->persistAndFlush($report);

        $count = $this->repository->count(['processResult' => null]);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithReportedContentAssociation(): void
    {
        $content1 = $this->createGeneratedContent();
        $content2 = $this->createGeneratedContent();

        $report1 = new Report();
        $report1->setReportReason('Report for content 1');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('user1');
        $report1->setReportedContent($content1);

        $report2 = new Report();
        $report2->setReportReason('Report for content 2');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('user2');
        $report2->setReportedContent($content2);

        $this->persistEntities([$report1, $report2]);

        $results = $this->repository->findBy(['reportedContent' => $content1]);

        $this->assertCount(1, $results);
        $this->assertEquals('Report for content 1', $results[0]->getReportReason());
    }

    public function testCountWithReportedContentAssociation(): void
    {
        $content1 = $this->createGeneratedContent();
        $content2 = $this->createGeneratedContent();

        $report1 = new Report();
        $report1->setReportReason('First report for content 1');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('user1');
        $report1->setReportedContent($content1);

        $report2 = new Report();
        $report2->setReportReason('Second report for content 1');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('user2');
        $report2->setReportedContent($content1);

        $report3 = new Report();
        $report3->setReportReason('Report for content 2');
        $report3->setProcessStatus(ProcessStatus::PENDING);
        $report3->setReportTime(new \DateTimeImmutable());
        $report3->setReporterUser('user3');
        $report3->setReportedContent($content2);

        $this->persistEntities([$report1, $report2, $report3]);

        $count = $this->repository->count(['reportedContent' => $content1]);

        $this->assertEquals(2, $count);
    }

    public function testCountByAssociationReportedContentShouldReturnCorrectNumber(): void
    {
        $content1 = $this->createGeneratedContent();
        $content2 = $this->createGeneratedContent();

        $report1 = new Report();
        $report1->setReportReason('Report for content 1 - 1');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('user1');
        $report1->setReportedContent($content1);

        $report2 = new Report();
        $report2->setReportReason('Report for content 1 - 2');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('user2');
        $report2->setReportedContent($content1);

        $report3 = new Report();
        $report3->setReportReason('Report for content 2');
        $report3->setProcessStatus(ProcessStatus::PENDING);
        $report3->setReportTime(new \DateTimeImmutable());
        $report3->setReporterUser('user3');
        $report3->setReportedContent($content2);

        $this->persistEntities([$report1, $report2, $report3]);

        $count = $this->repository->count(['reportedContent' => $content1]);

        $this->assertEquals(2, $count);
    }

    public function testFindOneByAssociationReportedContentShouldReturnMatchingEntity(): void
    {
        $content1 = $this->createGeneratedContent();
        $content2 = $this->createGeneratedContent();

        $report1 = new Report();
        $report1->setReportReason('Report for content 1');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('user1');
        $report1->setReportedContent($content1);

        $report2 = new Report();
        $report2->setReportReason('Report for content 2');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('user2');
        $report2->setReportedContent($content2);

        $this->persistEntities([$report1, $report2]);

        $result = $this->repository->findOneBy(['reportedContent' => $content1]);

        $this->assertInstanceOf(Report::class, $result);
        $this->assertEquals('Report for content 1', $result->getReportReason());
        $reportedContent = $result->getReportedContent();
        $this->assertNotNull($reportedContent);
        $this->assertEquals($content1->getId(), $reportedContent->getId());
    }

    protected function getRepository(): ReportRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $content = new GeneratedContent();
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setUser('test_user');
        $content->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        // 先持久化 GeneratedContent 实体
        self::getEntityManager()->persist($content);

        $report = new Report();
        $report->setReportReason('Test report reason');
        $report->setProcessStatus(ProcessStatus::PENDING);
        $report->setReportTime(new \DateTimeImmutable());
        $report->setReporterUser('test_reporter');
        $report->setReportedContent($content);

        return $report;
    }
}
