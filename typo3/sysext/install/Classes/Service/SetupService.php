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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * @throws SiteConfigurationWriteException
     */
    public function createSiteConfiguration(string $identifier, int $rootPageId, string $siteUrl): void
    {
        // Create a default site configuration called "main" as best practice
        $this->siteWriter->createNewBasicSite($identifier, $rootPageId, $siteUrl);
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

    public function createSite(): string
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
        $pageUid = $databaseConnectionForPages->lastInsertId();

        // add a root sys_template with fluid_styled_content and a default PAGE typoscript snippet
        $connectionPool->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => $pageUid,
                'crdate' => time(),
                'tstamp' => time(),
                'title' => 'Main TypoScript Rendering',
                'root' => 1,
                'clear' => 3,
                'include_static_file' => 'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:fluid_styled_content/Configuration/TypoScript/Styling/',
                'constants' => '',
                'config' => 'page = PAGE
page.10 = TEXT
page.10.value (
   <div style="width: 800px; margin: 15% auto;">
      <div style="width: 300px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 42"><path d="M60.2 14.4v27h-3.8v-27h-6.7v-3.3h17.1v3.3h-6.6zm20.2 12.9v14h-3.9v-14l-7.7-16.2h4.1l5.7 12.2 5.7-12.2h3.9l-7.8 16.2zm19.5 2.6h-3.6v11.4h-3.8V11.1s3.7-.3 7.3-.3c6.6 0 8.5 4.1 8.5 9.4 0 6.5-2.3 9.7-8.4 9.7m.4-16c-2.4 0-4.1.3-4.1.3v12.6h4.1c2.4 0 4.1-1.6 4.1-6.3 0-4.4-1-6.6-4.1-6.6m21.5 27.7c-7.1 0-9-5.2-9-15.8 0-10.2 1.9-15.1 9-15.1s9 4.9 9 15.1c.1 10.6-1.8 15.8-9 15.8m0-27.7c-3.9 0-5.2 2.6-5.2 12.1 0 9.3 1.3 12.4 5.2 12.4 3.9 0 5.2-3.1 5.2-12.4 0-9.4-1.3-12.1-5.2-12.1m19.9 27.7c-2.1 0-5.3-.6-5.7-.7v-3.1c1 .2 3.7.7 5.6.7 2.2 0 3.6-1.9 3.6-5.2 0-3.9-.6-6-3.7-6H138V24h3.1c3.5 0 3.7-3.6 3.7-5.3 0-3.4-1.1-4.8-3.2-4.8-1.9 0-4.1.5-5.3.7v-3.2c.5-.1 3-.7 5.2-.7 4.4 0 7 1.9 7 8.3 0 2.9-1 5.5-3.3 6.3 2.6.2 3.8 3.1 3.8 7.3 0 6.6-2.5 9-7.3 9"/><path fill="#FF8700" d="M31.7 28.8c-.6.2-1.1.2-1.7.2-5.2 0-12.9-18.2-12.9-24.3 0-2.2.5-3 1.3-3.6C12 1.9 4.3 4.2 1.9 7.2 1.3 8 1 9.1 1 10.6c0 9.5 10.1 31 17.3 31 3.3 0 8.8-5.4 13.4-12.8M28.4.5c6.6 0 13.2 1.1 13.2 4.8 0 7.6-4.8 16.7-7.2 16.7-4.4 0-9.9-12.1-9.9-18.2C24.5 1 25.6.5 28.4.5"/></svg>
      </div>
      <h4 style="font-family: sans-serif;">Welcome to a default website made with <a href="https://typo3.org">TYPO3</a></h4>
   </div>
)
page.100 = CONTENT
page.100 {
    table = tt_content
    select {
        orderBy = sorting
        where = {#colPos}=0
    }
}
',
                'description' => 'This is an Empty Site Package TypoScript record.

For each website you need a TypoScript record on the main page of your website (on the top level). For better maintenance all TypoScript should be extracted into external files via @import \'EXT:site_myproject/Configuration/TypoScript/setup.typoscript\'',
            ]
        );

        return $pageUid;
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
            $databaseConnection->getSchemaInformation()->introspectTable($table)->hasColumn('availableWidgets')
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
}
