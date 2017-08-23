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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Render cache clearing toolbar item
 * Adds a dropdown if there are more than one item to clear (usually for admins to render the flush all caches)
 *
 * The dropdown items can be extended via a hook named "cacheActions".
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
     * @throws \UnexpectedValueException
     */
    public function __construct()
    {
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar/ClearCacheMenu');
        $backendUser = $this->getBackendUser();

        // Clear all page-related caches
        if ($backendUser->isAdmin() || $backendUser->getTSConfigVal('options.clearCache.pages')) {
            $this->cacheActions[] = [
                'id' => 'pages',
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:flushPageCachesTitle',
                'description' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:flushPageCachesDescription',
                'href' => BackendUtility::getModuleUrl('tce_db', ['cacheCmd' => 'pages']),
                'iconIdentifier' => 'actions-system-cache-clear-impact-low'
            ];
            $this->optionValues[] = 'pages';
        }

        // Clearing of all caches is only shown if explicitly enabled via TSConfig
        // or if BE-User is admin and the TSconfig explicitly disables the possibility for admins.
        // This is useful for big production systems where admins accidentally could slow down the system.
        if ($backendUser->getTSConfigVal('options.clearCache.all') || ($backendUser->isAdmin() && $backendUser->getTSConfigVal('options.clearCache.all') !== '0')) {
            $this->cacheActions[] = [
                'id' => 'all',
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:flushAllCachesTitle2',
                'description' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:flushAllCachesDescription2',
                'href' => BackendUtility::getModuleUrl('tce_db', ['cacheCmd' => 'all']),
                'iconIdentifier' => 'actions-system-cache-clear-impact-high'
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
        foreach ($this->optionValues as $value) {
            if ($backendUser->getTSConfigVal('options.clearCache.' . $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render clear cache icon, based on the option if there is more than one icon or just one.
     *
     * @return string Icon HTML
     */
    public function getItem()
    {
        if ($this->hasDropDown()) {
            return $this->getFluidTemplateObject('ClearCacheToolbarItem.html')->render();
        }
        $view = $this->getFluidTemplateObject('ClearCacheToolbarItemSingle.html');
        $cacheAction = end($this->cacheActions);
        $view->assignMultiple([
                'link'  => $cacheAction['href'],
                'title' => $cacheAction['title'],
                'iconIdentifier'  => $cacheAction['iconIdentifier'],
            ]);
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
        $view->assign('cacheActions', $this->cacheActions);
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
     * This item has a drop down if there is more than one cache action available for the current Backend user.
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials/ToolbarItems']);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/ToolbarItems']);

        $view->setTemplate($filename);

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }
}
