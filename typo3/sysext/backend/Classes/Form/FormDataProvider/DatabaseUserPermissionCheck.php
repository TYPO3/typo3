<?php

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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedContentEditException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedEditInternalsException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedListenerException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedPageEditException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedPageNewException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedRootNodeException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedTableModifyException;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Determine user permission for action and check them
 */
class DatabaseUserPermissionCheck implements FormDataProviderInterface
{
    /**
     * Set userPermissionOnPage to result array and check access rights.
     *
     * A couple of different exceptions are thrown here:
     * * If something weird happens a top level SPL exception is thrown.
     *   This indicates a non recoverable error.
     * * If user has no access to whatever should be done, an exception that
     *   extends from Form\Exception\AccessDeniedException is thrown. This
     *   can be caught by upper level controller code and can be translated
     *   to a specific error message that is shown to the user depending on
     *   specific exception that is thrown.
     *
     * @param array $result
     * @return array
     * @throws AccessDeniedException
     */
    public function addData(array $result)
    {
        $backendUser = $this->getBackendUser();

        // Early return for admins
        if ($backendUser->isAdmin()) {
            $result['userPermissionOnPage'] = Permission::ALL;
            return $result;
        }

        if (!$backendUser->check('tables_modify', $result['tableName'])) {
            // If user has no modify rights on table, processing is stopped by throwing an
            // exception immediately. This case can not be circumvented by hooks.
            throw new AccessDeniedTableModifyException(
                'No table modify permission for user ' . $backendUser->user['uid'] . ' on table ' . $result['tableName'],
                1437683248
            );
        }

        $exception = null;
        $userPermissionOnPage = new Permission(Permission::NOTHING);
        if ($result['command'] === 'new') {
            // A new record is created. Access rights of parent record are important here
            // @todo: In case of new inline child, parentPageRow should probably be the
            // @todo: "inlineFirstPid" page - Maybe effectivePid and parentPageRow should be calculated differently then?
            if (is_array($result['parentPageRow'])) {
                // Record is added below an existing page
                $userPermissionOnPage = new Permission($backendUser->calcPerms($result['parentPageRow']));
                if ($result['tableName'] === 'pages') {
                    // New page is created, user needs PAGE_NEW for this
                    if (!$userPermissionOnPage->createPagePermissionIsGranted()) {
                        $exception = new AccessDeniedPageNewException(
                            'No page new permission for user ' . $backendUser->user['uid'] . ' on page ' . $result['databaseRow']['uid'],
                            1437745640
                        );
                    }
                } elseif (!$userPermissionOnPage->editContentPermissionIsGranted()) {
                    // A regular record is added, not a page. User needs CONTENT_EDIT permission
                    $exception = new AccessDeniedContentEditException(
                        'No content new permission for user ' . $backendUser->user['uid'] . ' on page ' . $result['parentPageRow']['uid'],
                        1437745759
                    );
                }
            } elseif (BackendUtility::isRootLevelRestrictionIgnored($result['tableName'])) {
                // Non admin is creating a record on root node for a table that is actively allowed
                $userPermissionOnPage->set(Permission::ALL);
            } else {
                // Non admin has no create permission on root node records
                $exception = new AccessDeniedRootNodeException(
                    'No record creation permission for user ' . $backendUser->user['uid'] . ' on page root node',
                    1437745221
                );
            }
        } else {
            // A page or a record on a page is edited
            if ($result['tableName'] === 'pages') {
                // A page record is edited, check edit rights of this record directly
                $userPermissionOnPage = new Permission($backendUser->calcPerms($result['defaultLanguagePageRow'] ?? $result['databaseRow']));
                if (!$userPermissionOnPage->editPagePermissionIsGranted()
                    || !$backendUser->check('pagetypes_select', $result['databaseRow'][$result['processedTca']['ctrl']['type']])
                ) {
                    $exception = new AccessDeniedPageEditException(
                        'No page edit permission for user ' . $backendUser->user['uid'] . ' on page ' . $result['databaseRow']['uid'],
                        1437679336
                    );
                }
            } elseif (isset($result['parentPageRow']) && is_array($result['parentPageRow'])) {
                // A non page record is edited.
                // If there is a parent page row, check content edit right of user
                $userPermissionOnPage = new Permission($backendUser->calcPerms($result['parentPageRow']));
                if (!$userPermissionOnPage->editContentPermissionIsGranted()) {
                    $exception = new AccessDeniedContentEditException(
                        'No content edit permission for user ' . $backendUser->user['uid'] . ' on page ' . $result['parentPageRow']['uid'],
                        1437679657
                    );
                }
            } elseif (BackendUtility::isRootLevelRestrictionIgnored($result['tableName'])) {
                // Non admin is editing a record on root node for a table that is actively allowed
                $userPermissionOnPage->set(Permission::ALL);
            } else {
                // Non admin has no edit permission on root node records
                // @todo: This probably needs further handling, see http://review.typo3.org/40835
                $exception = new AccessDeniedRootNodeException(
                    'No content edit permission for user ' . $backendUser->user['uid'] . ' on page root node',
                    1437679856
                );
            }
            // If general access is allowed, check "recordEditAccessInternals"
            if ($exception === null
                && !$backendUser->recordEditAccessInternals($result['tableName'], $result['databaseRow'])
            ) {
                $exception = new AccessDeniedEditInternalsException($backendUser->errorMsg, 1437687404);
            }
        }

        $userHasAccess = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new ModifyEditFormUserAccessEvent(
                $exception,
                $result['tableName'],
                $result['command'],
                $result['databaseRow'],
            )
        )->doesUserHaveAccess();

        // Throw specific exception because a listener to the Event denied the previous positive user access decision
        if ($exception === null && !$userHasAccess) {
            $exception = new AccessDeniedListenerException(
                'Access to table ' . $result['tableName'] . ' for user ' . $backendUser->user['uid'] . ' was denied by a ModifyRecordEditUserAccessEvent listener',
                1662727149
            );
        }

        // Unset a previous exception because a listener to the Event allowed the previous negative user access decision
        if ($exception !== null && $userHasAccess) {
            $exception = null;
        }

        if ($exception) {
            throw $exception;
        }

        $result['userPermissionOnPage'] = $userPermissionOnPage->__toInt();

        return $result;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
