<?php

namespace AIContentAuditBundle\Tests\Integration\Repository;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedContentRepositoryTest extends KernelTestCase
{
    private GeneratedContentRepository $repository;
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
        
        $repository = $this->entityManager->getRepository(GeneratedContent::class);
        $this->assertInstanceOf(GeneratedContentRepository::class, $repository);
        $this->repository = $repository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->entityManager->close();
    }

    public function testFindNeedManualAudit(): void
    {
        // Create test data
        $content1 = new GeneratedContent();
        $content1->setInputText('Test input 1');
        $content1->setOutputText('Test output 1');
        $content1->setUser('test_user');
        $content1->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable());
        
        $content2 = new GeneratedContent();
        $content2->setInputText('Test input 2');
        $content2->setOutputText('Test output 2');
        $content2->setUser('test_user');
        $content2->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());
        
        $content3 = new GeneratedContent();
        $content3->setInputText('Test input 3');
        $content3->setOutputText('Test output 3');
        $content3->setUser('test_user');
        $content3->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content3->setManualAuditResult(AuditResult::PASS);
        $content3->setMachineAuditTime(new \DateTimeImmutable());
        
        $this->entityManager->persist($content1);
        $this->entityManager->persist($content2);
        $this->entityManager->persist($content3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findNeedManualAudit();
        
        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Test input 1', $results[0]->getInputText());
    }

    public function testFindByMachineAuditResult(): void
    {
        // Create test data
        $content1 = new GeneratedContent();
        $content1->setInputText('High risk input');
        $content1->setOutputText('High risk output');
        $content1->setUser('test_user');
        $content1->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable());
        
        $content2 = new GeneratedContent();
        $content2->setInputText('Low risk input');
        $content2->setOutputText('Low risk output');
        $content2->setUser('test_user');
        $content2->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());
        
        $this->entityManager->persist($content1);
        $this->entityManager->persist($content2);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByMachineAuditResult(RiskLevel::HIGH_RISK);
        
        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('High risk input', $results[0]->getInputText());
    }

    public function testCountByRiskLevel(): void
    {
        // Create test data - high risk
        for ($i = 0; $i < 3; $i++) {
            $content = new GeneratedContent();
            $content->setInputText("Input $i");
            $content->setOutputText("Output $i");
            $content->setUser('test_user');
            $content->setMachineAuditResult(RiskLevel::HIGH_RISK);
            $content->setMachineAuditTime(new \DateTimeImmutable());
            $this->entityManager->persist($content);
        }
        
        // medium risk
        for ($i = 0; $i < 2; $i++) {
            $content = new GeneratedContent();
            $content->setInputText("Input $i");
            $content->setOutputText("Output $i");
            $content->setUser('test_user');
            $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
            $content->setMachineAuditTime(new \DateTimeImmutable());
            $this->entityManager->persist($content);
        }
        
        // low risk
        for ($i = 0; $i < 5; $i++) {
            $content = new GeneratedContent();
            $content->setInputText("Input $i");
            $content->setOutputText("Output $i");
            $content->setUser('test_user');
            $content->setMachineAuditResult(RiskLevel::LOW_RISK);
            $content->setMachineAuditTime(new \DateTimeImmutable());
            $this->entityManager->persist($content);
        }
        
        $this->entityManager->flush();
        
        // Test
        $counts = $this->repository->countByRiskLevel();
        
        // Assert
        $this->assertEquals(3, $counts['高风险']);
        $this->assertEquals(2, $counts['中风险']);
        $this->assertEquals(5, $counts['低风险']);
    }

    public function testFindByUser(): void
    {
        // Create test data
        $content1 = new GeneratedContent();
        $content1->setInputText('User 1 input');
        $content1->setOutputText('User 1 output');
        $content1->setUser('user1');
        $content1->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable('-1 hour'));
        
        $content2 = new GeneratedContent();
        $content2->setInputText('User 2 input');
        $content2->setOutputText('User 2 output');
        $content2->setUser('user2');
        $content2->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());
        
        $content3 = new GeneratedContent();
        $content3->setInputText('Another User 1 input');
        $content3->setOutputText('Another User 1 output');
        $content3->setUser('user1');
        $content3->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content3->setMachineAuditTime(new \DateTimeImmutable());
        
        $this->entityManager->persist($content1);
        $this->entityManager->persist($content2);
        $this->entityManager->persist($content3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByUser('user1');
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Another User 1 input', $results[0]->getInputText());
        $this->assertEquals('User 1 input', $results[1]->getInputText());
    }

    public function testFindByDateRange(): void
    {
        // Create test data
        $yesterday = new \DateTimeImmutable('-1 day');
        $today = new \DateTimeImmutable();
        $tomorrow = new \DateTimeImmutable('+1 day');
        
        $content1 = new GeneratedContent();
        $content1->setInputText('Yesterday input');
        $content1->setOutputText('Yesterday output');
        $content1->setUser('test_user');
        $content1->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content1->setMachineAuditTime($yesterday);
        
        $content2 = new GeneratedContent();
        $content2->setInputText('Today input');
        $content2->setOutputText('Today output');
        $content2->setUser('test_user');
        $content2->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content2->setMachineAuditTime($today);
        
        $content3 = new GeneratedContent();
        $content3->setInputText('Tomorrow input');
        $content3->setOutputText('Tomorrow output');
        $content3->setUser('test_user');
        $content3->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content3->setMachineAuditTime($tomorrow);
        
        $this->entityManager->persist($content1);
        $this->entityManager->persist($content2);
        $this->entityManager->persist($content3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByDateRange($yesterday, $today);
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Today input', $results[0]->getInputText());
        $this->assertEquals('Yesterday input', $results[1]->getInputText());
    }
}