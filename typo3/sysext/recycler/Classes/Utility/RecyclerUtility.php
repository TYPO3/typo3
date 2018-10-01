<?php
namespace TYPO3\CMS\Recycler\Utility;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
        BackendUtility::fixVersioningPid($table, $calcPRec);
        if (is_array($calcPRec)) {
            if ($table === 'pages') {
                $calculatedPermissions = $backendUser->calcPerms($calcPRec);
                $hasAccess = (bool)($calculatedPermissions & Permission::PAGE_EDIT);
            } else {
                $calculatedPermissions = $backendUser->calcPerms(BackendUtility::getRecord('pages', $calcPRec['pid']));
                // Fetching pid-record first.
                $hasAccess = (bool)($calculatedPermissions & Permission::CONTENT_EDIT);
            }
            // Check internals regarding access:
            if ($hasAccess) {
                $hasAccess = $backendUser->recordEditAccessInternals($table, $calcPRec);
            }
        }
        return $hasAccess;
    }

    /**
     * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
     * Each part of the path will be limited to $titleLimit characters
     * Deleted pages are filtered out.
     *
     * @param int $uid Page uid for which to create record path
     * @return string Path of record (string) OR array with short/long title if $fullTitleLimit is set.
     */
    public static function getRecordPath($uid)
    {
        $uid = (int)$uid;
        $output = '/';
        if ($uid === 0) {
            return $output;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $loopCheck = 100;
        while ($loopCheck > 0) {
            $loopCheck--;

            $queryBuilder
                ->select('uid', 'pid', 'title', 'deleted', 't3ver_oid', 't3ver_wsid')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)));
            $row = $queryBuilder->execute()->fetch();
            if ($row !== false) {
                BackendUtility::workspaceOL('pages', $row);
                if (is_array($row)) {
                    BackendUtility::fixVersioningPid('pages', $row);
                    $uid = (int)$row['pid'];
                    $output = '/' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], 1000)) . $output;
                    if ($row['deleted']) {
                        $output = '<span class="text-danger">' . $output . '</span>';
                    }
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        return $output;
    }

    /**
     * Gets the name of the field with the information whether a record is deleted.
     *
     * @param string $tableName Name of the table to get the deleted field for
     * @return string Name of the field with the information whether a record is deleted
     */
    public static function getDeletedField($tableName)
    {
        $TCA = self::getTableTCA($tableName);
        if ($TCA && isset($TCA['ctrl']['delete']) && $TCA['ctrl']['delete']) {
            return $TCA['ctrl']['delete'];
        }
        return '';
    }

    /**
     * Check if parent record is deleted
     *
     * @param int $pid
     * @return bool
     */
    public static function isParentPageDeleted($pid)
    {
        if ((int)$pid === 0) {
            return false;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $deleted = $queryBuilder
            ->select('deleted')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)))
            ->execute()
            ->fetchColumn();

        return (bool)$deleted;
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
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->execute()
            ->fetchColumn();

        return (int)$pid;
    }

    /**
     * Gets the TCA of the table used in the current context.
     *
     * @param string $tableName Name of the table to get TCA for
     * @return array|false TCA of the table used in the current context
     */
    public static function getTableTCA($tableName)
    {
        $TCA = false;
        if (isset($GLOBALS['TCA'][$tableName])) {
            $TCA = $GLOBALS['TCA'][$tableName];
        }
        return $TCA;
    }

    /**
     * Returns the BackendUser
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected static function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the modifyable tables of the current user
     */
    public static function getModifyableTables()
    {
        if ($GLOBALS['BE_USER']->isAdmin()) {
            $tables = array_keys($GLOBALS['TCA']);
        } else {
            $tables = explode(',', $GLOBALS['BE_USER']->groupData['tables_modify']);
        }
        return $tables;
    }
}
