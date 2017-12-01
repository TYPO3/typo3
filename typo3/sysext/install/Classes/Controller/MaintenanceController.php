<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\ClearCacheService;
use TYPO3\CMS\Install\Service\ClearTableService;
use TYPO3\CMS\Install\Service\Typo3tempFileService;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;

/**
 * Maintenance controller
 */
class MaintenanceController extends AbstractController
{
    /**
     * Main "show the cards" view
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function cardsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Maintenance/Cards.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'clearAllCacheOpcodeCaches' => (new OpcodeCacheService())->getAllActive(),
            'clearTablesClearToken' => $formProtection->generateToken('installTool', 'clearTablesClear'),
            'clearTypo3tempFilesToken' => $formProtection->generateToken('installTool', 'clearTypo3tempFiles'),
            'createAdminToken' => $formProtection->generateToken('installTool', 'createAdmin'),
            'databaseAnalyzerExecuteToken' => $formProtection->generateToken('installTool', 'databaseAnalyzerExecute'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Clear cache framework and opcode caches
     *
     * @return ResponseInterface
     */
    public function cacheClearAllAction(): ResponseInterface
    {
        GeneralUtility::makeInstance(ClearCacheService::class)->clearAll();
        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();
        $messageQueue = (new FlashMessageQueue('install'))->enqueue(
            new FlashMessage('Successfully cleared all caches and all available opcode caches.')
        );
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Clear typo3temp files statistics action
     *
     * @return ResponseInterface
     */
    public function clearTypo3tempFilesStatsAction(): ResponseInterface
    {
        return new JsonResponse(
            [
                'success' => true,
                'stats' => (new Typo3tempFileService())->getDirectoryStatistics(),
            ]
        );
    }

    /**
     * Clear Processed Files
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function clearTypo3tempFilesAction(ServerRequestInterface $request): ResponseInterface
    {
        $messageQueue = new FlashMessageQueue('install');
        $typo3tempFileService = new Typo3tempFileService();
        $folder = $request->getParsedBody()['install']['folder'];
        if ($folder === '_processed_') {
            $failedDeletions = $typo3tempFileService->clearProcessedFiles();
            if ($failedDeletions) {
                $messageQueue->enqueue(new FlashMessage(
                    'Failed to delete ' . $failedDeletions . ' processed files. See TYPO3 log (by default typo3temp/var/logs/typo3_*.log)',
                    '',
                    FlashMessage::ERROR
                ));
            } else {
                $messageQueue->enqueue(new FlashMessage('Cleared processed files'));
            }
        } else {
            $typo3tempFileService->clearAssetsFolder($folder);
            $messageQueue->enqueue(new FlashMessage('Cleared files in "' . $folder . '" folder'));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Dump autoload information
     *
     * @return ResponseInterface
     */
    public function dumpAutoloadAction(): ResponseInterface
    {
        $messageQueue = new FlashMessageQueue('install');
        if (Bootstrap::usesComposerClassLoading()) {
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Skipped generating additional class loading information in composer mode.',
                FlashMessage::NOTICE
            ));
        } else {
            ClassLoadingInformation::dumpClassLoadingInformation();
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Successfully dumped class loading information for extensions.'
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Analyze current database situation
     *
     * @return ResponseInterface
     */
    public function databaseAnalyzerAnalyzeAction(): ResponseInterface
    {
        $this->loadExtLocalconfDatabaseAndExtTables();
        $messageQueue = new FlashMessageQueue('install');

        $suggestions = [];
        try {
            $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
            $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $addCreateChange = $schemaMigrationService->getUpdateSuggestions($sqlStatements);

            // Aggregate the per-connection statements into one flat array
            $addCreateChange = array_merge_recursive(...array_values($addCreateChange));
            if (!empty($addCreateChange['create_table'])) {
                $suggestion = [
                    'key' => 'addTable',
                    'label' => 'Add tables',
                    'enabled' => true,
                    'children' => [],
                ];
                foreach ($addCreateChange['create_table'] as $hash => $statement) {
                    $suggestion['children'][] = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($addCreateChange['add'])) {
                $suggestion = [
                    'key' => 'addField',
                    'label' => 'Add fields to tables',
                    'enabled' => true,
                    'children' => [],
                ];
                foreach ($addCreateChange['add'] as $hash => $statement) {
                    $suggestion['children'][] = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($addCreateChange['change'])) {
                $suggestion = [
                    'key' => 'change',
                    'label' => 'Change fields',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($addCreateChange['change'] as $hash => $statement) {
                    $child = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                    if (isset($addCreateChange['change_currentValue'][$hash])) {
                        $child['current'] = $addCreateChange['change_currentValue'][$hash];
                    }
                    $suggestion['children'][] = $child;
                }
                $suggestions[] = $suggestion;
            }

            // Difference from current to expected
            $dropRename = $schemaMigrationService->getUpdateSuggestions($sqlStatements, true);

            // Aggregate the per-connection statements into one flat array
            $dropRename = array_merge_recursive(...array_values($dropRename));
            if (!empty($dropRename['change_table'])) {
                $suggestion = [
                    'key' => 'renameTableToUnused',
                    'label' => 'Remove tables (rename with prefix)',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($dropRename['change_table'] as $hash => $statement) {
                    $child = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                    if (!empty($dropRename['tables_count'][$hash])) {
                        $child['rowCount'] = $dropRename['tables_count'][$hash];
                    }
                    $suggestion['children'][] = $child;
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($dropRename['change'])) {
                $suggestion = [
                    'key' => 'renameTableFieldToUnused',
                    'label' => 'Remove unused fields (rename with prefix)',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($dropRename['change'] as $hash => $statement) {
                    $suggestion['children'][] = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($dropRename['drop'])) {
                $suggestion = [
                    'key' => 'deleteField',
                    'label' => 'Drop fields (really!)',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($dropRename['drop'] as $hash => $statement) {
                    $suggestion['children'][] = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($dropRename['drop_table'])) {
                $suggestion = [
                    'key' => 'deleteTable',
                    'label' => 'Drop tables (really!)',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($dropRename['drop_table'] as $hash => $statement) {
                    $child = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                    if (!empty($dropRename['tables_count'][$hash])) {
                        $child['rowCount'] = $dropRename['tables_count'][$hash];
                    }
                    $suggestion['children'][] = $child;
                }
                $suggestions[] = $suggestion;
            }

            $messageQueue->enqueue(new FlashMessage(
                '',
                'Analyzed current database'
            ));
        } catch (StatementException $e) {
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Database analysis failed',
                FlashMessage::ERROR
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Apply selected database changes
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function databaseAnalyzerExecuteAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->loadExtLocalconfDatabaseAndExtTables();
        $messageQueue = new FlashMessageQueue('install');
        $selectedHashes = $request->getParsedBody()['install']['hashes'] ?? [];
        if (empty($selectedHashes)) {
            $messageQueue->enqueue(new FlashMessage(
                '',
                'No database changes selected',
                FlashMessage::WARNING
            ));
        } else {
            $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
            $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $statementHashesToPerform = array_flip($selectedHashes);
            $results = $schemaMigrationService->migrate($sqlStatements, $statementHashesToPerform);
            // Create error flash messages if any
            foreach ($results as $errorMessage) {
                $messageQueue->enqueue(new FlashMessage(
                    'Error: ' . $errorMessage,
                    'Database update failed',
                    FlashMessage::ERROR
                ));
            }
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Executed database updates'
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Clear table overview statistics action
     *
     * @return ResponseInterface
     */
    public function clearTablesStatsAction(): ResponseInterface
    {
        return new JsonResponse([
            'success' => true,
            'stats' => (new ClearTableService())->getTableStatistics(),
        ]);
    }

    /**
     * Truncate a specific table
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function clearTablesClearAction(ServerRequestInterface $request): ResponseInterface
    {
        $table = $request->getParsedBody()['install']['table'];
        if (empty($table)) {
            throw new \RuntimeException(
                'No table name given',
                1501944076
            );
        }
        (new ClearTableService())->clearSelectedTable($table);
        $messageQueue = (new FlashMessageQueue('install'))->enqueue(
            new FlashMessage('Cleared table')
        );
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue
        ]);
    }

    /**
     * Create a backend administrator from given username and password
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function createAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $username = preg_replace('/\\s/i', '', $request->getParsedBody()['install']['userName']);
        $password = $request->getParsedBody()['install']['userPassword'];
        $passwordCheck = $request->getParsedBody()['install']['userPasswordCheck'];
        $isSystemMaintainer = ((bool)$request->getParsedBody()['install']['userSystemMaintainer'] == '1') ? true : false;

        $messages = new FlashMessageQueue('install');

        if (strlen($username) < 1) {
            $messages->enqueue(new FlashMessage(
                'No valid username given.',
                'Administrator user not created',
                FlashMessage::ERROR
            ));
        } elseif ($password !== $passwordCheck) {
            $messages->enqueue(new FlashMessage(
                'Passwords do not match.',
                'Administrator user not created',
                FlashMessage::ERROR
            ));
        } elseif (strlen($password) < 8) {
            $messages->enqueue(new FlashMessage(
                'Password must be at least eight characters long.',
                'Administrator user not created',
                FlashMessage::ERROR
            ));
        } else {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $userExists = $connectionPool->getConnectionForTable('be_users')
                ->count(
                    'uid',
                    'be_users',
                    ['username' => $username]
                );
            if ($userExists) {
                $messages->enqueue(new FlashMessage(
                    'A user with username "' . $username . '" exists already.',
                    'Administrator user not created',
                    FlashMessage::ERROR
                ));
            } else {
                $saltFactory = SaltFactory::getSaltingInstance(null, 'BE');
                $hashedPassword = $saltFactory->getHashedPassword($password);
                $adminUserFields = [
                    'username' => $username,
                    'password' => $hashedPassword,
                    'admin' => 1,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'crdate' => $GLOBALS['EXEC_TIME']
                ];
                $connectionPool->getConnectionForTable('be_users')->insert('be_users', $adminUserFields);

                if ($isSystemMaintainer) {

                    // Get the new admin user uid juste created
                    $newAdminUserUid = (int)$connectionPool->getConnectionForTable('be_users')->lastInsertId('be_users');

                    // Get the list of the existing systemMaintainer
                    $existingSystemMaintainersList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? [];

                    // Add the new admin user to the existing systemMaintainer list
                    $newSystemMaintainersList = $existingSystemMaintainersList;
                    $newSystemMaintainersList[] = $newAdminUserUid;

                    // Update the LocalConfiguration.php file with the new list
                    $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
                    $configurationManager->setLocalConfigurationValuesByPathValuePairs(
                        [ 'SYS/systemMaintainers' => $newSystemMaintainersList ]
                    );
                }

                $messages->enqueue(new FlashMessage(
                    '',
                    'Administrator created with username "' . $username . '".'
                ));
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Set 'uc' field of all backend users to empty string
     *
     * @return ResponseInterface
     */
    public function resetBackendUserUcAction(): ResponseInterface
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users')
            ->update('be_users')
            ->set('uc', '')
            ->execute();
        $messageQueue = new FlashMessageQueue('install');
        $messageQueue->enqueue(new FlashMessage(
            '',
            'Reset all backend users preferences'
        ));
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue
        ]);
    }
}
