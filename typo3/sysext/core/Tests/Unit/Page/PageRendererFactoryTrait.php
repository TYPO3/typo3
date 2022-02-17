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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\AssetRenderer;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\Resource\ResourceCompressor;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait PageRendererFactoryTrait
{
    use ProphecyTrait;

    protected function getPageRendererConstructorArgs(
        PackageManager $packageManager = null,
        CacheManager $cacheManager = null,
    ): array {
        $packageManager ??= new PackageManager(new DependencyOrderingService());
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManager ??= $cacheManagerProphecy->reveal();

        /**
         * prepare an EventDispatcher for ::makeInstance(AssetRenderer)
         * @see \TYPO3\CMS\Core\Page\PageRenderer::renderJavaScriptAndCss
         */
        GeneralUtility::setSingletonInstance(
            EventDispatcherInterface::class,
            new EventDispatcher(
                new ListenerProvider($this->createMock(ContainerInterface::class))
            )
        );

        $assetRenderer = new AssetRenderer();

        return [
            new NullFrontend('assets'),
            new Locales(),
            new MarkerBasedTemplateService(
                new NullFrontend('hash'),
                new NullFrontend('runtime'),
            ),
            new MetaTagManagerRegistry(),
            $packageManager,
            $assetRenderer,
            new ResourceCompressor(),
            new RelativeCssPathFixer(),
            new LocalizationFactory(new LanguageStore($packageManager), $cacheManager),
        ];
    }
}
