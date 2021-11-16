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
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class CacheModule extends AbstractModule implements PageSettingsProviderInterface, RequestEnricherInterface, ResourceProviderInterface
{
    /**
     * @return string
     */
    public function getIconIdentifier(): string
    {
        return 'apps-toolbar-menu-cache';
    }

    /**
     * @return string
     */
    public function getPageSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Settings/Cache.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $feCacheClear = $this->getBackendUser()->isAdmin() || $this->getBackendUser()->getTSConfig()['options.']['clearCache.']['pages'];

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
                'languageKey' => $this->getBackendUser()->user['lang'],
            ]
        );

        return $view->render();
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'cache';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        $locallangFileAndPath = 'LLL:EXT:adminpanel/Resources/Private/Language/locallang_cache.xlf:module.label';
        return $this->getLanguageService()->sL($locallangFileAndPath);
    }

    /**
     * @inheritdoc
     */
    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($this->configurationService->getConfigurationOption('cache', 'noCache')) {
            $request = $request->withAttribute('noCache', true);
        }
        return $request;
    }

    /**
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Cache.js'];
    }

    /**
     * Returns a string array with css files that will be rendered after the module
     *
     * Example: return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Edit.css'];
     *
     * @return array
     */
    public function getCssFiles(): array
    {
        return [];
    }
}
