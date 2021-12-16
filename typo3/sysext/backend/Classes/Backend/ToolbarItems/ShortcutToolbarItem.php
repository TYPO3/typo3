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

use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * Class to render the shortcut menu toolbar.
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ShortcutToolbarItem implements ToolbarItemInterface
{
    protected ShortcutRepository $shortcutRepository;

    public function __construct(
        ShortcutRepository $shortcutRepository
    ) {
        $this->shortcutRepository = $shortcutRepository;
    }

    /**
     * Checks whether the user has access to this toolbar item.
     */
    public function checkAccess(): bool
    {
        return (bool)($this->getBackendUser()->getTSConfig()['options.']['enableBookmarks'] ?? false);
    }

    /**
     * Render shortcut icon.
     */
    public function getItem(): string
    {
        return $this->getFluidTemplateObject()->render('ToolbarItems/ShortcutToolbarItemItem');
    }

    /**
     * This item has a drop-down.
     */
    public function hasDropDown(): bool
    {
        return true;
    }

    /**
     * Render drop-down content
     */
    public function getDropDown(): string
    {
        $shortcutMenu = [];
        $groups = $this->shortcutRepository->getGroupsFromShortcuts();
        arsort($groups, SORT_NUMERIC);
        foreach ($groups as $groupId => $groupLabel) {
            $shortcutMenu[] = [
                'id' => (int)$groupId,
                'title' => $groupLabel,
                'shortcuts' => $this->shortcutRepository->getShortcutsByGroup($groupId),
            ];
        }
        $dropDownView = $this->getFluidTemplateObject();
        $dropDownView->assign('shortcutMenu', $shortcutMenu);
        return $dropDownView->render('ToolbarItems/ShortcutToolbarItemDropDown');
    }

    /**
     * This toolbar item needs no additional attributes.
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * Position relative to others.
     */
    public function getIndex(): int
    {
        return 20;
    }

    protected function getFluidTemplateObject(): BackendTemplateView
    {
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        return $view;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
