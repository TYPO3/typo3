<?php
namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\LoadTcaService;

/**
 * Migrate values for database records having columns
 * using "l10n_mode" set to "mergeIfNotBlank".
 */
class DatabaseL10nModeUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate values in database records having "l10n_mode" set';

    /**
     * Field names that previously had a migrated l10n_mode setting in TCA.
     *
     * @var array
     */
    protected $migratedL10nCoreFieldNames = [
        'sys_category' => [
            'starttime' => 'mergeIfNotBlank',
            'endtime' => 'mergeIfNotBlank',
        ],
        'sys_file_metadata' => [
            'location_country' => 'mergeIfNotBlank',
            'location_region' => 'mergeIfNotBlank',
            'location_city' => 'mergeIfNotBlank',
        ],
    ];

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }
        if (count($this->getL10nModePayload()) === 0) {
            $this->markWizardAsDone();
            return false;
        }

        $description = 'Clones values for database records having columns using'
            . ' "l10n_mode" set to "mergeIfNotBlank".';
        return true;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$databaseQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        $payload = $this->getL10nModePayload();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $fakeAdminUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $fakeAdminUser->user = ['admin' => 1];

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php'])) {
            $dataHandlerHooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php'];
            unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']);
        }

        foreach ($payload as $tableName => $tablePayload) {
            $fields = $tablePayload['fields'];
            $fieldNames = array_keys($fields);
            $fieldTypes = $tablePayload['fieldTypes'];
            $sourceFieldName = $tablePayload['sourceFieldName'];

            foreach ($tablePayload['sources'] as $source => $ids) {
                $sourceTableName = $tableName;
                if ($tableName === 'pages_language_overlay') {
                    $sourceTableName = 'pages';
                }
                $sourceRow = $this->getRow($sourceTableName, $source);

                foreach ($ids as $id) {
                    $updateValues = [];

                    $row = $this->getRow($tableName, $id);
                    foreach ($row as $fieldName => $fieldValue) {
                        if (!in_array($fieldName, $fieldNames)) {
                            continue;
                        }

                        if (
                            // default
                            empty($fieldTypes[$fieldName])
                            && trim((string)$fieldValue) === ''
                            // group types (basically as comma seprated values)
                            || $fieldTypes[$fieldName] === 'group'
                            && (
                                $fieldValue === ''
                                || $fieldValue === null
                                || (string)$fieldValue === '0'
                            )
                        ) {
                            $updateValues[$fieldName] = $sourceRow[$fieldName];
                        }
                        // inline types, but only file references
                        if (
                            !empty($fieldTypes[$fieldName])
                            && $fieldTypes[$fieldName] === 'inline/FAL'
                        ) {
                            $parentId = (!empty($row['t3ver_oid']) ? $row['t3ver_oid'] : $source);
                            $commandMap = [
                                $sourceTableName => [
                                    $parentId => [
                                        'inlineLocalizeSynchronize' => [
                                            'action' => 'localize',
                                            'language' => $row[$sourceFieldName],
                                            'field' => $fieldName,
                                        ]
                                    ]
                                ]
                            ];
                            $fakeAdminUser->workspace = $row['t3ver_wsid'];
                            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                            $dataHandler->start([], $commandMap, $fakeAdminUser);
                            $dataHandler->process_cmdmap();
                        }
                    }

                    if (empty($updateValues)) {
                        continue;
                    }

                    $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
                    foreach ($updateValues as $updateFieldName => $updateValue) {
                        $queryBuilder->set($updateFieldName, $updateValue);
                    }

                    $queryBuilder
                        ->update($tableName)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                            )
                        )
                        ->execute();
                    $databaseQueries[] = $queryBuilder->getSQL();
                }
            }
        }

        if (!empty($dataHandlerHooks)) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php'] = $dataHandlerHooks;
        }

        $this->markWizardAsDone();
        return true;
    }

    /**
     * Retrieves field names grouped per table name having "l10n_mode" set
     * to a relevant value that shall be migrated in database records.
     *
     * Resulting array is structured like this:
     * + table name
     *   + fields: [field a, field b, ...]
     *   + sources
     *     + source uid: [localization uid, localization uid, ...]
     *
     * @return array
     */
    protected function getL10nModePayload(): array
    {
        $payload = [];

        $loadTcaService = GeneralUtility::makeInstance(LoadTcaService::class);
        $loadTcaService->loadExtensionTablesWithoutMigration();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        foreach ($GLOBALS['TCA'] as $tableName => $tableDefinition) {
            if (
                empty($tableDefinition['columns'])
                || !is_array($tableDefinition['columns'])
                || empty($tableDefinition['ctrl']['languageField'])
                || empty($tableDefinition['ctrl']['transOrigPointerField'])
            ) {
                continue;
            }

            $fields = [];
            $fieldTypes = [];
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfiguration) {
                if (
                    empty($fieldConfiguration['l10n_mode'])
                    && !empty($this->migratedL10nCoreFieldNames[$tableName][$fieldName])
                ) {
                    $fieldConfiguration['l10n_mode'] = $this->migratedL10nCoreFieldNames[$tableName][$fieldName];
                }

                if (
                    empty($fieldConfiguration['l10n_mode'])
                    || empty($fieldConfiguration['config']['type'])
                ) {
                    continue;
                }
                if ($fieldConfiguration['l10n_mode'] === 'mergeIfNotBlank') {
                    $fields[$fieldName] = $fieldConfiguration;
                }
            }

            if (empty($fields)) {
                continue;
            }

            $parentQueryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
            $parentQueryBuilder->getRestrictions()->removeAll();
            $parentQueryBuilder->from($tableName);

            $predicates = [];
            foreach ($fields as $fieldName => $fieldConfiguration) {
                $predicates[] = $parentQueryBuilder->expr()->comparison(
                    $parentQueryBuilder->expr()->trim($fieldName),
                    ExpressionBuilder::EQ,
                    $parentQueryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                );
                $predicates[] = $parentQueryBuilder->expr()->eq(
                    $fieldName,
                    $parentQueryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                );

                if (empty($fieldConfiguration['config']['type'])) {
                    continue;
                }

                if ($fieldConfiguration['config']['type'] === 'group') {
                    $fieldTypes[$fieldName] = 'group';
                    $predicates[] = $parentQueryBuilder->expr()->isNull(
                        $fieldName
                    );
                    $predicates[] = $parentQueryBuilder->expr()->eq(
                        $fieldName,
                        $parentQueryBuilder->createNamedParameter('0', \PDO::PARAM_STR)
                    );
                }
                if (
                    $fieldConfiguration['config']['type'] === 'inline'
                    && !empty($fieldConfiguration['config']['foreign_field'])
                    && $fieldConfiguration['config']['foreign_field'] === 'uid_foreign'
                    && !empty($fieldConfiguration['config']['foreign_table'])
                    && $fieldConfiguration['config']['foreign_table'] === 'sys_file_reference'
                ) {
                    $fieldTypes[$fieldName] = 'inline/FAL';

                    $childQueryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
                    $childQueryBuilder->getRestrictions()->removeAll();
                    $childExpression = $childQueryBuilder
                        ->count('uid')
                        ->from('sys_file_reference')
                        ->andWhere(
                            $childQueryBuilder->expr()->eq(
                                'sys_file_reference.uid_foreign',
                                $parentQueryBuilder->getConnection()->quoteIdentifier($tableName . '.uid')
                            ),
                            $childQueryBuilder->expr()->eq(
                                'sys_file_reference.tablenames',
                                $parentQueryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                            ),
                            $childQueryBuilder->expr()->eq(
                                'sys_file_reference.fieldname',
                                $parentQueryBuilder->createNamedParameter($fieldName, \PDO::PARAM_STR)
                            )
                        );

                    $predicates[] = $parentQueryBuilder->expr()->comparison(
                        '(' . $childExpression->getSQL() . ')',
                        ExpressionBuilder::GT,
                        $parentQueryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    );
                }
            }

            $sourceFieldName = $tableDefinition['ctrl']['transOrigPointerField'];
            $selectFieldNames = ['uid', $sourceFieldName];

            if (!empty($tableDefinition['ctrl']['versioningWS'])) {
                $selectFieldNames = array_merge(
                    $selectFieldNames,
                    ['t3ver_wsid', 't3ver_oid']
                );
            }

            $statement = $parentQueryBuilder
                ->select(...$selectFieldNames)
                ->andWhere(
                    $parentQueryBuilder->expr()->gt(
                        $tableDefinition['ctrl']['languageField'],
                        $parentQueryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $parentQueryBuilder->expr()->gt(
                        $sourceFieldName,
                        $parentQueryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $parentQueryBuilder->expr()->orX(...$predicates)
                )
                ->execute();

            foreach ($statement as $row) {
                $source = $row[$sourceFieldName];
                $payload[$tableName]['sources'][$source][] = $row['uid'];
            }

            if (
                empty($payload[$tableName]['fields'])
                && !empty($payload[$tableName]['sources'])
            ) {
                $payload[$tableName]['fields'] = $fields;
                $payload[$tableName]['fieldTypes'] = $fieldTypes;
                $payload[$tableName]['sourceFieldName'] = $sourceFieldName;
            }
        }

        return $payload;
    }

    /**
     * @param string $tableName
     * @param int $id
     * @return array
     */
    protected function getRow(string $tableName, int $id)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();

        $statement = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                )
            )
            ->execute();

        return $statement->fetch();
    }
}
