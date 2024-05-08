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

namespace TYPO3\CMS\Redirects\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
use TYPO3\CMS\Redirects\RedirectUpdate\PageTypeSource;
use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AddPageTypeZeroSourceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    #[Test]
    public function pageTypeSourceZeroReplacesPlainSlugReplacementSourceToAvoidDuplicates(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SimpleSiteRoot.csv');
        $this->buildBaseSite([]);
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId(1);
        $siteLanguage = $site->getDefaultLanguage();

        /** @var SlugRedirectChangeItem $changeItem */
        $changeItem = $this->get(EventDispatcherInterface::class)->dispatch(
            new SlugRedirectChangeItemCreatedEvent(
                new SlugRedirectChangeItem(
                    1,
                    1,
                    $site,
                    $siteLanguage,
                    ['slug' => '/'],
                    new RedirectSourceCollection(),
                    ['slug' => '/changed'],
                )
            )
        )->getSlugRedirectChangeItem();

        self::assertSame(1, $changeItem->getSourcesCollection()->count());

        $source = $changeItem->getSourcesCollection()->all()[0] ?? null;
        self::assertInstanceOf(PageTypeSource::class, $source);
        self::assertSame('*', $source->getHost());
        self::assertSame('/', $source->getPath());
        self::assertSame([], $source->getTargetLinkParameters());
        self::assertSame(0, $source->getPageType());
    }

    #[Test]
    public function pageTypeSourceZeroWithPageTypeSuffixRouteEnhancerIsAddedAsAdditionalSource(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SimpleSiteRootWithPage.csv');
        $this->buildSite([
            'rootPageId' => 1,
            'base' => '/',
            'settings' => [],
            'routeEnhancers' => [
                'PageTypeSuffix' => [
                    'type' => 'PageType',
                    'default' => '.html',
                    'index' => 'index',
                    'map' => [
                        '.html' => 0,
                    ],
                ],
            ],
        ]);
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId(1);
        $siteLanguage = $site->getDefaultLanguage();

        /** @var SlugRedirectChangeItem $changeItem */
        $changeItem = $this->get(EventDispatcherInterface::class)->dispatch(
            new SlugRedirectChangeItemCreatedEvent(
                new SlugRedirectChangeItem(
                    2,
                    2,
                    $site,
                    $siteLanguage,
                    ['slug' => '/first-page'],
                    new RedirectSourceCollection(),
                    ['slug' => '/changed'],
                )
            )
        )->getSlugRedirectChangeItem();

        self::assertSame(2, $changeItem->getSourcesCollection()->count());
        $sources = $changeItem->getSourcesCollection()->all();
        $source = $sources[0] ?? null;
        self::assertInstanceOf(PlainSlugReplacementRedirectSource::class, $source);
        self::assertSame('*', $source->getHost());
        self::assertSame('/first-page', $source->getPath());
        self::assertSame([], $source->getTargetLinkParameters());

        $source = $sources[1] ?? null;
        self::assertInstanceOf(PageTypeSource::class, $source);
        self::assertSame('*', $source->getHost());
        self::assertSame('/first-page.html', $source->getPath());
        self::assertSame([], $source->getTargetLinkParameters());
        self::assertSame(0, $source->getPageType());
    }

    #[Test]
    public function customPageTypeSourceCanBeAdded(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SimpleSiteRootWithPage.csv');
        $this->buildSite([
            'rootPageId' => 1,
            'base' => '/',
            'settings' => [],
            'routeEnhancers' => [
                'PageTypeSuffix' => [
                    'type' => 'PageType',
                    'default' => '.html',
                    'index' => 'index',
                    'map' => [
                        '.html' => 0,
                        '-search.html' => 1,
                    ],
                ],
            ],
        ]);
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId(1);
        $siteLanguage = $site->getDefaultLanguage();

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'add-custom-page-type-source',
            static function (SlugRedirectChangeItemCreatedEvent $event) {
                $changeItem = $event->getSlugRedirectChangeItem();
                $site = $changeItem->getSite();
                $siteLanguage = $changeItem->getSiteLanguage();
                $pageUid = $changeItem->getPageId();
                $pageType = 1;
                try {
                    $context = GeneralUtility::makeInstance(Context::class);
                    $uri = $site->getRouter($context)->generateUri(
                        $pageUid,
                        [
                            '_language' => $siteLanguage,
                            'type' => $pageType,
                        ],
                        '',
                        RouterInterface::ABSOLUTE_URL
                    );
                    $source = new PageTypeSource(
                        $uri->getHost() ?: '*',
                        $uri->getPath(),
                        $pageType,
                        [
                            'type' => $pageType,
                        ],
                    );
                    $sources = $changeItem->getSourcesCollection()->all();
                    $sources[] = $source;
                    $changeItem = $changeItem
                        ->withSourcesCollection(new RedirectSourceCollection(...array_values($sources)));
                    $event->setSlugRedirectChangeItem($changeItem);
                } catch (\InvalidArgumentException | InvalidRouteArgumentsException $e) {
                    throw new UnableToLinkToPageException(
                        sprintf(
                            'The link to the page with ID "%d" and type "%d" could not be generated: %s',
                            $pageUid,
                            $pageType,
                            $e->getMessage()
                        ),
                        1675435942,
                        $e
                    );
                }
            }
        );

        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(SlugRedirectChangeItemCreatedEvent::class, 'add-custom-page-type-source');

        /** @var SlugRedirectChangeItem $changeItem */
        $changeItem = $this->get(EventDispatcherInterface::class)->dispatch(
            new SlugRedirectChangeItemCreatedEvent(
                new SlugRedirectChangeItem(
                    2,
                    2,
                    $site,
                    $siteLanguage,
                    ['slug' => '/first-page'],
                    new RedirectSourceCollection(),
                    ['slug' => '/changed'],
                )
            )
        )->getSlugRedirectChangeItem();

        self::assertSame(3, $changeItem->getSourcesCollection()->count());
        $sources = $changeItem->getSourcesCollection()->all();
        $source = $sources[0] ?? null;
        self::assertInstanceOf(PlainSlugReplacementRedirectSource::class, $source);
        self::assertSame('*', $source->getHost());
        self::assertSame('/first-page', $source->getPath());
        self::assertSame([], $source->getTargetLinkParameters());

        $source = $sources[1] ?? null;
        self::assertInstanceOf(PageTypeSource::class, $source);
        self::assertSame('*', $source->getHost());
        self::assertSame('/first-page.html', $source->getPath());
        self::assertSame([], $source->getTargetLinkParameters());
        self::assertSame(0, $source->getPageType());

        $source = $sources[2] ?? null;
        self::assertInstanceOf(PageTypeSource::class, $source);
        self::assertSame('*', $source->getHost());
        self::assertSame('/first-page-search.html', $source->getPath());
        self::assertSame(['type' => 1], $source->getTargetLinkParameters());
        self::assertSame(1, $source->getPageType());
    }

    protected function buildBaseSite(array $settings): void
    {
        $configuration = [
            'rootPageId' => 1,
            'base' => '/',
            'settings' => $settings,
        ];
        $this->buildSite($configuration);
    }

    protected function buildSite(array $configuration): void
    {
        $siteWriter = $this->get(SiteWriter::class);
        $siteWriter->write('testing', $configuration);
    }
}
