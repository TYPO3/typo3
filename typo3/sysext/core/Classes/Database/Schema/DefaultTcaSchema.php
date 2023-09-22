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

namespace TYPO3\CMS\Core\Database\Schema;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Schema\Exception\DefaultTcaSchemaTablePositionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class is called by the SchemaMigrator after all extension's ext_tables.sql
 * files have been parsed and processed to the doctrine Table/Column/Index objects.
 *
 * Method enrich() goes through all $GLOBALS['TCA'] tables and adds fields like
 * 'uid', 'sorting', 'deleted' and friends if the feature is enabled in TCA and the
 * field has not been defined in ext_tables.sql files.
 *
 * This allows extension developers to leave out the TYPO3 DB management fields
 * and reduce ext_tables.sql of extensions down to the business fields.
 *
 * @internal
 */
class DefaultTcaSchema
{
    /**
     * Add fields to $tables array that has been created from ext_tables.sql files.
     * This goes through all tables defined in TCA, looks for 'ctrl' features like
     * "soft delete" ['ctrl']['delete'] and adds the field if it has not been
     * defined in ext_tables.sql, yet.
     *
     * @param Table[] $tables
     * @return Table[] Modified tables
     */
    public function enrich(array $tables): array
    {
        // Sanity check to ensure all TCA tables are already defined in incoming table list.
        // This prevents a misuse, calling code needs to ensure there is at least an empty
        // table object (no columns) for all TCA tables.
        $tableNamesFromTca = array_keys($GLOBALS['TCA']);
        $existingTableNames = [];
        foreach ($tables as $table) {
            $existingTableNames[] = $table->getName();
        }
        foreach ($tableNamesFromTca as $tableName) {
            if (!in_array($tableName, $existingTableNames, true)) {
                throw new \RuntimeException(
                    'Table name ' . $tableName . ' does not exist in incoming table list',
                    1696424993
                );
            }
        }

        $tables = $this->enrichSingleTableFieldsFromTcaCtrl($tables);
        $tables = $this->enrichSingleTableFieldsFromTcaColumns($tables);
        return $this->enrichMmTables($tables);
    }

