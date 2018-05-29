<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class CacheModule extends AbstractModule
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
    public function getSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = $this->extResources . '/Templates/Modules/Settings/Cache.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths([$this->extResources . '/Partials']);

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $feCacheClear = $this->getBackendUser()->isAdmin() || $this->getBackendUser()->getTSConfig()['options.']['clearCache.']['pages'];

        $view->assignMultiple(
            [
                'isEnabled' => $this->getBackendUser()->uc['TSFE_adminConfig']['display_cache'],
                'noCache' => $this->getBackendUser()->uc['TSFE_adminConfig']['cache_noCache'],
                'currentId' => $this->getTypoScriptFrontendController()->id,
                'clearPageCacheUrl' => $feCacheClear ? (string)$uriBuilder->buildUriFromRoute('tce_db', ['cacheCmd' => 'pages']) : '',
                'clearCurrentPageCacheUrl' => (string)$uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cacheCmd' => $this->getTypoScriptFrontendController()->id,
                    ]
                ),
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
        $locallangFileAndPath = 'LLL:' . $this->extResources . '/Language/locallang_cache.xlf:module.label';
        return $this->getLanguageService()->sL($locallangFileAndPath);
    }

    /**
     * @inheritdoc
     */
    public function initializeModule(ServerRequestInterface $request): void
    {
        if ($this->configurationService->getConfigurationOption('cache', 'noCache')) {
            $this->getTypoScriptFrontendController()->set_no_cache('Admin Panel: No Caching', true);
        }
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Cache.js'];
    }
}
