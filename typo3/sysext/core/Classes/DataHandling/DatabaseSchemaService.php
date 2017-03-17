<?php
namespace TYPO3\CMS\Core\DataHandling;

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

/**
 * This service provides the sql schema database records.
 */
class DatabaseSchemaService
{
    const TABLE_TEMPLATE = 'CREATE TABLE %s (' . LF . '%s' . LF . ');';
    const FIELD_L10N_STATE_TEMPLATE = '  l10n_state text';

    /**
     * Add l10n_state field to tables that provide localization
     *
     * @param array $sqlString Current SQL statements to be executed
     * @return array Modified arguments of SqlExpectedSchemaService::tablesDefinitionIsBeingBuilt signal
     */
    public function getLocalizationRequiredDatabaseSchema(array $sqlString)
    {
        $additionalSqlString = $this->buildLocalizationRequiredDatabaseSchema();
        if (!empty($additionalSqlString)) {
            $sqlString[] = $additionalSqlString;
        }
        return ['sqlString' => $sqlString];
    }

    /**
     * Add l10n_state field to tables that provide localization
     *
     * @param array $sqlString Current SQL statements to be executed
     * @param string $extensionKey Extension key currently processed
     * @return array Modified arguments of InstallUtility::tablesDefinitionIsBeingBuilt signal
     */
    public function getLocalizationRequiredDatabaseSchemaForExtension(array $sqlString, string $extensionKey)
    {
        $additionalSqlString = $this->buildLocalizationRequiredDatabaseSchema();
        if (!empty($additionalSqlString)) {
            $sqlString[] = $additionalSqlString;
        }
        return ['sqlString' => $sqlString, 'extensionKey' => $extensionKey];
    }

    /**
     * Add l10n_state field to tables that provide localization
     *
     * @return string Localization fields database schema
     */
    protected function buildLocalizationRequiredDatabaseSchema()
    {
        $tableSchemas = [];

        foreach ($GLOBALS['TCA'] as $tableName => $tableDefinition) {
            if (
                empty($tableDefinition['columns'])
                || empty($tableDefinition['ctrl']['languageField'])
                || empty($tableDefinition['ctrl']['transOrigPointerField'])
            ) {
                continue;
            }

            $fieldSchemas = [];
            $fieldSchemas[] = static::FIELD_L10N_STATE_TEMPLATE;

            $tableSchemas[] = sprintf(
                static::TABLE_TEMPLATE,
                $tableName,
                implode(',' . LF, $fieldSchemas)
            );
        }

        return implode(LF, $tableSchemas);
    }
}
