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

namespace TYPO3\CMS\Recordlist\RecordList;

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fetches all records like in the list module but returns them as array in order to allow
 * downloads (e.g. CSV) in the Controller with prepared data.
 *
 * This class acts as a composition-based wrapper for DatabaseRecordList for creating records
 * ready to be downloaded.
 *
 * @internal this class is not part of the TYPO3 Core API due to its nature as being a wrapper for DatabaseRecordList and a very specific implementation.
 */
class DownloadRecordList
{
    protected DatabaseRecordList $recordList;
    protected TranslationConfigurationProvider $translationConfigurationProvider;

    public function __construct(DatabaseRecordList $recordList, TranslationConfigurationProvider $translationConfigurationProvider)
    {
        $this->recordList = $recordList;
        $this->translationConfigurationProvider = $translationConfigurationProvider;
    }

    /**
     * Add header line with field names.
     *
     * @param string[] $columnsToRender
     * @return array the columns to be used / shown.
     */
    public function getHeaderRow(array $columnsToRender): array
    {
        $columnsToRender = array_combine($columnsToRender, $columnsToRender);
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][DatabaseRecordList::class]['customizeCsvHeader'] ?? [];
        if (!empty($hooks)) {
            $hookParameters = [
                'fields' => &$columnsToRender,
            ];
            foreach ($hooks as $hookFunction) {
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this->recordList);
            }
        }
        return $columnsToRender;
    }

    /**
     * Fetches records including translations (if not hidden) from the database in the specified order given by
     * DatabaseRecordList and returns the prepared records ready to be rendered.
     *
     * @param string $table the TCA table
     * @param int $pageId the page ID to select records from
     * @param string[] $columnsToRender
     * @param BackendUserAuthentication $backendUser the current backend user needed to check for permissions
     * @param bool $hideTranslations
     * @param bool $rawValues Whether the field values should not be processed
     * @return array[] an array of rows ready to be output
     */
    public function getRecords(
        string $table,
        int $pageId,
        array $columnsToRender,
        BackendUserAuthentication $backendUser,
        bool $hideTranslations = false,
        bool $rawValues = false
    ): array {
        // Creating the list of fields to include in the SQL query
        $selectFields = $this->recordList->getFieldsToSelect($table, $columnsToRender);
        $queryResult = $this->recordList->getQueryBuilder($table, $pageId, [], $selectFields, true, 0, 0)->executeQuery();
        $l10nEnabled = BackendUtility::isTableLocalizable($table);
        $result = [];
        // Render items
        while ($row = $queryResult->fetchAssociative()) {
            // In offline workspace, look for alternative record
            BackendUtility::workspaceOL($table, $row, $backendUser->workspace, true);
            if (!is_array($row)) {
                continue;
            }
            $result[] = $this->prepareRow($table, $row, $columnsToRender, $pageId, $rawValues);
            if (!$l10nEnabled) {
                continue;
            }
            if ($hideTranslations) {
                continue;
            }
            // Guard clause so we can quickly return if a record is localized to "all languages"
            // It should only be possible to localize a record off default (uid 0)
            if ((int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] === -1) {
                continue;
            }
            $translationsRaw = $this->translationConfigurationProvider->translationInfo($table, $row['uid'], 0, $row, $selectFields);
            foreach ($translationsRaw['translations'] ?? [] as $languageId => $translationRow) {
                // In offline workspace, look for alternative record
                BackendUtility::workspaceOL($table, $translationRow, $backendUser->workspace, true);
                if (is_array($translationRow) && $backendUser->checkLanguageAccess($languageId)) {
                    $result[] = $this->prepareRow($table, $translationRow, $columnsToRender, $pageId, $rawValues);
                }
            }
        }
        return $result;
    }

    /**
     * Prepares a DB row to process the values and maps the values to the columns to render
     * to have the same output.
     *
     * @param string $table Table name
     * @param mixed[] $row Current record
     * @param string[] $columnsToRender the columns to be displayed / downloaded
     * @param int $pageId used for the legacy hook
     * @param bool $rawValues Whether the field values should not be processed
     * @return array the prepared row
     */
    protected function prepareRow(string $table, array $row, array $columnsToRender, int $pageId, bool $rawValues): array
    {
        foreach ($columnsToRender as $columnName) {
            if (!$rawValues) {
                if ($columnName === $GLOBALS['TCA'][$table]['ctrl']['label']) {
                    $row[$columnName] = BackendUtility::getRecordTitle($table, $row, false, true);
                } elseif ($columnName !== 'pid') {
                    $row[$columnName] = BackendUtility::getProcessedValueExtra($table, $columnName, $row[$columnName], 0, $row['uid']);
                }
            }
        }
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][DatabaseRecordList::class]['customizeCsvRow'] ?? [];
        if (!empty($hooks)) {
            $hookParameters = [
                'databaseRow' => &$row,
                'tableName' => $table,
                'pageId' => $pageId,
            ];
            foreach ($hooks as $hookFunction) {
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this->recordList);
            }
        }
        return array_intersect_key($row, array_flip($columnsToRender));
    }
}
