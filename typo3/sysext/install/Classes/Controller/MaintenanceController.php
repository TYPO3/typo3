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

namespace TYPO3\CMS\Install\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\ClearCacheService;
use TYPO3\CMS\Install\Service\ClearTableService;
use TYPO3\CMS\Install\Service\LanguagePackService;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\Typo3tempFileService;

/**
 * Maintenance controller
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class MaintenanceController extends AbstractController
{
    /**
     * @var LateBootService
     */
    private $lateBootService;

    /**
     * @var ClearCacheService
     */
    private $clearCacheService;

    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    /**
     * @var PasswordHashFactory
     */
    private $passwordHashFactory;

    /**
     * @var Locales
     */
    private $locales;

    public function __construct(
        LateBootService $lateBootService,
        ClearCacheService $clearCacheService,
        ConfigurationManager $configurationManager,
        PasswordHashFactory $passwordHashFactory,
        Locales $locales
    ) {
        $this->lateBootService = $lateBootService;
        $this->clearCacheService = $clearCacheService;
        $this->configurationManager = $configurationManager;
        $this->passwordHashFactory = $passwordHashFactory;
        $this->locales = $locales;
    }
    /**
     * Main "show the cards" view
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function cardsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Maintenance/Cards.html');
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
        $this->clearCacheService->clearAll();
        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();
        $messageQueue = (new FlashMessageQueue('install'))->enqueue(
            new FlashMessage('Successfully cleared all caches and all available opcode caches.', 'Caches cleared')
        );
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Clear typo3temp files statistics action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function clearTypo3tempFilesStatsAction(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables(false);
        $typo3tempFileService = $container->get(Typo3tempFileService::class);

        $view = $this->initializeStandaloneView($request, 'Maintenance/ClearTypo3tempFiles.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'clearTypo3tempFilesToken' => $formProtection->generateToken('installTool', 'clearTypo3tempFiles'),
        ]);
        return new JsonResponse(
            [
                'success' => true,
                'stats' => $typo3tempFileService->getDirectoryStatistics(),
                'html' => $view->render(),
                'buttons' => [
                    [
                        'btnClass' => 'btn-default t3js-clearTypo3temp-stats',
                        'text' => 'Scan again',
                    ],
                ],
            ]
        );
    }

    /**
     * Clear typo3temp/assets or FAL processed Files
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function clearTypo3tempFilesAction(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables(false);
        $typo3tempFileService = $container->get(Typo3tempFileService::class);
        $messageQueue = new FlashMessageQueue('install');
        $folder = $request->getParsedBody()['install']['folder'];
        // storageUid is an optional post param if FAL storages should be cleaned
        $storageUid = $request->getParsedBody()['install']['storageUid'] ?? null;
        if ($storageUid === null) {
            $typo3tempFileService->clearAssetsFolder($folder);
            $messageQueue->enqueue(new FlashMessage('The directory "' . $folder . '" has been cleared successfully', 'Directory cleared'));
        } else {
            $storageUid = (int)$storageUid;
            // We have to get the stats before deleting files, otherwise we're not able to retrieve the amount of files anymore
            $stats = $typo3tempFileService->getStatsFromStorageByUid($storageUid);
            $failedDeletions = $typo3tempFileService->clearProcessedFiles($storageUid);
            if ($failedDeletions) {
                $messageQueue->enqueue(new FlashMessage(
                    'Failed to delete ' . $failedDeletions . ' processed files. See TYPO3 log (by default typo3temp/var/log/typo3_*.log)',
                    'Failed to delete files',
                    FlashMessage::ERROR
                ));
            } else {
                $messageQueue->enqueue(new FlashMessage(
                    sprintf('Removed %d files from directory "%s"', $stats['numberOfFiles'], $stats['directory']),
                    'Deleted processed files'
                ));
            }
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
        if (Environment::isComposerMode()) {
            $messageQueue->enqueue(new FlashMessage(
                'Skipped generating additional class loading information in Composer mode.',
                'Autoloader not dumped',
                FlashMessage::NOTICE
            ));
        } else {
            ClassLoadingInformation::dumpClassLoadingInformation();
            $messageQueue->enqueue(new FlashMessage(
                'Successfully dumped class loading information for extensions.',
                'Dumped autoloader'
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Get main database analyzer modal HTML
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function databaseAnalyzerAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Maintenance/DatabaseAnalyzer.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'databaseAnalyzerExecuteToken' => $formProtection->generateToken('installTool', 'databaseAnalyzerExecute'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-databaseAnalyzer-analyze',
                    'text' => 'Run database compare again',
                ], [
                    'btnClass' => 'btn-warning t3js-databaseAnalyzer-execute',
                    'text' => 'Apply selected changes',
                ],
            ],
        ]);
    }

    /**
     * Analyze current database situation
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function databaseAnalyzerAnalyzeAction(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables();
        $messageQueue = new FlashMessageQueue('install');
        $suggestions = [];
        try {
            $sqlReader = $container->get(SqlReader::class);
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
        } catch (StatementException $e) {
            $messageQueue->enqueue(new FlashMessage(
                $e->getMessage(),
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
        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables();
        $messageQueue = new FlashMessageQueue('install');
        $selectedHashes = $request->getParsedBody()['install']['hashes'] ?? [];
        if (empty($selectedHashes)) {
            $messageQueue->enqueue(new FlashMessage(
                'Please select any change by activating their respective checkboxes.',
                'No database changes selected',
                FlashMessage::WARNING
            ));
        } else {
            $sqlReader = $container->get(SqlReader::class);
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
                'Executed database updates',
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
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function clearTablesStatsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Maintenance/ClearTables.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'clearTablesClearToken' => $formProtection->generateToken('installTool', 'clearTablesClear'),
        ]);
        return new JsonResponse([
            'success' => true,
            'stats' => (new ClearTableService())->getTableStatistics(),
            'html' => $view->render(),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-clearTables-stats',
                    'text' => 'Scan again',
                ],
            ],
        ]);
    }

    /**
     * Truncate a specific table
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
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
            new FlashMessage('The table ' . $table . ' has been cleared.', 'Table cleared')
        );
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }
    /**
     * Create Admin Get Data action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function createAdminGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Maintenance/CreateAdmin.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'createAdminToken' => $formProtection->generateToken('installTool', 'createAdmin'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-createAdmin-create',
                    'text' => 'Create administrator user',
                ],
            ],
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
        $email = $request->getParsedBody()['install']['userEmail'] ?? '';
        $isSystemMaintainer = ((bool)$request->getParsedBody()['install']['userSystemMaintainer'] == '1') ? true : false;

        $messages = new FlashMessageQueue('install');

        if ($username === '') {
            $messages->enqueue(new FlashMessage(
                'No username given.',
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
                $hashInstance = $this->passwordHashFactory->getDefaultHashInstance('BE');
                $hashedPassword = $hashInstance->getHashedPassword($password);
                $adminUserFields = [
                    'username' => $username,
                    'password' => $hashedPassword,
                    'admin' => 1,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'crdate' => $GLOBALS['EXEC_TIME'],
                ];
                if (GeneralUtility::validEmail($email)) {
                    $adminUserFields['email'] = $email;
                }
                $connectionPool->getConnectionForTable('be_users')->insert('be_users', $adminUserFields);

                if ($isSystemMaintainer) {

                    // Get the new admin user uid just created
                    $newAdminUserUid = (int)$connectionPool->getConnectionForTable('be_users')->lastInsertId('be_users');

                    // Get the list of the existing systemMaintainer
                    $existingSystemMaintainersList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? [];

                    // Add the new admin user to the existing systemMaintainer list
                    $newSystemMaintainersList = $existingSystemMaintainersList;
                    $newSystemMaintainersList[] = $newAdminUserUid;

                    // Update the LocalConfiguration.php file with the new list
                    $this->configurationManager->setLocalConfigurationValuesByPathValuePairs(
                        ['SYS/systemMaintainers' => $newSystemMaintainersList]
                    );
                }

                $messages->enqueue(new FlashMessage(
                    'Administrator created',
                    'An administrator with username "' . $username . '" has been created successfully.'
                ));
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Entry action of language packs module gets
     * * list of available languages with details like active or not and last update
     * * list of loaded extensions
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function languagePacksGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Maintenance/LanguagePacks.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'languagePacksActivateLanguageToken' => $formProtection->generateToken('installTool', 'languagePacksActivateLanguage'),
            'languagePacksDeactivateLanguageToken' => $formProtection->generateToken('installTool', 'languagePacksDeactivateLanguage'),
            'languagePacksUpdatePackToken' => $formProtection->generateToken('installTool', 'languagePacksUpdatePack'),
            'languagePacksUpdateIsoTimesToken' => $formProtection->generateToken('installTool', 'languagePacksUpdateIsoTimes'),
        ]);
        // This action needs TYPO3_CONF_VARS for full GeneralUtility::getUrl() config
        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables(false, true);
        $languagePackService = $container->get(LanguagePackService::class);
        $extensions = $languagePackService->getExtensionLanguagePackDetails();
        return new JsonResponse([
            'success' => true,
            'languages' => $languagePackService->getLanguageDetails(),
            'extensions' => $extensions,
            'activeLanguages' => $languagePackService->getActiveLanguages(),
            'activeExtensions' => array_column($extensions, 'key'),
            'html' => $view->render(),
        ]);
    }

    /**
     * Activate a language and any possible dependency it may have
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function languagePacksActivateLanguageAction(ServerRequestInterface $request): ResponseInterface
    {
        $messageQueue = new FlashMessageQueue('install');
        $languagePackService = GeneralUtility::makeInstance(LanguagePackService::class);
        $availableLanguages = $languagePackService->getAvailableLanguages();
        $activeLanguages = $languagePackService->getActiveLanguages();
        $iso = $request->getParsedBody()['install']['iso'];
        $activateArray = [];
        foreach ($availableLanguages as $availableIso => $name) {
            if ($availableIso === $iso && !in_array($availableIso, $activeLanguages, true)) {
                $activateArray[] = $iso;
                $dependencies = $this->locales->getLocaleDependencies($availableIso);
                if (!empty($dependencies)) {
                    foreach ($dependencies as $dependency) {
                        if (!in_array($dependency, $activeLanguages, true)) {
                            $activateArray[] = $dependency;
                        }
                    }
                }
            }
        }
        if (!empty($activateArray)) {
            $activeLanguages = array_merge($activeLanguages, $activateArray);
            sort($activeLanguages);
            $this->configurationManager->setLocalConfigurationValueByPath(
                'EXTCONF/lang',
                ['availableLanguages' => $activeLanguages]
            );
            $activationArray = [];
            foreach ($activateArray as $activateIso) {
                $activationArray[] = $availableLanguages[$activateIso] . ' (' . $activateIso . ')';
            }
            $messageQueue->enqueue(
                new FlashMessage(
                    'These languages have been activated: ' . implode(', ', $activationArray)
                )
            );
        } else {
            $messageQueue->enqueue(
                new FlashMessage('Language with ISO code "' . $iso . '" not found or already active.', '', FlashMessage::ERROR)
            );
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Deactivate a language if no other active language depends on it
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function languagePacksDeactivateLanguageAction(ServerRequestInterface $request): ResponseInterface
    {
        $messageQueue = new FlashMessageQueue('install');
        $languagePackService = GeneralUtility::makeInstance(LanguagePackService::class);
        $availableLanguages = $languagePackService->getAvailableLanguages();
        $activeLanguages = $languagePackService->getActiveLanguages();
        $iso = $request->getParsedBody()['install']['iso'];
        if (empty($iso)) {
            throw new \RuntimeException('No iso code given', 1520109807);
        }
        $otherActiveLanguageDependencies = [];
        foreach ($activeLanguages as $activeLanguage) {
            if ($activeLanguage === $iso) {
                continue;
            }
            $dependencies = $this->locales->getLocaleDependencies($activeLanguage);
            if (in_array($iso, $dependencies, true)) {
                $otherActiveLanguageDependencies[] = $activeLanguage;
            }
        }
        if (!empty($otherActiveLanguageDependencies)) {
            // Error: Must disable dependencies first
            $dependentArray = [];
            foreach ($otherActiveLanguageDependencies as $dependency) {
                $dependentArray[] = $availableLanguages[$dependency] . ' (' . $dependency . ')';
            }
            $messageQueue->enqueue(
                new FlashMessage(
                    'Language "' . $availableLanguages[$iso] . ' (' . $iso . ')" can not be deactivated. These'
                    . ' other languages depend on it and need to be deactivated before:'
                    . implode(', ', $dependentArray),
                    '',
                    FlashMessage::ERROR
                )
            );
        } else {
            if (in_array($iso, $activeLanguages, true)) {
                // Deactivate this language
                $newActiveLanguages = [];
                foreach ($activeLanguages as $activeLanguage) {
                    if ($activeLanguage === $iso) {
                        continue;
                    }
                    $newActiveLanguages[] = $activeLanguage;
                }
                $this->configurationManager->setLocalConfigurationValueByPath(
                    'EXTCONF/lang',
                    ['availableLanguages' => $newActiveLanguages]
                );
                $messageQueue->enqueue(
                    new FlashMessage(
                        'Language "' . $availableLanguages[$iso] . ' (' . $iso . ')" has been deactivated'
                    )
                );
            } else {
                $messageQueue->enqueue(
                    new FlashMessage(
                        'Language "' . $availableLanguages[$iso] . ' (' . $iso . ')" has not been deactivated',
                        '',
                        FlashMessage::ERROR
                    )
                );
            }
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }

    /**
     * Update a pack of one extension and one language
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function languagePacksUpdatePackAction(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables(false, true);
        $iso = $request->getParsedBody()['install']['iso'];
        $key = $request->getParsedBody()['install']['extension'];

        $languagePackService = $container->get(LanguagePackService::class);

        return new JsonResponse([
            'success' => true,
            'packResult' => $languagePackService->languagePackDownload($key, $iso),
        ]);
    }

    /**
     * Set "last updated" time in registry for fully updated language packs.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function languagePacksUpdateIsoTimesAction(ServerRequestInterface $request): ResponseInterface
    {
        $isos = $request->getParsedBody()['install']['isos'];
        $languagePackService = GeneralUtility::makeInstance(LanguagePackService::class);
        $languagePackService->setLastUpdatedIsoCode($isos);

        // The cache manager is already instantiated in the install tool
        // with some hacked settings to disable caching of extbase and fluid.
        // We want a "fresh" object here to operate on a different cache setup.
        // cacheManager implements SingletonInterface, so the only way to get a "fresh"
        // instance is by circumventing makeInstance and using new directly!
        $cacheManager = new CacheManager();
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        $cacheManager->getCache('l10n')->flush();

        return new JsonResponse(['success' => true]);
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
            ->executeStatement();
        $messageQueue = new FlashMessageQueue('install');
        $messageQueue->enqueue(new FlashMessage(
            'Preferences of all backend users have been reset',
            'Reset preferences of all backend users'
        ));
        return new JsonResponse([
            'success' => true,
            'status' => $messageQueue,
        ]);
    }
}
