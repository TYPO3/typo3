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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AddPlainSlugReplacementSourceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    /**
     * @test
     */
    public function plainSlugReplacementSourceIsAdded(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SimpleSiteRoot.csv');
        $this->buildBaseSite([]);
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId(1);
        $siteLanguage = $site->getDefaultLanguage();

        /** @var SlugRedirectChangeItem $changeItem */
        $changeItem = $this->get(EventDispatcherInterface::class)->dispatch(
            new SlugRedirectChangeItemCreatedEvent(
                new SlugRedirectChangeItem(
                    defaultLanguagePageId: 1,
                    pageId: 1,
                    site: $site,
                    siteLanguage: $siteLanguage,
                    original: ['slug' => '/original'],
                    sourcesCollection: new RedirectSourceCollection(),
                    changed: ['slug' => '/changed'],
                )
            )
        )->getSlugRedirectChangeItem();

        self::assertSame(1, $changeItem->getSourcesCollection()->count());

        $source = $changeItem->getSourcesCollection()->all()[0] ?? null;
        self::assertInstanceOf(PlainSlugReplacementRedirectSource::class, $source);
        self::assertSame('*', $source->getHost());
        self::assertSame('/original', $source->getPath());
        self::assertSame([], $source->getTargetLinkParameters());
    }

    protected function buildBaseSite(array $settings): void
    {
        $configuration = [
            'rootPageId' => 1,
            'base' => '/',
            'settings' => $settings,
        ];
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $siteConfiguration->write('testing', $configuration);
    }
}
