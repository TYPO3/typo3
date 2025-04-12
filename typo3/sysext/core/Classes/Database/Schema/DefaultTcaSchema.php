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

use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\DefaultTcaSchemaTablePositionException;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\CategoryFieldType;
use TYPO3\CMS\Core\Schema\Field\CheckboxFieldType;
use TYPO3\CMS\Core\Schema\Field\ColorFieldType;
use TYPO3\CMS\Core\Schema\Field\CountryFieldType;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\EmailFieldType;
use TYPO3\CMS\Core\Schema\Field\FileFieldType;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\Field\FolderFieldType;
use TYPO3\CMS\Core\Schema\Field\GroupFieldType;
use TYPO3\CMS\Core\Schema\Field\ImageManipulationFieldType;
use TYPO3\CMS\Core\Schema\Field\InlineFieldType;
use TYPO3\CMS\Core\Schema\Field\InputFieldType;
use TYPO3\CMS\Core\Schema\Field\JsonFieldType;
use TYPO3\CMS\Core\Schema\Field\LanguageFieldType;
use TYPO3\CMS\Core\Schema\Field\LinkFieldType;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\Field\PasswordFieldType;
use TYPO3\CMS\Core\Schema\Field\RadioFieldType;
use TYPO3\CMS\Core\Schema\Field\SelectRelationFieldType;
use TYPO3\CMS\Core\Schema\Field\SlugFieldType;
use TYPO3\CMS\Core\Schema\Field\StaticSelectFieldType;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;
use TYPO3\CMS\Core\Schema\Field\UuidFieldType;
use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
    public function __construct(
        private ?TcaSchemaFactory $tcaSchemaFactory = null,
    ) {
        $this->tcaSchemaFactory = $tcaSchemaFactory ?? GeneralUtility::makeInstance(TcaSchemaFactory::class);
    }

    /**
     * Add fields to $tables array that has been created from ext_tables.sql files.
     * This goes through all tables defined in TCA, looks for 'ctrl' features like
     * "soft delete" ['ctrl']['delete'] and adds the field if it has not been
     * defined in ext_tables.sql, yet.
     *
     * @param array<non-empty-string, Table> $tables
     * @return array<non-empty-string, Table> Modified tables
     */
    public function enrich(array $tables): array
    {
        // Sanity check to ensure all TCA tables are already defined in the incoming table list.
        // This prevents misuse, calling code needs to ensure there is at least an empty
        // table object (no columns) for all TCA tables.
        $existingTableNames = array_keys($tables);
        foreach ($this->tcaSchemaFactory->all() as $tableName => $schema) {
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
     *
     * @param array<non-empty-string, Table> $tables
     * @return array<non-empty-string, Table>
     */
    protected function enrichSingleTableFieldsFromTcaCtrl(array $tables): array
    {
        foreach ($this->tcaSchemaFactory->all() as $tableName => $schema) {
            if (!$this->isColumnDefinedForTable($tables, $tableName, 'uid')) {
                $tables[$tableName]->addColumn(
                    $this->quote('uid'),
                    Types::INTEGER,
                    [
                        'notnull' => true,
                        'unsigned' => true,
                        'autoincrement' => true,
                    ]
                );
                $tables[$tableName]->setPrimaryKey(['uid']);
            }

            // pid column and prepare parent key if pid is not defined
            $pidColumnAdded = false;
            if (!$this->isColumnDefinedForTable($tables, $tableName, 'pid')) {
                $options = [
                    'default' => 0,
                    'notnull' => true,
                    'unsigned' => true,
                ];
                $tables[$tableName]->addColumn($this->quote('pid'), Types::INTEGER, $options);
                $pidColumnAdded = true;
            }

            // tstamp column
            // not converted to bigint because already unsigned and date before 1970 not needed
            if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName()),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // crdate column
            if ($schema->hasCapability(TcaSchemaCapability::CreatedAt)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::CreatedAt)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::CreatedAt)->getFieldName()),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // deleted column - soft delete
            if ($schema->hasCapability(TcaSchemaCapability::SoftDelete)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName()),
                    Types::SMALLINT,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // disabled column
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName()),
                    Types::SMALLINT,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // starttime column
            // not converted to bigint because already unsigned and date before 1970 not needed
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionStartTime)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName()),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // endtime column
            // not converted to bigint because already unsigned and date before 1970 not needed
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionEndTime)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName()),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // fe_group column
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionUserGroup)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::RestrictionUserGroup)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::RestrictionUserGroup)->getFieldName()),
                    Types::STRING,
                    [
                        'default' => '0',
                        'notnull' => true,
                        'length' => 255,
                    ]
                );
            }

            // sorting column
            if ($schema->hasCapability(TcaSchemaCapability::SortByField)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName()),
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
                if ($schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                    $parentIndexFields[] = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
                }
                if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
                    $parentIndexFields[] = $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();
                }
                $tables[$tableName]->addIndex($parentIndexFields, 'parent');
            }

            // description column
            if ($schema->hasCapability(TcaSchemaCapability::InternalDescription)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::InternalDescription)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::InternalDescription)->getFieldName()),
                    Types::TEXT,
                    [
                        'notnull' => false,
                        'length' => 65535,
                    ]
                );
            }

            // editlock column
            if ($schema->hasCapability(TcaSchemaCapability::EditLock)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()),
                    Types::SMALLINT,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // sys_language_uid column
            $languageColumnAdded = false;
            if ($schema->isLanguageAware()
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName()),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => false,
                    ]
                );
                $languageColumnAdded = true;
            }

            // l10n_parent column
            $translationOriginPointerColumnAdded = false;
            if ($schema->isLanguageAware()
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName()),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
                $translationOriginPointerColumnAdded = true;
            }

            // Add index for sys_language_uid and l10n_parent
            if ($languageColumnAdded
                && $translationOriginPointerColumnAdded
                && !$this->isIndexDefinedForTable($tables, $tableName, 'language_identifier')
                && $schema->isLanguageAware()
            ) {
                $tables[$tableName]->addIndex([
                    (string)$schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(),
                    (string)$schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                ], 'language_identifier');
            }

            // l10n_source column
            if ($schema->isLanguageAware()
                && $schema->getCapability(TcaSchemaCapability::Language)->hasTranslationSourceField()
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::Language)->getTranslationSourceField()->getName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::Language)->getTranslationSourceField()->getName()),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
                $tables[$tableName]->addIndex([$schema->getCapability(TcaSchemaCapability::Language)->getTranslationSourceField()->getName()], 'translation_source');
            }

            // l10n_state column, this is not defined in TCA, but always added if the table is language-aware
            if ($schema->isLanguageAware()
                && !$this->isColumnDefinedForTable($tables, $tableName, 'l10n_state')
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote('l10n_state'),
                    Types::TEXT,
                    [
                        'notnull' => false,
                        'length' => 65535,
                    ]
                );
            }

            // t3_origuid column
            if ($schema->hasCapability(TcaSchemaCapability::AncestorReferenceField)
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::AncestorReferenceField)->getFieldName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::AncestorReferenceField)->getFieldName()),
                    Types::INTEGER,
                    [
                        'default' => 0,
                        'notnull' => true,
                        'unsigned' => true,
                    ]
                );
            }

            // l18n_diffsource column
            if ($schema->isLanguageAware() && $schema->getCapability(TcaSchemaCapability::Language)->hasDiffSourceField()
                && !$this->isColumnDefinedForTable($tables, $tableName, $schema->getCapability(TcaSchemaCapability::Language)->getDiffSourceField()->getName())
            ) {
                $tables[$tableName]->addColumn(
                    $this->quote($schema->getCapability(TcaSchemaCapability::Language)->getDiffSourceField()->getName()),
                    Types::BLOB,
                    [
                        // mediumblob (16MB) on mysql
                        'length' => 16777215,
                        'notnull' => false,
                    ]
                );
            }

            // workspaces t3ver_oid column
            if ($schema->isWorkspaceAware()
                && !$this->isColumnDefinedForTable($tables, $tableName, 't3ver_oid')
            ) {
                $tables[$tableName]->addColumn(
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
            if ($schema->isWorkspaceAware()
                && !$this->isColumnDefinedForTable($tables, $tableName, 't3ver_wsid')
            ) {
                $tables[$tableName]->addColumn(
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
            if ($schema->isWorkspaceAware()
                && !$this->isColumnDefinedForTable($tables, $tableName, 't3ver_state')
            ) {
                $tables[$tableName]->addColumn(
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
            if ($schema->isWorkspaceAware()
                && !$this->isColumnDefinedForTable($tables, $tableName, 't3ver_stage')
            ) {
                $tables[$tableName]->addColumn(
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
            if ($schema->isWorkspaceAware()
                && !$this->isIndexDefinedForTable($tables, $tableName, 't3ver_oid')
            ) {
                $tables[$tableName]->addIndex(['t3ver_oid', 't3ver_wsid'], 't3ver_oid');
            }
        }

        return $tables;
    }

    /**
     * Add single fields based on tables TCA 'columns'.
     *
     * @param array<non-empty-string, Table> $tables
     * @return array<non-empty-string, Table>
     */
    protected function enrichSingleTableFieldsFromTcaColumns(array $tables): array
    {
        foreach ($this->tcaSchemaFactory->all() as $tableName => $schema) {
            /** @var TcaSchema $schema  */
            // In the following, columns for TCA fields with a dedicated TCA type are
            // added. In the unlikely case that no columns exist, we can skip the table.
            if ($schema->getFields()->count() === 0) {
                continue;
            }
            $tableConnectionPlatform = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName)->getDatabasePlatform();

            foreach ($schema->getFields() as $fieldName => $fieldType) {
                if ($this->isColumnDefinedForTable($tables, $tableName, $fieldName)) {
                    continue;
                }
                $fieldTypeConfiguration = $fieldType->getConfiguration();
                switch (true) {
                    case $fieldType instanceof CategoryFieldType:
                        if ($fieldType->getRelationshipType() === RelationshipType::OneToMany) {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::TEXT,
                                [
                                    'notnull' => false,
                                ]
                            );
                        } else {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::INTEGER,
                                [
                                    'default' => 0,
                                    'notnull' => true,
                                    'unsigned' => true,
                                ]
                            );
                        }
                        break;

                    case $fieldType instanceof DateTimeFieldType:
                        $dbType = $fieldType->getPersistenceType() ?? '';
                        // Add datetime fields for all tables, defining datetime columns (TCA type=datetime), except
                        // those columns, which had already been added due to definition in "ctrl", e.g. "starttime".
                        if ($dbType) {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                $dbType,
                                [
                                    'notnull' => !$fieldType->isNullable(),
                                ]
                            );
                        } else {
                            // int unsigned:            from 1970 to 2106.
                            // int signed:              from 1901 to 2038.
                            // bigint unsigned/signed:  from whenever to whenever
                            //
                            // Anything like crdate,tstamp,starttime,endtime is good with
                            //  "int unsigned" and can survive the 2038 apocalypse (until 2106).
                            //
                            // However, anything that has birthdates or dates
                            // from the past (sys_file_metadata.content_creation_date) was saved
                            // as a SIGNED INT. It allowed birthdays of people older than 1970,
                            // but with the downside that it ends in 2038.
                            //
                            // This is now changed to utilize BIGINT everywhere, even when smaller
                            // date ranges are requested. To reduce complexity, we specifically
                            // do not evaluate "range.upper/lower" fields and use a unified type here.
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::BIGINT,
                                [
                                    'default' => $fieldType->isNullable() ? null : 0,
                                    'notnull' => !$fieldType->isNullable(),
                                    'unsigned' => false,
                                ]
                            );
                        }
                        break;

                    case $fieldType instanceof SlugFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::TEXT,
                            [
                                'length' => 65535,
                                'notnull' => false,
                            ]
                        );
                        break;

                    case $fieldType instanceof JsonFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::JSON,
                            [
                                'notnull' => false,
                            ]
                        );
                        break;

                    case $fieldType instanceof UuidFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::STRING,
                            [
                                'length' => 36,
                                'default' => '',
                                'notnull' => true,
                            ]
                        );
                        break;

                    case $fieldType instanceof FileFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::INTEGER,
                            [
                                'default' => 0,
                                'notnull' => true,
                                'unsigned' => true,
                            ]
                        );
                        break;

                    case $fieldType instanceof FolderFieldType:
                    case $fieldType instanceof ImageManipulationFieldType:
                    case $fieldType instanceof FlexFormFieldType:
                    case $fieldType instanceof TextFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::TEXT,
                            [
                                'notnull' => false,
                            ]
                        );
                        break;

                    case $fieldType instanceof EmailFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::STRING,
                            [
                                'length' => 255,
                                'default' => ($fieldType->isNullable() ? null : ''),
                                'notnull' => !$fieldType->isNullable(),
                            ]
                        );
                        break;

                    case $fieldType instanceof CheckboxFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::SMALLINT,
                            [
                                // Even though CheckboxFieldType::getDefaultValue() returns null, the DB stores "0"
                                // as this was like that before, and might have complications, so should be analyzed separately
                                'default' => $fieldType->getDefaultValue() ?? 0,
                                'notnull' => true,
                                'unsigned' => true,
                            ]
                        );
                        break;

                    case $fieldType instanceof LanguageFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::INTEGER,
                            [
                                'default' => 0,
                                'notnull' => true,
                                'unsigned' => false,
                            ]
                        );
                        break;

                    case $fieldType instanceof GroupFieldType:
                        if ($fieldType->getRelationshipType() === RelationshipType::ManyToMany) {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::INTEGER,
                                [
                                    'default' => 0,
                                    'notnull' => true,
                                    'unsigned' => true,
                                ]
                            );
                        } else {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::TEXT,
                                [
                                    'notnull' => false,
                                ]
                            );
                        }
                        break;

                    case $fieldType instanceof PasswordFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::STRING,
                            [
                                'default' => ($fieldType->isNullable() ? null : ''),
                                'notnull' => !$fieldType->isNullable(),
                            ]
                        );
                        break;

                    case $fieldType instanceof ColorFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::STRING,
                            [
                                'length' => $fieldType->supportsOpacity() ? 9 : 7,
                                'default' => ($fieldType->isNullable() ? null : ''),
                                'notnull' => !$fieldType->isNullable(),
                            ]
                        );
                        break;

                    case $fieldType instanceof RadioFieldType:
                        $hasItemsProcFunc = ($fieldTypeConfiguration['itemsProcFunc'] ?? '') !== '';
                        $items = $fieldTypeConfiguration['items'] ?? [];
                        // With itemsProcFunc we can't be sure, which values are persisted. Use type string.
                        if ($hasItemsProcFunc) {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::STRING,
                                [
                                    'length' => 255,
                                    'default' => '',
                                    'notnull' => true,
                                ]
                            );
                            break;
                        }
                        // If no items are configured, use type string to be safe for values added directly.
                        if ($items === []) {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::STRING,
                                [
                                    'length' => 255,
                                    'default' => '',
                                    'notnull' => true,
                                ]
                            );
                            break;
                        }
                        // If only one value is NOT an integer use type string.
                        foreach ($items as $item) {
                            if (!MathUtility::canBeInterpretedAsInteger($item['value'])) {
                                $tables[$tableName]->addColumn(
                                    $this->quote($fieldName),
                                    Types::STRING,
                                    [
                                        'length' => 255,
                                        'default' => '',
                                        'notnull' => true,
                                    ]
                                );
                                // continue with next $tableDefinition['columns']
                                // see: DefaultTcaSchemaTest->enrichAddsRadioStringVerifyThatCorrectLoopIsContinued()
                                break 2;
                            }
                        }
                        // Use integer type.
                        $allValues = array_map(fn(array $item): int => (int)$item['value'], $items);
                        $minValue = min($allValues);
                        $maxValue = max($allValues);
                        // Try to safe some bytes - can be reconsidered to simply use Types::INTEGER.
                        $integerType = ($minValue >= -32768 && $maxValue < 32768)
                            ? Types::SMALLINT
                            : Types::INTEGER;
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            $integerType,
                            [
                                'default' => 0,
                                'notnull' => true,
                            ]
                        );
                        break;

                    case $fieldType instanceof LinkFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::TEXT,
                            [
                                'length' => 65535,
                                'default' => $fieldType->isNullable() ? null : '',
                                'notnull' => !$fieldType->isNullable(),
                            ]
                        );
                        break;

                    case $fieldType instanceof InputFieldType:
                        $length = (int)($fieldTypeConfiguration['max'] ?? 255);
                        if ($length > 255) {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::TEXT,
                                [
                                    'length' => 65535,
                                    'default' => $fieldType->isNullable() ? null : '',
                                    'notnull' => !$fieldType->isNullable(),
                                ]
                            );
                            break;
                        }
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::STRING,
                            [
                                'length' => $length,
                                'default' => $fieldType->isNullable() ? null : '',
                                'notnull' => !$fieldType->isNullable(),
                            ]
                        );
                        break;

                    case $fieldType instanceof InlineFieldType:
                        // Must be MM or foreign_field
                        if (in_array($fieldType->getRelationshipType(), [RelationshipType::OneToOne, RelationshipType::ManyToMany, RelationshipType::OneToMany], true)
                            || ($fieldType->getRelationshipType() === RelationshipType::ManyToOne && ($fieldTypeConfiguration['foreign_field'] ?? '') !== '')
                        ) {
                            // Parent "count" field
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::INTEGER,
                                [
                                    'default' => 0,
                                    'notnull' => true,
                                    'unsigned' => true,
                                ]
                            );
                        } else {
                            // Inline "csv"
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::STRING,
                                [
                                    'default' => '',
                                    'notnull' => true,
                                    'length' => 255,
                                ]
                            );
                        }
                        if (($fieldTypeConfiguration['foreign_field'] ?? '') !== '') {
                            // Add definition for "foreign_field" (contains parent uid) in the child table if it is not defined
                            // in child TCA, or if it is "just" a "passthrough" field, and not manually configured in ext_tables.sql
                            $childTable = $fieldTypeConfiguration['foreign_table'];
                            if (!(($tables[$childTable] ?? null) instanceof Table)) {
                                throw new DefaultTcaSchemaTablePositionException('Table ' . $childTable . ' not found in schema list', 1527854474);
                            }
                            $childTableForeignFieldName = $fieldTypeConfiguration['foreign_field'];
                            if ($this->tcaSchemaFactory->has($childTable)) {
                                $childSchema = $this->tcaSchemaFactory->get($childTable);
                                if ((!$childSchema->hasField($childTableForeignFieldName) || $childSchema->getField($childTableForeignFieldName)->isType(TableColumnType::PASSTHROUGH))
                                    && !$this->isColumnDefinedForTable($tables, $childTable, $childTableForeignFieldName)
                                ) {
                                    $tables[$childTable]->addColumn(
                                        $this->quote($childTableForeignFieldName),
                                        Types::INTEGER,
                                        [
                                            'default' => 0,
                                            'notnull' => true,
                                            'unsigned' => true,
                                        ]
                                    );
                                }
                                // Add definition for "foreign_table_field" (contains name of parent table) in the child table if it is not
                                // defined in child TCA or if it is "just" a "passthrough" field, and not manually configured in ext_tables.sql
                                $childTableForeignTableFieldName = $fieldTypeConfiguration['foreign_table_field'] ?? '';
                                if ($childTableForeignTableFieldName !== ''
                                    && (!$childSchema->hasField($childTableForeignTableFieldName) || $childSchema->getField($childTableForeignTableFieldName)->isType(TableColumnType::PASSTHROUGH))
                                    && !$this->isColumnDefinedForTable($tables, $childTable, $childTableForeignTableFieldName)
                                ) {
                                    $tables[$childTable]->addColumn(
                                        $this->quote($childTableForeignTableFieldName),
                                        Types::STRING,
                                        [
                                            'default' => '',
                                            'notnull' => true,
                                            'length' => 255,
                                        ]
                                    );
                                }
                            }
                        }
                        break;

                    case $fieldType instanceof NumberFieldType:
                        $type = $fieldType->getFormat() === 'decimal' ? Types::DECIMAL : Types::INTEGER;
                        $lowerRange = $fieldTypeConfiguration['range']['lower'] ?? -1;
                        // Integer type for all database platforms.
                        if ($type === Types::INTEGER) {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::INTEGER,
                                [
                                    'default' => $fieldType->isNullable() === true ? null : 0,
                                    'notnull' => !$fieldType->isNullable(),
                                    'unsigned' => $lowerRange >= 0,
                                ]
                            );
                            break;
                        }
                        // SQLite internally defines NUMERIC() fields as real, and therefore as floating numbers. pdo_sqlite
                        // then returns PHP float which can lead to rounding issues. See https://bugs.php.net/bug.php?id=81397
                        // for more details. We create a 'string' field on SQLite as workaround.
                        // @todo: Database schema should be created with MySQL in mind and not mixed. Transforming to the
                        //        concrete database platform is handled in the database compare area. Sadly, this is not
                        //        possible right now but upcoming preparation towards doctrine/dbal 4 makes it possible to
                        //        move this "hack" to a different place.
                        if ($tableConnectionPlatform instanceof DoctrineSQLitePlatform) {
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::STRING,
                                [
                                    'default' => $fieldType->isNullable() === true ? null : '0.00',
                                    'notnull' => !$fieldType->isNullable(),
                                    'length' => 255,
                                ]
                            );
                            break;
                        }
                        // Decimal for all supported platforms except SQLite
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::DECIMAL,
                            [
                                'default' => $fieldType->isNullable() === true ? null : 0.00,
                                'notnull' => !$fieldType->isNullable(),
                                'unsigned' => $lowerRange >= 0,
                                'precision' => 10,
                                'scale' => 2,
                            ]
                        );
                        break;

                    case $fieldType instanceof SelectRelationFieldType || $fieldType instanceof StaticSelectFieldType:
                        if (($fieldTypeConfiguration['MM'] ?? '') !== '') {
                            // MM relation, this is a "parent count" field. Have an int.
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::INTEGER,
                                [
                                    'notnull' => true,
                                    'default' => 0,
                                    'unsigned' => true,
                                ]
                            );
                            break;
                        }
                        $dbFieldLength = (int)($fieldTypeConfiguration['dbFieldLength'] ?? 0);
                        // If itemsProcFunc is not set, check the item values
                        if (($fieldTypeConfiguration['itemsProcFunc'] ?? '') === '') {
                            $items = $fieldTypeConfiguration['items'] ?? [];
                            $itemsContainsOnlyIntegers = true;
                            foreach ($items as $item) {
                                if (!MathUtility::canBeInterpretedAsInteger($item['value'])) {
                                    $itemsContainsOnlyIntegers = false;
                                    break;
                                }
                            }
                            $itemsAreAllPositive = true;
                            foreach ($items as $item) {
                                if ($item['value'] < 0) {
                                    $itemsAreAllPositive = false;
                                    break;
                                }
                            }
                            // @todo: The dependency to renderType is unfortunate here. It's only purpose is to potentially have int fields
                            //        instead of string when this is a 'single' relation / value. However, renderType should usually not
                            //        influence DB layer at all. Maybe 'selectSingle' should be changed to an own 'type' instead to make
                            //        this more explicit. Maybe DataHandler could benefit from this as well?
                            if (($fieldTypeConfiguration['renderType'] ?? '') === 'selectSingle' || ($fieldTypeConfiguration['maxitems'] ?? 0) === 1) {
                                // With 'selectSingle' or with 'maxitems = 1', only a single value can be selected.
                                if (
                                    !is_array($fieldTypeConfiguration['fileFolderConfig'] ?? false)
                                    && ($items !== [] || ($fieldTypeConfiguration['foreign_table'] ?? '') !== '')
                                    && $itemsContainsOnlyIntegers === true
                                ) {
                                    // If the item list is empty, or if it contains only int values, an int field is enough.
                                    // Also, the config must not be a 'fileFolderConfig' field which takes string values.
                                    $tables[$tableName]->addColumn(
                                        $this->quote($fieldName),
                                        Types::INTEGER,
                                        [
                                            'notnull' => true,
                                            'default' => 0,
                                            'unsigned' => $itemsAreAllPositive,
                                        ]
                                    );
                                    break;
                                }
                                // If int is no option, have a string field.
                                $tables[$tableName]->addColumn(
                                    $this->quote($fieldName),
                                    Types::STRING,
                                    [
                                        'notnull' => true,
                                        'default' => '',
                                        'length' => $dbFieldLength > 0 ? $dbFieldLength : 255,
                                    ]
                                );
                                break;
                            }
                            if ($itemsContainsOnlyIntegers) {
                                // Multiple values can be selected and will be stored comma separated. When manual item values are
                                // all integers, or if there is a foreign_table, we end up with a comma separated list of integers.
                                // Using string / varchar 255 here should be long enough to store plenty of values, and can be
                                // changed by setting 'dbFieldLength'.
                                $tables[$tableName]->addColumn(
                                    $this->quote($fieldName),
                                    Types::STRING,
                                    [
                                        // @todo: nullable = true is not a good default here. This stems from the fact that this
                                        //        if triggers a lot of TEXT->VARCHAR() field changes during upgrade, where TEXT
                                        //        is always nullable, but varchar() is not. As such, we for now declare this
                                        //        nullable, but could have a look at it later again when a value upgrade
                                        //        for such cases is in place that updates existing null fields to empty string.
                                        'notnull' => false,
                                        'default' => '',
                                        'length' => $dbFieldLength > 0 ? $dbFieldLength : 255,
                                    ]
                                );
                                break;
                            }
                        }
                        if ($dbFieldLength > 0) {
                            // If nothing else matches, but there is a dbFieldLength set, have varchar with that length.
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::STRING,
                                [
                                    'notnull' => true,
                                    'default' => '',
                                    'length' => $dbFieldLength,
                                ]
                            );
                        } else {
                            // Final fallback creates a (nullable) text field.
                            $tables[$tableName]->addColumn(
                                $this->quote($fieldName),
                                Types::TEXT,
                                [
                                    'notnull' => false,
                                ]
                            );
                        }
                        break;
                    case $fieldType instanceof CountryFieldType:
                        $tables[$tableName]->addColumn(
                            $this->quote($fieldName),
                            Types::STRING,
                            [
                                'length' => 16, // Even though ISO2 is stored by default, custom additional items may need some (limited) storage
                                'notnull' => false,
                            ]
                        );
                        break;

                }
            }
        }

        return $tables;
    }

    /**
     * Find table fields that configure a "true" MM relation and define the
     * according mm table schema for them. True MM tables are intermediate tables
     * that have NO TCA itself. Those are indicated by type=select and type=group
     * and type=inline fields with MM property.
     *
     * @param array<non-empty-string, Table> $tables
     * @return array<non-empty-string, Table>
     */
    protected function enrichMmTables(array $tables): array
    {
        foreach ($this->tcaSchemaFactory->all() as $schema) {
            foreach ($schema->getFields() as $field) {
                // Broken TCA or not of expected type, or no MM, or foreign side
                if (!$field->isType(TableColumnType::SELECT, TableColumnType::GROUP, TableColumnType::INLINE, TableColumnType::CATEGORY)) {
                    continue;
                }
                $fieldConfiguration = $field->getConfiguration();
                if (!is_string($fieldConfiguration['MM'] ?? false)
                    // Consider this mm only if looking at it from the local side
                    || ($fieldConfiguration['MM_opposite_field'] ?? false)
                ) {
                    continue;
                }
                $mmTableName = $fieldConfiguration['MM'];
                if (!array_key_exists($mmTableName, $tables)) {
                    // If the mm table is defined, work with it. Else add at and.
                    $tables[$mmTableName] = GeneralUtility::makeInstance(
                        Table::class,
                        $mmTableName
                    );
                }

                // Add 'uid' field with primary key if multiple is set: 'multiple' allows using a left or right
                // side more than once in a relation which would lead to duplicate primary key entries. To
                // avoid this, we add a uid column and make it primary key instead.
                $needsUid = (bool)($fieldConfiguration['multiple'] ?? false);
                if ($needsUid && !$this->isColumnDefinedForTable($tables, $mmTableName, 'uid')) {
                    $tables[$mmTableName]->addColumn(
                        $this->quote('uid'),
                        Types::INTEGER,
                        [
                            'notnull' => true,
                            'unsigned' => true,
                            'autoincrement' => true,
                        ]
                    );
                    $tables[$mmTableName]->setPrimaryKey(['uid']);
                }

                if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'uid_local')) {
                    $tables[$mmTableName]->addColumn(
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
                    $tables[$mmTableName]->addIndex(['uid_local'], 'uid_local');
                }

                if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'uid_foreign')) {
                    $tables[$mmTableName]->addColumn(
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
                    $tables[$mmTableName]->addIndex(['uid_foreign'], 'uid_foreign');
                }

                if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'sorting')) {
                    $tables[$mmTableName]->addColumn(
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
                    $tables[$mmTableName]->addColumn(
                        $this->quote('sorting_foreign'),
                        Types::INTEGER,
                        [
                            'default' => 0,
                            'notnull' => true,
                            'unsigned' => true,
                        ]
                    );
                }

                $hasTablenamesFieldname = false;
                if ( // Local side of MM with MM_oppositeUsage forces tablenames and fieldname
                    !empty($fieldConfiguration['MM_oppositeUsage'])
                    || (
                        // MM group with allowed more than one table forces tablenames and fieldname
                        $field->isType(TableColumnType::GROUP) && !empty($fieldConfiguration['allowed'])
                        && (
                            count(GeneralUtility::trimExplode(',', $fieldConfiguration['allowed'])) > 1
                            || $fieldConfiguration['allowed'] === '*'
                        )
                    )
                ) {
                    $hasTablenamesFieldname = true;
                    // This local table can be the target of multiple foreign tables and table fields. The mm table
                    // thus needs two further fields to specify which foreign/table field combination links is used.
                    // Those are stored in two additional fields called "tablenames" and "fieldname".
                    if (!$this->isColumnDefinedForTable($tables, $mmTableName, 'tablenames')) {
                        $tables[$mmTableName]->addColumn(
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
                        $tables[$mmTableName]->addColumn(
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
                if (!$needsUid && $tables[$mmTableName]->getPrimaryKey() === null && $hasTablenamesFieldname) {
                    $tables[$mmTableName]->setPrimaryKey(['uid_local', 'uid_foreign', 'tablenames', 'fieldname']);
                } elseif (!$needsUid && $tables[$mmTableName]->getPrimaryKey() === null) {
                    $tables[$mmTableName]->setPrimaryKey(['uid_local', 'uid_foreign']);
                }
            }
        }
        return $tables;
    }

    /**
     * True if a column with a given name is defined within the incoming
     * array of Table's.
     *
     * @param array<non-empty-string, Table> $tables
     */
    protected function isColumnDefinedForTable(array $tables, string $tableName, string $fieldName): bool
    {
        return ($tables[$tableName] ?? null)?->hasColumn($fieldName) ?? false;
    }

    /**
     * True if an index with a given name is defined within the incoming
     * array of Table's.
     *
     * @param array<non-empty-string, Table> $tables
     */
    protected function isIndexDefinedForTable(array $tables, string $tableName, string $indexName): bool
    {
        return ($tables[$tableName] ?? null)?->hasIndex($indexName) ?? false;
    }

    protected function quote(string $identifier): string
    {
        return '`' . $identifier . '`';
    }
}
