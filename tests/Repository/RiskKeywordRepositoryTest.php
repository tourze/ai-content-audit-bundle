<?php

namespace AIContentAuditBundle\Tests\Repository;

use AIContentAuditBundle\Entity\RiskKeyword;
use AIContentAuditBundle\Enum\RiskLevel;
use AIContentAuditBundle\Repository\RiskKeywordRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(RiskKeywordRepository::class)]
#[RunTestsInSeparateProcesses]
final class RiskKeywordRepositoryTest extends AbstractRepositoryTestCase
{
    private RiskKeywordRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getService(RiskKeywordRepository::class);
        self::assertInstanceOf(RiskKeywordRepository::class, $repository);
        $this->repository = $repository;
    }

    public function testFindByRiskLevel(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $uniqueId = uniqid();

        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('high risk word - ' . $uniqueId);
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);

        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('medium risk word - ' . $uniqueId);
        $keyword2->setRiskLevel(RiskLevel::MEDIUM_RISK);

        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('another high risk - ' . $uniqueId);
        $keyword3->setRiskLevel(RiskLevel::HIGH_RISK);

        $this->persistEntities([$keyword1, $keyword2, $keyword3]);

        // Test
        $results = $this->repository->findByRiskLevel(RiskLevel::HIGH_RISK);

        // Assert - should find at least our 2 test keywords
        $this->assertGreaterThanOrEqual(2, count($results));

        // Find our specific test keywords
        $foundKeyword1 = false;
        $foundKeyword3 = false;
        foreach ($results as $result) {
            if (str_contains($result->getKeyword() ?? '', 'high risk word - ' . $uniqueId)) {
                $foundKeyword1 = true;
            }
            if (str_contains($result->getKeyword() ?? '', 'another high risk - ' . $uniqueId)) {
                $foundKeyword3 = true;
            }
        }
        $this->assertTrue($foundKeyword1, 'Should find our first high risk keyword');
        $this->assertTrue($foundKeyword3, 'Should find our second high risk keyword');
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

        $this->persistEntities([$keyword1, $keyword2, $keyword3]);

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

        $this->persistEntities([$keyword1, $keyword2, $keyword3]);

        // Test
        $results = $this->repository->findByCategory('政治');

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('another political', $results[0]->getKeyword());
        $this->assertEquals('political word', $results[1]->getKeyword());
    }

    public function testFindAllCategories(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $uniqueId = uniqid();

        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('word1 - ' . $uniqueId);
        $keyword1->setCategory('政治 - ' . $uniqueId);
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);

        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('word2 - ' . $uniqueId);
        $keyword2->setCategory('暴力 - ' . $uniqueId);
        $keyword2->setRiskLevel(RiskLevel::MEDIUM_RISK);

        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('word3 - ' . $uniqueId);
        $keyword3->setCategory('政治 - ' . $uniqueId);
        $keyword3->setRiskLevel(RiskLevel::HIGH_RISK);

        $keyword4 = new RiskKeyword();
        $keyword4->setKeyword('word4 - ' . $uniqueId);
        $keyword4->setRiskLevel(RiskLevel::LOW_RISK);
        // No category set

        $this->persistEntities([$keyword1, $keyword2, $keyword3, $keyword4]);

        // Test
        $categories = $this->repository->findAllCategories();

        // Assert - should find at least our 2 test categories
        $this->assertGreaterThanOrEqual(2, count($categories));

        // Find our specific test categories
        $foundCategory1 = false;
        $foundCategory2 = false;
        foreach ($categories as $category) {
            if (str_contains($category, '政治 - ' . $uniqueId)) {
                $foundCategory1 = true;
            }
            if (str_contains($category, '暴力 - ' . $uniqueId)) {
                $foundCategory2 = true;
            }
        }
        $this->assertTrue($foundCategory1, 'Should find our first category');
        $this->assertTrue($foundCategory2, 'Should find our second category');
    }

    public function testExistsByKeyword(): void
    {
        // Create test data
        $keyword = new RiskKeyword();
        $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword->setKeyword('existing keyword');

        $this->persistAndFlush($keyword);

        // Test
        $exists = $this->repository->existsByKeyword('existing keyword');
        $notExists = $this->repository->existsByKeyword('non-existing keyword');

        // Assert
        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }

    public function testCountByRiskLevel(): void
    {
        $entities = [];
        $uniqueId = uniqid();

        // Create test data - high risk
        for ($i = 0; $i < 3; ++$i) {
            $keyword = new RiskKeyword();
            $keyword->setKeyword("keyword high {$i} - " . $uniqueId);
            $keyword->setRiskLevel(RiskLevel::HIGH_RISK);
            $entities[] = $keyword;
        }

        // medium risk
        for ($i = 0; $i < 2; ++$i) {
            $keyword = new RiskKeyword();
            $keyword->setKeyword("keyword medium {$i} - " . $uniqueId);
            $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
            $entities[] = $keyword;
        }

        // low risk
        for ($i = 0; $i < 5; ++$i) {
            $keyword = new RiskKeyword();
            $keyword->setKeyword("keyword low {$i} - " . $uniqueId);
            $keyword->setRiskLevel(RiskLevel::LOW_RISK);
            $entities[] = $keyword;
        }

        $this->persistEntities($entities);

        // Test
        $counts = $this->repository->countByRiskLevel();

        // Assert - should find at least our test reports plus any from fixtures
        $this->assertGreaterThanOrEqual(3, $counts['高风险']);
        $this->assertGreaterThanOrEqual(2, $counts['中风险']);
        $this->assertGreaterThanOrEqual(5, $counts['低风险']);

        // Also verify the counts are reasonable (not too high)
        $this->assertLessThan(100, $counts['高风险'], 'High risk count should be reasonable');
        $this->assertLessThan(100, $counts['中风险'], 'Medium risk count should be reasonable');
        $this->assertLessThan(100, $counts['低风险'], 'Low risk count should be reasonable');
    }

    public function testCountByCategory(): void
    {
        // Create test data
        $categories = [
            '政治' => 3,
            '暴力' => 2,
            '色情' => 4,
        ];

        $entities = [];

        foreach ($categories as $category => $count) {
            for ($i = 0; $i < $count; ++$i) {
                $keyword = new RiskKeyword();
                $keyword->setKeyword("keyword {$category} {$i}");
                $keyword->setCategory($category);
                $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
                $entities[] = $keyword;
            }
        }

        $this->persistEntities($entities);

        // Test
        $counts = $this->repository->countByCategory();

        // Assert
        $this->assertEquals(3, $counts['政治']);
        $this->assertEquals(2, $counts['暴力']);
        $this->assertEquals(4, $counts['色情']);
    }

    public function testFindRecentUpdated(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $now = new \DateTimeImmutable();
        $entities = [];
        $uniqueId = uniqid();

        for ($i = 0; $i < 5; ++$i) {
            $keyword = new RiskKeyword();
            $keyword->setKeyword("recent keyword {$i} - {$uniqueId}");
            $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
            // Set update time to be very recent (within the last minute)
            $keyword->setUpdateTime($now->modify("-{$i} seconds"));
            $entities[] = $keyword;
        }

        $this->persistEntities($entities);

        // Test with default limit
        $results = $this->repository->findRecentUpdated();

        // Assert - should find results
        $this->assertGreaterThan(0, count($results));

        // Test with custom limit
        $results = $this->repository->findRecentUpdated(3);
        $this->assertGreaterThan(0, count($results));
        $this->assertLessThanOrEqual(3, count($results));

        // Verify results are RiskKeyword instances
        foreach ($results as $result) {
            $this->assertInstanceOf(RiskKeyword::class, $result);
        }
    }

    public function testFindByAddedBy(): void
    {
        // Create test data with unique identifiers to avoid conflicts with fixtures
        $uniqueId = uniqid();
        $testUser = 'test_user_' . $uniqueId;

        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('test keyword 1 - ' . $uniqueId);
        $keyword1->setAddedBy($testUser);
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);
        $keyword1->setUpdateTime(new \DateTimeImmutable('-1 hour'));

        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('other user keyword - ' . $uniqueId);
        $keyword2->setAddedBy('other_user_' . $uniqueId);
        $keyword2->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword2->setUpdateTime(new \DateTimeImmutable());

        $keyword3 = new RiskKeyword();
        $keyword3->setKeyword('test keyword 2 - ' . $uniqueId);
        $keyword3->setAddedBy($testUser);
        $keyword3->setRiskLevel(RiskLevel::HIGH_RISK);
        $keyword3->setUpdateTime(new \DateTimeImmutable());

        $this->persistEntities([$keyword1, $keyword2, $keyword3]);

        // Test
        $results = $this->repository->findByAddedBy($testUser);

        // Assert - should find at least our 2 test keywords
        $this->assertGreaterThanOrEqual(2, count($results));

        // Find our specific test keywords
        $foundKeyword1 = false;
        $foundKeyword3 = false;
        foreach ($results as $result) {
            if (str_contains($result->getKeyword() ?? '', 'test keyword 1 - ' . $uniqueId)) {
                $foundKeyword1 = true;
            }
            if (str_contains($result->getKeyword() ?? '', 'test keyword 2 - ' . $uniqueId)) {
                $foundKeyword3 = true;
            }
        }
        $this->assertTrue($foundKeyword1, 'Should find our first test keyword');
        $this->assertTrue($foundKeyword3, 'Should find our second test keyword');
    }

    public function testSave(): void
    {
        $keyword = new RiskKeyword();
        $keyword->setKeyword('test keyword');
        $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword->setCategory('测试');

        $this->repository->save($keyword);

        $this->assertNotNull($keyword->getId());

        $found = $this->repository->find($keyword->getId());
        $this->assertInstanceOf(RiskKeyword::class, $found);
        $this->assertEquals('test keyword', $found->getKeyword());
    }

    public function testSaveWithoutFlush(): void
    {
        $keyword = new RiskKeyword();
        $keyword->setKeyword('test keyword');
        $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword->setCategory('测试');

        $this->repository->save($keyword, false);
        $this->assertNull($keyword->getId());

        self::getEntityManager()->flush();
        $this->assertNotNull($keyword->getId());
    }

    public function testRemove(): void
    {
        $keyword = new RiskKeyword();
        $keyword->setKeyword('test keyword');
        $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword->setCategory('测试');

        $persisted = $this->persistAndFlush($keyword);
        $this->assertInstanceOf(RiskKeyword::class, $persisted);
        $entityId = $persisted->getId();
        $this->assertNotNull($entityId);

        $this->repository->remove($persisted);

        $found = $this->repository->find($entityId);
        $this->assertNull($found);
    }

    public function testFindOneByWithOrderByClause(): void
    {
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('Z keyword');
        $keyword1->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword1->setCategory('测试');

        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('A keyword');
        $keyword2->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword2->setCategory('测试');

        $this->persistEntities([$keyword1, $keyword2]);

        $result = $this->repository->findOneBy(['category' => '测试'], ['keyword' => 'ASC']);

        $this->assertInstanceOf(RiskKeyword::class, $result);
        $this->assertEquals('A keyword', $result->getKeyword());
    }

    public function testFindByWithNullCriteria(): void
    {
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('keyword 1');
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);
        $keyword1->setCategory('测试');

        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('keyword 2');
        $keyword2->setRiskLevel(RiskLevel::LOW_RISK);
        // No category set (null)

        $this->persistEntities([$keyword1, $keyword2]);

        $results = $this->repository->findBy(['category' => null]);

        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertNull($result->getCategory());
        }
    }

    public function testCountWithNullCriteria(): void
    {
        $keyword1 = new RiskKeyword();
        $keyword1->setKeyword('keyword 1');
        $keyword1->setRiskLevel(RiskLevel::HIGH_RISK);
        $keyword1->setCategory('测试');

        $keyword2 = new RiskKeyword();
        $keyword2->setKeyword('keyword 2');
        $keyword2->setRiskLevel(RiskLevel::LOW_RISK);
        // No category set (null)

        $this->persistEntities([$keyword1, $keyword2]);

        $count = $this->repository->count(['category' => null]);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    protected function getRepository(): RiskKeywordRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $keyword = new RiskKeyword();
        $keyword->setKeyword('test keyword');
        $keyword->setRiskLevel(RiskLevel::MEDIUM_RISK);
        $keyword->setCategory('测试分类');

        return $keyword;
    }
}
