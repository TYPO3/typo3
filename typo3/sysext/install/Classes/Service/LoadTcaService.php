<?php

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

use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Service for loading the TCA
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class LoadTcaService
{
    /**
     * @var LateBootService
     */
    private $lateBootService;

    public function __construct(LateBootService $lateBootService)
    {
        $this->lateBootService = $lateBootService;
    }

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
        $container = $this->lateBootService->getContainer();
        $backup = $this->lateBootService->makeCurrent($container);

        $GLOBALS['TCA'] = [];

        $activePackages = $container->get(PackageManager::class)->getActivePackages();

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
        // @deprecated since v11, can be removed in v12
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

        $this->lateBootService->makeCurrent(null, $backup);
    }

    /**
     * Load ext_tables.php of a single extension
     *
     * @param string $extensionKey The extension to load an ext_tables.php file from.
     */
    public function loadSingleExtTablesFile(string $extensionKey)
    {
        $container = $this->lateBootService->getContainer();
        $backup = $this->lateBootService->makeCurrent($container);

        $packageManager = $container->get(PackageManager::class);
        try {
            $package = $packageManager->getPackage($extensionKey);
        } catch (UnknownPackageException $e) {
            throw new \RuntimeException(
                'Extension ' . $extensionKey . ' is not active',
                1477217619
            );
        }

        $extTablesPath = $package->getPackagePath() . 'ext_tables.php';
        // Load ext_tables.php file of the extension
        if (@file_exists($extTablesPath)) {
            require $extTablesPath;
        }

        $this->lateBootService->makeCurrent(null, $backup);
    }
}
