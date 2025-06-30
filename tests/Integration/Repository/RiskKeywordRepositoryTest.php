<?php

namespace AIContentAuditBundle\Tests\Integration\Repository;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RiskKeywordRepositoryTest extends KernelTestCase
{
    private RiskKeywordRepository $repository;
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
        
        $repository = $this->entityManager->getRepository(RiskKeyword::class);
        $this->assertInstanceOf(RiskKeywordRepository::class, $repository);
        $this->repository = $repository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->entityManager->close();
    }

    public function testFindByRiskLevel(): void
    {
        // Create test data
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('high risk word');
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);
        
        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('medium risk word');
        $keyword2->setRiskLevel(RiskLevel::MEDIUM_RISK);
        
        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('another high risk');
        $keyword3->setRiskLevel(RiskLevel::HIGH_RISK);
        
        $this->entityManager->persist($keyword1);
        $this->entityManager->persist($keyword2);
        $this->entityManager->persist($keyword3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByRiskLevel(RiskLevel::HIGH_RISK);
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('another high risk', $results[0]->getKeyword());
        $this->assertEquals('high risk word', $results[1]->getKeyword());
    }

    public function testFindByKeywordLike(): void
    {
        // Create test data
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('test keyword');
        $keyword1->setRiskLevel(RiskLevel::LOW_RISK);
        
        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('another test');
        $keyword2->setRiskLevel(RiskLevel::LOW_RISK);
        
        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('different word');
        $keyword3->setRiskLevel(RiskLevel::LOW_RISK);
        
        $this->entityManager->persist($keyword1);
        $this->entityManager->persist($keyword2);
        $this->entityManager->persist($keyword3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByKeywordLike('test');
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('another test', $results[0]->getKeyword());
        $this->assertEquals('test keyword', $results[1]->getKeyword());
    }

    public function testFindByCategory(): void
    {
        // Create test data
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('political word');
        $keyword1->setCategory('政治');
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);
        
        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('violence word');
        $keyword2->setCategory('暴力');
        $keyword2->setRiskLevel(RiskLevel::MEDIUM_RISK);
        
        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('another political');
        $keyword3->setCategory('政治');
        $keyword3->setRiskLevel(RiskLevel::HIGH_RISK);
        
        $this->entityManager->persist($keyword1);
        $this->entityManager->persist($keyword2);
        $this->entityManager->persist($keyword3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByCategory('政治');
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('another political', $results[0]->getKeyword());
        $this->assertEquals('political word', $results[1]->getKeyword());
    }

    public function testFindAllCategories(): void
    {
        // Create test data
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('word1');
        $keyword1->setCategory('政治');
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);
        
        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('word2');
        $keyword2->setCategory('暴力');
        $keyword2->setRiskLevel(RiskLevel::MEDIUM_RISK);
        
        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('word3');
        $keyword3->setCategory('政治');
        $keyword3->setRiskLevel(RiskLevel::HIGH_RISK);
        
        $keyword4 = new RiskKeyword();
        $keyword4->setKeyword('word4');
        $keyword4->setRiskLevel(RiskLevel::LOW_RISK);
        // No category set
        
        $this->entityManager->persist($keyword1);
        $this->entityManager->persist($keyword2);
        $this->entityManager->persist($keyword3);
        $this->entityManager->persist($keyword4);
        $this->entityManager->flush();
        
        // Test
        $categories = $this->repository->findAllCategories();
        
        // Assert
        $this->assertCount(2, $categories);
        $this->assertContains('政治', $categories);
        $this->assertContains('暴力', $categories);
    }

    public function testExistsByKeyword(): void
    {
        // Create test data
        $keyword = new RiskKeyword();
        $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword->setKeyword('existing keyword');
        
        $this->entityManager->persist($keyword);
        $this->entityManager->flush();
        
        // Test
        $exists = $this->repository->existsByKeyword('existing keyword');
        $notExists = $this->repository->existsByKeyword('non-existing keyword');
        
        // Assert
        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }

    public function testCountByRiskLevel(): void
    {
        // Create test data - high risk
        for ($i = 0; $i < 3; $i++) {
            $keyword = new RiskKeyword();
            $keyword->setKeyword("keyword high $i");
            $keyword->setRiskLevel(RiskLevel::HIGH_RISK);
            $this->entityManager->persist($keyword);
        }
        
        // medium risk
        for ($i = 0; $i < 2; $i++) {
            $keyword = new RiskKeyword();
            $keyword->setKeyword("keyword medium $i");
            $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
            $this->entityManager->persist($keyword);
        }
        
        // low risk
        for ($i = 0; $i < 5; $i++) {
            $keyword = new RiskKeyword();
            $keyword->setKeyword("keyword low $i");
            $keyword->setRiskLevel(RiskLevel::LOW_RISK);
            $this->entityManager->persist($keyword);
        }
        
        $this->entityManager->flush();
        
        // Test
        $counts = $this->repository->countByRiskLevel();
        
        // Assert
        $this->assertEquals(3, $counts['高风险']);
        $this->assertEquals(2, $counts['中风险']);
        $this->assertEquals(5, $counts['低风险']);
    }

    public function testCountByCategory(): void
    {
        // Create test data
        $categories = [
            '政治' => 3,
            '暴力' => 2,
            '色情' => 4,
        ];
        
        foreach ($categories as $category => $count) {
            for ($i = 0; $i < $count; $i++) {
                $keyword = new RiskKeyword();
                $keyword->setKeyword("keyword $category $i");
                $keyword->setCategory($category);
                $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
                $this->entityManager->persist($keyword);
            }
        }
        
        $this->entityManager->flush();
        
        // Test
        $counts = $this->repository->countByCategory();
        
        // Assert
        $this->assertEquals(3, $counts['政治']);
        $this->assertEquals(2, $counts['暴力']);
        $this->assertEquals(4, $counts['色情']);
    }

    public function testFindRecentUpdated(): void
    {
        // Create test data
        $now = new \DateTimeImmutable();
        
        for ($i = 0; $i < 15; $i++) {
            $keyword = new RiskKeyword();
            $keyword->setKeyword("keyword $i");
            $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
            $keyword->setUpdateTime($now->modify("-$i hours"));
            $this->entityManager->persist($keyword);
        }
        
        $this->entityManager->flush();
        
        // Test with default limit
        $results = $this->repository->findRecentUpdated();
        
        // Assert
        $this->assertCount(10, $results);
        $this->assertEquals('keyword 0', $results[0]->getKeyword());
        $this->assertEquals('keyword 9', $results[9]->getKeyword());
        
        // Test with custom limit
        $results = $this->repository->findRecentUpdated(5);
        $this->assertCount(5, $results);
    }

    public function testFindByAddedBy(): void
    {
        // Create test data
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('admin keyword 1');
        $keyword1->setAddedBy('admin');
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);
        $keyword1->setUpdateTime(new \DateTimeImmutable('-1 hour'));
        
        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('user keyword');
        $keyword2->setAddedBy('user');
        $keyword2->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword2->setUpdateTime(new \DateTimeImmutable());
        
        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('admin keyword 2');
        $keyword3->setAddedBy('admin');
        $keyword3->setRiskLevel(RiskLevel::HIGH_RISK);
        $keyword3->setUpdateTime(new \DateTimeImmutable());
        
        $this->entityManager->persist($keyword1);
        $this->entityManager->persist($keyword2);
        $this->entityManager->persist($keyword3);
        $this->entityManager->flush();
        
        // Test
        $results = $this->repository->findByAddedBy('admin');
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('admin keyword 2', $results[0]->getKeyword());
        $this->assertEquals('admin keyword 1', $results[1]->getKeyword());
    }
}