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

namespace TYPO3\CMS\Recycler\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for the 'recycler' extension.
 * @internal
 */
class RecyclerUtility
{
    /************************************************************
     * USER ACCESS
     *
     *
     ************************************************************/
    /**
     * Checks the page access rights (Code for access check mostly taken from FormEngine)
     * as well as the table access rights of the user.
     *
     * @param string $table The table to check access for
     * @param array $row Record array
     * @return bool Returns TRUE is the user has access, or FALSE if not
     */
    public static function checkAccess($table, $row)
    {
        $backendUser = static::getBackendUser();

        if ($backendUser->isAdmin()) {
            return true;
        }

        if (!$backendUser->check('tables_modify', $table)) {
            return false;
        }

        // Checking if the user has permissions? (Only working as a precaution, because the final permission check is always down in TCE. But it's good to notify the user on beforehand...)
        // First, resetting flags.
        $hasAccess = false;
        $calcPRec = $row;
        BackendUtility::workspaceOL($table, $calcPRec, $backendUser->workspace);
        if (is_array($calcPRec)) {
            if ($table === 'pages') {
                $calculatedPermissions = new Permission($backendUser->calcPerms($calcPRec));
                $hasAccess = $calculatedPermissions->editPagePermissionIsGranted();
            } else {
                $calculatedPermissions = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $calcPRec['pid'])));
                // Fetching pid-record first.
                $hasAccess = $calculatedPermissions->editContentPermissionIsGranted();
            }
            // Check internals regarding access:
            if ($hasAccess) {
                $hasAccess = $backendUser->recordEditAccessInternals($table, $calcPRec);
            }
        }
        return $hasAccess;
    }

    /**
     * Gets the name of the field with the information whether a record is deleted.
     *
     * @param string $tableName Name of the table to get the deleted field for
     * @return string Name of the field with the information whether a record is deleted
     */
    public static function getDeletedField($tableName): string
    {
        $tcaForTable = self::getTableTCA((string)$tableName);
        if ($tcaForTable && isset($tcaForTable['ctrl']['delete']) && !empty($tcaForTable['ctrl']['delete'])) {
            return $tcaForTable['ctrl']['delete'];
        }
        return '';
    }

    /**
     * Get pid of uid
     *
     * @param int $uid
     * @param string $table
     * @return int
     */
    public static function getPidOfUid($uid, $table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $pid = $queryBuilder
            ->select('pid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        return (int)$pid;
    }

    /**
     * Gets the TCA of the table used in the current context.
     *
     * @param string $tableName Name of the table to get TCA for
     * @return array|false TCA of the table used in the current context
     */
    protected static function getTableTCA(string $tableName): array|false
    {
        $TCA = false;
        if (isset($GLOBALS['TCA'][$tableName])) {
            $TCA = $GLOBALS['TCA'][$tableName];
        }
        return $TCA;
    }

    protected static function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the modifiable tables of the current user
     */
    public static function getModifyableTables(): array
    {
        if (self::getBackendUser()->isAdmin()) {
            $tables = array_keys($GLOBALS['TCA']);
        } else {
            $tables = explode(',', $GLOBALS['BE_USER']->groupData['tables_modify']);
        }
        return $tables;
    }
}
