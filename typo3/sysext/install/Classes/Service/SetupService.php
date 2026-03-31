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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Exception\ImportFailedException;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Install\Command\BackendUserGroupType;
use TYPO3\CMS\Install\Configuration\FeatureManager;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\Service\Exception\ConfigurationDirectoryDoesNotExistException;
use TYPO3\CMS\Install\Service\Exception\ConfigurationFileAlreadyExistsException;
use TYPO3\CMS\Install\WebserverType;

/**
 * Service class helping to manage parts of the setup process (set configuration,
 * create backend user, create a basic site, create default backend groups, etc.)
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
readonly class SetupService
{
    public function __construct(
        private ConfigurationManager $configurationManager,
        private SiteWriter $siteWriter,
        private YamlFileLoader $yamlFileLoader,
        private FailsafePackageManager $packageManager,
    ) {}

    /**
     * @param WebserverType $webserverType
     * @return FlashMessage[]
     */
    public function createDirectoryStructure(WebserverType $webserverType): array
    {
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFixMessageQueue = $folderStructureFactory->getStructure($webserverType)->fix();
        return $structureFixMessageQueue->getAllMessages(ContextualFeedbackSeverity::ERROR);
    }

    public function setSiteName(string $name): bool
    {
        return $this->configurationManager->setLocalConfigurationValueByPath('SYS/sitename', $name);
    }

    /**
     * Creates a site configuration with one language "English" which is the de-facto default language for TYPO3 in general.
     *
     * @param string[] $dependencies Site set identifiers to add as dependencies
     * @throws SiteConfigurationWriteException
     */
    private function createSiteConfiguration(string $identifier, int $rootPageId, string $siteUrl, array $dependencies = []): void
    {
        // Create a default site configuration called "main" as best practice
        $this->siteWriter->createNewBasicSite($identifier, $rootPageId, $siteUrl, $dependencies);
    }

    /**
     * Returns all available packages that ship initialisation data (data.xml or data.t3d)
     * which can be imported during installation.
     *
     * @return array<string, array{packageKey: string, title: string, description: string}> Keyed by package key
     */
    public function getAvailableDistributions(): array
    {
        $distributions = [];
        foreach ($this->packageManager->getAvailablePackages() as $packageKey => $package) {
            $packagePath = $package->getPackagePath();
            if (!file_exists($packagePath . 'Initialisation/data.xml')
                && !file_exists($packagePath . 'Initialisation/data.t3d')
            ) {
                continue;
            }
            $metaData = $package->getPackageMetaData();
            $distributions[$packageKey] = [
                'packageKey' => $packageKey,
                'title' => $metaData->getTitle() ?: $packageKey,
                'description' => $metaData->getDescription() ?: '',
            ];
        }
        return $distributions;
    }

    /**
     * This function returns a salted hashed key for new backend user password and install tool password.
     *
     * This method is executed during installation *before* the preset did set up proper hash method
     * selection in LocalConfiguration. So PasswordHashFactory is not usable at this point. We thus loop through
     * the default hash mechanisms and select the first one that works. The preset calculation of step
     * executeDefaultConfigurationAction() basically does the same later.
     *
     * @param string $password Plain text password
     * @return string Hashed password
     */
    private function getHashedPassword(string $password): string
    {
        $okHashMethods = [
            Argon2iPasswordHash::class,
            Argon2idPasswordHash::class,
            BcryptPasswordHash::class,
        ];
        foreach ($okHashMethods as $className) {
            /** @var PasswordHashInterface $instance */
            $instance = GeneralUtility::makeInstance($className);
            if ($instance->isAvailable()) {
                return $instance->getHashedPassword($password);
            }
        }
        // Should never happen since bcrypt is always available
        throw new InvalidPasswordHashException('No suitable hash method found', 1533988846);
    }

    /**
     * Create a backend user with maintainer and admin flag
     * set by default, because the initial user always requires
     * these flags to grant full permissions to the system.
     */
    public function createUser(string $username, string $password, string $email = ''): void
    {
        $adminUserFields = [
            'username' => $username,
            'password' => $this->getHashedPassword($password),
            'email' => GeneralUtility::validEmail($email) ? $email : '',
            'admin' => 1,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
        ];

        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_users');
        $databaseConnection->insert('be_users', $adminUserFields);
        $adminUserUid = (int)$databaseConnection->lastInsertId();

        $maintainerIds = $this->configurationManager->getConfigurationValueByPath('SYS/systemMaintainers') ?? [];
        sort($maintainerIds);
        $maintainerIds[] = $adminUserUid;
        $this->configurationManager->setLocalConfigurationValuesByPathValuePairs([
            'SYS/systemMaintainers' => array_unique($maintainerIds),
        ]);
    }

    public function setInstallToolPassword(string $password): bool
    {
        return $this->configurationManager->setLocalConfigurationValuesByPathValuePairs([
            'BE/installToolPassword' => $this->getHashedPassword($password),
        ]);
    }

    /**
     * @throws ConfigurationFileAlreadyExistsException
     * @throws ConfigurationDirectoryDoesNotExistException
     */
    public function prepareSystemSettings(bool $forceOverwrite = false): void
    {
        $configurationFileLocation = $this->configurationManager->getSystemConfigurationFileLocation();
        $configDir = dirname($configurationFileLocation);
        if (!is_dir($configDir)) {
            throw new ConfigurationDirectoryDoesNotExistException(
                'Configuration directory ' . $this->makePathRelativeToProjectDirectory($configDir) . ' does not exist!',
                1700401774,
            );
        }
        if (@is_file($configurationFileLocation)) {
            if (!$forceOverwrite) {
                throw new ConfigurationFileAlreadyExistsException(
                    'Configuration file ' . $this->makePathRelativeToProjectDirectory($configurationFileLocation) . ' already exists!',
                    1669747685,
                );
            }
            unlink($configurationFileLocation);
        }
        $this->configurationManager->createLocalConfigurationFromFactoryConfiguration();
        $randomKey = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(96);
        $this->configurationManager->setLocalConfigurationValueByPath('SYS/encryptionKey', $randomKey);
        $extensionConfiguration = new ExtensionConfiguration();
        $extensionConfiguration->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();

        // Get best matching configuration presets
        $featureManager = new FeatureManager();
        $configurationValues = $featureManager->getBestMatchingConfigurationForAllFeatures();
        $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationValues);

        // In non Composer mode, create a PackageStates.php with all packages activated marked as "part of factory default"
        $this->packageManager->recreatePackageStatesFileIfMissing(true);
    }

    /**
     * Create a root page and site configuration with appropriate site set dependencies, if available
     */
    public function createSite(string $siteIdentifier, string $siteUrl): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnectionForPages = $connectionPool->getConnectionForTable('pages');
        $databaseConnectionForPages->insert(
            'pages',
            [
                'pid' => 0,
                'crdate' => time(),
                'tstamp' => time(),
                'title' => 'Home',
                'slug' => '/',
                'doktype' => 1,
                'is_siteroot' => 1,
                'perms_userid' => 1,
                'perms_groupid' => 1,
                'perms_user' => 31,
                'perms_group' => 31,
                'perms_everybody' => 1,
            ]
        );
        $pageId = (int)$databaseConnectionForPages->lastInsertId();

        $databaseConnectionForContent = $connectionPool->getConnectionForTable('tt_content');
        $databaseConnectionForContent->insert(
            'tt_content',
            [
                'pid' => $pageId,
                'crdate' => time(),
                'tstamp' => time(),
                'CType' => 'text',
                'colPos' => 0,
                'header' => 'Welcome to your default website',
                'bodytext' => '<p>This website is made with <a href="https://typo3.org" target="_blank">TYPO3</a>.</p>',
            ]
        );

        $dependencies = [];
        if ($this->packageManager->isPackageActive('fluid_styled_content')) {
            $dependencies = ['typo3/fluid-styled-content', 'typo3/fluid-styled-content-css'];
        }
        $this->createSiteConfiguration($siteIdentifier, $pageId, $siteUrl, $dependencies);
        $this->writeSiteSetupTypoScript($siteIdentifier);

        return $pageId;
    }

    /**
     * Import a distribution's initialisation data (pages, content elements, files)
     * using the impexp import mechanism. Requires a fully booted container because
     * the import relies on TCA, DataHandler and other runtime services.
     *
     * @param ContainerInterface $container The fully booted (non-failsafe) DI container
     * @param string $packageKey The extension key of the distribution to import
     */
    public function importDistributionData(ContainerInterface $container, string $packageKey): void
    {
        $package = $this->packageManager->getAvailablePackages()[$packageKey] ?? null;
        if ($package === null) {
            return;
        }

        $importFile = $package->getPackagePath() . 'Initialisation/data.xml';
        if (!file_exists($importFile)) {
            $importFile = $package->getPackagePath() . 'Initialisation/data.t3d';
            if (!file_exists($importFile)) {
                return;
            }
        }

        // Bootstrap a backend user context required by the import engine.
        // Use the first admin user created during installation.
        $previousBackendUser = $GLOBALS['BE_USER'] ?? null;
        $previousLanguageService = $GLOBALS['LANG'] ?? null;

        try {
            $backendUser = new BackendUserAuthentication();
            $backendUser->user = $this->getFirstAdminUser();
            $backendUser->workspace = 0;
            $GLOBALS['BE_USER'] = $backendUser;

            $GLOBALS['LANG'] = $container->get(LanguageServiceFactory::class)->create('en');

            $import = $container->get(Import::class);
            $import->setPid(0);
            $import->loadFile($importFile);
            $import->importData();
        } catch (ImportFailedException) {
            // importData() performs all writes first, then throws if there were
            // non-critical errors. The data is already imported at this point,
            // so we can safely continue with the installation.
        } finally {
            $GLOBALS['BE_USER'] = $previousBackendUser;
            $GLOBALS['LANG'] = $previousLanguageService;
        }
    }

    /**
     * Writes a setup.typoscript file to the site configuration directory with basic PAGE rendering.
     */
    private function writeSiteSetupTypoScript(string $siteIdentifier): void
    {
        $siteConfigPath = Environment::getConfigPath() . '/sites/' . $siteIdentifier;
        $typoScriptContent = <<<'TYPOSCRIPT'
page = PAGE
page.10 = COA
page.10.stdWrap.wrap = <div style="max-width: 800px; margin: 2em auto;">|</div>
page.10.10 = TEXT
page.10.10.value (
  <div style="width: 300px;">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 42"><path d="M60.2 14.4v27h-3.8v-27h-6.7v-3.3h17.1v3.3h-6.6zm20.2 12.9v14h-3.9v-14l-7.7-16.2h4.1l5.7 12.2 5.7-12.2h3.9l-7.8 16.2zm19.5 2.6h-3.6v11.4h-3.8V11.1s3.7-.3 7.3-.3c6.6 0 8.5 4.1 8.5 9.4 0 6.5-2.3 9.7-8.4 9.7m.4-16c-2.4 0-4.1.3-4.1.3v12.6h4.1c2.4 0 4.1-1.6 4.1-6.3 0-4.4-1-6.6-4.1-6.6m21.5 27.7c-7.1 0-9-5.2-9-15.8 0-10.2 1.9-15.1 9-15.1s9 4.9 9 15.1c.1 10.6-1.8 15.8-9 15.8m0-27.7c-3.9 0-5.2 2.6-5.2 12.1 0 9.3 1.3 12.4 5.2 12.4 3.9 0 5.2-3.1 5.2-12.4 0-9.4-1.3-12.1-5.2-12.1m19.9 27.7c-2.1 0-5.3-.6-5.7-.7v-3.1c1 .2 3.7.7 5.6.7 2.2 0 3.6-1.9 3.6-5.2 0-3.9-.6-6-3.7-6H138V24h3.1c3.5 0 3.7-3.6 3.7-5.3 0-3.4-1.1-4.8-3.2-4.8-1.9 0-4.1.5-5.3.7v-3.2c.5-.1 3-.7 5.2-.7 4.4 0 7 1.9 7 8.3 0 2.9-1 5.5-3.3 6.3 2.6.2 3.8 3.1 3.8 7.3 0 6.6-2.5 9-7.3 9"/><path fill="#FF8700" d="M31.7 28.8c-.6.2-1.1.2-1.7.2-5.2 0-12.9-18.2-12.9-24.3 0-2.2.5-3 1.3-3.6C12 1.9 4.3 4.2 1.9 7.2 1.3 8 1 9.1 1 10.6c0 9.5 10.1 31 17.3 31 3.3 0 8.8-5.4 13.4-12.8M28.4.5c6.6 0 13.2 1.1 13.2 4.8 0 7.6-4.8 16.7-7.2 16.7-4.4 0-9.9-12.1-9.9-18.2C24.5 1 25.6.5 28.4.5"/></svg>
  </div>
)
page.10.20 = CONTENT
page.10.20 {
    table = tt_content
    select {
        orderBy = sorting
        where = {#colPos}=0
    }
}
TYPOSCRIPT;
        GeneralUtility::writeFile($siteConfigPath . '/setup.typoscript', $typoScriptContent);
    }

    /**
     * Initializes backend user group presets. Currently hard-coded to editor and advanced editor.
     * When more backend user group presets are added, please refactor (maybe DTO).
     *
     * @return string[]
     */
    public function createBackendUserGroups(bool $createEditor = true, bool $createAdvancedEditor = true, bool $force = false): array
    {
        $messages = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->createFileMount('1:/user_upload/', 'User Upload');
        if ($createEditor) {
            if (!$force && $this->countBackendGroupsByTitle($connectionPool, BackendUserGroupType::EDITOR->value) > 0) {
                $messages[] = sprintf('Group "%s" could not be created. A backend user group of that name already exists and option --force was not set. ', BackendUserGroupType::EDITOR->value);
            } else {
                $connectionPool->getConnectionForTable('be_groups')->insert(
                    'be_groups',
                    [
                        'title' => BackendUserGroupType::EDITOR->value,
                        'description' => 'Editors have access to basic content element and modules in the backend.',
                        'tstamp' => time(),
                        'crdate' => time(),
                    ]
                );
                $editorGroupUid = (int)$connectionPool->getConnectionForTable('be_groups')->lastInsertId();
                $editorPermissionPreset = $this->yamlFileLoader->load('EXT:install/Configuration/PermissionPreset/be_groups_editor.yaml');
                $this->applyPermissionPreset($editorPermissionPreset, 'be_groups', $editorGroupUid);
            }
        }
        if ($createAdvancedEditor) {
            if (!$force && $this->countBackendGroupsByTitle($connectionPool, BackendUserGroupType::ADVANCED_EDITOR->value) > 0) {
                $messages[] = sprintf('Group "%s" could not be created. A backend user group of that name already exists and option --force was not set. ', BackendUserGroupType::ADVANCED_EDITOR->value);
            } else {
                $connectionPool->getConnectionForTable('be_groups')->insert(
                    'be_groups',
                    [
                        'title' => BackendUserGroupType::ADVANCED_EDITOR->value,
                        'description' => 'Advanced Editors have access to all content elements and non administrative modules in the backend.',
                        'tstamp' => time(),
                        'crdate' => time(),
                    ]
                );
                $advancedEditorGroupUid = (int)$connectionPool->getConnectionForTable('be_groups')->lastInsertId();
                $advancedEditorPermissionPreset = $this->yamlFileLoader->load('EXT:install/Configuration/PermissionPreset/be_groups_advanced_editor.yaml');
                $this->applyPermissionPreset($advancedEditorPermissionPreset, 'be_groups', $advancedEditorGroupUid);
            }
        }
        return $messages;
    }

    private function applyPermissionPreset(array $permissionPreset, string $table, int $recordId): void
    {
        $mappedPermissions = [];
        if (isset($permissionPreset['dbMountpoints']) && is_array($permissionPreset['dbMountpoints'])) {
            $mappedPermissions['db_mountpoints'] = implode(',', $permissionPreset['dbMountpoints']);
        }
        if (isset($permissionPreset['fileMountpoints']) && is_array($permissionPreset['fileMountpoints'])) {
            $fileMountIds = [];
            foreach ($permissionPreset['fileMountpoints'] as $fileMountpoint) {
                $fileMountpointId = $this->getFileMount($fileMountpoint);
                if ($fileMountpointId > 0) {
                    $fileMountIds[] = $fileMountpointId;
                }
            }
            $mappedPermissions['file_mountpoints'] = implode(',', $fileMountIds);
        }
        if (isset($permissionPreset['groupMods']) && is_array($permissionPreset['groupMods'])) {
            $mappedPermissions['groupMods'] = implode(',', $permissionPreset['groupMods']);
        }
        if (isset($permissionPreset['pageTypesSelect']) && is_array($permissionPreset['pageTypesSelect'])) {
            $mappedPermissions['pagetypes_select'] = implode(',', $permissionPreset['pageTypesSelect']);
        }
        if (isset($permissionPreset['tablesModify']) && is_array($permissionPreset['tablesModify'])) {
            $mappedPermissions['tables_modify'] = implode(',', $permissionPreset['tablesModify']);
        }
        if (isset($permissionPreset['tablesSelect']) && is_array($permissionPreset['tablesSelect'])) {
            $mappedPermissions['tables_select'] = implode(',', $permissionPreset['tablesSelect']);
        }
        if (isset($permissionPreset['nonExcludeFields']) && is_array($permissionPreset['nonExcludeFields'])) {
            $nonExcludeFields = [];
            foreach ($permissionPreset['nonExcludeFields'] as $tableName => $fields) {
                foreach ($fields as $field) {
                    $nonExcludeFields[] = "$tableName:$field";
                }
            }
            if ($nonExcludeFields !== []) {
                $mappedPermissions['non_exclude_fields'] = implode(',', $nonExcludeFields);
            }
        }
        if (isset($permissionPreset['explicitAllowDeny']) && is_array($permissionPreset['explicitAllowDeny'])) {
            $explicitAllowDeny = [];
            foreach ($permissionPreset['explicitAllowDeny'] as $tableName => $columns) {
                foreach ($columns as $column => $values) {
                    foreach ($values as $value) {
                        $explicitAllowDeny[] = "$tableName:$column:$value";
                    }
                }
            }
            if ($explicitAllowDeny !== []) {
                $mappedPermissions['explicit_allowdeny'] = implode(',', $explicitAllowDeny);
            }
        }

        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        if (
            // availableWidgets is only available if typo3/cms-dashboard is installed
            $databaseConnection->getSchemaInformation()->getTableInfo($table)->hasColumnInfo('availableWidgets')
            && isset($permissionPreset['availableWidgets'])
            && is_array($permissionPreset['availableWidgets'])
        ) {
            $mappedPermissions['availableWidgets'] = implode(',', $permissionPreset['availableWidgets']);
        }
        if ($mappedPermissions !== []) {
            $databaseConnection->update(
                $table,
                $mappedPermissions,
                ['uid' => $recordId]
            );
        }
    }

    private function getFirstAdminUser(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $row = $queryBuilder->select('*')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(1, \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if (!is_array($row)) {
            throw new \RuntimeException('No admin backend user found for import context', 1743400000);
        }
        return $row;
    }

    private function makePathRelativeToProjectDirectory(string $absolutePath): string
    {
        return str_replace(Environment::getProjectPath(), '', $absolutePath);
    }

    private function countBackendGroupsByTitle(ConnectionPool $connectionPool, string $title): int
    {
        $queryBuilder = $connectionPool->getQueryBuilderForTable('be_groups');
        return (int)$queryBuilder->count('*')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($title))
            )->executeQuery()->fetchOne();
    }

    private function createFileMount(string $identifier, string $title): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_filemounts');
        $row = $queryBuilder->select('uid')
            ->from('sys_filemounts')
            ->where($queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier)))
            ->executeQuery()
            ->fetchAssociative();
        if (is_array($row)) {
            return (int)$row['uid'];
        }
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_filemounts');
        $queryBuilder->insert('sys_filemounts')->values(
            [
                'pid' => 0,
                'tstamp' => time(),
                'title' => $title,
                'identifier' => $identifier,
            ]
        )->executeStatement();
        return (int)$connectionPool->getConnectionForTable('sys_filemounts')->lastInsertId();
    }

    private function getFileMount(string $identifier): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_filemounts');
        $row = $queryBuilder->select('uid')
            ->from('sys_filemounts')
            ->where($queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier)))
            ->executeQuery()
            ->fetchAssociative();
        return (int)($row['uid'] ?? 0);
    }
}
