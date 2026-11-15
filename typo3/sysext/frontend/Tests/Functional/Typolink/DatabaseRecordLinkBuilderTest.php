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

namespace TYPO3\CMS\Frontend\Tests\Functional\Typolink;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\BeforeDatabaseRecordLinkResolvedEvent;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseRecordLinkBuilderTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'features' => [
                'frontend.cache.autoTagging' => true,
            ],
        ],
    ];

    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content_record_links.csv');
    }

    #[Test]
    public function buildLinkTriggersBeforeDatabaseRecordLinkResolvedEvent(): void
    {
        // Set up page information with rootline
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setPageRecord(['uid' => 1, 'pid' => 0, 'title' => 'Root']);
        $pageInformation->setRootLine([
            0 => [
                'uid' => 1,
                'pid' => 0,
                'title' => 'Root',
                'TSconfig' => 'TCEMAIN.linkHandler.foo.configuration.table = tx_table',
            ],
        ]);

        // Set up TypoScript configuration
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'config.' => [
                'recordLinks.' => [
                    'foo.' => [
                        'forceLink' => false,
                        'typolink.' => [
                            'parameter' => '1',
                        ],
                    ],
                ],
            ],
        ]);
        $frontendTypoScript->setConfigArray([
            'recordLinks.' => [
                'foo.' => [
                    'forceLink' => false,
                    'typolink.' => [
                        'parameter' => '1',
                    ],
                ],
            ],
        ]);

        // Create a minimal site
        $site = new Site('test', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'base' => '/'],
            ],
        ]);

        $request = new ServerRequest('https://example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('site', $site);

        // Create a ContentObjectRenderer for the currentContentObject attribute
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $request = $request->withAttribute('currentContentObject', $contentObjectRenderer);

        $testRecord = ['uid' => 1, 'pid' => 0, 'title' => 'My test record'];

        /** @var Container $container */
        $container = $this->getContainer();
        // This is called but does not set a record
        $container->set(
            'before-database-record-link-resolved-event-listener-called',
            function (BeforeDatabaseRecordLinkResolvedEvent $event): void {
                $this->dispatchedEvents[] = $event;
            }
        );
        // This is called and sets a record
        $container->set(
            'before-database-record-link-resolved-event-listener-called-sets-record',
            function (BeforeDatabaseRecordLinkResolvedEvent $event) use ($testRecord): void {
                $this->dispatchedEvents[] = $event;
                // Set a record to stop propagation and prevent default logic from running
                $event->record = $testRecord;
            }
        );
        // This should not be called
        $container->set(
            'before-database-record-link-resolved-event-listener-no-called',
            function (BeforeDatabaseRecordLinkResolvedEvent $event): void {
                $this->dispatchedEvents[] = $event;
                $event->record = ['uid' => 2, 'pid' => 0, 'title' => 'My second test record'];
            }
        );

        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(
            BeforeDatabaseRecordLinkResolvedEvent::class,
            'before-database-record-link-resolved-event-listener-called'
        );
        $listenerProvider->addListener(
            BeforeDatabaseRecordLinkResolvedEvent::class,
            'before-database-record-link-resolved-event-listener-called-sets-record'
        );
        $listenerProvider->addListener(
            BeforeDatabaseRecordLinkResolvedEvent::class,
            'before-database-record-link-resolved-event-listener-no-called'
        );

        try {
            // Call DatabaseRecordLinkBuilder directly
            $this->get(DatabaseRecordLinkBuilder::class)->buildLink(
                [
                    'identifier' => 'foo',
                    'uid' => '1',
                    'typoLinkParameter' => 't3://record?identifier=foo&uid=1',
                ],
                [],
                $request
            );
        } catch (\Exception $e) {
            // We don't care if the link building fails after the event is dispatched
            // We only care that the event was triggered
        }

        // Verify event properties
        self::assertCount(2, $this->dispatchedEvents);
        $event = $this->dispatchedEvents[1];
        self::assertSame('foo', $event->linkDetails['identifier']);
        self::assertSame('1', $event->linkDetails['uid']);
        self::assertSame('tx_table', $event->databaseTable);
        self::assertSame($testRecord, $event->record);
    }

    #[Test]
    public function buildLinkAddsCacheTagOfLinkedRecord(): void
    {
        $cacheDataCollector = new CacheDataCollector();

        $linkResult = $this->buildLinkToRecord(5, $cacheDataCollector);

        self::assertSame('https://example.com/record', $linkResult->getUrl());
        self::assertContains('tt_content_5', $this->getCacheTagNames($cacheDataCollector));
    }

    #[Test]
    public function buildLinkAddsCacheTagOfLinkedPage(): void
    {
        $cacheDataCollector = new CacheDataCollector();

        $linkResult = $this->buildLinkToRecord(5, $cacheDataCollector, 'pages');

        self::assertSame('https://example.com/record', $linkResult->getUrl());
        self::assertContains('pages_5', $this->getCacheTagNames($cacheDataCollector));
    }

    #[Test]
    public function buildLinkAddsCacheTagOfHiddenLinkedRecord(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')->update('tt_content', ['hidden' => 1], ['uid' => 5]);
        $cacheDataCollector = new CacheDataCollector();

        try {
            $this->buildLinkToRecord(5, $cacheDataCollector);
            self::fail('Record 5 is hidden and must not be linked');
        } catch (UnableToLinkException) {
        }

        self::assertContains('tt_content_5', $this->getCacheTagNames($cacheDataCollector));
    }

    #[Test]
    public function buildLinkAddsNoCacheTagIfAutomaticCacheTaggingIsDisabled(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['frontend.cache.autoTagging'] = false;
        $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')->update('tt_content', ['hidden' => 1], ['uid' => 5]);
        $cacheDataCollector = new CacheDataCollector();

        try {
            $this->buildLinkToRecord(5, $cacheDataCollector);
            self::fail('Record 5 is hidden and must not be linked');
        } catch (UnableToLinkException) {
        }

        self::assertNotContains('tt_content_5', $this->getCacheTagNames($cacheDataCollector));
    }

    #[Test]
    public function cacheTagOfLinkedRecordExpiresWithStartTimeOfRecord(): void
    {
        $startTime = $GLOBALS['EXEC_TIME'] + 3600;
        $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')->update('tt_content', ['starttime' => $startTime], ['uid' => 5]);
        $cacheDataCollector = new CacheDataCollector();

        try {
            $this->buildLinkToRecord(5, $cacheDataCollector);
            self::fail('Record 5 is not started yet and must not be linked');
        } catch (UnableToLinkException) {
        }

        $lifetime = $this->getCacheTagLifetime($cacheDataCollector, 'tt_content_5');
        self::assertLessThan(3660, $lifetime);
        self::assertGreaterThan(3500, $lifetime);
    }

    #[Test]
    public function buildLinkAddsCacheTagOfLinkedRecordWhichCanNotBeLinked(): void
    {
        $cacheDataCollector = new CacheDataCollector();

        try {
            $this->buildLinkToRecord(99, $cacheDataCollector);
            self::fail('Record 99 does not exist and must not be linked');
        } catch (UnableToLinkException) {
        }

        self::assertContains('tt_content_99', $this->getCacheTagNames($cacheDataCollector));
    }

    #[Test]
    public function buildLinkAddsCacheTagOfTranslationOfLinkedRecord(): void
    {
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [0]));
        $cacheDataCollector = new CacheDataCollector();

        $this->buildLinkToRecord(5, $cacheDataCollector);

        $cacheTagNames = $this->getCacheTagNames($cacheDataCollector);
        self::assertContains('tt_content_5', $cacheTagNames);
        self::assertContains('tt_content_8', $cacheTagNames);
    }

    #[Test]
    public function cacheTagOfTranslationOfLinkedRecordExpiresWithEndTimeOfTranslation(): void
    {
        $endTime = $GLOBALS['EXEC_TIME'] + 3600;
        $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')->update('tt_content', ['endtime' => $endTime], ['uid' => 8]);
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [0]));
        $cacheDataCollector = new CacheDataCollector();

        $this->buildLinkToRecord(5, $cacheDataCollector);

        $lifetime = $this->getCacheTagLifetime($cacheDataCollector, 'tt_content_8');
        self::assertLessThan(3660, $lifetime);
        self::assertGreaterThan(3500, $lifetime);
    }

    #[Test]
    public function buildLinkAddsNoCacheTagWithoutTable(): void
    {
        $cacheDataCollector = new CacheDataCollector();

        try {
            $this->buildLinkToRecord(5, $cacheDataCollector, '');
        } catch (UnableToLinkException) {
        }

        self::assertNotContains('_5', $this->getCacheTagNames($cacheDataCollector));
    }

    private function buildLinkToRecord(int $uid, CacheDataCollector $cacheDataCollector, string $table = 'tt_content'): LinkResultInterface
    {
        return $this->get(DatabaseRecordLinkBuilder::class)->buildLink(
            [
                'identifier' => 'foo',
                'uid' => (string)$uid,
                'typoLinkParameter' => 't3://record?identifier=foo&uid=' . $uid,
            ],
            [],
            $this->buildRequestForRecordLink($cacheDataCollector, $table)
        );
    }

    private function buildRequestForRecordLink(CacheDataCollector $cacheDataCollector, string $table): ServerRequest
    {
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setPageRecord(['uid' => 1, 'pid' => 0, 'title' => 'Root']);
        $pageInformation->setRootLine([
            0 => [
                'uid' => 1,
                'pid' => 0,
                'title' => 'Root',
                'TSconfig' => 'TCEMAIN.linkHandler.foo.configuration.table = ' . $table,
            ],
        ]);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'config.' => [
                'recordLinks.' => [
                    'foo.' => [
                        'typolink.' => [
                            'parameter' => 'https://example.com/record',
                        ],
                    ],
                ],
            ],
        ]);
        $frontendTypoScript->setConfigArray([]);
        $request = new ServerRequest('https://example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('frontend.cache.collector', $cacheDataCollector);
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        return $request->withAttribute('currentContentObject', $contentObjectRenderer);
    }

    /**
     * @return list<string>
     */
    private function getCacheTagNames(CacheDataCollector $cacheDataCollector): array
    {
        return array_map(static fn(CacheTag $cacheTag): string => $cacheTag->name, $cacheDataCollector->getCacheTags());
    }

    private function getCacheTagLifetime(CacheDataCollector $cacheDataCollector, string $name): int
    {
        foreach ($cacheDataCollector->getCacheTags() as $cacheTag) {
            if ($cacheTag->name === $name) {
                return $cacheTag->lifetime;
            }
        }
        self::fail('Cache tag "' . $name . '" was not added');
    }
}
