<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action;

/**
 * Handle important actions
 */
class ImportantActions extends Action\AbstractAction
{
    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        $actionMessages = [];
        if (isset($this->postValues['set']['changeInstallToolPassword'])) {
            $actionMessages[] = $this->changeInstallToolPassword();
        }
        if (isset($this->postValues['set']['createAdministrator'])) {
            $actionMessages[] = $this->createAdministrator();
        }

        // Database analyzer handling
        if (isset($this->postValues['set']['databaseAnalyzerExecute'])
            || isset($this->postValues['set']['databaseAnalyzerAnalyze'])
        ) {
            $this->loadExtLocalconfDatabaseAndExtTables();
        }
        if (isset($this->postValues['set']['databaseAnalyzerExecute'])) {
            $actionMessages = array_merge($actionMessages, $this->databaseAnalyzerExecute());
        }
        if (isset($this->postValues['set']['databaseAnalyzerAnalyze'])) {
            try {
                $actionMessages[] = $this->databaseAnalyzerAnalyze();
            } catch (\TYPO3\CMS\Core\Database\Schema\Exception\StatementException $e) {
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                $message->setTitle('Database analysis failed');
                $message->setMessage($e->getMessage());
                $actionMessages[] = $message;
            }
        }

        $this->view->assign('actionMessages', $actionMessages);

        $operatingSystem = TYPO3_OS === 'WIN' ? 'Windows' : 'Unix';

        $opcodeCacheService = GeneralUtility::makeInstance(OpcodeCacheService::class);

