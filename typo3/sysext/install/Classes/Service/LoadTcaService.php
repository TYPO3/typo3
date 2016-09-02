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
     *
     * This will set up $GLOBALS['TCA']
     */
    public function loadExtensionTablesWithoutMigration()
    {
        $this->loadBaseTca();
    }

    /**
     * Copy of ExtensionManagementUtility to include TCA without migrations
     *
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::buildBaseTcaFromSingleFiles
     */
    protected function loadBaseTca()
    {
        $GLOBALS['TCA'] = [];

        $activePackages = GeneralUtility::makeInstance(PackageManager::class)
            ->getActivePackages();

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
}
