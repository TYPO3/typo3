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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FrontendIndexingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
    ];

    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/indexed_search/Tests/Functional/Fixtures/Extensions/test_indexing_events',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Frontend/pages.csv');
        $this->setUpFrontendRootPage(1, ['EXT:indexed_search/Tests/Functional/Fixtures/Frontend/setup.typoscript']);
    }

    #[Test]
    public function pageIsIndexedByDefault(): void
    {
        $this->executeFrontendSubRequest(new InternalRequest('https://acme.com/'));

        self::assertGreaterThan(0, $this->countIndexedPages());
    }

    #[Test]
    public function listenerCanDisableIndexingOfCurrentPage(): void
    {
        $this->executeFrontendSubRequest(
            (new InternalRequest('https://acme.com/'))->withHeader('X-Test-Disable-Indexing', '1')
        );

        self::assertSame(0, $this->countIndexedPages());
    }

    #[Test]
    public function disablingIndexingWinsOverEnablingIndexing(): void
    {
        $this->executeFrontendSubRequest(
            (new InternalRequest('https://acme.com/'))
                ->withHeader('X-Test-Enable-Indexing', '1')
                ->withHeader('X-Test-Disable-Indexing', '1')
        );

        self::assertSame(0, $this->countIndexedPages());
    }

    private function countIndexedPages(): int
    {
        return (int)$this->get(ConnectionPool::class)
            ->getConnectionForTable('index_phash')
            ->count('*', 'index_phash', []);
    }
}
