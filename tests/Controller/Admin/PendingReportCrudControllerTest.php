<?php

declare(strict_types=1);

namespace AIContentAuditBundle\Tests\Controller\Admin;

use AIContentAuditBundle\Controller\Admin\PendingReportCrudController;
use AIContentAuditBundle\Entity\GeneratedContent;
use AIContentAuditBundle\Entity\Report;
use AIContentAuditBundle\Enum\ProcessStatus;
use AIContentAuditBundle\Enum\RiskLevel;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * PendingReportCrudController HTTP集成测试
 *
 * 通过HTTP层测试控制器功能，符合WebTestCase标准
 *
 * @internal
 */
#[CoversClass(PendingReportCrudController::class)]
#[RunTestsInSeparateProcesses]
final class PendingReportCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<Report>
     */
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(PendingReportCrudController::class);
        self::assertInstanceOf(AbstractCrudController::class, $controller);

        return $controller;
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield '举报用户' => ['举报用户'];
        yield '被举报内容' => ['被举报内容'];
        yield '举报时间' => ['举报时间'];
        yield '举报理由' => ['举报理由'];
        yield '处理状态' => ['处理状态'];
        yield '处理时间' => ['处理时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'reporterUser' => ['reporterUser'];
        yield 'reportedContent' => ['reportedContent'];
        yield 'reportTime' => ['reportTime'];
        yield 'reportReason' => ['reportReason'];
        yield 'processStatus' => ['processStatus'];
        yield 'processTime' => ['processTime'];
        yield 'processResult' => ['processResult'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'reporterUser' => ['reporterUser'];
        yield 'reportedContent' => ['reportedContent'];
        yield 'reportTime' => ['reportTime'];
        yield 'reportReason' => ['reportReason'];
        yield 'processStatus' => ['processStatus'];
        yield 'processTime' => ['processTime'];
        yield 'processResult' => ['processResult'];
    }

    public function testAuthenticatedAdminCanAccessDashboard(): void
    {
        $client = self::createAuthenticatedClient();

        // 认证用户应该能访问Dashboard
        $crawler = $client->request('GET', '/admin');

        // 验证响应状态
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful(), 'Response should be successful');
        $content = $response->getContent();
        $this->assertStringContainsString('dashboard', false !== $content ? $content : '');
    }

    public function testIndexRowActionLinksFollowRedirectsShouldNot500(): void
    {
        $client = self::createAuthenticatedClient();

        $this->createTestReport();

        $indexUrl = $this->generateAdminUrl(Action::INDEX);
        $crawler = $client->request('GET', $indexUrl);
        $this->assertTrue($client->getResponse()->isSuccessful(), 'Index page should be successful');

        $links = $this->extractActionLinks($crawler);
        $this->assertNotEmpty($links, '列表页每行应该至少包含一个动作链接');

        $this->verifyLinksDoNotReturn500($client, $links);
    }

    private function createTestReport(): void
    {
        $em = self::getService(EntityManagerInterface::class);

        $content = new GeneratedContent();
        $content->setUser('report_user');
        $content->setInputText('Reported input');
        $content->setOutputText('Reported output');
        $content->setMachineAuditResult(RiskLevel::MEDIUM_RISK);
        $content->setMachineAuditTime(new \DateTimeImmutable());
        $em->persist($content);

        $report = new Report();
        $report->setReporterUser('reporter_1');
        $report->setReportedContent($content);
        $report->setReportTime(new \DateTimeImmutable());
        $report->setReportReason('spam');
        $report->setProcessStatus(ProcessStatus::PENDING);
        $em->persist($report);

        $em->flush();
    }

    /**
     * @return array<int, string>
     */
    private function extractActionLinks(Crawler $crawler): array
    {
        $links = [];
        foreach ($crawler->filter('table tbody tr[data-id]') as $row) {
            $rowCrawler = new Crawler($row);
            foreach ($rowCrawler->filter('td.actions a[href]') as $a) {
                if (!$a instanceof \DOMElement) {
                    continue;
                }
                $href = $a->getAttribute('href');
                if ('' === $href || 'javascript:' === substr($href, 0, 11) || '#' === $href) {
                    continue;
                }
                $links[] = $href;
            }
        }

        return array_values(array_unique($links));
    }

    /**
     * @param KernelBrowser $client
     * @param array<int, string> $links
     */
    private function verifyLinksDoNotReturn500(KernelBrowser $client, array $links): void
    {
        foreach ($links as $href) {
            $client->request('GET', $href);
            $hops = 0;
            while ($client->getResponse()->isRedirection() && $hops < 3) {
                $client->followRedirect();
                ++$hops;
            }
            $status = $client->getResponse()->getStatusCode();
            $this->assertLessThan(500, $status, sprintf('链接 %s 最终返回了 %d', $href, $status));
        }
    }
}
