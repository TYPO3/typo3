<?php
namespace TYPO3\CMS\Backend\Domain\Repository\Localization;

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

/**
 * Repository for record localizations
 */
class LocalizationRepository
{
    /**
     * Fetch the language from which the records of a colPos in a certain language were initially localized
     *
     * @param int $pageId
     * @param int $colPos
     * @param int $localizedLanguage
     * @return array|false|null
     */
    public function fetchOriginLanguage($pageId, $colPos, $localizedLanguage)
    {
        $record = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'tt_content_orig.sys_language_uid',
            'tt_content,tt_content AS tt_content_orig,sys_language',
            'tt_content.colPos = ' . (int)$colPos
            . ' AND tt_content.pid = ' . (int)$pageId
            . ' AND tt_content.sys_language_uid = ' . (int)$localizedLanguage
            . ' AND tt_content.t3_origuid = tt_content_orig.uid'
            . ' AND tt_content_orig.sys_language_uid=sys_language.uid'
            . $this->getExcludeQueryPart()
            . $this->getAllowedLanguagesForBackendUser(),
            'tt_content_orig.sys_language_uid'
        );

        return $record;
    }

    /**
     * @param int $pageId
     * @param int $colPos
     * @param int $languageId
     * @return int
     */
    public function getLocalizedRecordCount($pageId, $colPos, $languageId)
    {
        $rows = (int)$this->getDatabaseConnection()->exec_SELECTcountRows(
            'uid',
            'tt_content',
            'tt_content.sys_language_uid=' . (int)$languageId
            . ' AND tt_content.colPos = ' . (int)$colPos
            . ' AND tt_content.pid=' . (int)$pageId
            . ' AND tt_content.t3_origuid <> 0'
            . $this->getExcludeQueryPart()
        );

        return $rows;
    }

    /**
     * Fetch all available languages
     *
     * @param int $pageId
     * @param int $colPos
     * @param int $languageId
     * @return array
     */
    public function fetchAvailableLanguages($pageId, $colPos, $languageId)
    {
        $result = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'sys_language.uid',
            'tt_content,sys_language',
            'tt_content.sys_language_uid=sys_language.uid'
            . ' AND tt_content.colPos = ' . (int)$colPos
            . ' AND tt_content.pid = ' . (int)$pageId
            . ' AND sys_language.uid <> ' . (int)$languageId
            . $this->getExcludeQueryPart()
            . $this->getAllowedLanguagesForBackendUser(),
            'sys_language.uid',
            'sys_language.title'
        );

        return $result;
    }

    /**
     * Builds an additional where clause to exclude deleted records and setting the versioning placeholders
     *
     * @return string
     */
    public function getExcludeQueryPart()
    {
        return BackendUtility::deleteClause('tt_content')
            . BackendUtility::versioningPlaceholderClause('tt_content');
    }

    /**
     * Builds an additional where clause to exclude hidden languages and limit a backend user to its allowed languages,
     * if the user is not an admin.
     *
     * @return string
     */
    public function getAllowedLanguagesForBackendUser()
    {
        $backendUser = $this->getBackendUser();
        $additionalWhere = '';
        if (!$backendUser->isAdmin()) {
            $additionalWhere .= ' AND sys_language.hidden=0';

            if (!empty($backendUser->user['allowed_languages'])) {
                $additionalWhere .= ' AND sys_language.uid IN(' . $this->getDatabaseConnection()->cleanIntList($backendUser->user['allowed_languages']) . ')';
            }
        }

        return $additionalWhere;
    }

    /**
     * Get records for copy process
     *
     * @param int $pageId
     * @param int $colPos
     * @param int $destLanguageId
     * @param int $languageId
     * @param string $fields
     * @return bool|\mysqli_result|object
     */
    public function getRecordsToCopyDatabaseResult($pageId, $colPos, $destLanguageId, $languageId, $fields = '*')
    {
        $db = $this->getDatabaseConnection();

        // Get original uid of existing elements triggered language / colpos
        $originalUids = $db->exec_SELECTgetRows(
            't3_origuid',
            'tt_content',
            'sys_language_uid=' . (int)$destLanguageId
            . ' AND tt_content.colPos = ' . (int)$colPos
            . ' AND tt_content.pid=' . (int)$pageId
            . $this->getExcludeQueryPart(),
            '',
            '',
            '',
            't3_origuid'
        );
        $originalUidList = $db->cleanIntList(implode(',', array_keys($originalUids)));

        $res = $db->exec_SELECTquery(
            $fields,
            'tt_content',
            'tt_content.sys_language_uid=' . (int)$languageId
            . ' AND tt_content.colPos = ' . (int)$colPos
            . ' AND tt_content.pid=' . (int)$pageId
            . ' AND tt_content.uid NOT IN (' . $originalUidList . ')'
            . $this->getExcludeQueryPart(),
            '',
            'tt_content.sorting'
        );

        return $res;
    }

    /**
     * Fetches the localization for a given record.
     *
     * @FIXME: This method is a clone of BackendUtility::getRecordLocalization, using origUid instead of transOrigPointerField
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @param int $uid The uid of the record
     * @param int $language The uid of the language record in sys_language
     * @param string $andWhereClause Optional additional WHERE clause (default: '')
     * @return mixed Multidimensional array with selected records; if none exist, FALSE is returned
     */
    public function getRecordLocalization($table, $uid, $language, $andWhereClause = '')
    {
        $recordLocalization = false;

        // Check if translations are stored in other table
        if (isset($GLOBALS['TCA'][$table]['ctrl']['transForeignTable'])) {
            $table = $GLOBALS['TCA'][$table]['ctrl']['transForeignTable'];
        }

        if (BackendUtility::isTableLocalizable($table)) {
            $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

            if (isset($tcaCtrl['origUid'])) {
                $recordLocalization = BackendUtility::getRecordsByField(
                    $table,
                    $tcaCtrl['origUid'],
                    $uid,
                    'AND ' . $tcaCtrl['languageField'] . '=' . (int)$language . ($andWhereClause ? ' ' . $andWhereClause : ''),
                    '',
                    '',
                    '1'
                );
            }
        }
        return $recordLocalization;
    }

    /**
     * Returning uid of previous localized record, if any, for tables with a "sortby" column
     * Used when new localized records are created so that localized records are sorted in the same order as the default language records
     *
     * @FIXME: This method is a clone of DataHandler::getPreviousLocalizedRecordUid which is protected there and uses
     * BackendUtility::getRecordLocalization which we also needed to clone in this class. Also, this method takes two
     * language arguments.
     *
     * @param string $table Table name
     * @param int $uid Uid of default language record
     * @param int $pid Pid of default language record
     * @param int $sourceLanguage Language of origin
     * @param int $destinationLanguage Language of localization
     * @return int uid of record after which the localized record should be inserted
     */
    public function getPreviousLocalizedRecordUid($table, $uid, $pid, $sourceLanguage, $destinationLanguage)
    {
        $previousLocalizedRecordUid = $uid;
        if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
            $sortRow = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
            $select = $sortRow . ',pid,uid';
            // For content elements, we also need the colPos
            if ($table === 'tt_content') {
                $select .= ',colPos';
            }
            // Get the sort value of the default language record
            $row = BackendUtility::getRecord($table, $uid, $select);
            if (is_array($row)) {
                // Find the previous record in default language on the same page
                $where = 'pid=' . (int)$pid . ' AND ' . 'sys_language_uid=' . (int)$sourceLanguage . ' AND ' . $sortRow . '<' . (int)$row[$sortRow];
                // Respect the colPos for content elements
                if ($table === 'tt_content') {
                    $where .= ' AND colPos=' . (int)$row['colPos'];
                }
                $res = $this->getDatabaseConnection()->exec_SELECTquery(
                    $select,
                    $table,
                    $where . BackendUtility::deleteClause($table),
                    '',
                    $sortRow . ' DESC',
                    '1'
                );
                // If there is an element, find its localized record in specified localization language
                if ($previousRow = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                    $previousLocalizedRecord = $this->getRecordLocalization($table, $previousRow['uid'], $destinationLanguage);
                    if (is_array($previousLocalizedRecord[0])) {
                        $previousLocalizedRecordUid = $previousLocalizedRecord[0]['uid'];
                    }
                }
                $this->getDatabaseConnection()->sql_free_result($res);
            }
        }
        return $previousLocalizedRecordUid;
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
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
