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

use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Adds backend live search to the toolbar by adding JavaScript and adding an input search field
 */
class LiveSearchToolbarItem implements ToolbarItemInterface
{
    /**
     * Loads the needed JavaScript file, ands includes it to the page renderer
     */
    public function __construct()
    {
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/LiveSearch');
    }

    /**
     * Checks whether the user has access to this toolbar item,
     * only allowed when the list module is available.
     * Live search is heavily dependent on the list module and only available when that module is.
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        $backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        $listModule = $backendModuleRepository->findByModuleName('web_list');
        return $listModule !== null && $listModule !== false;
    }

    /**
     * Render search field
     *
     * @return string Live search form HTML
     */
    public function getItem()
    {
        return $this->getFluidTemplateObject('LiveSearchToolbarItem.html')->render();
    }

    /**
     * This item needs to additional attributes
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return ['class' => 'toolbar-item-search t3js-toolbar-item-search'];
    }

    /**
     * This item has no drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return false;
    }

    /**
     * No drop down here
     *
     * @return string
     */
    public function getDropDown()
    {
        return '';
    }

    /**
     * Position relative to others, live search should be very right
     *
     * @return int
     */
    public function getIndex()
    {
        return 90;
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
