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

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Redirects\Service\SlugService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class SlugServiceTest extends FunctionalTestCase
{
    /**
     * @var SlugService
     */
    private $subject;

    /**
     * @var CorrelationId
     */
    private $correlationId;

    private $languages = [
        [
            'title' => 'English',
            'enabled' => true,
            'languageId' => '0',
            'base' => '/en/',
            'typo3Language' => 'default',
            'locale' => 'en_US.UTF-8',
            'iso-639-1' => 'en',
            'navigationTitle' => 'English',
            'hreflang' => 'en-us',
            'direction' => 'ltr',
            'flag' => 'us',
        ],
        [
            'title' => 'German',
            'enabled' => true,
            'languageId' => '1',
            'base' => 'https://de.example.com/',
            'typo3Language' => 'de',
            'locale' => 'de_DE.UTF-8',
            'iso-639-1' => 'de',
            'navigationTitle' => 'German',
            'hreflang' => 'de-de',
            'direction' => 'ltr',
            'flag' => 'de',
        ],
        [
            'title' => 'Spanish',
            'enabled' => true,
            'languageId' => '2',
            'base' => '/es/',
            'typo3Language' => 'es',
            'locale' => 'es_ES.UTF-8',
            'iso-639-1' => 'es',
            'navigationTitle' => 'Spanish',
            'hreflang' => 'es-es',
            'direction' => 'ltr',
            'flag' => 'es',
        ],
    ];

    protected array $coreExtensionsToLoad = ['redirects'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->correlationId = CorrelationId::forScope(StringUtility::getUniqueId('test'));
        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();
    }

    protected function tearDown(): void
    {
        unset($this->subject, $this->correlationId);
        parent::tearDown();
    }

    /**
     * This test should prove, that a renaming of a subtree works as expected
     * and all slugs of sub pages are renamed and redirects are created.
     *
     * We test here that rebuildSlugsForSlugChange works for a partial tree.
     * @test
     */
    public function rebuildSlugsForSlugChangeRenamesSubSlugsAndCreatesRedirects(): void
    {
        $this->buildBaseSite();
        $this->createSubject();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SlugServiceTest_pages_test1.csv');
        $this->subject->rebuildSlugsForSlugChange(2, '/dummy-1-2', '/test-new', $this->correlationId);

        // These are the slugs after rebuildSlugsForSlugChange() has run
        $slugs = [
            '/',
            '/test-new',
            '/dummy-1-3',
            '/dummy-1-4',
            '/test-new/dummy-1-2-5',
            '/test-new/dummy-1-2-6',
            '/test-new/dummy-1-2-7',
            '/dummy-1-3/dummy-1-3-8',
            '/dummy-1-3/dummy-1-3-9',
            '/dummy-1-4/dummy-1-4-10',
        ];

        // This redirects should exists, after rebuildSlugsForSlugChange() has run
        $redirects = [
            ['source_host' => '*', 'source_path' => '/dummy-1-2', 'target' => 't3://page?uid=2&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-2/dummy-1-2-5', 'target' => 't3://page?uid=5&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-2/dummy-1-2-6', 'target' => 't3://page?uid=6&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-2/dummy-1-2-7', 'target' => 't3://page?uid=7&_language=0'],
        ];

        $this->assertSlugsAndRedirectsExists($slugs, $redirects);
    }

    /**
     * This test should prove, that a renaming of a complete tree works as expected
     * and all slugs of sub pages are renamed and redirects are created.
     *
     * We test here that rebuildSlugsForSlugChange works for a complete tree inclusive the root page.
     * @test
     */
    public function rebuildSlugsForSlugChangeRenamesSubSlugsAndCreatesRedirectsForRootChange(): void
    {
        $this->buildBaseSite();
        $this->createSubject();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SlugServiceTest_pages_test2.csv');
        $this->subject->rebuildSlugsForSlugChange(1, '/', '/new-home', $this->correlationId);

        // These are the slugs after rebuildSlugsForSlugChange() has run
        $slugs = [
            '/new-home',
            '/new-home/dummy-1-2',
            '/new-home/dummy-1-3',
            '/new-home/dummy-1-4',
            '/new-home/dummy-1-2/dummy-1-2-5',
            '/new-home/dummy-1-2/dummy-1-2-6',
            '/new-home/dummy-1-2/dummy-1-2-7',
            '/new-home/dummy-1-3/dummy-1-3-8',
            '/new-home/dummy-1-3/dummy-1-3-9',
            '/new-home/dummy-1-4/dummy-1-4-10',
        ];

        // This redirects should exists, after rebuildSlugsForSlugChange() has run
        $redirects = [
            ['source_host' => '*', 'source_path' => '/', 'target' => 't3://page?uid=1&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-2', 'target' => 't3://page?uid=2&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-3', 'target' => 't3://page?uid=3&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-4', 'target' => 't3://page?uid=4&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-2/dummy-1-2-5', 'target' => 't3://page?uid=5&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-2/dummy-1-2-6', 'target' => 't3://page?uid=6&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-2/dummy-1-2-7', 'target' => 't3://page?uid=7&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-3/dummy-1-3-8', 'target' => 't3://page?uid=8&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-3/dummy-1-3-9', 'target' => 't3://page?uid=9&_language=0'],
            ['source_host' => '*', 'source_path' => '/dummy-1-4/dummy-1-4-10', 'target' => 't3://page?uid=10&_language=0'],
        ];

        $this->assertSlugsAndRedirectsExists($slugs, $redirects);
    }

    /**
     * This test should prove, that a renaming of a subtree works as expected
     * and all slugs of sub pages are renamed and redirects are created.
     *
     * We test here that rebuildSlugsForSlugChange works for a setup with a base in a sub-folder.
     * @test
     */
    public function rebuildSlugsForSlugChangeRenamesSubSlugsAndCreatesRedirectsWithSubFolderBase(): void
    {
        $this->buildBaseSiteInSubfolder();
        $this->createSubject();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SlugServiceTest_pages_test1.csv');
        $this->subject->rebuildSlugsForSlugChange(2, '/dummy-1-2', '/test-new', $this->correlationId);

        // These are the slugs after rebuildSlugsForSlugChange() has run
        $slugs = [
            '/',
            '/test-new',
            '/dummy-1-3',
            '/dummy-1-4',
            '/test-new/dummy-1-2-5',
            '/test-new/dummy-1-2-6',
            '/test-new/dummy-1-2-7',
            '/dummy-1-3/dummy-1-3-8',
            '/dummy-1-3/dummy-1-3-9',
            '/dummy-1-4/dummy-1-4-10',
        ];

        // This redirects should exists, after rebuildSlugsForSlugChange() has run
        $redirects = [
            ['source_host' => '*', 'source_path' => '/sub-folder/dummy-1-2', 'target' => 't3://page?uid=2&_language=0'],
            ['source_host' => '*', 'source_path' => '/sub-folder/dummy-1-2/dummy-1-2-5', 'target' => 't3://page?uid=5&_language=0'],
            ['source_host' => '*', 'source_path' => '/sub-folder/dummy-1-2/dummy-1-2-6', 'target' => 't3://page?uid=6&_language=0'],
            ['source_host' => '*', 'source_path' => '/sub-folder/dummy-1-2/dummy-1-2-7', 'target' => 't3://page?uid=7&_language=0'],
        ];

        $this->assertSlugsAndRedirectsExists($slugs, $redirects);
    }

    /**
     * This test should prove, that a renaming of a subtree works as expected
     * and all slugs of sub pages are renamed and redirects are created.
     *
     * We test here that rebuildSlugsForSlugChange works for a setup with languages.
     * @test
     */
    public function rebuildSlugsForSlugChangeRenamesSubSlugsAndCreatesRedirectsWithLanguages(): void
    {
        $this->buildBaseSiteWithLanguages();
        $this->createSubject();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SlugServiceTest_pages_test3.csv');
        $this->subject->rebuildSlugsForSlugChange(31, '/dummy-1-3', '/test-new', $this->correlationId);

        // These are the slugs after rebuildSlugsForSlugChange() has run
        $slugs = [
            '/',
            '/dummy-1-2',
            '/test-new',
            '/dummy-1-3',
            '/dummy-1-4',
            '/dummy-1-2/dummy-1-2-5',
            '/dummy-1-2/dummy-1-2-6',
            '/dummy-1-2/dummy-1-2-7',
            '/dummy-1-3/dummy-1-3-8',
            '/test-new/dummy-1-3-8',
            '/dummy-1-3/dummy-1-3-9',
            '/dummy-1-4/dummy-1-4-10',
        ];

        // This redirects should exists, after rebuildSlugsForSlugChange() has run
        $redirects = [
            ['source_host' => 'de.example.com', 'source_path' => '/dummy-1-3', 'target' => 't3://page?uid=3&_language=1'],
            ['source_host' => 'de.example.com', 'source_path' => '/dummy-1-3/dummy-1-3-8', 'target' => 't3://page?uid=8&_language=1'],
        ];

        $this->assertSlugsAndRedirectsExists($slugs, $redirects);
    }

    /**
     * This test should prove, that a renaming of a subtree works as expected
     * and all slugs of sub pages are renamed and redirects are created.
     *
     * We test here that rebuildSlugsForSlugChange works with languages and a base in a sub-folder.
     * @test
     */
    public function rebuildSlugsForSlugChangeRenamesSubSlugsAndCreatesRedirectsWithLanguagesInSubFolder(): void
    {
        $this->buildBaseSiteWithLanguagesInSubFolder();
        $this->createSubject();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SlugServiceTest_pages_test3.csv');
        $this->subject->rebuildSlugsForSlugChange(31, '/dummy-1-3', '/test-new', $this->correlationId);

        // These are the slugs after rebuildSlugsForSlugChange() has run
        $slugs = [
            '/',
            '/dummy-1-2',
            '/test-new',
            '/dummy-1-3',
            '/dummy-1-4',
            '/dummy-1-2/dummy-1-2-5',
            '/dummy-1-2/dummy-1-2-6',
            '/dummy-1-2/dummy-1-2-7',
            '/dummy-1-3/dummy-1-3-8',
            '/test-new/dummy-1-3-8',
            '/dummy-1-3/dummy-1-3-9',
            '/dummy-1-4/dummy-1-4-10',
        ];

        // This redirects should exists, after rebuildSlugsForSlugChange() has run
        $redirects = [
            ['source_host' => 'de.example.com', 'source_path' => '/sub-folder/dummy-1-3', 'target' => 't3://page?uid=3&_language=1'],
            ['source_host' => 'de.example.com', 'source_path' => '/sub-folder/dummy-1-3/dummy-1-3-8', 'target' => 't3://page?uid=8&_language=1'],
        ];

        $this->assertSlugsAndRedirectsExists($slugs, $redirects);
    }

    /**
     * This test should prove, that a renaming of a subtree works as expected
     * and all slugs of sub pages are renamed and redirects are created.
     *
     * We test here that rebuildSlugsForSlugChange works with languages and a base in a sub-folder.
     * @test
     */
    public function rebuildSlugsForSlugChangeRenamesSubSlugsAndCreatesRedirectsWithDefaultLanguageInSubFolder(): void
    {
        $this->buildBaseSiteWithLanguagesInSubFolder();
        $this->createSubject();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SlugServiceTest_pages_test3.csv');
        $this->subject->rebuildSlugsForSlugChange(3, '/dummy-1-3', '/test-new', $this->correlationId);

        // These are the slugs after rebuildSlugsForSlugChange() has run
        $slugs = [
            '/',
            '/dummy-1-2',
            '/test-new',
            '/dummy-1-3',
            '/dummy-1-4',
            '/dummy-1-2/dummy-1-2-5',
            '/dummy-1-2/dummy-1-2-6',
            '/dummy-1-2/dummy-1-2-7',
            '/dummy-1-3/dummy-1-3-8',
            '/test-new/dummy-1-3-8',
            '/test-new/dummy-1-3-9',
            '/dummy-1-4/dummy-1-4-10',
        ];

        // This redirects should exists, after rebuildSlugsForSlugChange() has run
        $redirects = [
            ['source_host' => '*', 'source_path' => '/sub-folder/en/dummy-1-3', 'target' => 't3://page?uid=3&_language=0'],
            ['source_host' => '*', 'source_path' => '/sub-folder/en/dummy-1-3/dummy-1-3-8', 'target' => 't3://page?uid=8&_language=0'],
            ['source_host' => '*', 'source_path' => '/sub-folder/en/dummy-1-3/dummy-1-3-9', 'target' => 't3://page?uid=9&_language=0'],
        ];

        $this->assertSlugsAndRedirectsExists($slugs, $redirects);
    }

    /**
     * This test should prove, that a renaming of a subtree works as expected
     * and all slugs of sub pages are renamed and redirects are created.
     *
     * We test here that rebuildSlugsForSlugChange works when changing a L>0 siteroot which has pid=0
     * @test
     */
    public function rebuildSlugsForSlugChangeRenamesSubSlugsAndCreatesRedirectsWithLanguagesForSiteroot(): void
    {
        $this->buildBaseSiteWithLanguages();
        $this->createSubject();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SlugServiceTest_pages_test4.csv');
        $this->subject->rebuildSlugsForSlugChange(5, '/', '/test-new', $this->correlationId);

        // These are the slugs after rebuildSlugsForSlugChange() has run
        $slugs = [
            '/',
            '/dummy-1-2',
            '/dummy-1-3',
            '/dummy-1-2/dummy-1-2-3',
            '/test-new',
            '/test-new/dummy-1-2',
            '/test-new/dummy-1-3',
            '/test-new/dummy-1-2/dummy-1-2-3',
        ];

        // This redirects should exists, after rebuildSlugsForSlugChange() has run
        $redirects = [
            ['source_host' => 'de.example.com', 'source_path' => '/', 'target' => 't3://page?uid=1&_language=1'],
            ['source_host' => 'de.example.com', 'source_path' => '/dummy-1-2', 'target' => 't3://page?uid=2&_language=1'],
            ['source_host' => 'de.example.com', 'source_path' => '/dummy-1-3', 'target' => 't3://page?uid=3&_language=1'],
            ['source_host' => 'de.example.com', 'source_path' => '/dummy-1-2/dummy-1-2-3', 'target' => 't3://page?uid=4&_language=1'],
        ];

        $this->assertSlugsAndRedirectsExists($slugs, $redirects);
    }

    protected function buildBaseSite(): void
    {
        $configuration = [
            'rootPageId' => 1,
            'base' => '/',
        ];
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $siteConfiguration->write('testing', $configuration);
    }

    protected function buildBaseSiteInSubfolder(): void
    {
        $configuration = [
            'rootPageId' => 1,
            'base' => '/sub-folder',
        ];
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $siteConfiguration->write('testing', $configuration);
    }

    protected function buildBaseSiteWithLanguages(): void
    {
        $configuration = [
            'rootPageId' => 1,
            'base' => '/',
            'languages' => $this->languages,
        ];
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $siteConfiguration->write('testing', $configuration);
    }

    protected function buildBaseSiteWithLanguagesInSubFolder(): void
    {
        $languages = $this->languages;
        array_walk($languages, static function (&$languageData) {
            $languageData['base'] = (
                !str_contains($languageData['base'], 'http')
                    ? $languageData['base']
                    : $languageData['base'] . 'sub-folder/'
            );
        });
        $configuration = [
            'rootPageId' => 1,
            'base' => '/sub-folder',
            'languages' => $languages,
        ];
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $siteConfiguration->write('testing', $configuration);
    }

    protected function createSubject(): void
    {
        GeneralUtility::makeInstance(SiteMatcher::class)->refresh();
        $this->subject = new SlugService(
            GeneralUtility::makeInstance(Context::class),
            GeneralUtility::makeInstance(SiteFinder::class),
            GeneralUtility::makeInstance(PageRepository::class),
            GeneralUtility::makeInstance(LinkService::class)
        );
        $this->subject->setLogger(new NullLogger());
    }

    protected function assertSlugsAndRedirectsExists(array $slugs, array $redirects): void
    {
        $pageRecords = $this->getAllRecords('pages');
        self::assertCount(count($slugs), $pageRecords);
        foreach ($pageRecords as $record) {
            self::assertContains($record['slug'], $slugs, 'unexpected slug: ' . $record['slug']);
        }

        $redirectRecords = $this->getAllRecords('sys_redirect');
        self::assertCount(count($redirects), $redirectRecords);
        foreach ($redirectRecords as $record) {
            $combination = [
                'source_host' => $record['source_host'],
                'source_path' => $record['source_path'],
                'target' => $record['target'],
            ];
            self::assertContains($combination, $redirects, 'wrong redirect found');
        }
    }
}
