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

use TYPO3\CMS\Core\Configuration\Tca\TcaFactory;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Service for loading the TCA
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class LoadTcaService
{
    public function __construct(
        private readonly LateBootService $lateBootService
    ) {}

    /**
     * Load TCA
     * Mostly a copy of ExtensionManagementUtility to include TCA without migrations.
     * To be used in install tool only.
     *
     * This will set up $GLOBALS['TCA']
     */
    public function loadExtensionTablesWithoutMigration(): void
    {
        $container = $this->lateBootService->getContainer();
        $backup = $this->lateBootService->makeCurrent($container);
        $tcaFactory = $container->get(TcaFactory::class);
        $GLOBALS['TCA'] = $tcaFactory->createNotMigrated();
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
        } catch (UnknownPackageException) {
            throw new \RuntimeException('Extension ' . $extensionKey . ' is not active', 1477217619);
        }

        $extTablesPath = $package->getPackagePath() . 'ext_tables.php';
        // Load ext_tables.php file of the extension
        if (@file_exists($extTablesPath)) {
            require $extTablesPath;
        }

        $this->lateBootService->makeCurrent(null, $backup);
    }
}
