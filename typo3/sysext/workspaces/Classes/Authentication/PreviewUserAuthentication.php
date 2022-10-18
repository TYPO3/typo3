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

namespace TYPO3\CMS\Workspaces\Authentication;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A backend-user like preview user with read-only permissions for a certain workspace
 * is used for previewing a workspace in the frontend without having a full backend user
 * available.
 *
 * Has
 * - no user[uid]
 * - cookie fetched from ADMCMD_prev cookie name
 * - read-only everywhere
 * - locked to a certain workspace > 0
 * - locked to the current page ID as webmount
 *
 * This class explicitly does not derive from FrontendBackendUserAuthentication.
 * As this user is only meant for using against GET/cookie of "ADMCMD_prev" = clicked on a preview link
 * This user cannot use any admin panel / frontend editing capabilities.
 *
 * @internal
 */
class PreviewUserAuthentication extends BackendUserAuthentication
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'ADMCMD_prev';
    }

    /**
     * Checking if a workspace is allowed for backend user
     * This method is intentionally called with setTemporaryWorkspace() to check if the workspace exists.
     *
     * @param array|int $wsRec If integer, workspace record is looked up, if array it is seen as a Workspace record with at least uid, title, members and adminusers columns. Can be faked for workspaces uid 0
     * @return array|false Output will also show how access was granted. For preview users, if the record exists, it's a go.
     */
    public function checkWorkspace(int|array $wsRec): array|false
    {
        // If not array, look up workspace record:
        if (!is_array($wsRec)) {
            if ($wsRec === 0) {
                return ['uid' => 0, '_ACCESS' => 'member'];
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_workspace');
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));
            $wsRec = $queryBuilder
                ->select('*')
                ->from('sys_workspace')
                ->where($queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($wsRec, Connection::PARAM_INT)
                ))
                ->executeQuery()
                ->fetchAssociative();
        }
        // If the workspace exists in the database, the preview user is automatically a member to that workspace
        if (is_array($wsRec)) {
            return array_merge($wsRec, ['_ACCESS' => 'member']);
        }
        return false;
    }

    /**
     * A preview user has read-only permissions, always.
     *
     * @param int $perms
     * @return string
     */
    public function getPagePermsClause($perms)
    {
        if ($perms === Permission::PAGE_SHOW) {
            return '1=1';
        }
        return '0=1';
    }

    /**
     * Has read permissions on the whole workspace, but nothing else
     *
     * @param array $row
     * @return int
     */
    public function calcPerms($row)
    {
        return Permission::PAGE_SHOW;
    }
}