        /** @var \TYPO3\CMS\Install\Service\CoreUpdateService $coreUpdateService */
        $coreUpdateService = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\CoreUpdateService::class);
        /** @var  $coreVersionService \TYPO3\CMS\Install\Service\CoreVersionService */
        $coreVersionService = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\CoreVersionService::class);
        $this->view
            ->assign('enableCoreUpdate', $coreUpdateService->isCoreUpdateEnabled())
            ->assign('composerMode', Bootstrap::usesComposerClassLoading())
            ->assign('isInstalledVersionAReleasedVersion', $coreVersionService->isInstalledVersionAReleasedVersion())
            ->assign('isSymLinkedCore', is_link(PATH_site . 'typo3_src'))
            ->assign('operatingSystem', $operatingSystem)
            ->assign('cgiDetected', GeneralUtility::isRunningOnCgiServerApi())
            ->assign('extensionCompatibilityTesterProtocolFile', GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3temp/assets/ExtensionCompatibilityTester.txt')
            ->assign('extensionCompatibilityTesterErrorProtocolFile', GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3temp/assets/ExtensionCompatibilityTesterErrors.json')
            ->assign('extensionCompatibilityTesterMessages', $this->getExtensionCompatibilityTesterMessages())
            ->assign('listOfOpcodeCaches', $opcodeCacheService->getAllActive());

        $connectionInfos = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($connectionPool->getConnectionNames() as $connectionName) {
            $connection = $connectionPool->getConnectionByName($connectionName);
            $connectionParameters = $connection->getParams();
            $connectionInfo = [
                'connectionName' => $connectionName,
                'version' => $connection->getServerVersion(),
                'databaseName' => $connection->getDatabase(),
                'username' => $connection->getUsername(),
                'host' => $connection->getHost(),
                'port' => $connection->getPort(),
                'socket' => $connectionParameters['unix_socket'] ?? '',
                'numberOfTables' => count($connection->getSchemaManager()->listTableNames()),
                'numberOfMappedTables' => 0,
            ];
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'])
                && is_array($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'])
            ) {
                // Count number of array keys having $connectionName as value
                $connectionInfo['numberOfMappedTables'] = count(array_intersect(
                    $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'],
                    [$connectionName]
                ));
            }
            $connectionInfos[] = $connectionInfo;
        }

        $this->view->assign('connections', $connectionInfos);

        return $this->view->render();
    }

    /**
     * Set new password if requested
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function changeInstallToolPassword()
    {
        $values = $this->postValues['values'];
        if ($values['newInstallToolPassword'] !== $values['newInstallToolPasswordCheck']) {
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $message->setTitle('Install tool password not changed');
            $message->setMessage('Given passwords do not match.');
        } elseif (strlen($values['newInstallToolPassword']) < 8) {
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $message->setTitle('Install tool password not changed');
            $message->setMessage('Given password must be at least eight characters long.');
        } else {
            /** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
            $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
            $configurationManager->setLocalConfigurationValueByPath(
                'BE/installToolPassword',
                $this->getHashedPassword($values['newInstallToolPassword'])
            );
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\OkStatus::class);
            $message->setTitle('Install tool password changed');
        }
        return $message;
    }

    /**
     * Create administrator user
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function createAdministrator()
    {
        $values = $this->postValues['values'];
        $username = preg_replace('/\\s/i', '', $values['newUserUsername']);
        $password = $values['newUserPassword'];
        $passwordCheck = $values['newUserPasswordCheck'];

        if (strlen($username) < 1) {
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $message->setTitle('Administrator user not created');
            $message->setMessage('No valid username given.');
        } elseif ($password !== $passwordCheck) {
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $message->setTitle('Administrator user not created');
            $message->setMessage('Passwords do not match.');
        } elseif (strlen($password) < 8) {
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $message->setTitle('Administrator user not created');
            $message->setMessage('Password must be at least eight characters long.');
        } else {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $userExists = $connectionPool->getConnectionForTable('be_users')
                ->count(
                    'uid',
                    'be_users',
                    ['username' => $username]
                );

            if ($userExists) {
                /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                $message->setTitle('Administrator user not created');
                $message->setMessage('A user with username "' . $username . '" exists already.');
            } else {
                $hashedPassword = $this->getHashedPassword($password);
                $adminUserFields = [
                    'username' => $username,
                    'password' => $hashedPassword,
                    'admin' => 1,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'crdate' => $GLOBALS['EXEC_TIME']
                ];
                $connectionPool->getConnectionForTable('be_users')
                    ->insert('be_users', $adminUserFields);
                /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\OkStatus::class);
                $message->setTitle('Administrator created with username "' . $username . '".');
            }
        }

        return $message;
    }

    /**
     * Execute database migration
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    protected function databaseAnalyzerExecute()
    {
        $messages = [];

        // Early return in case no update was selected
        if (empty($this->postValues['values'])) {
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\WarningStatus::class);
            $message->setTitle('No database changes selected');
            $messages[] = $message;
            return $messages;
        }

        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);

        $statementHashesToPerform = $this->postValues['values'];

        $results = $schemaMigrationService->migrate($sqlStatements, $statementHashesToPerform);

        // Create error flash messages if any
        foreach ($results as $errorMessage) {
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $message->setTitle('Database update failed');
            $message->setMessage('Error: ' . $errorMessage);
            $messages[] = $message;
        }

        $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\OkStatus::class);
        $message->setTitle('Executed database updates');
        $messages[] = $message;

        return $messages;
    }

    /**
     * "Compare" action of analyzer
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \RuntimeException
     */
    protected function databaseAnalyzerAnalyze()
    {
        $databaseAnalyzerSuggestion = [];

        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);

        $addCreateChange = $schemaMigrationService->getUpdateSuggestions($sqlStatements);
        // Aggregate the per-connection statements into one flat array
        $addCreateChange = array_merge_recursive(...array_values($addCreateChange));

        if (!empty($addCreateChange['create_table'])) {
            $databaseAnalyzerSuggestion['addTable'] = [];
            foreach ($addCreateChange['create_table'] as $hash => $statement) {
                $databaseAnalyzerSuggestion['addTable'][$hash] = [
                    'hash' => $hash,
                    'statement' => $statement,
                ];
            }
        }
        if (!empty($addCreateChange['add'])) {
            $databaseAnalyzerSuggestion['addField'] = [];
            foreach ($addCreateChange['add'] as $hash => $statement) {
                $databaseAnalyzerSuggestion['addField'][$hash] = [
                    'hash' => $hash,
                    'statement' => $statement,
                ];
            }
        }
        if (!empty($addCreateChange['change'])) {
            $databaseAnalyzerSuggestion['change'] = [];
            foreach ($addCreateChange['change'] as $hash => $statement) {
                $databaseAnalyzerSuggestion['change'][$hash] = [
                    'hash' => $hash,
                    'statement' => $statement,
                ];
                if (isset($addCreateChange['change_currentValue'][$hash])) {
                    $databaseAnalyzerSuggestion['change'][$hash]['current'] = $addCreateChange['change_currentValue'][$hash];
                }
            }
        }

        // Difference from current to expected
        $dropRename = $schemaMigrationService->getUpdateSuggestions($sqlStatements, true);
        // Aggregate the per-connection statements into one flat array
        $dropRename = array_merge_recursive(...array_values($dropRename));
        if (!empty($dropRename['change_table'])) {
            $databaseAnalyzerSuggestion['renameTableToUnused'] = [];
            foreach ($dropRename['change_table'] as $hash => $statement) {
                $databaseAnalyzerSuggestion['renameTableToUnused'][$hash] = [
                    'hash' => $hash,
                    'statement' => $statement,
                ];
                if (!empty($dropRename['tables_count'][$hash])) {
                    $databaseAnalyzerSuggestion['renameTableToUnused'][$hash]['count'] = $dropRename['tables_count'][$hash];
                }
            }
        }
        if (!empty($dropRename['change'])) {
            $databaseAnalyzerSuggestion['renameTableFieldToUnused'] = [];
            foreach ($dropRename['change'] as $hash => $statement) {
                $databaseAnalyzerSuggestion['renameTableFieldToUnused'][$hash] = [
                    'hash' => $hash,
                    'statement' => $statement,
                ];
            }
        }
        if (!empty($dropRename['drop'])) {
            $databaseAnalyzerSuggestion['deleteField'] = [];
            foreach ($dropRename['drop'] as $hash => $statement) {
                $databaseAnalyzerSuggestion['deleteField'][$hash] = [
                    'hash' => $hash,
                    'statement' => $statement,
                ];
            }
        }
        if (!empty($dropRename['drop_table'])) {
            $databaseAnalyzerSuggestion['deleteTable'] = [];
            foreach ($dropRename['drop_table'] as $hash => $statement) {
                $databaseAnalyzerSuggestion['deleteTable'][$hash] = [
                    'hash' => $hash,
                    'statement' => $statement,
                ];
                if (!empty($dropRename['tables_count'][$hash])) {
                    $databaseAnalyzerSuggestion['deleteTable'][$hash]['count'] = $dropRename['tables_count'][$hash];
                }
            }
        }

        $this->view->assign('databaseAnalyzerSuggestion', $databaseAnalyzerSuggestion);

        /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
        $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\OkStatus::class);
        $message->setTitle('Analyzed current database');

        return $message;
    }
}
