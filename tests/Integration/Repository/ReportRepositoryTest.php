<?php

namespace AIContentAuditBundle\Tests\Integration\Repository;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\ReportRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class ReportRepositoryTest extends KernelTestCase
{
    private ReportRepository $repository;
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
        
        $repository = $this->entityManager->getRepository(Report::class);
        $this->assertInstanceOf(ReportRepository::class, $repository);
        $this->repository = $repository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->entityManager->close();
    }


    private function createGeneratedContent(): GeneratedContent
    {
        $content = new GeneratedContent();
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setUser('test_user');
        $content->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        return $content;
    }

    public function testFindPendingReports(): void
    {
        // Create test data
        $content = $this->createGeneratedContent();
        $this->entityManager->persist($content);
        
        $report1 = new Report();
        $report1->setReportReason('Test reason 1');
        $report1->setProcessStatus(ProcessStatus::PENDING);
        $report1->setReportTime(new \DateTimeImmutable('-2 hours'));
        $report1->setReporterUser('reporter');
        $report1->setReportedContent($content);
        
        $report2 = new Report();
        $report2->setReportReason('Test reason 2');
        $report2->setProcessStatus(ProcessStatus::PROCESSING);
        $report2->setReportTime(new \DateTimeImmutable('-1 hour'));
        $report2->setReporterUser('reporter');
        $report2->setReportedContent($content);
        
        $report3 = new Report();
        $report3->setReportReason('Test reason 3');
        $report3->setProcessStatus(ProcessStatus::PENDING);
        $report3->setReportTime(new \DateTimeImmutable());
        $report3->setReporterUser('reporter');
        $report3->setReportedContent($content);
        
        $this->entityManager->persist($report1);
        $this->entityManager->persist($report2);
        $this->entityManager->persist($report3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findPendingReports();
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Test reason 1', $results[0]->getReportReason());
        $this->assertEquals('Test reason 3', $results[1]->getReportReason());
    }

    public function testFindProcessingReports(): void
    {
        // Create test data
        $content = $this->createGeneratedContent();
        $this->entityManager->persist($content);
        
        $report1 = new Report();
        $report1->setReportReason('Processing 1');
        $report1->setProcessStatus(ProcessStatus::PROCESSING);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setReporterUser('reporter');
        $report1->setReportedContent($content);
        
        $report2 = new Report();
        $report2->setReportReason('Pending');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('reporter');
        $report2->setReportedContent($content);
        
        $this->entityManager->persist($report1);
        $this->entityManager->persist($report2);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findProcessingReports();
        
        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Processing 1', $results[0]->getReportReason());
    }

    public function testFindCompletedReports(): void
    {
        // Create test data
        $content = $this->createGeneratedContent();
        $this->entityManager->persist($content);
        
        $report1 = new Report();
        $report1->setReportReason('Completed 1');
        $report1->setProcessStatus(ProcessStatus::COMPLETED);
        $report1->setReportTime(new \DateTimeImmutable());
        $report1->setProcessTime(new \DateTimeImmutable());
        $report1->setReporterUser('reporter');
        $report1->setReportedContent($content);
        
        $report2 = new Report();
        $report2->setReportReason('Pending');
        $report2->setProcessStatus(ProcessStatus::PENDING);
        $report2->setReportTime(new \DateTimeImmutable());
        $report2->setReporterUser('reporter');
        $report2->setReportedContent($content);
        
        $this->entityManager->persist($report1);
        $this->entityManager->persist($report2);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findCompletedReports();
        
        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Completed 1', $results[0]->getReportReason());
    }

    public function testFindByReporterUser(): void
    {
        // Create test data
        $content = $this->createGeneratedContent();
        $this->entityManager->persist($content);
        
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
        
        $this->entityManager->persist($report1);
        $this->entityManager->persist($report2);
        $this->entityManager->persist($report3);
        $this->entityManager->flush();
        
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
        $this->entityManager->persist($content1);
        $this->entityManager->persist($content2);
        
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
        
        $this->entityManager->persist($report1);
        $this->entityManager->persist($report2);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByReportedContent($content1->getId());
        
        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Report for content 1', $results[0]->getReportReason());
    }

    public function testCountByStatus(): void
    {
        // Create test data
        $content = $this->createGeneratedContent();
        $this->entityManager->persist($content);
        
        // pending
        for ($i = 0; $i < 3; $i++) {
            $report = new Report();
            $report->setReportReason("Report $i");
            $report->setProcessStatus(ProcessStatus::PENDING);
            $report->setReportTime(new \DateTimeImmutable());
            $report->setReporterUser('reporter');
            $report->setReportedContent($content);
            $this->entityManager->persist($report);
        }
        
        // processing
        for ($i = 0; $i < 2; $i++) {
            $report = new Report();
            $report->setReportReason("Report $i");
            $report->setProcessStatus(ProcessStatus::PROCESSING);
            $report->setReportTime(new \DateTimeImmutable());
            $report->setReporterUser('reporter');
            $report->setReportedContent($content);
            $this->entityManager->persist($report);
        }
        
        // completed
        for ($i = 0; $i < 5; $i++) {
            $report = new Report();
            $report->setReportReason("Report $i");
            $report->setProcessStatus(ProcessStatus::COMPLETED);
            $report->setReportTime(new \DateTimeImmutable());
            $report->setProcessTime(new \DateTimeImmutable());
            $report->setReporterUser('reporter');
            $report->setReportedContent($content);
            $this->entityManager->persist($report);
        }
        
        $this->entityManager->flush();
        
        // Test
        $counts = $this->repository->countByStatus();
        
        // Assert
        $this->assertEquals(3, $counts['待审核']);
        $this->assertEquals(2, $counts['审核中']);
        $this->assertEquals(5, $counts['已处理']);
    }

    public function testFindByDateRange(): void
    {
        // Create test data
        $content = $this->createGeneratedContent();
        $this->entityManager->persist($content);
        
        $yesterday = new \DateTimeImmutable('-1 day');
        $today = new \DateTimeImmutable();
        $tomorrow = new \DateTimeImmutable('+1 day');
        
        $report1 = new Report();
        $report1->setReportReason('Yesterday report');
        $report1->setReportTime($yesterday);
        $report1->setReporterUser('reporter');
        $report1->setReportedContent($content);
        
        $report2 = new Report();
        $report2->setReportReason('Today report');
        $report2->setReportTime($today);
        $report2->setReporterUser('reporter');
        $report2->setReportedContent($content);
        
        $report3 = new Report();
        $report3->setReportReason('Tomorrow report');
        $report3->setReportTime($tomorrow);
        $report3->setReporterUser('reporter');
        $report3->setReportedContent($content);
        
        $this->entityManager->persist($report1);
        $this->entityManager->persist($report2);
        $this->entityManager->persist($report3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByDateRange($yesterday, $today);
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Today report', $results[0]->getReportReason());
        $this->assertEquals('Yesterday report', $results[1]->getReportReason());
    }
}