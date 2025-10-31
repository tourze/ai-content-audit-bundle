<?php

namespace AIContentAuditBundle\Tests\Repository;

use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Enum\AuditResult;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\GeneratedContentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(GeneratedContentRepository::class)]
#[RunTestsInSeparateProcesses]
final class GeneratedContentRepositoryTest extends AbstractRepositoryTestCase
{
    private GeneratedContentRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(GeneratedContentRepository::class);
        self::assertInstanceOf(GeneratedContentRepository::class, $this->repository);
    }

    public function testFindNeedManualAudit(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $content1 = new GeneratedContent();
        $content1->setInputText('Test input manual audit needed - ' . uniqid());
        $content1->setOutputText('Test output manual audit needed - ' . uniqid());
        $content1->setUser('test_user_' . uniqid());
        $content1->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable());

        $content2 = new GeneratedContent();
        $content2->setInputText('Test input high risk - ' . uniqid());
        $content2->setOutputText('Test output high risk - ' . uniqid());
        $content2->setUser('test_user_' . uniqid());
        $content2->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());

        $content3 = new GeneratedContent();
        $content3->setInputText('Test input already audited - ' . uniqid());
        $content3->setOutputText('Test output already audited - ' . uniqid());
        $content3->setUser('test_user_' . uniqid());
        $content3->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content3->setManualAuditResult(AuditResult::PASS);
        $content3->setMachineAuditTime(new \DateTimeImmutable());

        $this->persistEntities([$content1, $content2, $content3]);

        // Test
        $results = $this->repository->findNeedManualAudit();

        // Assert - should find at least our test content that needs manual audit
        $this->assertGreaterThanOrEqual(1, count($results));

        // Find our specific test content
        $foundOurContent = false;
        foreach ($results as $result) {
            if (str_contains($result->getInputText() ?? '', 'Test input manual audit needed')) {
                $foundOurContent = true;
                break;
            }
        }
        $this->assertTrue($foundOurContent, 'Should find our test content that needs manual audit');
    }

    public function testFindByMachineAuditResult(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $content1 = new GeneratedContent();
        $content1->setInputText('High risk input - ' . uniqid());
        $content1->setOutputText('High risk output - ' . uniqid());
        $content1->setUser('test_user_' . uniqid());
        $content1->setMachineAuditResult(RiskLevel::HIGH_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable());

        $content2 = new GeneratedContent();
        $content2->setInputText('Low risk input - ' . uniqid());
        $content2->setOutputText('Low risk output - ' . uniqid());
        $content2->setUser('test_user_' . uniqid());
        $content2->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());

        $this->persistEntities([$content1, $content2]);

        // Test
        $results = $this->repository->findByMachineAuditResult(RiskLevel::HIGH_RISK);

        // Assert - should find at least our test content
        $this->assertGreaterThanOrEqual(1, count($results));

        // Find our specific test content
        $foundOurContent = false;
        foreach ($results as $result) {
            if (str_contains($result->getInputText() ?? '', 'High risk input - ')) {
                $foundOurContent = true;
                break;
            }
        }
        $this->assertTrue($foundOurContent, 'Should find our test high risk content');
    }

    public function testCountByRiskLevel(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $entities = [];
        $uniqueId = uniqid();

        // high risk
        for ($i = 0; $i < 3; ++$i) {
            $content = new GeneratedContent();
            $content->setInputText("High risk input {$i} - {$uniqueId}");
            $content->setOutputText("High risk output {$i} - {$uniqueId}");
            $content->setUser('test_user_' . $uniqueId . '_' . $i);
            $content->setMachineAuditResult(RiskLevel::HIGH_RISK);
            $content->setMachineAuditTime(new \DateTimeImmutable());
            $entities[] = $content;
        }

        // medium risk
        for ($i = 0; $i < 2; ++$i) {
            $content = new GeneratedContent();
            $content->setInputText("Medium risk input {$i} - {$uniqueId}");
            $content->setOutputText("Medium risk output {$i} - {$uniqueId}");
            $content->setUser('test_user_' . $uniqueId . '_' . $i);
            $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
            $content->setMachineAuditTime(new \DateTimeImmutable());
            $entities[] = $content;
        }

        // low risk
        for ($i = 0; $i < 5; ++$i) {
            $content = new GeneratedContent();
            $content->setInputText("Low risk input {$i} - {$uniqueId}");
            $content->setOutputText("Low risk output {$i} - {$uniqueId}");
            $content->setUser('test_user_' . $uniqueId . '_' . $i);
            $content->setMachineAuditResult(RiskLevel::LOW_RISK);
            $content->setMachineAuditTime(new \DateTimeImmutable());
            $entities[] = $content;
        }

        $this->persistEntities($entities);

        // Test
        $counts = $this->repository->countByRiskLevel();

        // Assert - should find at least our test data plus any from fixtures
        $this->assertGreaterThanOrEqual(3, $counts['高风险']);
        $this->assertGreaterThanOrEqual(2, $counts['中风险']);
        $this->assertGreaterThanOrEqual(5, $counts['低风险']);

        // Verify that counts are reasonable (not too high)
        $this->assertLessThan(20, $counts['高风险'], 'High risk count should be reasonable');
        $this->assertLessThan(20, $counts['中风险'], 'Medium risk count should be reasonable');
        $this->assertLessThan(50, $counts['低风险'], 'Low risk count should be reasonable');
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

        $this->persistEntities([$content1, $content2, $content3]);

        // Test
        $results = $this->repository->findByUser('user1');

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Another User 1 input', $results[0]->getInputText());
        $this->assertEquals('User 1 input', $results[1]->getInputText());
    }

    public function testFindByDateRange(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $yesterday = new \DateTimeImmutable('-1 day');
        $today = new \DateTimeImmutable();
        $tomorrow = new \DateTimeImmutable('+1 day');
        $uniqueId = uniqid();

        $content1 = new GeneratedContent();
        $content1->setInputText('Yesterday input - ' . $uniqueId);
        $content1->setOutputText('Yesterday output - ' . $uniqueId);
        $content1->setUser('test_user_' . $uniqueId);
        $content1->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content1->setMachineAuditTime($yesterday);

        $content2 = new GeneratedContent();
        $content2->setInputText('Today input - ' . $uniqueId);
        $content2->setOutputText('Today output - ' . $uniqueId);
        $content2->setUser('test_user_' . $uniqueId);
        $content2->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content2->setMachineAuditTime($today);

        $content3 = new GeneratedContent();
        $content3->setInputText('Tomorrow input - ' . $uniqueId);
        $content3->setOutputText('Tomorrow output - ' . $uniqueId);
        $content3->setUser('test_user_' . $uniqueId);
        $content3->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content3->setMachineAuditTime($tomorrow);

        $this->persistEntities([$content1, $content2, $content3]);

        // Test
        $results = $this->repository->findByDateRange($yesterday, $today);

        // Assert - should find at least our test data
        $this->assertGreaterThanOrEqual(2, count($results));

        // Find our specific test content
        $foundYesterday = false;
        $foundToday = false;
        foreach ($results as $result) {
            if (str_contains($result->getInputText() ?? '', 'Yesterday input - ' . $uniqueId)) {
                $foundYesterday = true;
            }
            if (str_contains($result->getInputText() ?? '', 'Today input - ' . $uniqueId)) {
                $foundToday = true;
            }
        }
        $this->assertTrue($foundYesterday, 'Should find our yesterday content');
        $this->assertTrue($foundToday, 'Should find our today content');
    }

    public function testSave(): void
    {
        $content = new GeneratedContent();
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setUser('test_user');
        $content->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $this->repository->save($content);

        $this->assertNotNull($content->getId());

        $found = $this->repository->find($content->getId());
        $this->assertInstanceOf(GeneratedContent::class, $found);
        $this->assertEquals('test_user', $found->getUser());
    }

    public function testSaveWithoutFlush(): void
    {
        $content = new GeneratedContent();
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setUser('test_user');
        $content->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $this->repository->save($content, false);
        $this->assertNull($content->getId());

        self::getEntityManager()->flush();
        $this->assertNotNull($content->getId());
    }

    public function testRemove(): void
    {
        $content = new GeneratedContent();
        $content->setInputText('Test input');
        $content->setOutputText('Test output');
        $content->setUser('test_user');
        $content->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());

        $persisted = $this->persistAndFlush($content);
        $this->assertInstanceOf(GeneratedContent::class, $persisted);
        $entityId = $persisted->getId();
        $this->assertNotNull($entityId);

        $this->repository->remove($persisted);

        $found = $this->repository->find($entityId);
        $this->assertNull($found);
    }

    public function testFindOneByWithOrderByClause(): void
    {
        $content1 = new GeneratedContent();
        $content1->setInputText('B input');
        $content1->setOutputText('Test output');
        $content1->setUser('test_user');
        $content1->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable());

        $content2 = new GeneratedContent();
        $content2->setInputText('A input');
        $content2->setOutputText('Test output');
        $content2->setUser('test_user');
        $content2->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());

        $this->persistEntities([$content1, $content2]);

        $result = $this->repository->findOneBy(['user' => 'test_user'], ['inputText' => 'ASC']);

        $this->assertInstanceOf(GeneratedContent::class, $result);
        $this->assertEquals('A input', $result->getInputText());
    }

    public function testFindByWithNullCriteria(): void
    {
        $content1 = new GeneratedContent();
        $content1->setInputText('Input 1');
        $content1->setOutputText('Output 1');
        $content1->setUser('user1');
        $content1->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable());

        $content2 = new GeneratedContent();
        $content2->setInputText('Input 2');
        $content2->setOutputText('Output 2');
        $content2->setUser('user2');
        $content2->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());
        $content2->setManualAuditResult(null);

        $this->persistEntities([$content1, $content2]);

        $results = $this->repository->findBy(['manualAuditResult' => null]);

        $this->assertGreaterThanOrEqual(2, count($results));
    }

    public function testCountWithNullCriteria(): void
    {
        $content1 = new GeneratedContent();
        $content1->setInputText('Input 1');
        $content1->setOutputText('Output 1');
        $content1->setUser('user1');
        $content1->setMachineAuditResult(RiskLevel::LOW_RISK);
        $content1->setMachineAuditTime(new \DateTimeImmutable());

        $content2 = new GeneratedContent();
        $content2->setInputText('Input 2');
        $content2->setOutputText('Output 2');
        $content2->setUser('user2');
        $content2->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content2->setMachineAuditTime(new \DateTimeImmutable());
        $content2->setManualAuditResult(null);

        $this->persistEntities([$content1, $content2]);

        $count = $this->repository->count(['manualAuditResult' => null]);

        $this->assertGreaterThanOrEqual(2, $count);
    }

    protected function getRepository(): GeneratedContentRepository
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

        return $content;
    }
}
