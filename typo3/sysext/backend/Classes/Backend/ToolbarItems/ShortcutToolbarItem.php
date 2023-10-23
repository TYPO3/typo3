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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Class to render the shortcut menu toolbar.
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ShortcutToolbarItem implements ToolbarItemInterface, RequestAwareToolbarItemInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly ShortcutRepository $shortcutRepository,
        private readonly BackendViewFactory $backendViewFactory,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
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
        $view = $this->backendViewFactory->create($this->request);
        return $view->render('ToolbarItems/ShortcutToolbarItemItem');
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
        $view = $this->backendViewFactory->create($this->request);
        $view->assign('shortcutMenu', $shortcutMenu);
        return $view->render('ToolbarItems/ShortcutToolbarItemDropDown');
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

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
