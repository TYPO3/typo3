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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * User toolbar item
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class UserToolbarItem implements ToolbarItemInterface
{
    /**
     * Item is always enabled
     *
     * @return bool TRUE
     */
    public function checkAccess()
    {
        return true;
    }

    /**
     * Render username and an icon
     *
     * @return string HTML
     */
    public function getItem()
    {
        $backendUser = $this->getBackendUser();
        $view = $this->getFluidTemplateObject('UserToolbarItem.html');
        $view->assignMultiple([
            'currentUser' => $backendUser->user,
            'switchUserMode' => $backendUser->user['ses_backuserid'],
        ]);
        return $view->render();
    }

    /**
     * Render drop down
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $backendUser = $this->getBackendUser();

        /** @var BackendModuleRepository $backendModuleRepository */
        $backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $mostRecentUsers = [];
        if (ExtensionManagementUtility::isLoaded('beuser')
            && $backendUser->isAdmin()
            && (int)$backendUser->user['ses_backuserid'] === 0
            && isset($backendUser->uc['recentSwitchedToUsers'])
            && is_array($backendUser->uc['recentSwitchedToUsers'])
        ) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            $result = $queryBuilder
                ->select('uid', 'username', 'realName')
                ->from('be_users')
                ->where(
                    $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($backendUser->uc['recentSwitchedToUsers'], Connection::PARAM_INT_ARRAY))
                )->execute();

            // Flip the array to have a "sorted" list of items
            $mostRecentUsers = array_flip($backendUser->uc['recentSwitchedToUsers']);

            while ($row = $result->fetch()) {
                $row['switchUserLink'] = (string)$uriBuilder->buildUriFromRoute(
                    'system_BeuserTxBeuser',
                    [
                        'SwitchUser' => $row['uid']
                    ]
                );

                $mostRecentUsers[$row['uid']] = $row;
            }

            // Remove any item that is not an array (means, the stored uid is not available anymore)
            $mostRecentUsers = array_filter($mostRecentUsers, function ($record) {
                return is_array($record);
            });

            $availableUsers = array_keys($mostRecentUsers);
            if (!empty(array_diff($backendUser->uc['recentSwitchedToUsers'], $availableUsers))) {
                $backendUser->uc['recentSwitchedToUsers'] = $availableUsers;
                $backendUser->writeUC();
            }
        }

        $view = $this->getFluidTemplateObject('UserToolbarItemDropDown.html');
        $view->assignMultiple([
            'modules' => $backendModuleRepository->findByModuleName('user')->getChildren(),
            'logoutUrl' => (string)$uriBuilder->buildUriFromRoute('logout'),
            'switchUserMode' => $this->getBackendUser()->user['ses_backuserid'],
            'recentUsers' => $mostRecentUsers,
        ]);
        return $view->render();
    }

    /**
     * Returns an additional class if user is in "switch user" mode
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        $result = [
            'class' => 'toolbar-item-user'
        ];
        if ($this->getBackendUser()->user['ses_backuserid']) {
            $result['class'] .= ' su-user';
        }
        return $result;
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
        return 80;
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
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     *
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
