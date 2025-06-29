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

namespace TYPO3\CMS\Backend\Tests\Functional\RecordList;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\RecordList\Event\AfterRecordListRowPreparedEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseRecordListTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'FR-CA' => ['id' => 2, 'title' => 'French (CA)', 'locale' => 'fr_CA.UTF8'],
        'ES' => ['id' => 3, 'title' => 'Spanish', 'locale' => 'es_ES.UTF8'],
    ];
    protected array $coreExtensionsToLoad = ['workspaces'];
    private BackendUserAuthentication $backendUser;
    private DatabaseRecordList $recordList;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
                $scenarioFile = __DIR__ . '/../Fixtures/CommonScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($this->backendUser);
                $writer->invokeFactory($factory);
                static::failIfArrayIsNotEmpty($writer->getErrors());
            },
            function () {
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
            }
        );

        // Create site configuration for default tests
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1100, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr'),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca'),
                $this->buildLanguageConfiguration('ES', '/es'),
            ]
        );

        // Create request with site and PageContext for page 1100 with default language
        $request = (new ServerRequest('http://localhost/'))
            ->withAttribute('route', (new Route('/typo3/module/content/records', ['_identifier' => 'records'])))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test'));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createWithLanguages($request, 1100, [0], $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        $this->recordList = $this->get(DatabaseRecordList::class);
        $this->recordList->setRequest($request);
        $this->recordList->start(1100, 'tt_content', 0);
    }

    /**
     * @throws \Exception
     */
    #[DataProvider('itemCountPerLanguageDataProvider')]
    #[Test]
    public function listReturnsCorrectAmountOfItemsPerLanguage(int $pageId, array $languages, int $expectedItemCount): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration($pageId, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr'),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca'),
                $this->buildLanguageConfiguration('ES', '/es'),
            ]
        );

        $request = (new ServerRequest('http://localhost/'))
            ->withAttribute('route', (new Route('/typo3/module/content/records', ['_identifier' => 'records'])))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test'));

        // Create PageContext with specified languages
        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createWithLanguages($request, $pageId, $languages, $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest($request);
        $recordList->start($pageId, 'tt_content', 0);

        $listHtml = $recordList->generateList();

        if ($expectedItemCount === 0) {
            // When no content is expected, the HTML should be empty or contain no records
            self::assertStringNotContainsString('data-table="tt_content"', $listHtml);
            return;
        }

        // Remove SVGs from source to prevent namespace issues during XML parsing
        $listHtml = preg_replace('/(<svg.*<\/svg>)/Uui', '', $listHtml);

        // Disable libxml errors due to usage of web components
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($listHtml);
        $xpath = new \DOMXPath($dom);
        $items = $xpath->query('//tr[@data-table="tt_content"]');

        self::assertSame(count($items), $expectedItemCount);
    }

    public static function itemCountPerLanguageDataProvider(): array
    {
        return [
            'page 1100 with all languages shows all 4 content elements' => [
                'pageId' => 1100,
                'languages' => [0, 1, 2, 3], // All available languages
                'expectedItemCount' => 4,
            ],
            'page 1100 with default language only shows 2 default content elements' => [
                'pageId' => 1100,
                'languages' => [0],
                'expectedItemCount' => 2,
            ],
            'page 1100 with French (1) shows 3 elements (2 default + 1 French)' => [
                'pageId' => 1100,
                'languages' => [0, 1],
                'expectedItemCount' => 3,
            ],
            'page 1100 with French-CA (2) shows 3 elements (2 default + 1 FR-CA)' => [
                'pageId' => 1100,
                'languages' => [0, 2],
                'expectedItemCount' => 3,
            ],
            'page 1200 with default language (no translations) shows no content' => [
                'pageId' => 1200,
                'languages' => [0],
                'expectedItemCount' => 0,
            ],
            'page 1200 with default and French shows no content' => [
                'pageId' => 1200,
                'languages' => [0, 1],
                'expectedItemCount' => 0,
            ],
        ];
    }

    #[Test]
    public function moduleDataLanguageSettingIsRespected(): void
    {
        // Create PageContext with default and French languages [0, 1]
        $request = (new ServerRequest('http://localhost/'))
            ->withAttribute('route', (new Route('/typo3/module/content/records', ['_identifier' => 'records'])))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test'));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createWithLanguages($request, 1100, [0, 1], $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest($request);
        $recordList->start(1100, 'tt_content', 0);

        // Generate list for page 1100 which has French translation
        $listHtml = $recordList->generateList();

        // Verify French content is shown
        self::assertStringContainsString('FR: Content Element #1', $listHtml);
        // Default elements should also be shown
        self::assertStringContainsString('EN: Content Element #1', $listHtml);
        self::assertStringContainsString('EN: Content Element #2', $listHtml);
    }

    #[Test]
    public function allLanguagesModeShowsAllElements(): void
    {
        // Create PageContext with all available languages [0, 1, 2, 3]
        $request = (new ServerRequest('http://localhost/'))
            ->withAttribute('route', (new Route('/typo3/module/content/records', ['_identifier' => 'records'])))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test'));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createWithLanguages($request, 1100, [0, 1, 2, 3], $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest($request);
        $recordList->start(1100, 'tt_content', 0);

        $listHtml = $recordList->generateList();

        // All content elements should be visible
        self::assertStringContainsString('EN: Content Element #1', $listHtml);
        self::assertStringContainsString('EN: Content Element #2', $listHtml);
        self::assertStringContainsString('FR: Content Element #1', $listHtml);
        self::assertStringContainsString('FR-CA: Content Element #1', $listHtml);
    }

    #[Test]
    public function languageFilterDoesNotShowWrongLanguageTranslations(): void
    {
        // Create PageContext with default and French languages only [0, 1]
        $request = (new ServerRequest('http://localhost/'))
            ->withAttribute('route', (new Route('/typo3/module/content/records', ['_identifier' => 'records'])))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test'));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createWithLanguages($request, 1100, [0, 1], $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest($request);
        $recordList->start(1100, 'tt_content', 0);

        $listHtml = $recordList->generateList();

        // French content should be shown
        self::assertStringContainsString('FR: Content Element #1', $listHtml);
        // French-CA content should NOT be shown when only French is selected
        self::assertStringNotContainsString('FR-CA: Content Element #1', $listHtml);
    }

    #[Test]
    public function localizationPanelDoesNotOfferLanguageWithoutPageTranslation(): void
    {
        // Create PageContext with only default language [0] for page 1200
        $request = (new ServerRequest('http://localhost/'))
            ->withAttribute('route', (new Route('/typo3/module/content/records', ['_identifier' => 'records'])))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test'));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createWithLanguages($request, 1200, [0], $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest($request);

        // Page 1200 has NO translations
        $recordList->start(1200, 'tt_content', 0);

        $listHtml = $recordList->generateList();

        // The localization panel should NOT be present at all
        // because only default language is selected and page has no translations
        self::assertStringNotContainsString('t3js-action-localize', $listHtml);
    }

    #[Test]
    public function afterRecordListRowPreparedEventIsTriggered(): void
    {
        $request = (new ServerRequest('http://localhost/'))
            ->withAttribute('route', (new Route('/typo3/module/content/records', ['_identifier' => 'records'])))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test'));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createWithLanguages($request, 1100, [0], $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-record-list-row-prepared-event-listener',
            static function (AfterRecordListRowPreparedEvent $event) {
                $data = $event->getData();
                $data['__label'] = 'NEW LABEL';
                $data['header'] = 'NEW HEADER'; // This field is ignored since __label is set.
                $data['rowDescription'] = 'NEW rowDescription';
                $event->setData($data);
            }
        );

        $listenerProvider = $this->get(ListenerProvider::class);
        $listenerProvider->addListener(AfterRecordListRowPreparedEvent::class, 'after-record-list-row-prepared-event-listener');

        $container->set(EventDispatcherInterface::class, new EventDispatcher($listenerProvider));

        $table = 'tt_content';
        $record = new RawRecord(1, 1, [
            'CType' => 'my_plugin_pi1',
        ], new ComputedProperties(), $table . '.my_plugin_pi1');

        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest($request);
        $recordList->fieldArray = $recordList->getColumnsToRender($table, true);

        $listRow = $recordList->renderListRow($table, $record, 0, [], false);

        self::assertStringContainsString('NEW LABEL', $listRow);
        self::assertStringContainsString('NEW rowDescription', $listRow);
    }
}