    /**
     * Add single fields like uid, sorting and similar, based on tables TCA 'ctrl' settings.
     */
    protected function enrichSingleTableFieldsFromTcaCtrl($tables)
    {
        foreach ($GLOBALS['TCA'] as $tableName => $tableDefinition) {
            // If the table is given in existing $tables list, add all fields to the first
            // position of that table - in case it is in there multiple times which happens
            // if extensions add single fields to tables that have been defined in
            // other ext_tables.sql, too.
            $tablePosition = $this->getTableFirstPosition($tables, $tableName);

            // uid column and primary key if uid is not defined
            if (!$this->isColumnDefinedForTable($tables, $tableName, 'uid')) {
                $tables[$tablePosition]->addColumn(
                    $this->quote('uid'),
                    Types::INTEGER,
                    [
                        'notnull' => true,
                        'unsigned' => true,
                        'autoincrement' => true,
                    ]
                );
                $tables[$tablePosition]->setPrimaryKey(['uid']);
            }

            // pid column and prepare parent key if pid is not defined
            $pidColumnAdded = false;
            if (!$this->isColumnDefinedForTable($tables, $tableName, 'pid')) {
                $options = [
                    'default' => 0,
                    'notnull' => true,
                    'unsigned' => true,
                ];
                $tables[$tablePosition]->addColumn($this->quote('pid'), Types::INTEGER, $options);
                $pidColumnAdded = true;
            }

            // tstamp column
            if (!empty($tableDefinition['ctrl']['tstamp'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['tstamp'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['tstamp']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // crdate column
            if (!empty($tableDefinition['ctrl']['crdate'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['crdate'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['crdate']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // deleted column - soft delete
            if (!empty($tableDefinition['ctrl']['delete'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['delete'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['delete']),
                    Types::SMALLINT,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // disabled column
            if (!empty($tableDefinition['ctrl']['enablecolumns']['disabled'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['enablecolumns']['disabled'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['enablecolumns']['disabled']),
                    Types::SMALLINT,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // starttime column
            if (!empty($tableDefinition['ctrl']['enablecolumns']['starttime'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['enablecolumns']['starttime'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['enablecolumns']['starttime']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // endtime column
            if (!empty($tableDefinition['ctrl']['enablecolumns']['endtime'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['enablecolumns']['endtime'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['enablecolumns']['endtime']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // fe_group column
            if (!empty($tableDefinition['ctrl']['enablecolumns']['fe_group'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['enablecolumns']['fe_group'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['enablecolumns']['fe_group']),
                    Types::STRING,
                    [
                        'default' => '0',
                        'notnull' => true,
                        'length' => 255,
                    ]
                );
            }

            // sorting column
            if (!empty($tableDefinition['ctrl']['sortby'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['sortby'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['sortby']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => false,
                    ]
                );
            }

            // index on pid column and maybe others - only if pid has not been defined via ext_tables.sql before
            if ($pidColumnAdded && !$this->isIndexDefinedForTable($tables, $tableName, 'parent')) {
                $parentIndexFields = ['pid'];
                if (!empty($tableDefinition['ctrl']['delete'])) {
                    $parentIndexFields[] = (string)$tableDefinition['ctrl']['delete'];
                }
                if (!empty($tableDefinition['ctrl']['enablecolumns']['disabled'])) {
                    $parentIndexFields[] = (string)$tableDefinition['ctrl']['enablecolumns']['disabled'];
                }
                $tables[$tablePosition]->addIndex($parentIndexFields, 'parent');
            }

            // description column
            if (!empty($tableDefinition['ctrl']['descriptionColumn'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['descriptionColumn'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['descriptionColumn']),
                    Types::TEXT,
                    [
                        'notnull' => false,
                        'length' => 65535,
                    ]
                );
            }

            // editlock column
            if (!empty($tableDefinition['ctrl']['editlock'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['editlock'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['editlock']),
                    Types::SMALLINT,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // sys_language_uid column
            if (!empty($tableDefinition['ctrl']['languageField'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['languageField'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote((string)$tableDefinition['ctrl']['languageField']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => false,
                    ]
                );
            }

            // l10n_parent column
            if (!empty($tableDefinition['ctrl']['languageField'])
                && !empty($tableDefinition['ctrl']['transOrigPointerField'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['transOrigPointerField'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote((string)$tableDefinition['ctrl']['transOrigPointerField']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // l10n_source column
            if (!empty($tableDefinition['ctrl']['languageField'])
                && !empty($tableDefinition['ctrl']['translationSource'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['translationSource'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote((string)$tableDefinition['ctrl']['translationSource']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
                $tables[$tablePosition]->addIndex([$tableDefinition['ctrl']['translationSource']], 'translation_source');
            }

            // l10n_state column
            if (!empty($tableDefinition['ctrl']['languageField'])
                && !empty($tableDefinition['ctrl']['transOrigPointerField'])
                && !$this->isColumnDefinedForTable($tables, $tableName, 'l10n_state')
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote('l10n_state'),
                    Types::TEXT,
                    [
                        'notnull' => false,
                        'length' => 65535,
                    ]
                );
            }

            // t3_origuid column
            if (!empty($tableDefinition['ctrl']['origUid'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['origUid'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['origUid']),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // l18n_diffsource column
            if (!empty($tableDefinition['ctrl']['transOrigDiffSourceField'])
                && !$this->isColumnDefinedForTable($tables, $tableName, $tableDefinition['ctrl']['transOrigDiffSourceField'])
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote($tableDefinition['ctrl']['transOrigDiffSourceField']),
                    Types::BLOB,
                    [
                        // mediumblob (16MB) on mysql
                        'length' => 16777215,
                        'notnull' => false,
                    ]
                );
            }

            // workspaces t3ver_oid column
            if (!empty($tableDefinition['ctrl']['versioningWS'])
                && (bool)$tableDefinition['ctrl']['versioningWS'] === true
                && !$this->isColumnDefinedForTable($tables, $tableName, 't3ver_oid')
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote('t3ver_oid'),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // workspaces t3ver_wsid column
            if (!empty($tableDefinition['ctrl']['versioningWS'])
                && (bool)$tableDefinition['ctrl']['versioningWS'] === true
                && !$this->isColumnDefinedForTable($tables, $tableName, 't3ver_wsid')
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote('t3ver_wsid'),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // workspaces t3ver_state column
            if (!empty($tableDefinition['ctrl']['versioningWS'])
                && (bool)$tableDefinition['ctrl']['versioningWS'] === true
                && !$this->isColumnDefinedForTable($tables, $tableName, 't3ver_state')
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote('t3ver_state'),
                    Types::SMALLINT,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => false,
                    ]
                );
            }

            // workspaces t3ver_stage column
            if (!empty($tableDefinition['ctrl']['versioningWS'])
                && (bool)$tableDefinition['ctrl']['versioningWS'] === true
                && !$this->isColumnDefinedForTable($tables, $tableName, 't3ver_stage')
            ) {
                $tables[$tablePosition]->addColumn(
                    $this->quote('t3ver_stage'),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => false,
                    ]
                );
            }

            // workspaces index on t3ver_oid and t3ver_wsid fields
            if (!empty($tableDefinition['ctrl']['versioningWS'])
                && (bool)$tableDefinition['ctrl']['versioningWS'] === true
                && !$this->isIndexDefinedForTable($tables, $tableName, 't3ver_oid')
            ) {
                $tables[$tablePosition]->addIndex(['t3ver_oid', 't3ver_wsid'], 't3ver_oid');
            }
        }

        return $tables;
    }

    /**
     * Add single fields based on tables TCA 'columns'.
     */
    protected function enrichSingleTableFieldsFromTcaColumns($tables)
    {
        foreach ($GLOBALS['TCA'] as $tableName => $tableDefinition) {
            // If the table is given in existing $tables list, add all fields to the first
            // position of that table - in case it is supplied multiple times which happens
            // if extensions add single fields to tables that have been defined in
            // other ext_tables.sql, too.
            $tablePosition = $this->getTableFirstPosition($tables, $tableName);

            // In the following, columns for TCA fields with a dedicated TCA type are
            // added. In the unlikely case that no columns exist, we can skip the table.
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            // Add category fields for all tables, defining category columns (TCA type=category)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'category'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }

                if (($fieldConfig['config']['relationship'] ?? '') === 'oneToMany') {
                    $tables[$tablePosition]->addColumn(
                        $this->quote($fieldName),
                        Types::TEXT,
                        [
                            'notnull' => false,
                        ]
                    );
                } else {
                    $tables[$tablePosition]->addColumn(
                        $this->quote($fieldName),
                        Types::INTEGER,
                        [
                            'default' => 0,
                            'notnull' => true,
                            'unsigned' => true,
                        ]
                    );
                }
            }

            // Add datetime fields for all tables, defining datetime columns (TCA type=datetime), except
            // those columns, which had already been added due to definition in "ctrl", e.g. "starttime".
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'datetime'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }

                if (in_array($fieldConfig['config']['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true)) {
                    $tables[$tablePosition]->addColumn(
                        $this->quote($fieldName),
                        $fieldConfig['config']['dbType'],
                        [
                            'notnull' => false,
                        ]
                    );
                } else {
                    $tables[$tablePosition]->addColumn(
                        $this->quote($fieldName),
                        Types::INTEGER,
                        [
                            'default' => 0,
                            'notnull' => !($fieldConfig['config']['nullable'] ?? false),
                            'unsigned' => false,
                        ]
                    );
                }
            }

            // Add slug fields for all tables, defining slug columns (TCA type=slug)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'slug'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::STRING,
                    [
                        'length' => 2048,
                        'notnull' => false,
                    ]
                );
            }

            // Add json fields for all tables, defining json columns (TCA type=json)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'json'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::JSON,
                    [
                        'notnull' => false,
                    ]
                );
            }

            // Add uuid fields for all tables, defining uuid columns (TCA type=uuid)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'uuid'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::STRING,
                    [
                        'length' => 36,
                        'default' => '',
                        'notnull' => true,
                    ]
                );
            }

            // Add file fields for all tables, defining file columns (TCA type=file)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'file'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // Add folder fields for all tables, defining file columns (TCA type=folder)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'folder'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::TEXT,
                    [
                        'notnull' => false,
                    ]
                );
            }

            // Add email fields for all tables, defining email columns (TCA type=email)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'email'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $isNullable = (bool)($fieldConfig['config']['nullable'] ?? false);
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::STRING,
                    [
                        'length' => 255,
                        'default' => ($isNullable ? null : ''),
                        'notnull' => !$isNullable,
                    ]
                );
            }

            // Add check fields for all tables, defining check columns (TCA type=check)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'check'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::SMALLINT,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // Add file fields for all tables, defining crop columns (TCA type=imageManipulation)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'imageManipulation'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::TEXT,
                    [
                        'notnull' => false,
                    ]
                );
            }

            // Add fields for all tables, defining language columns (TCA type=language)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'language'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => false,
                    ]
                );
            }

            // Add fields for all tables, defining group columns (TCA type=group)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'group'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                if (isset($fieldConfig['config']['MM'])) {
                    $tables[$tablePosition]->addColumn(
                        $this->quote($fieldName),
                        Types::INTEGER,
                        [
                            'default' => 0,
                            'notnull' => true,
                            'unsigned' => true,
                        ]
                    );
                } else {
                    $tables[$tablePosition]->addColumn(
                        $this->quote($fieldName),
                        Types::TEXT,
                        [
                            'notnull' => false,
                        ]
                    );
                }
            }

            // Add fields for all tables, defining flex columns (TCA type=flex)
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'flex'
                    || $this->isColumnDefinedForTable($tables, $tableName, $fieldName)
                ) {
                    continue;
                }
                $tables[$tablePosition]->addColumn(
                    $this->quote($fieldName),
                    Types::TEXT,
                    [
                        'notnull' => false,
                    ]
                );
            }
        }

        return $tables;
    }

    /**
     * Find table fields that configure a "true" MM relation and define the
     * according mm table schema for them. True MM tables are intermediate tables
     * that have NO TCA itself. Those are indicated by type=select and type=group
     * and type=inline fields with MM property.
     */
    protected function enrichMmTables($tables): array
    {
        foreach ($GLOBALS['TCA'] as $tableName => $tableDefinition) {
            if (!is_array($tableDefinition['columns'] ?? false)) {
                // TCA definition in general is broken if there are no specified columns. Skip to be sure here.
                continue;
            }
            foreach ($tableDefinition['columns'] as $tcaColumn) {
                if (
                    !is_array($tcaColumn['config'] ?? false)
                    || !is_string($tcaColumn['config']['type'] ?? false)
                    || !in_array($tcaColumn['config']['type'], ['select', 'group', 'inline', 'category'], true)
                    || !is_string($tcaColumn['config']['MM'] ?? false)
                    // Consider this mm only if looking at it from the local side
                    || ($tcaColumn['config']['MM_opposite_field'] ?? false)
                ) {
                    // Broken TCA or not of expected type, or no MM, or foreign side
                    continue;
                }
                $mmTableName = $tcaColumn['config']['MM'];
                try {
                    // If the mm table is defined, work with it. Else add at and.
                    $tablePosition = $this->getTableFirstPosition($tables, $mmTableName);
                } catch (DefaultTcaSchemaTablePositionException) {
                    $tablePosition = array_key_last($tables) + 1;
                    $tables[$tablePosition] = GeneralUtility::makeInstance(
                        Table::class,
                        $mmTableName
                    );
                }

                // Add 'uid' field with primary key if multiple is set: 'multiple' allows using a left or right
                // side more than once in a relation which would lead to duplicate primary key entries. To
                // avoid this, we add a uid column and make it primary key instead.
                $needsUid = (bool)($tcaColumn['config']['multiple'] ?? false);
                if ($needsUid && !$this->isColumnDefinedForTable($tables, $mmTableName, 'uid')) {
                    $tables[$tablePosition]->addColumn(
                        $this->quote('uid'),
                        Types::INTEGER,
                        [
                            'notnull' => true,
                            'unsigned' => true,
                            'autoincrement' => true,
                        ]
                    );
                    $tables[$tablePosition]->setPrimaryKey(['uid']);
                }

                if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'uid_local')) {
                    $tables[$tablePosition]->addColumn(
                        $this->quote('uid_local'),
                        Types::INTEGER,
                        [
                            'default' => 0,
                            'notnull' => true,
                            'unsigned' => true,
                        ]
                    );
                }
                if (!$this->isIndexDefinedForTable($tables, $mmTableName, 'uid_local')) {
                    $tables[$tablePosition]->addIndex(['uid_local'], 'uid_local');
                }

                if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'uid_foreign')) {
                    $tables[$tablePosition]->addColumn(
                        $this->quote('uid_foreign'),
                        Types::INTEGER,
                        [
                            'default' => 0,
                            'notnull' => true,
                            'unsigned' => true,
                        ]
                    );
                }
                if (!$this->isIndexDefinedForTable($tables, $mmTableName, 'uid_foreign')) {
                    $tables[$tablePosition]->addIndex(['uid_foreign'], 'uid_foreign');
                }

                if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'sorting')) {
                    $tables[$tablePosition]->addColumn(
                        $this->quote('sorting'),
                        Types::INTEGER,
                        [
                            'default' => 0,
                            'notnull' => true,
                            'unsigned' => true,
                        ]
                    );
                }
                if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'sorting_foreign')) {
                    $tables[$tablePosition]->addColumn(
                        $this->quote('sorting_foreign'),
                        Types::INTEGER,
                        [
                            'default' => 0,
                            'notnull' => true,
                            'unsigned' => true,
                        ]
                    );
                }

                if (!empty($tcaColumn['config']['MM_oppositeUsage'])) {
                    // This local table can be the target of multiple foreign tables and table fields. The mm table
                    // thus needs two further fields to specify which foreign/table field combination links is used.
                    // Those are stored in two additional fields called "tablenames" and "fieldname".
                    if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'tablenames')) {
                        $tables[$tablePosition]->addColumn(
                            $this->quote('tablenames'),
                            Types::STRING,
                            [
                                'default' => '',
                                'length' => 64,
                                'notnull' => true,
                            ]
                        );
                    }
                    if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'fieldname')) {
                        $tables[$tablePosition]->addColumn(
                            $this->quote('fieldname'),
                            Types::STRING,
                            [
                                'default' => '',
                                'length' => 64,
                                'notnull' => true,
                            ]
                        );
                    }
                }

                // Primary key handling: If there is a uid field, PK has been added above already.
                // Otherwise, the PK combination is either "uid_local, uid_foreign", or
                // "uid_local, uid_foreign, tablenames, fieldname" if this is a multi-foreign setup.
                if (!$needsUid && $tables[$tablePosition]->getPrimaryKey() === null && !empty($tcaColumn['config']['MM_oppositeUsage'])) {
                    $tables[$tablePosition]->setPrimaryKey(['uid_local', 'uid_foreign', 'tablenames', 'fieldname']);
                } elseif (!$needsUid && $tables[$tablePosition]->getPrimaryKey() === null) {
                    $tables[$tablePosition]->setPrimaryKey(['uid_local', 'uid_foreign']);
                }
            }
        }
        return $tables;
    }

    /**
     * True if a column with a given name is defined within the incoming
     * array of Table's.
     *
     * @param Table[] $tables
     */
    protected function isColumnDefinedForTable(array $tables, string $tableName, string $fieldName): bool
    {
        foreach ($tables as $table) {
            if ($table->getName() !== $tableName) {
                continue;
            }
            $columns = $table->getColumns();
            foreach ($columns as $column) {
                if ($column->getName() === $fieldName) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * True if an index with a given name is defined within the incoming
     * array of Table's.
     *
     * @param Table[] $tables
     */
    protected function isIndexDefinedForTable(array $tables, string $tableName, string $indexName): bool
    {
        foreach ($tables as $table) {
            if ($table->getName() !== $tableName) {
                continue;
            }
            $indexes = $table->getIndexes();
            foreach ($indexes as $index) {
                if ($index->getName() === $indexName) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * The incoming $tables array can contain Table objects for the same table
     * multiple times. This can happen if an extension has the main CREATE TABLE
     * statement in its ext_tables.sql and another extension adds or changes further
     * fields in an own CREATE TABLE statement.
     *
     * @todo It would be better if the incoming $tables structure would be cleaned
     *       to contain a table only once before this class is entered.
     *
     * @param Table[] $tables
     * @throws DefaultTcaSchemaTablePositionException
     */
    protected function getTableFirstPosition(array $tables, string $tableName): int
    {
        foreach ($tables as $position => $table) {
            if ($table->getName() === $tableName) {
                return (int)$position;
            }
        }
        throw new DefaultTcaSchemaTablePositionException('Table ' . $tableName . ' not found in schema list', 1527854474);
    }

    protected function quote(string $identifier): string
    {
        return '`' . $identifier . '`';
    }
}
