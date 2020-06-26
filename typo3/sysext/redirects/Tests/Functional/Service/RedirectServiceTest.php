<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Redirects\Tests\Functional\Service;

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Configuration\RedirectCleanupConfiguration;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\RedirectService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class RedirectServiceTest extends FunctionalTestCase
{
    /**
     * @var RedirectService
     */
    private $redirectService;

    protected $coreExtensionsToLoad = ['redirects'];

    protected function setUp(): void
    {
        parent::setUp();
        $siteFinder = $this->prophesizeSiteFinder()->reveal();
        $this->redirectService = new RedirectService(
            new RedirectCacheService(),
            $this->prophesize(LinkService::class)->reveal(),
            $siteFinder,
            new RedirectRepository()
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_redirect')
            ->truncate('sys_redirect');
    }

    public function cleanupRedirectsByConfigurationDataProvider(): array
    {
        $allRecordCount = 6;
        return [
            'empty configuration' => [new RedirectCleanupConfiguration(), $allRecordCount, $allRecordCount-4],
            'configuration with hitCount' => [(new RedirectCleanupConfiguration())->setHitCount(2), $allRecordCount, $allRecordCount-3],
            'configuration with statusCode 302' => [(new RedirectCleanupConfiguration())->setStatusCodes([302]), $allRecordCount, $allRecordCount-1],
            'configuration with statusCode 302, 303' => [(new RedirectCleanupConfiguration())->setStatusCodes([302, 303]), $allRecordCount, $allRecordCount-2],
            'configuration with domain' => [(new RedirectCleanupConfiguration())->setDomains(['foo.com']), $allRecordCount, $allRecordCount-2],
            'configuration with domains' => [(new RedirectCleanupConfiguration())->setDomains(['foo.com', 'bar.com']), $allRecordCount, $allRecordCount-3],
            'configuration with path' => [(new RedirectCleanupConfiguration())->setPath('/foo'), $allRecordCount, $allRecordCount-1],
            'configuration with path starts with' => [(new RedirectCleanupConfiguration())->setPath('/foo%'), $allRecordCount, $allRecordCount-3],
            'configuration with path ends with' => [(new RedirectCleanupConfiguration())->setPath('%/foo'), $allRecordCount, $allRecordCount-1],
            'configuration with path in the middle' => [(new RedirectCleanupConfiguration())->setPath('%foo%'), $allRecordCount, $allRecordCount-3],
        ];
    }

    /**
     * @dataProvider cleanupRedirectsByConfigurationDataProvider
     * @test
     * @param RedirectCleanupConfiguration $redirectCleanupConfiguration
     * @param int $redirectBeforeCleanup
     * @param int $redirectAfterCleanup
     */
    public function cleanupRedirectsByConfiguration(RedirectCleanupConfiguration $redirectCleanupConfiguration, int $redirectBeforeCleanup, int $redirectAfterCleanup): void
    {
        self::assertSame(0, $this->getRedirectCount());
        $this->importDataSet(__DIR__ . '/Fixtures/RedirectServiceTest_redirects.xml');

        self::assertSame($redirectBeforeCleanup, $this->getRedirectCount());
        $this->redirectService->cleanupRedirectsByConfiguration($redirectCleanupConfiguration);
        self::assertSame($redirectAfterCleanup, $this->getRedirectCount());
    }

    protected function getRedirectCount(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_redirect')
            ->execute()
            ->fetchColumn(0);
    }

    private function prophesizeSiteFinder(): ObjectProphecy
    {
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);

        $simpleSite = new Site('simple-page', 1, [
            'base' => 'https://example.com',
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'United States',
                    'locale' => 'en_US.UTF-8',
                ],
            ]
        ]);
        $localizedSite = new Site('localized-page', 100, [
            'base' => 'https://another.example.com',
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'United States',
                    'locale' => 'en_US.UTF-8',
                ],
                [
                    'base' => '/de/',
                    'languageId' => 1,
                    'title' => 'DE',
                    'locale' => 'de_DE.UTF-8',
                ]
            ]
        ]);

        $siteFinderProphecy->getSiteByIdentifier('simple-page')->willReturn($simpleSite);
        $siteFinderProphecy->getSiteByIdentifier('localized-page')->willReturn($localizedSite);
        $siteFinderProphecy->getAllSites()->willReturn([$simpleSite, $localizedSite]);

        return $siteFinderProphecy;
    }
}
