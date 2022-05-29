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

namespace TYPO3\CMS\Install\Service;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\StreamOutput;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\RowUpdater\RowUpdaterInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Service class helping managing upgrade wizards
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class UpgradeWizardsService
{
    /**
     * @var StreamOutput
     */
    private $output;

    public function __construct()
    {
        $fileName = 'php://temp';
        if (($stream = fopen($fileName, 'wb')) === false) {
            throw new \RuntimeException('Unable to open stream "' . $fileName . '"', 1598341765);
        }
        $this->output = new StreamOutput($stream, Output::VERBOSITY_NORMAL, false);
    }

    /**
     * @return array List of wizards marked as done in registry
     */
    public function listOfWizardsDone(): array
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
    public function listOfRowUpdatersDone(): array
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
     * @throws \RuntimeException
     */
    public function markWizardUndone(string $identifier): bool
    {
        $this->assertIdentifierIsValid($identifier);

        $registry = GeneralUtility::makeInstance(Registry::class);
        $aWizardHasBeenMarkedUndone = false;
        foreach ($this->listOfWizardsDone() as $wizard) {
            if ($wizard['identifier'] === $identifier) {
                $aWizardHasBeenMarkedUndone = true;
                $registry->set('installUpdate', $wizard['class'], 0);
            }
        }
        if (!$aWizardHasBeenMarkedUndone) {
            $rowUpdatersDoneList = $this->listOfRowUpdatersDone();
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
                /** @var Table $newTable */
                if (!is_array($adds['tables'] ?? false)) {
                    $adds['tables'] = [];
                }
                $adds['tables'][] = [
                    'table' => $newTable->getName(),
                ];
            }
            foreach ($schemaDiff->changedTables as $changedTable) {
                foreach ($changedTable->addedColumns as $addedColumn) {
                    /** @var Column $addedColumn */
                    if (!is_array($adds['columns'] ?? false)) {
                        $adds['columns'] = [];
                    }
                    $adds['columns'][] = [
                        'table' => $changedTable->name,
                        'field' => $addedColumn->getName(),
                    ];
                }
                foreach ($changedTable->addedIndexes as $addedIndex) {
                    /** $var Index $addedIndex */
                    if (!is_array($adds['indexes'] ?? false)) {
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
    public function addMissingTablesAndFields(): array
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $databaseDefinitions = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $schemaMigrator = GeneralUtility::makeInstance(SchemaMigrator::class);
        return $schemaMigrator->install($databaseDefinitions, true);
    }

    /**
     * True if DB main charset on mysql is utf8
     *
     * @return bool True if charset is ok
     */
    public function isDatabaseCharsetUtf8(): bool
    {
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
                ->executeQuery()
                ->fetchOne();
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
     * Get list of registered upgrade wizards not marked done.
     *
     * @return array List of upgrade wizards in correct order with detail information
     */
    public function getUpgradeWizardsList(): array
    {
        $wizards = [];
        foreach (array_keys($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']) as $identifier) {
            $identifier = (string)$identifier;
            if ($this->isWizardDone($identifier)) {
                continue;
            }

            $wizards[] = $this->getWizardInformationByIdentifier($identifier);
        }
        return $wizards;
    }

    public function getWizardInformationByIdentifier(string $identifier): array
    {
        if (class_exists($identifier)) {
            $class = $identifier;
        } else {
            $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];
        }
        /** @var UpgradeWizardInterface $wizardInstance */
        $wizardInstance = GeneralUtility::makeInstance($class);
        $explanation = '';

        // $explanation is changed by reference in Update objects!
        $shouldRenderWizard = false;
        if ($wizardInstance instanceof UpgradeWizardInterface) {
            if ($wizardInstance instanceof ChattyInterface) {
                $wizardInstance->setOutput($this->output);
            }
            $shouldRenderWizard = $wizardInstance->updateNecessary();
            $explanation = $wizardInstance->getDescription();
        }

        return [
            'class' => $class,
            'identifier' => $identifier,
            'title' => $wizardInstance->getTitle(),
            'shouldRenderWizard' => $shouldRenderWizard,
            'explanation' => $explanation,
        ];
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
        $this->assertIdentifierIsValid($identifier);

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];
        $updateObject = GeneralUtility::makeInstance($class);
        $wizardHtml = '';
        if ($updateObject instanceof UpgradeWizardInterface && $updateObject instanceof ConfirmableInterface) {
            $markup = [];
            $radioAttributes = [
                'type' => 'radio',
                'class' => 'btn-check',
                'name' => 'install[values][' . $updateObject->getIdentifier() . '][install]',
                'value' => '0',
            ];
            $markup[] = '<div class="panel panel-danger">';
            $markup[] = '   <div class="panel-heading">';
            $markup[] = htmlspecialchars($updateObject->getConfirmation()->getTitle());
            $markup[] = '    </div>';
            $markup[] = '    <div class="panel-body">';
            $markup[] = '        <p>' . nl2br(htmlspecialchars($updateObject->getConfirmation()->getMessage())) . '</p>';
            $markup[] = '        <div class="btn-group">';
            if (!$updateObject->getConfirmation()->isRequired()) {
                $markup[] = '        <input ' . GeneralUtility::implodeAttributes($radioAttributes, true) . ' checked id="upgrade-wizard-deny">';
                $markup[] = '        <label class="btn btn-default" for="upgrade-wizard-deny">' . $updateObject->getConfirmation()->getDeny() . '</label>';
            }
            $radioAttributes['value'] = '1';
            $markup[] = '            <input ' . GeneralUtility::implodeAttributes($radioAttributes, true) . ' id="upgrade-wizard-confirm">';
            $markup[] = '            <label class="btn btn-default" for="upgrade-wizard-confirm">' . $updateObject->getConfirmation()->getConfirm() . '</label>';
            $markup[] = '        </div>';
            $markup[] = '    </div>';
            $markup[] = '</div>';
            $wizardHtml = implode('', $markup);
        }

        $result = [
            'identifier' => $identifier,
            'title' => $updateObject->getTitle(),
            'description' => $updateObject->getDescription(),
            'wizardHtml' => $wizardHtml,
        ];

        return $result;
    }

    /**
     * Execute a single update wizard
     *
     * @param string $identifier
     * @return FlashMessageQueue
     * @throws \RuntimeException
     */
    public function executeWizard(string $identifier): FlashMessageQueue
    {
        $performResult = false;
        $this->assertIdentifierIsValid($identifier);

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];
        $updateObject = GeneralUtility::makeInstance($class);

        if ($updateObject instanceof ChattyInterface) {
            $updateObject->setOutput($this->output);
        }
        $messages = new FlashMessageQueue('install');

        if ($updateObject instanceof UpgradeWizardInterface) {
            $requestParams = GeneralUtility::_GP('install');
            if ($updateObject instanceof ConfirmableInterface) {
                // value is set in request but is empty
                $isSetButEmpty = isset($requestParams['values'][$updateObject->getIdentifier()]['install'])
                    && empty($requestParams['values'][$updateObject->getIdentifier()]['install']);

                $checkValue = (int)$requestParams['values'][$updateObject->getIdentifier()]['install'];

                if ($checkValue === 1) {
                    // confirmation = yes, we do the update
                    $performResult = $updateObject->executeUpdate();
                } elseif ($updateObject->getConfirmation()->isRequired()) {
                    // confirmation = no, but is required, we do *not* the update and fail
                    $performResult = false;
                } elseif ($isSetButEmpty) {
                    // confirmation = no, but it is *not* required, we do *not* the update, but mark the wizard as done
                    $this->output->writeln('No changes applied, marking wizard as done.');
                    // confirmation was set to "no"
                    $performResult = true;
                }
            } else {
                // confirmation yes or non-confirmable
                $performResult = $updateObject->executeUpdate();
            }
        }

        $stream = $this->output->getStream();
        rewind($stream);
        if ($performResult) {
            if ($updateObject instanceof UpgradeWizardInterface && !($updateObject instanceof RepeatableInterface)) {
                // mark wizard as done if it's not repeatable and was successful
                $this->markWizardAsDone($updateObject->getIdentifier());
            }
            $messages->enqueue(
                new FlashMessage(
                    (string)stream_get_contents($stream),
                    'Update successful'
                )
            );
        } else {
            $messages->enqueue(
                new FlashMessage(
                    (string)stream_get_contents($stream),
                    'Update failed!',
                    FlashMessage::ERROR
                )
            );
        }
        return $messages;
    }

    /**
     * Marks some wizard as being "seen" so that it not shown again.
     * Writes the info in LocalConfiguration.php
     *
     * @param string $identifier
     * @throws \RuntimeException
     */
    public function markWizardAsDone(string $identifier): void
    {
        $this->assertIdentifierIsValid($identifier);

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];
        GeneralUtility::makeInstance(Registry::class)->set('installUpdate', $class, 1);
    }

    /**
     * Checks if this wizard has been "done" before
     *
     * @param string $identifier
     * @return bool TRUE if wizard has been done before, FALSE otherwise
     * @throws \RuntimeException
     */
    public function isWizardDone(string $identifier): bool
    {
        $this->assertIdentifierIsValid($identifier);

        $class = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];
        return (bool)GeneralUtility::makeInstance(Registry::class)->get('installUpdate', $class, false);
    }

    /**
     * Validate identifier exists in upgrade wizard list
     *
     * @param string $identifier
     * @throws \RuntimeException
     */
    protected function assertIdentifierIsValid(string $identifier): void
    {
        if ($identifier === '') {
            throw new \RuntimeException('Empty upgrade wizard identifier given', 1650579934);
        }
        if (
            !isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier])
            && !is_subclass_of($identifier, RowUpdaterInterface::class)
        ) {
            throw new \RuntimeException(
                'The upgrade wizard identifier "' . $identifier . '" must either be found in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'ext/install\'][\'update\'] or it must implement TYPO3\CMS\Install\Updates\RowUpdater\RowUpdaterInterface',
                1650546252
            );
        }
    }
}
