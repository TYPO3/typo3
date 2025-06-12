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

namespace TYPO3\CMS\Backend\RecordList;

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

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
    public function __construct(
        protected DatabaseRecordList $recordList,
        protected TranslationConfigurationProvider $translationConfigurationProvider,
        protected TcaSchemaFactory $tcaSchemaFactory
    ) {}

    /**
     * Add header line with field names.
     *
     * @param string[] $columnsToRender
     * @return array the columns to be used / shown.
     */
    public function getHeaderRow(array $columnsToRender): array
    {
        // @todo: array_combine() was used in the initial revision already,
        //        probably to filter out illegal values? Looks odd, but may be due to CSV quirks?
        return array_combine($columnsToRender, $columnsToRender);
    }

    /**
     * Fetches records including translations (if not hidden) from the database in the specified order given by
     * DatabaseRecordList and returns the prepared records ready to be rendered.
     *
     * @param string $table the TCA table
     * @param string[] $columnsToRender
     * @param BackendUserAuthentication $backendUser the current backend user needed to check for permissions
     * @param bool $rawValues Whether the field values should not be processed
     * @return array[] an array of rows ready to be output
     */
    public function getRecords(
        string $table,
        array $columnsToRender,
        BackendUserAuthentication $backendUser,
        bool $hideTranslations = false,
        bool $rawValues = false
    ): array {
        // Creating the list of fields to include in the SQL query
        $selectFields = $this->recordList->getFieldsToSelect($table, $columnsToRender);
        $queryResult = $this->recordList->getQueryBuilder($table, $selectFields)->executeQuery();
        $schema = $this->tcaSchemaFactory->get($table);
        $result = [];
        $languageField = $schema->isLanguageAware() ? $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName() : null;
        // Render items
        while ($row = $queryResult->fetchAssociative()) {
            // In offline workspace, look for alternative record
            BackendUtility::workspaceOL($table, $row, $backendUser->workspace, true);
            if (!is_array($row)) {
                continue;
            }
            $result[] = $this->prepareRow($table, $row, $columnsToRender, $rawValues);
            if (!$schema->isLanguageAware()) {
                continue;
            }
            if ($hideTranslations) {
                continue;
            }
            // Guard clause so we can quickly return if a record is localized to "all languages"
            // It should only be possible to localize a record off default (uid 0)
            if ((int)$row[$languageField] === -1) {
                continue;
            }
            $translationsRaw = $this->translationConfigurationProvider->translationInfo($table, $row['uid'], 0, $row, $selectFields);
            foreach ($translationsRaw['translations'] ?? [] as $languageId => $translationRow) {
                // In offline workspace, look for alternative record
                BackendUtility::workspaceOL($table, $translationRow, $backendUser->workspace, true);
                if (is_array($translationRow) && $backendUser->checkLanguageAccess($languageId)) {
                    $result[] = $this->prepareRow($table, $translationRow, $columnsToRender, $rawValues);
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
     * @param array $row Current record
     * @param string[] $columnsToRender the columns to be displayed / downloaded
     * @param bool $rawValues Whether the field values should not be processed
     * @return array the prepared row
     */
    protected function prepareRow(string $table, array $row, array $columnsToRender, bool $rawValues): array
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $labelFieldName = $schema->getCapability(TcaSchemaCapability::Label)->getPrimaryFieldName() ?? '';
        foreach ($columnsToRender as $columnName) {
            if (!$rawValues) {
                if ($columnName === $labelFieldName) {
                    $row[$columnName] = BackendUtility::getRecordTitle($table, $row);
                } elseif ($columnName !== 'pid') {
                    $row[$columnName] = BackendUtility::getProcessedValueExtra($table, $columnName, $row[$columnName], 0, $row['uid'], false, 0, $row);
                }
            }
        }
        return array_intersect_key($row, array_flip($columnsToRender));
    }
}
