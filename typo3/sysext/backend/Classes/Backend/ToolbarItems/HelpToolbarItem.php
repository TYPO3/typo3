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

use TYPO3\CMS\Backend\Domain\Model\Module\BackendModule;
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Help toolbar item
 */
class HelpToolbarItem implements ToolbarItemInterface
{
    /**
     * @var BackendModule
     */
    protected $helpModuleMenu;

    /**
     * Constructor
     */
    public function __construct()
    {
        $backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        $helpModuleMenu = $backendModuleRepository->findByModuleName('help');
        if ($helpModuleMenu && $helpModuleMenu->getChildren()->count() > 0) {
            $this->helpModuleMenu = $helpModuleMenu;
        }
    }

    /**
     * Users see this if a module is available
     *
     * @return bool TRUE
     */
    public function checkAccess()
    {
        return (bool)$this->helpModuleMenu;
    }

    /**
     * Render help icon
     *
     * @return string toolbar item for the help icon
     */
    public function getItem()
    {
        return $this->getFluidTemplateObject('HelpToolbarItem.html')->render();
    }

    /**
     * Render drop down
     *
     * @return string
     */
    public function getDropDown()
    {
        $view = $this->getFluidTemplateObject('HelpToolbarItemDropDown.html');
        $view->assign('modules', $this->helpModuleMenu->getChildren());
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
        return true;
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 70;
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
