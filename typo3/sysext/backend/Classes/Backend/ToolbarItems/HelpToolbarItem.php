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

namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

use TYPO3\CMS\Backend\Module\MenuModule;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * Help toolbar item - The question mark icon in toolbar
 */
class HelpToolbarItem implements ToolbarItemInterface
{
    protected ?MenuModule $helpModule = null;

    public function __construct(ModuleProvider $moduleProvider)
    {
        $helpModule = $moduleProvider->getModuleForMenu('help', $this->getBackendUser());
        if ($helpModule && $helpModule->hasSubModules()) {
            $this->helpModule = $helpModule;
        }
    }

    /**
     * Users see this if a module is available
     */
    public function checkAccess(): bool
    {
        return $this->helpModule !== null;
    }

    /**
     * Render help icon
     */
    public function getItem(): string
    {
        return $this->getFluidTemplateObject()->render('ToolbarItems/HelpToolbarItem');
    }

    /**
     * Render drop down
     */
    public function getDropDown(): string
    {
        if ($this->helpModule === null) {
            // checkAccess() is called before and prevents call to getDropDown() if there is no help.
            throw new \RuntimeException('No HelpModuleMenu found.', 1641993564);
        }
        $view = $this->getFluidTemplateObject();
        $view->assign('modules', $this->helpModule->getSubModules());
        return $view->render('ToolbarItems/HelpToolbarItemDropDown');
    }

    /**
     * No additional attributes needed.
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * This item has a drop-down
     */
    public function hasDropDown(): bool
    {
        return true;
    }

    /**
     * Position relative to others
     */
    public function getIndex(): int
    {
        return 70;
    }

    protected function getFluidTemplateObject(): BackendTemplateView
    {
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        return $view;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
