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

namespace TYPO3\CMS\Adminpanel\Modules;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;

#[Autoconfigure(public: true)]
class CacheModule extends AbstractModule implements PageSettingsProviderInterface, RequestEnricherInterface, ResourceProviderInterface
{
    public function __construct(
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function getIconIdentifier(): string
    {
        return 'apps-toolbar-menu-cache';
    }

    public function getPageSettings(): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $feCacheClear = $this->getBackendUser()->isAdmin()
            || !empty($this->getBackendUser()->getTSConfig()['options.']['clearCache.']['pages']);

        $pageId = 0;
        $pageArguments = $GLOBALS['TYPO3_REQUEST']->getAttribute('routing');
        if ($pageArguments instanceof PageArguments) {
            $pageId = $pageArguments->getPageId();
        }

        $view->assignMultiple(
            [
                'isEnabled' => $this->getBackendUser()->uc['AdminPanel']['display_cache'] ?? false,
                'noCache' => $this->getBackendUser()->uc['AdminPanel']['cache_noCache'] ?? false,
                'currentId' => $pageId,
                'clearPageCacheUrl' => $feCacheClear ? (string)$uriBuilder->buildUriFromRoute('tce_db', ['cacheCmd' => 'pages']) : '',
                'clearCurrentPageCacheUrl' => (string)$uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cacheCmd' => $pageId,
                    ]
                ),
                'languageKey' => $this->getBackendUser()->user['lang'] ?? null,
            ]
        );

        return $view->render('Modules/Settings/Cache');
    }

    public function getIdentifier(): string
    {
        return 'cache';
    }

    public function getLabel(): string
    {
        $locallangFileAndPath = 'LLL:EXT:adminpanel/Resources/Private/Language/locallang_cache.xlf:module.label';
        return $this->getLanguageService()->sL($locallangFileAndPath);
    }

    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($this->configurationService->getConfigurationOption('cache', 'noCache')) {
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
            $cacheInstruction->disableCache('EXT:adminpanel: "No caching" disables cache.');
            $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
        }
        return $request;
    }

    public function getJavaScriptFiles(): array
    {
        return ['EXT:adminpanel/Resources/Public/JavaScript/modules/cache.js'];
    }

    /**
     * Returns a string array with css files that will be rendered after the module
     *
     * Example: return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Edit.css'];
     */
    public function getCssFiles(): array
    {
        return [];
    }
}
