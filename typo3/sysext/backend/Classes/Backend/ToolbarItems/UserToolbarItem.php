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
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * User toolbar item and drop-down.
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class UserToolbarItem implements ToolbarItemInterface, RequestAwareToolbarItemInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly ModuleProvider $moduleProvider,
        private readonly BackendViewFactory $backendViewFactory,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Item is always enabled.
     */
    public function checkAccess(): bool
    {
        return true;
    }

    /**
     * Render username and an icon.
     */
    public function getItem(): string
    {
        $backendUser = $this->getBackendUser();
        $view = $this->backendViewFactory->create($this->request);
        $view->assignMultiple([
            'currentUser' => $backendUser->user,
            'switchUserMode' => (int)$backendUser->getOriginalUserIdWhenInSwitchUserMode(),
        ]);
        return $view->render('ToolbarItems/UserToolbarItem');
    }

    /**
     * Render drop-down content.
     */
    public function getDropDown(): string
    {
        $backendUser = $this->getBackendUser();

        $mostRecentUsers = [];
        if ($backendUser->isAdmin()
            && $backendUser->getOriginalUserIdWhenInSwitchUserMode() === null
            && isset($backendUser->uc['recentSwitchedToUsers'])
            && is_array($backendUser->uc['recentSwitchedToUsers'])
        ) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            $result = $queryBuilder
                ->select('uid', 'username', 'realName')
                ->from('be_users')
                ->where(
                    $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($backendUser->uc['recentSwitchedToUsers'], Connection::PARAM_INT_ARRAY))
                )->executeQuery();

            // Flip the array to have a "sorted" list of items
            $mostRecentUsers = array_flip($backendUser->uc['recentSwitchedToUsers']);

            while ($row = $result->fetchAssociative()) {
                $mostRecentUsers[$row['uid']] = $row;
            }

            // Remove any item that is not an array (means, the stored uid is not available anymore)
            $mostRecentUsers = array_filter($mostRecentUsers, static function ($record) {
                return is_array($record);
            });

            $availableUsers = array_keys($mostRecentUsers);
            if (!empty(array_diff($backendUser->uc['recentSwitchedToUsers'], $availableUsers))) {
                $backendUser->uc['recentSwitchedToUsers'] = $availableUsers;
                $backendUser->writeUC();
            }
        }

        $modules = null;
        if ($userModule = $this->moduleProvider->getModuleForMenu('user', $backendUser)) {
            $modules = $userModule->getSubModules();
        }
        $view = $this->backendViewFactory->create($this->request);
        $view->assignMultiple([
            'modules' => $modules,
            'switchUserMode' => $this->getBackendUser()->getOriginalUserIdWhenInSwitchUserMode() !== null,
            'recentUsers' => $mostRecentUsers,
        ]);
        return $view->render('ToolbarItems/UserToolbarItemDropDown');
    }

    /**
     * Returns an additional class if user is in "switch user" mode.
     */
    public function getAdditionalAttributes(): array
    {
        $result = [
            'class' => 'toolbar-item-user',
        ];
        if ($this->getBackendUser()->getOriginalUserIdWhenInSwitchUserMode()) {
            $result['class'] .= ' su-user';
        }
        return $result;
    }

    /**
     * This item has a drop-down.
     */
    public function hasDropDown(): bool
    {
        return true;
    }

    /**
     * Position relative to others.
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
