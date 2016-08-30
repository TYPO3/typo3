<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

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

use TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render cache clearing toolbar item
 */
class ClearCacheToolbarItem implements ToolbarItemInterface
{
    /**
     * @var array
     */
    protected $cacheActions = [];

    /**
     * @var array
     */
    protected $optionValues = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     *
     * @throws \UnexpectedValueException
     */
    public function __construct()
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar/ClearCacheMenu');

        // Clear all page-related caches
        if ($backendUser->isAdmin() || $backendUser->getTSConfigVal('options.clearCache.pages')) {
            $this->cacheActions[] = [
                'id' => 'pages',
                'title' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:flushPageCachesTitle', true),
                'description' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:flushPageCachesDescription', true),
                'href' => BackendUtility::getModuleUrl('tce_db', ['vC' => $backendUser->veriCode(), 'cacheCmd' => 'pages', 'ajaxCall' => 1]),
                'icon' => $this->iconFactory->getIcon('actions-system-cache-clear-impact-low', Icon::SIZE_SMALL)->render()
            ];
            $this->optionValues[] = 'pages';
        }

        // Clear cache for ALL tables!
        if ($backendUser->isAdmin() || $backendUser->getTSConfigVal('options.clearCache.all')) {
            $this->cacheActions[] = [
                'id' => 'all',
                'title' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:flushGeneralCachesTitle', true),
                'description' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:flushGeneralCachesDescription', true),
                'href' => BackendUtility::getModuleUrl('tce_db', ['vC' => $backendUser->veriCode(), 'cacheCmd' => 'all', 'ajaxCall' => 1]),
                'icon' => $this->iconFactory->getIcon('actions-system-cache-clear-impact-medium', Icon::SIZE_SMALL)->render()
            ];
            $this->optionValues[] = 'all';
        }

        // Clearing of system cache (core cache, class cache etc)
        // is only shown explicitly if activated for a BE-user (not activated for admins by default)
        // or if the system runs in development mode (only for admins)
        // or if $GLOBALS['TYPO3_CONF_VARS']['SYS']['clearCacheSystem'] is set (only for admins)
        if (
            $backendUser->getTSConfigVal('options.clearCache.system')
            || (GeneralUtility::getApplicationContext()->isDevelopment() && $backendUser->isAdmin())
            || ((bool)$GLOBALS['TYPO3_CONF_VARS']['SYS']['clearCacheSystem'] === true && $backendUser->isAdmin())
        ) {
            $this->cacheActions[] = [
                'id' => 'system',
                'title' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:flushSystemCachesTitle', true),
                'description' => $languageService->sL('LLL:EXT:lang/locallang_core.xlf:flushSystemCachesDescription', true),
                'href' => BackendUtility::getModuleUrl('tce_db', ['vC' => $backendUser->veriCode(), 'cacheCmd' => 'system', 'ajaxCall' => 1]),
                'icon' => $this->iconFactory->getIcon('actions-system-cache-clear-impact-high', Icon::SIZE_SMALL)->render()
            ];
            $this->optionValues[] = 'system';
        }

        // Hook for manipulating cacheActions
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'] as $cacheAction) {
                $hookObject = GeneralUtility::getUserObj($cacheAction);
                if (!$hookObject instanceof ClearCacheActionsHookInterface) {
                    throw new \UnexpectedValueException($cacheAction . ' must implement interface ' . ClearCacheActionsHookInterface::class, 1228262000);
                }
                $hookObject->manipulateCacheActions($this->cacheActions, $this->optionValues);
            }
        }
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }
        if (is_array($this->optionValues)) {
            foreach ($this->optionValues as $value) {
                if ($backendUser->getTSConfigVal('options.clearCache.' . $value)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Render clear cache icon
     *
     * @return string Icon HTML
     */
    public function getItem()
    {
        $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.clearCache_clearCache', true);
        return '<span title="' . $title . '">'
            . $this->iconFactory->getIcon('apps-toolbar-menu-cache', Icon::SIZE_SMALL)->render('inline')
            . '</span>';
    }

    /**
     * Render drop down
     *
     * @return string Drop down HTML
     */
    public function getDropDown()
    {
        $result = [];
        $result[] = '<ul class="dropdown-list">';
        foreach ($this->cacheActions as $cacheAction) {
            $title = $cacheAction['description'] ?: $cacheAction['title'];
            $result[] = '<li>';
            $result[] = '<a class="dropdown-list-link" href="' . htmlspecialchars($cacheAction['href']) . '" title="' . htmlspecialchars($title) . '">';
            $result[] = $cacheAction['icon'] . ' ' . htmlspecialchars($cacheAction['title']);
            $result[] = '</a>';
            $result[] = '</li>';
        }
        $result[] = '</ul>';
        return implode(LF, $result);
    }

    /**
     * No additional attributes needed.
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 25;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns current PageRenderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
