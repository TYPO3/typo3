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
            'tt_content,tt_content AS tt_content_orig',
            'tt_content.colPos = ' . (int)$colPos
            . ' AND tt_content.pid = ' . (int)$pageId
            . ' AND tt_content.sys_language_uid = ' . (int)$localizedLanguage
            . ' AND tt_content.t3_origuid = tt_content_orig.uid'
            . $this->getExcludeQueryPart()
            . $this->getAllowedLanguagesForBackendUser(),
            'tt_content_orig.sys_language_uid'
        );

        return $record;
    }

    /**
     * @param $pageId
     * @param $colPos
     * @param $languageId
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
