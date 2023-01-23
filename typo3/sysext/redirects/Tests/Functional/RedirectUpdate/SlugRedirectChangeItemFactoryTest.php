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

namespace TYPO3\CMS\Redirects\Tests\Functional\RedirectUpdate;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItemFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SlugRedirectChangeItemFactoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    /**
     * @test
     */
    public function returnsNullIfNoSiteConfigurationCanBeFound(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');

        /** @var ?SlugRedirectChangeItem $changeItem */
        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertNull($changeItem);
    }

    /**
     * @test
     */
    public function returnsNullIfSiteConfigurationFoundButAutoCreateRedirectsAndAutoUpdateSlugsOptionsDisabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');
        $this->buildBaseSite([
            'redirects' => [
                'autoUpdateSlugs' => false,
                'autoCreateRedirects' => false,
                'redirectTTL' => 30,
                'httpStatusCode' => 307,
            ],
        ]);

        /** @var ?SlugRedirectChangeItem $changeItem */
        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertNull($changeItem);
    }

    /**
     * @test
     */
    public function returnsChangeItemIfSiteConfigurationFoundAndOnlyAutoCreateRedirectsEnabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');
        $this->buildBaseSite([
            'redirects' => [
                'autoUpdateSlugs' => false,
                'autoCreateRedirects' => true,
                'redirectTTL' => 30,
                'httpStatusCode' => 307,
            ],
        ]);

        /** @var ?SlugRedirectChangeItem $changeItem */
        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertInstanceOf(SlugRedirectChangeItem::class, $changeItem);
        self::assertSame(1, $changeItem->getPageId());
        self::assertSame(1, $changeItem->getDefaultLanguagePageId());
    }

    /**
     * @test
     */
    public function returnsChangeItemIfSiteConfigurationFoundAndOnlyAutoUpdateSlugsEnabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysFolderAsRootPage.csv');
        $this->buildBaseSite([
            'redirects' => [
                'autoUpdateSlugs' => true,
                'autoCreateRedirects' => false,
                'redirectTTL' => 30,
                'httpStatusCode' => 307,
            ],
        ]);

        /** @var ?SlugRedirectChangeItem $changeItem */
        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(1);
        self::assertInstanceOf(SlugRedirectChangeItem::class, $changeItem);
        self::assertSame(1, $changeItem->getPageId());
        self::assertSame(1, $changeItem->getDefaultLanguagePageId());
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
