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
use TYPO3\CMS\Fluid\View\StandaloneView;

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
                'title' => htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:flushPageCachesTitle')),
                'description' => htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:flushPageCachesDescription')),
                'href' => BackendUtility::getModuleUrl('tce_db', ['vC' => $backendUser->veriCode(), 'cacheCmd' => 'pages']),
                'icon' => $this->iconFactory->getIcon('actions-system-cache-clear-impact-low', Icon::SIZE_SMALL)->render()
            ];
            $this->optionValues[] = 'pages';
        }

        // Clearing of all caches is only shown if explicitly enabled via TSConfig
        // or if BE-User is admin and the TSconfig explicitly disables the possibility for admins.
        // This is useful for big production systems where admins accidentally could slow down the system.
        if ($backendUser->getTSConfigVal('options.clearCache.all') || ($backendUser->isAdmin() && $backendUser->getTSConfigVal('options.clearCache.all') !== '0')) {
            $this->cacheActions[] = [
                'id' => 'all',
                'title' => htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:flushAllCachesTitle2')),
                'description' => htmlspecialchars($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:flushAllCachesDescription2')),
                'href' => BackendUtility::getModuleUrl('tce_db', ['vC' => $backendUser->veriCode(), 'cacheCmd' => 'all']),
                'icon' => $this->iconFactory->getIcon('actions-system-cache-clear-impact-high', Icon::SIZE_SMALL)->render()
            ];
            $this->optionValues[] = 'all';
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
        if ($this->hasDropDown()) {
            $templateReference = 'ClearCacheToolbarItem.html';
            $icon = $this->iconFactory->getIcon('apps-toolbar-menu-cache', Icon::SIZE_SMALL)->render('inline');
            $variables = [
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.clearCache_clearCache',
                'icon' => $icon
            ];
        } else {
            $templateReference = 'ClearCacheToolbarItemSingle.html';
            $cacheAction = end($this->cacheActions);
            $variables['link'] = $cacheAction['href'];
            $variables['title'] = $cacheAction['title'];
            $variables['icon'] = $cacheAction['icon'];
        }
        $view = $this->getFluidTemplateObject($templateReference);
        $view->assignMultiple($variables);

        return $view->render();
    }

    /**
     * Render drop down
     *
     * @return string Drop down HTML
     */
    public function getDropDown()
    {
        $view = $this->getFluidTemplateObject('ClearCacheToolbarItemDropDown.html');
        $view->assignMultiple([
                'title' =>  'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.clearCache_clearCache',
                'cacheActions' => $this->cacheActions,
            ]
        );

        return $view->render();
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
        return count($this->cacheActions) > 1;
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

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename):StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials/ToolbarItems')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/ToolbarItems')]);

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/ToolbarItems/' . $filename));

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }
}
