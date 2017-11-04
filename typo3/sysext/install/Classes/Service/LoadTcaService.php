<?php
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

use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for loading the TCA
 */
class LoadTcaService
{
    /**
     * Load TCA
     * Mostly a copy of ExtensionManagementUtility to include TCA without migrations.
     * To be used in install tool only.
     *
     * This will set up $GLOBALS['TCA']
     *
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::buildBaseTcaFromSingleFiles
     */
    public function loadExtensionTablesWithoutMigration()
    {
        $GLOBALS['TCA'] = [];

        $activePackages = GeneralUtility::makeInstance(PackageManager::class)->getActivePackages();

        // First load "full table" files from Configuration/TCA
        foreach ($activePackages as $package) {
            $tcaConfigurationDirectory = $package->getPackagePath() . 'Configuration/TCA';
            if (is_dir($tcaConfigurationDirectory)) {
                $files = scandir($tcaConfigurationDirectory);
                foreach ($files as $file) {
                    if (is_file($tcaConfigurationDirectory . '/' . $file)
                        && ($file !== '.')
                        && ($file !== '..')
                        && (substr($file, -4, 4) === '.php')
                    ) {
                        $tcaOfTable = require $tcaConfigurationDirectory . '/' . $file;
                        if (is_array($tcaOfTable)) {
                            // TCA table name is filename without .php suffix, eg 'sys_notes', not 'sys_notes.php'
                            $tcaTableName = substr($file, 0, -4);
                            $GLOBALS['TCA'][$tcaTableName] = $tcaOfTable;
                        }
                    }
                }
            }
        }

        // Apply category stuff
        CategoryRegistry::getInstance()->applyTcaForPreRegisteredTables();

        // Execute override files from Configuration/TCA/Overrides
        foreach ($activePackages as $package) {
            $tcaOverridesPathForPackage = $package->getPackagePath() . 'Configuration/TCA/Overrides';
            if (is_dir($tcaOverridesPathForPackage)) {
                $files = scandir($tcaOverridesPathForPackage);
                foreach ($files as $file) {
                    if (is_file($tcaOverridesPathForPackage . '/' . $file)
                        && ($file !== '.')
                        && ($file !== '..')
                        && (substr($file, -4, 4) === '.php')
                    ) {
                        require $tcaOverridesPathForPackage . '/' . $file;
                    }
                }
            }
        }
    }

    /**
     * Load ext_tables.php of a single extension
     *
     * @param string $extensionKey The extension to load a ext_tables.php file from.
     */
    public function loadSingleExtTablesFile(string $extensionKey)
    {
        global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
        global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
        global $PAGES_TYPES, $TBE_STYLES;
        global $_EXTKEY;

        if (!isset($GLOBALS['TYPO3_LOADED_EXT'][$extensionKey])) {
            throw new \RuntimeException(
                'Extension ' . $extensionKey . ' does not exist in TYPO3_LOADED_EXT',
                1477217619
            );
        }

        $extensionInformation = $GLOBALS['TYPO3_LOADED_EXT'][$extensionKey];
        $_EXTKEY = $extensionKey;
        // Load each ext_tables.php file of loaded extensions
        if ((is_array($extensionInformation) || $extensionInformation instanceof \ArrayAccess)
            && $extensionInformation['ext_tables.php']
        ) {
            // $_EXTKEY and $_EXTCONF are available in ext_tables.php
            // and are explicitly set in cached file as well
            $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY] ?? null;
            require $extensionInformation['ext_tables.php'];
        }
    }
}
