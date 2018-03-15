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

use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class CacheModule extends AbstractModule
{
    /**
     * Creates the content for the "cache" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     */
    public function getContent(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = $this->extResources . '/Templates/Modules/Cache.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths([$this->extResources . '/Partials']);

        $view->assignMultiple([
            'isEnabled' => $this->getBackendUser()->uc['TSFE_adminConfig']['display_cache'],
            'noCache' => $this->getBackendUser()->uc['TSFE_adminConfig']['cache_noCache'],
            'cacheLevels' => $this->getBackendUser()->uc['TSFE_adminConfig']['cache_clearCacheLevels'],
            'currentId' => $this->getTypoScriptFrontendController()->id
        ]);

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
    public function initializeModule(): void
    {
        if ($this->getConfigurationOption('noCache')) {
            $this->getTypoScriptFrontendController()->set_no_cache('Admin Panel: No Caching', true);
        }
    }

    /**
     * Clear cache on saving if requested
     *
     * @param array $input
     */
    public function onSubmit(array $input): void
    {
        if (($input['action']['clearCache'] ?? false) || isset($input['preview_showFluidDebug'])) {
            $theStartId = (int)$input['cache_clearCacheId'];
            $this->getTypoScriptFrontendController()
                ->clearPageCacheContent_pidList(
                    $this->getBackendUser()->extGetTreeList(
                        $theStartId,
                        (int)$this->getConfigurationOption('clearCacheLevels'),
                        0,
                        $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                    ) . $theStartId
                );
        }
    }

    /**
     * @inheritdoc
     */
    public function showFormSubmitButton(): bool
    {
        return true;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
