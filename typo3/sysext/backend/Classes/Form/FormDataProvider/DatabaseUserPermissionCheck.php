<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\Exception\AccessDeniedContentEditException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedEditInternalsException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedHookException;
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
     * @throws AccessDeniedException|\LogicException|\RuntimeException
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
        $userHasAccess = false;
        $userPermissionOnPage = Permission::NOTHING;
        if ($result['command'] === 'new') {
            // A new record is created. Access rights of parent record are important here
            // @todo: In case of new inline child, parentPageRow should probably be the
            // @todo: "inlineFirstPid" page - Maybe effectivePid and parentPageRow should be calculated differently then?
            if (is_array($result['parentPageRow'])) {
                // Record is added below an existing page
                $userPermissionOnPage = $backendUser->calcPerms($result['parentPageRow']);
                if ($result['tableName'] === 'pages') {
                    // New page is created, user needs PAGE_NEW for this
                    if ((bool)($userPermissionOnPage & Permission::PAGE_NEW)) {
                        $userHasAccess = true;
                    } else {
                        $exception = new AccessDeniedPageNewException(
                            'No page new permission for user ' . $backendUser->user['uid'] . ' on page ' . $result['databaseRow']['uid'],
                            1437745640
                        );
                    }
                } else {
                    // A regular record is added, not a page. User needs CONTENT_EDIT permission
                    if ((bool)($userPermissionOnPage & Permission::CONTENT_EDIT)) {
                        $userHasAccess = true;
                    } else {
                        $exception = new AccessDeniedContentEditException(
                            'No content new permission for user ' . $backendUser->user['uid'] . ' on page ' . $result['parentPageRow']['uid'],
                            1437745759
                        );
                    }
                }
            } elseif (BackendUtility::isRootLevelRestrictionIgnored($result['tableName'])) {
                // Non admin is creating a record on root node for a table that is actively allowed
                $userHasAccess = true;
                $userPermissionOnPage = Permission::ALL;
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
                $userPermissionOnPage = $backendUser->calcPerms($result['databaseRow']);
                if ((bool)($userPermissionOnPage & Permission::PAGE_EDIT) && $backendUser->check('pagetypes_select', $result['databaseRow']['doktype'])) {
                    $userHasAccess = true;
                } else {
                    $exception = new AccessDeniedPageEditException(
                        'No page edit permission for user ' . $backendUser->user['uid'] . ' on page ' . $result['databaseRow']['uid'],
                        1437679336
                    );
                }
            } else {
                // A non page record is edited.
                if (is_array($result['parentPageRow'])) {
                    // If there is a parent page row, check content edit right of user
                    $userPermissionOnPage = $backendUser->calcPerms($result['parentPageRow']);
                    if ((bool)($userPermissionOnPage & Permission::CONTENT_EDIT)) {
                        $userHasAccess = true;
                    } else {
                        $exception = new AccessDeniedContentEditException(
                            'No content edit permission for user ' . $backendUser->user['uid'] . ' on page ' . $result['parentPageRow']['uid'],
                            1437679657
                        );
                    }
                } elseif (BackendUtility::isRootLevelRestrictionIgnored($result['tableName'])) {
                    // Non admin is editing a record on root node for a table that is actively allowed
                    $userHasAccess = true;
                    $userPermissionOnPage = Permission::ALL;
                } else {
                    // Non admin has no edit permission on root node records
                    // @todo: This probably needs further handling, see http://review.typo3.org/40835
                    $exception = new AccessDeniedRootNodeException(
                        'No content edit permission for user ' . $backendUser->user['uid'] . ' on page root node',
                        1437679856
                    );
                }
            }
            if ($userHasAccess) {
                // If general access is allowed, check "recordEditAccessInternals"
                $userHasAccess = $backendUser->recordEditAccessInternals($result['tableName'], $result['databaseRow']);
                if (!$userHasAccess) {
                    $exception = new AccessDeniedEditInternalsException(
                        $backendUser->errorMsg,
                        1437687404
                    );
                }
            }
        }

        if ($userHasAccess && $exception) {
            // Having user access TRUE here and an exception defined must not happen,
            // indicates an internal error and throws a logic exception
            throw new \LogicException(
                'Access was TRUE but an exception was raised as well for table ' . $result['tableName'] . ' and user ' . $backendUser->user['uid'],
                1437688402
            );
        }

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck'])
        ) {
            // A hook may modify the $userHasAccess decision. Previous state is saved to see if a hook changed
            // a previous decision from TRUE to FALSE to throw a specific exception in this case
            $userHasAccessBeforeHook = $userHasAccess;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck'] as $methodReference) {
                $parameters = [
                    'table' => $result['tableName'],
                    'uid' => $result['databaseRow']['uid'],
                    'cmd' => $result['command'],
                    'hasAccess' => $userHasAccess,
                ];
                $userHasAccess = (bool)GeneralUtility::callUserFunction($methodReference, $parameters, $this);
            }
            if ($userHasAccessBeforeHook && !$userHasAccess) {
                $exception = new AccessDeniedHookException(
                    'Access to table ' . $result['tableName'] . ' for user ' . $backendUser->user['uid'] . ' was denied by a makeEditForm_accessCheck hook',
                    1437689705
                );
            }
            if (!$userHasAccessBeforeHook && $userHasAccess) {
                // Unset a previous exception if hook allowed access where previous checks didn't
                $exception = null;
            }
        }

        if (!$userHasAccess && !$exception) {
            // User has no access, but no according exception was defined. This is an
            // internal error and throws a logic exception.
            throw new \LogicException(
                'Access to table ' . $result['tableName'] . ' denied, but no reason given',
                1437690507
            );
        }

        if ($exception) {
            throw $exception;
        }

        $result['userPermissionOnPage'] = $userPermissionOnPage;

        return $result;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
