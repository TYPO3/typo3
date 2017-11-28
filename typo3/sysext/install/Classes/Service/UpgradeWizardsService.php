<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Service;

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

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Cache\DatabaseSchemaService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;
use TYPO3\CMS\Install\Updates\RowUpdater\RowUpdaterInterface;

/**
 * Service class helping managing upgrade wizards
 */
class UpgradeWizardsService
{
    /**
     * Force creation / update of caching framework tables that are needed by some update wizards
     *
     * @return array List of executed statements
     */
    public function silentCacheFrameworkTableSchemaMigration(): array
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $cachingFrameworkDatabaseSchemaService = GeneralUtility::makeInstance(DatabaseSchemaService::class);
        $createTableStatements = $sqlReader->getStatementArray(
            $cachingFrameworkDatabaseSchemaService->getCachingFrameworkRequiredDatabaseSchema()
        );
        $statements = [];
        if (!empty($createTableStatements)) {
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $statements = $schemaMigrationService->install($createTableStatements);
        }
        return $statements;
    }

    /**
     * @return array List of wizards marked as done in registry
     */
    public function listOfWizardsDoneInRegistry(): array
    {
        $wizardsDoneInRegistry = [];
        $registry = GeneralUtility::makeInstance(Registry::class);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $className) {
            if ($registry->get('installUpdate', $className, false)) {
                $wizardInstance = GeneralUtility::makeInstance($className);
                $wizardsDoneInRegistry[] = [
                    'class' => $className,
                    'identifier' => $identifier,
                    'title' => $wizardInstance->getTitle(),
                ];
            }
        }
        return $wizardsDoneInRegistry;
    }

    /**
     * @return array List of row updaters marked as done in registry
     * @throws \RuntimeException
     */
    public function listOfRowUpdatersDoneInRegistry(): array
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $rowUpdatersDoneClassNames = $registry->get('installUpdateRows', 'rowUpdatersDone', []);
        $rowUpdatersDone = [];
        foreach ($rowUpdatersDoneClassNames as $rowUpdaterClassName) {
            // Silently skip non existing DatabaseRowsUpdateWizards
            if (!class_exists($rowUpdaterClassName)) {
                continue;
            }
            /** @var RowUpdaterInterface $rowUpdater */
            $rowUpdater = GeneralUtility::makeInstance($rowUpdaterClassName);
            if (!$rowUpdater instanceof RowUpdaterInterface) {
                throw new \RuntimeException(
                    'Row updater must implement RowUpdaterInterface',
                    1484152906
                );
            }
            $rowUpdatersDone[] = [
                'class' => $rowUpdaterClassName,
                'identifier' => $rowUpdaterClassName,
                'title' => $rowUpdater->getTitle(),
            ];
        }
        return $rowUpdatersDone;
    }

    /**
     * Mark one wizard as undone. This can be a "casual" wizard
     * or a single "row updater".
     *
     * @param string $identifier Wizard or RowUpdater identifier
     * @return bool True if wizard has been marked as undone
     */
    public function markWizardUndoneInRegistry(string $identifier): bool
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $aWizardHasBeenMarkedUndone = false;
        $wizardsDoneList = $this->listOfWizardsDoneInRegistry();
        foreach ($wizardsDoneList as $wizard) {
            if ($wizard['identifier'] === $identifier) {
                $aWizardHasBeenMarkedUndone = true;
                $registry->set('installUpdate', $wizard['class'], 0);
            }
        }
        if (!$aWizardHasBeenMarkedUndone) {
            $rowUpdatersDoneList = $this->listOfRowUpdatersDoneInRegistry();
            $registryArray = $registry->get('installUpdateRows', 'rowUpdatersDone', []);
            foreach ($rowUpdatersDoneList as $rowUpdater) {
                if ($rowUpdater['identifier'] === $identifier) {
                    $aWizardHasBeenMarkedUndone = true;
                    foreach ($registryArray as $rowUpdaterMarkedAsDonePosition => $rowUpdaterMarkedAsDone) {
                        if ($rowUpdaterMarkedAsDone === $rowUpdater['class']) {
                            unset($registryArray[$rowUpdaterMarkedAsDonePosition]);
                            break;
                        }
                    }
                    $registry->set('installUpdateRows', 'rowUpdatersDone', $registryArray);
                }
            }
        }
        return $aWizardHasBeenMarkedUndone;
    }

    /**
     * Get a list of tables, single columns and indexes to add.
     *
     * @return array Array with possible keys "tables", "columns", "indexes"
     */
    public function getBlockingDatabaseAdds(): array
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $databaseDefinitions = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());

        $schemaMigrator = GeneralUtility::makeInstance(SchemaMigrator::class);
        $databaseDifferences = $schemaMigrator->getSchemaDiffs($databaseDefinitions);

        $adds = [];
        foreach ($databaseDifferences as $schemaDiff) {
            foreach ($schemaDiff->newTables as $newTable) {
                /** @var Table $newTable*/
                if (!is_array($adds['tables'])) {
                    $adds['tables'] = [];
                }
                $adds['tables'][] = [
                    'table' => $newTable->getName(),
                ];
            }
            foreach ($schemaDiff->changedTables as $changedTable) {
                foreach ($changedTable->addedColumns as $addedColumn) {
                    /** @var Column $addedColumn */
                    if (!is_array($adds['columns'])) {
                        $adds['columns'] = [];
                    }
                    $adds['columns'][] = [
                        'table' => $changedTable->name,
                        'field' => $addedColumn->getName(),
                    ];
                }
                foreach ($changedTable->addedIndexes as $addedIndex) {
                    /** $var Index $addedIndex */
                    if (!is_array($adds['indexes'])) {
                        $adds['indexes'] = [];
                    }
                    $adds['indexes'][] = [
                        'table' => $changedTable->name,
                        'index' => $addedIndex->getName(),
                    ];
                }
            }
        }

        return $adds;
    }

    /**
     * Add missing tables, indexes and fields to DB.
     */
    public function addMissingTablesAndFields()
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $databaseDefinitions = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $schemaMigrator = GeneralUtility::makeInstance(SchemaMigrator::class);
        $schemaMigrator->install($databaseDefinitions, true);
    }

    /**
     * True if DB main charset on mysql is utf8
     *
     * @return bool True if charset is ok
     */
    public function isDatabaseCharsetUtf8(): bool
    {
        /** @var \TYPO3\CMS\Core\Database\Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        $isDefaultConnectionMysql = ($connection->getDatabasePlatform() instanceof MySqlPlatform);

        if (!$isDefaultConnectionMysql) {
            // Not tested on non mysql
            $charsetOk = true;
        } else {
            $queryBuilder = $connection->createQueryBuilder();
            $charset = (string)$queryBuilder->select('DEFAULT_CHARACTER_SET_NAME')
                ->from('information_schema.SCHEMATA')
                ->where(
                    $queryBuilder->expr()->eq(
                        'SCHEMA_NAME',
                        $queryBuilder->createNamedParameter($connection->getDatabase(), \PDO::PARAM_STR)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetchColumn();
            // check if database charset is utf-8, also allows utf8mb4
            $charsetOk = strpos($charset, 'utf8') === 0;
        }
        return $charsetOk;
    }

    /**
     * Set default connection MySQL database charset to utf8.
     * Should be called only *if* default database connection is actually MySQL
     */
    public function setDatabaseCharsetUtf8()
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $sql = 'ALTER DATABASE ' . $connection->quoteIdentifier($connection->getDatabase()) . ' CHARACTER SET utf8';
        $connection->exec($sql);
    }

    /**
     * Get list of registered upgrade wizards.
     *
     * @return array List of upgrade wizards in correct order with detail information
     */
    public function getUpgradeWizardsList(): array
    {
        $wizards = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $class) {
            /** @var AbstractUpdate $wizardInstance */
            $wizardInstance = GeneralUtility::makeInstance($class);

            // $explanation is changed by reference in Update objects!
            $explanation = '';
            $wizardInstance->checkForUpdate($explanation);

            $wizards[] = [
                'class' => $class,
                'identifier' => $identifier,
                'title' => $wizardInstance->getTitle(),
                'shouldRenderWizard' => $wizardInstance->shouldRenderWizard(),
                'markedDoneInRegistry' => GeneralUtility::makeInstance(Registry::class)->get('installUpdate', $class, false),
                'explanation' => $explanation,
            ];
        }
        return $wizards;
    }

    /**
     * Execute the "get user input" step of a wizard
     *
     * @param string $identifier
     * @return array
     * @throws \RuntimeException
     */
    public function getWizardUserInput(string $identifier): array
    {
        // Validate identifier exists in upgrade wizard list
        if (empty($identifier)
            || !array_key_exists($identifier, $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'])
        ) {
            throw new \RuntimeException(
                'No valid wizard identifier given',
                1502721731
            );
        }
        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];
        $updateObject = GeneralUtility::makeInstance($class);
        $wizardHtml = '';
        if (method_exists($updateObject, 'getUserInput')) {
            $wizardHtml = $updateObject->getUserInput('install[values][' . $identifier . ']');
        }

        $result = [
            'identifier' => $identifier,
            'title' => $updateObject->getTitle(),
            'wizardHtml' => $wizardHtml,
        ];

        return $result;
    }

    /**
     * Execute a single update wizard
     *
     * @param string $identifier
     * @param array $postValues
     * @return FlashMessageQueue
     * @throws \RuntimeException
     */
    public function executeWizard(string $identifier, array $postValues = []): FlashMessageQueue
    {
        if (empty($identifier)
            || !array_key_exists($identifier, $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'])
        ) {
            throw new \RuntimeException(
                'No valid wizard identifier given',
                1502721732
            );
        }
        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];
        $updateObject = GeneralUtility::makeInstance($class);

        $wizardData = [
            'identifier' => $identifier,
            'title' => $updateObject->getTitle(),
        ];

        $messages = new FlashMessageQueue('install');
        // $wizardInputErrorMessage is given as reference to wizard object!
        $wizardInputErrorMessage = '';
        if (method_exists($updateObject, 'checkUserInput') && !$updateObject->checkUserInput($wizardInputErrorMessage)) {
            $messages->enqueue(new FlashMessage(
                $wizardInputErrorMessage ?: 'Something went wrong!',
                'Input parameter broken',
                FlashMessage::ERROR
            ));
        } else {
            if (!method_exists($updateObject, 'performUpdate')) {
                throw new \RuntimeException(
                    'No performUpdate method in update wizard with identifier ' . $identifier,
                    1371035200
                );
            }

            // Both variables are used by reference in performUpdate()
            $customOutput = '';
            $databaseQueries = [];
            $performResult = $updateObject->performUpdate($databaseQueries, $customOutput);

            if ($performResult) {
                $messages->enqueue(new FlashMessage(
                    '',
                    'Update successful'
                ));
            } else {
                $messages->enqueue(new FlashMessage(
                    $customOutput,
                    'Update failed!',
                    FlashMessage::ERROR
                ));
            }

            if ($postValues['values']['showDatabaseQueries'] == 1) {
                $wizardData['queries'] = $databaseQueries;
            }
        }

        return $messages;
    }
}
