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

namespace TYPO3\CMS\Backend\Tests\Functional\Tree\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
use TYPO3\CMS\Backend\Tree\Repository\BeforePageTreeIsFilteredEvent;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeFilter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTreeFilterTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de'],
        'AR' => ['id' => 2, 'title' => 'Arabic', 'locale' => 'ar_SA.UTF8', 'iso' => 'ar'],
        'ZH' => ['id' => 3, 'title' => 'Chinese', 'locale' => 'zh_CN.UTF8', 'iso' => 'zh'],
    ];

    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN']),
                $this->buildLanguageConfiguration('AR', '/ar/', ['EN']),
                $this->buildLanguageConfiguration('ZH', '/zh/', ['EN']),
            ]
        );
    }

    #[Test]
    public function attachTranslationInfoLabelAddsLabelForTranslatedPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        // Simulate that page 20 was found via translation search in German (language UID 1)
        // This would normally be set by TreeController from PageTreeRepository
        $searchPhrase = 'Hauptbereich';

        $items = [
            [
                'identifier' => '20',
                'name' => 'Main Area Sub 1',
                '_page' => [
                    'uid' => 20,
                    'title' => 'Main Area Sub 1',
                ],
                '_translationLanguageUids' => [1], // German
            ],
        ];

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['q' => $searchPhrase]);
        $event = new AfterPageTreeItemsPreparedEvent($request, $items);

        $subject = $this->get(PageTreeFilter::class);
        $subject->attachTranslationInfoLabel($event);

        $result = $event->getItems();

        self::assertArrayHasKey('labels', $result[0]);
        self::assertNotEmpty($result[0]['labels']);
        $label = $result[0]['labels'][0];
        self::assertInstanceOf(Label::class, $label);
        self::assertStringContainsString('Found in translation: German', $label->label);
    }

    #[Test]
    public function attachTranslationInfoLabelAddsLabelForMultipleTranslations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        // Simulate that page 20 was found via multiple translations (German + Arabic)
        $searchPhrase = 'Unterseite';

        $items = [
            [
                'identifier' => '20',
                'name' => 'Main Area Sub 1',
                '_page' => [
                    'uid' => 20,
                    'title' => 'Main Area Sub 1',
                ],
                '_translationLanguageUids' => [1, 2], // German + Arabic
            ],
        ];

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['q' => $searchPhrase]);
        $event = new AfterPageTreeItemsPreparedEvent($request, $items);

        $subject = $this->get(PageTreeFilter::class);
        $subject->attachTranslationInfoLabel($event);

        $result = $event->getItems();

        self::assertArrayHasKey('labels', $result[0]);
        self::assertNotEmpty($result[0]['labels']);
        $label = $result[0]['labels'][0];
        self::assertInstanceOf(Label::class, $label);
        self::assertEquals('Found in multiple translations', $label->label);
    }

    #[Test]
    public function attachTranslationInfoLabelDoesNothingWhenNoTranslationsFound(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        $searchPhrase = 'Main';

        // Page 20 without translation matches
        $items = [
            [
                'identifier' => '20',
                'name' => 'Main Area Sub 1',
                '_page' => [
                    'uid' => 20,
                    'title' => 'Main Area Sub 1',
                ],
                '_translationLanguageUids' => [], // No translations matched
            ],
        ];

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['q' => $searchPhrase]);
        $event = new AfterPageTreeItemsPreparedEvent($request, $items);

        $subject = $this->get(PageTreeFilter::class);
        $subject->attachTranslationInfoLabel($event);

        $result = $event->getItems();
        self::assertArrayNotHasKey('labels', $result[0]);
    }

    #[Test]
    public function attachTranslationInfoLabelAddsLabelForArabicTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPagesMultiByteCharacters.csv');

        // Simulate that page 20 was found via Arabic translation search
        $searchPhrase = 'المنطقة الرئيسية';

        $items = [
            [
                'identifier' => '20',
                'name' => 'Main Area Sub 1',
                '_page' => [
                    'uid' => 20,
                    'title' => 'Main Area Sub 1',
                ],
                '_translationLanguageUids' => [2], // Arabic
            ],
        ];

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['q' => $searchPhrase]);
        $event = new AfterPageTreeItemsPreparedEvent($request, $items);

        $subject = $this->get(PageTreeFilter::class);
        $subject->attachTranslationInfoLabel($event);

        $result = $event->getItems();

        self::assertArrayHasKey('labels', $result[0]);
        self::assertNotEmpty($result[0]['labels']);
        $label = $result[0]['labels'][0];
        self::assertInstanceOf(Label::class, $label);
        self::assertStringContainsString('Found in translation: Arabic', $label->label);
    }

    #[Test]
    public function attachTranslationInfoLabelAddsLabelForChineseTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPagesMultiByteCharacters.csv');

        // Simulate that page 20 was found via Chinese translation search
        $searchPhrase = '主要区域';

        $items = [
            [
                'identifier' => '20',
                'name' => 'Main Area Sub 1',
                '_page' => [
                    'uid' => 20,
                    'title' => 'Main Area Sub 1',
                ],
                '_translationLanguageUids' => [3], // Chinese
            ],
        ];

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['q' => $searchPhrase]);
        $event = new AfterPageTreeItemsPreparedEvent($request, $items);

        $subject = $this->get(PageTreeFilter::class);
        $subject->attachTranslationInfoLabel($event);

        $result = $event->getItems();

        self::assertArrayHasKey('labels', $result[0]);
        self::assertNotEmpty($result[0]['labels']);
        $label = $result[0]['labels'][0];
        self::assertInstanceOf(Label::class, $label);
        self::assertStringContainsString('Found in translation: Chinese', $label->label);
    }

    #[Test]
    public function attachTranslationInfoLabelAddsLabelForGermanUmlaute(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPagesMultiByteCharacters.csv');

        // Simulate that page 21 was found via German Umlaute translation search
        $searchPhrase = 'Ähnliche Größen';

        $items = [
            [
                'identifier' => '21',
                'name' => 'Main Area Sub 2',
                '_page' => [
                    'uid' => 21,
                    'title' => 'Main Area Sub 2',
                ],
                '_translationLanguageUids' => [1], // German
            ],
        ];

        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams(['q' => $searchPhrase]);
        $event = new AfterPageTreeItemsPreparedEvent($request, $items);

        $subject = $this->get(PageTreeFilter::class);
        $subject->attachTranslationInfoLabel($event);

        $result = $event->getItems();

        self::assertArrayHasKey('labels', $result[0]);
        self::assertNotEmpty($result[0]['labels']);
        $label = $result[0]['labels'][0];
        self::assertInstanceOf(Label::class, $label);
        self::assertStringContainsString('Found in translation: German', $label->label);
    }

    #[Test]
    public function searchingByTranslationUidFindsParentPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        // Search for translation UID 1020 (German translation of page 20)
        $searchPhrase = '1020';

        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->select('uid', 'pid', 'title', 'nav_title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_wsid', 0)
            );

        $event = new BeforePageTreeIsFilteredEvent(
            searchParts: $queryBuilder->expr()->and(),
            searchUids: [],
            searchPhrase: $searchPhrase,
            queryBuilder: $queryBuilder
        );

        $subject = $this->get(PageTreeFilter::class);
        $subject->addUidsFromSearchPhrase($event);

        // Should add both the translation UID (1020) and the parent UID (20)
        self::assertContains(1020, $event->searchUids);
        self::assertContains(20, $event->searchUids);
    }

    #[Test]
    public function searchingInTranslatedTitlesFindsParentPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        // Search for German translation title "Hauptbereich"
        $searchPhrase = 'Hauptbereich';

        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->select('uid', 'pid', 'title', 'nav_title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_wsid', 0)
            );

        $event = new BeforePageTreeIsFilteredEvent(
            searchParts: $queryBuilder->expr()->and(),
            searchUids: [],
            searchPhrase: $searchPhrase,
            queryBuilder: $queryBuilder
        );

        $subject = $this->get(PageTreeFilter::class);
        $subject->addTranslatedPagesFilter($event);

        // Should add the parent page UID (20) because translation 1020 matches "Hauptbereich"
        self::assertContains(20, $event->searchUids);
    }

    #[Test]
    public function searchingByTranslationUidStoresLanguageInformationInCache(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        $runtimeCache = $this->get('cache.runtime');
        $runtimeCache->flush();

        // Search for translation UID 1020 (German translation of page 20)
        $searchPhrase = '1020';

        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->select('uid', 'pid', 'title', 'nav_title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_wsid', 0)
            );

        $event = new BeforePageTreeIsFilteredEvent(
            searchParts: $queryBuilder->expr()->and(),
            searchUids: [],
            searchPhrase: $searchPhrase,
            queryBuilder: $queryBuilder
        );

        $subject = $this->get(PageTreeFilter::class);
        $subject->addUidsFromSearchPhrase($event);

        // Check that runtime cache contains translation match information
        $cachedData = $runtimeCache->get('pageTree_translationMatches');
        self::assertIsArray($cachedData);
        self::assertArrayHasKey(20, $cachedData);
        self::assertContains(1, $cachedData[20]);
    }

    #[Test]
    public function wildcardSearchFindsPagesByTitle(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        $searchPhrase = 'Main Area';

        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->select('uid', 'pid', 'title', 'nav_title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_wsid', 0)
            );

        $event = new BeforePageTreeIsFilteredEvent(
            searchParts: $queryBuilder->expr()->and(),
            searchUids: [],
            searchPhrase: $searchPhrase,
            queryBuilder: $queryBuilder
        );
        $subject = $this->get(PageTreeFilter::class);
        $subject->addWildCardAliasFilter($event);

        // Execute the query with the added wildcard filter
        $queryBuilder->andWhere($event->searchParts);
        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        // Should find pages with "Main Area" in title: page 2, 20, 21, 22
        $foundUids = array_column($results, 'uid');
        self::assertContains(2, $foundUids, '(Main Area)');
        self::assertContains(20, $foundUids, '(Main Area Sub 1)');
        self::assertContains(21, $foundUids, '(Main Area Sub 2)');
        self::assertContains(22, $foundUids, '(Main Area Sub 3)');
    }

    #[Test]
    public function wildcardSearchFindsPagesByNavTitle(): void
    {
        // Create a test page with a specific nav_title
        $this->get(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->insert('pages', [
                'uid' => 99,
                'pid' => 1,
                'title' => 'Some Regular Title',
                'nav_title' => 'Navigation Special',
                'sys_language_uid' => 0,
                't3ver_wsid' => 0,
            ]);

        $searchPhrase = 'Navigation Special';

        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->select('uid', 'pid', 'title', 'nav_title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_wsid', 0)
            );

        $event = new BeforePageTreeIsFilteredEvent(
            searchParts: $queryBuilder->expr()->and(),
            searchUids: [],
            searchPhrase: $searchPhrase,
            queryBuilder: $queryBuilder
        );

        $subject = $this->get(PageTreeFilter::class);
        $subject->addWildCardAliasFilter($event);

        // Execute the query with the added wildcard filter
        $queryBuilder->andWhere($event->searchParts);
        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        // Should find page 99 by its nav_title
        $foundUids = array_column($results, 'uid');
        self::assertContains(99, $foundUids, 'Should find page by nav_title');
    }

    #[Test]
    public function wildcardSearchIsCaseInsensitive(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        // Search with lowercase, should find "Main Area" (mixed case)
        $searchPhrase = 'main area';

        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->select('uid', 'pid', 'title', 'nav_title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_wsid', 0)
            );

        $event = new BeforePageTreeIsFilteredEvent(
            searchParts: $queryBuilder->expr()->and(),
            searchUids: [],
            searchPhrase: $searchPhrase,
            queryBuilder: $queryBuilder
        );

        $subject = $this->get(PageTreeFilter::class);
        $subject->addWildCardAliasFilter($event);

        // Execute the query with the added wildcard filter
        $queryBuilder->andWhere($event->searchParts);
        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        // Should find pages despite case difference
        $foundUids = array_column($results, 'uid');
        self::assertNotEmpty($foundUids, 'Case-insensitive search should find results');
        self::assertContains(2, $foundUids, 'Should find page 2 (Main Area) with lowercase search');
    }

    #[Test]
    public function translationSearchRespectsUserConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslatedPages.csv');

        // Disable translation search via user preference
        $this->backendUser->uc['pageTree_searchInTranslatedPages'] = false;

        $searchPhrase = 'Hauptbereich';

        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->select('uid', 'pid', 'title', 'nav_title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_wsid', 0)
            );

        $event = new BeforePageTreeIsFilteredEvent(
            searchParts: $queryBuilder->expr()->and(),
            searchUids: [],
            searchPhrase: $searchPhrase,
            queryBuilder: $queryBuilder
        );

        $subject = $this->get(PageTreeFilter::class);
        $subject->addTranslatedPagesFilter($event);

        // Should NOT add page 20 because translation search is disabled
        self::assertEmpty($event->searchUids);

        // Re-enable for other tests
        $this->backendUser->uc['pageTree_searchInTranslatedPages'] = true;
    }
}
