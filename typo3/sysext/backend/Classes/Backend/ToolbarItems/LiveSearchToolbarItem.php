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

use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * Adds backend live search to the toolbar by adding JavaScript and adding an input search field
 */
class LiveSearchToolbarItem implements ToolbarItemInterface
{
    public function __construct(protected readonly ModuleProvider $moduleProvider)
    {
    }

    /**
     * Checks whether the user has access to this toolbar item.
     * Live search depends on the list module and only available when that module is allowed.
     */
    public function checkAccess(): bool
    {
        return $this->moduleProvider->accessGranted('web_list', $this->getBackendUser());
    }

    /**
     * Render search field.
     */
    public function getItem(): string
    {
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        return $view->render('ToolbarItems/LiveSearchToolbarItem');
    }

    /**
     * This item needs additional attributes.
     */
    public function getAdditionalAttributes(): array
    {
        return ['class' => 'toolbar-item-search t3js-toolbar-item-search'];
    }

    /**
     * This item has no drop-down.
     */
    public function hasDropDown(): bool
    {
        return false;
    }

    /**
     * No drop-down here.
     */
    public function getDropDown(): string
    {
        return '';
    }

    /**
     * Position relative to others, live search should be very right.
     */
    public function getIndex(): int
    {
        return 90;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
