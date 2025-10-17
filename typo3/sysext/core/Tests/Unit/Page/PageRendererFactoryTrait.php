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

use Symfony\Component\Translation\Translator;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LabelFileResolver;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\AssetRenderer;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\Resource\ResourceCompressor;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;

/**
 * @internal Only for core internal testing.
 */
trait PageRendererFactoryTrait
{
    protected function getPageRendererConstructorArgs(
        ?PackageManager $packageManager = null,
        ?CacheManager $cacheManager = null,
    ): array {
        $packageManager ??= new PackageManager(new DependencyOrderingService());
        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheManagerMock->method('getCache')->with('l10n')->willReturn(new NullFrontend('l10n'));
        $cacheManager ??= $cacheManagerMock;

        $labelMapperMock = $this->createMock(TranslationDomainMapper::class);
        $labelMapperMock->method('mapDomainToFileName')->willReturnArgument(0);

        return [
            new NullFrontend('assets'),
            new MarkerBasedTemplateService(
                new NullFrontend('hash'),
                new NullFrontend('runtime'),
            ),
            new MetaTagManagerRegistry(),
            new AssetRenderer(new AssetCollector(), new NoopEventDispatcher()),
            new AssetCollector(),
            new ResourceCompressor(),
            new RelativeCssPathFixer(),
            new LanguageServiceFactory(
                new Locales(),
                new LocalizationFactory(new Translator('en'), $cacheManager->getCache('l10n'), new NullFrontend('runtime'), $labelMapperMock, new LabelFileResolver($packageManager)),
                new NullFrontend('null')
            ),
            new ResponseFactory(),
            new StreamFactory(),
            new IconRegistry(
                new NullFrontend('assets'),
                'foobar',
            ),
        ];
    }
}
