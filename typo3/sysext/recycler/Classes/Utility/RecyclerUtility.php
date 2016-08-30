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
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for the 'recycler' extension.
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
     * @param string $row Record array
     * @return bool Returns TRUE is the user has access, or FALSE if not
     */
    public static function checkAccess($table, $row)
    {
        $backendUser = static::getBackendUser();

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
        if (!$backendUser->check('tables_modify', $table)) {
            $hasAccess = false;
        }
        return $hasAccess;
    }

    /**
     * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
     * Each part of the path will be limited to $titleLimit characters
     * Deleted pages are filtered out.
     *
     * @param int $uid Page uid for which to create record path
     * @param string $clause is additional where clauses, eg.
     * @param int $titleLimit Title limit
     * @param int $fullTitleLimit Title limit of Full title (typ. set to 1000 or so)
     * @return mixed Path of record (string) OR array with short/long title if $fullTitleLimit is set.
     */
    public static function getRecordPath($uid, $clause = '', $titleLimit = 1000, $fullTitleLimit = 0)
    {
        $uid = (int)$uid;
        $output = ($fullOutput = '/');
        if ($uid === 0) {
            return $output;
        }
        $databaseConnection = static::getDatabaseConnection();
        $clause = trim($clause) !== '' ? ' AND ' . $clause : '';
        $loopCheck = 100;
        while ($loopCheck > 0) {
            $loopCheck--;
            $res = $databaseConnection->exec_SELECTquery('uid,pid,title,deleted,t3ver_oid,t3ver_wsid', 'pages', 'uid=' . $uid . $clause);
            if ($res !== false) {
                $row = $databaseConnection->sql_fetch_assoc($res);
                $databaseConnection->sql_free_result($res);
                BackendUtility::workspaceOL('pages', $row);
                if (is_array($row)) {
                    BackendUtility::fixVersioningPid('pages', $row);
                    $uid = (int)$row['pid'];
                    $output = '/' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], $titleLimit)) . $output;
                    if ($row['deleted']) {
                        $output = '<span class="text-danger">' . $output . '</span>';
                    }
                    if ($fullTitleLimit) {
                        $fullOutput = '/' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], $fullTitleLimit)) . $fullOutput;
                    }
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        if ($fullTitleLimit) {
            return [$output, $fullOutput];
        } else {
            return $output;
        }
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
     * Get pid of uid
     *
     * @param int $uid
     * @param string $table
     * @return int
     */
    public static function getPidOfUid($uid, $table)
    {
        $db = static::getDatabaseConnection();
        $res = $db->exec_SELECTquery('pid', $table, 'uid=' . (int)$uid);
        if ($res !== false) {
            $record = $db->sql_fetch_assoc($res);
            return $record['pid'];
        }
        return 0;
    }

    /**
     * Gets the TCA of the table used in the current context.
     *
     * @param string $tableName Name of the table to get TCA for
     * @return array|FALSE TCA of the table used in the current context
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
     * Gets the current backend charset.
     *
     * @return string The current backend charset
     */
    public static function getCurrentCharset()
    {
        $lang = static::getLanguageService();
        return $lang->csConvObj->parse_charset($lang->charSet);
    }

    /**
     * Determines whether the current charset is not UTF-8
     *
     * @return bool Whether the current charset is not UTF-8
     */
    public static function isNotUtf8Charset()
    {
        return self::getCurrentCharset() !== 'utf-8';
    }

    /**
     * Gets an UTF-8 encoded string (only if the current charset is not UTF-8!).
     *
     * @param string $string String to be converted to UTF-8 if required
     * @return string UTF-8 encoded string
     */
    public static function getUtf8String($string)
    {
        if (self::isNotUtf8Charset()) {
            $string = static::getLanguageService()->csConvObj->utf8_encode($string, self::getCurrentCharset());
        }
        return $string;
    }

    /**
     * Returns an instance of DatabaseConnection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
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
     * @return \TYPO3\CMS\Lang\LanguageService
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
        if ((bool)$GLOBALS['BE_USER']->user['admin']) {
            $tables = array_keys($GLOBALS['TCA']);
        } else {
            $tables = explode(',', $GLOBALS['BE_USER']->groupData['tables_modify']);
        }
        return $tables;
    }
}
