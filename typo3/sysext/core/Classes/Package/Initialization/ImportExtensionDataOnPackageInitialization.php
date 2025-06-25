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

namespace TYPO3\CMS\Core\Package\Initialization;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Listener to import extension data after package activation
 */
final readonly class ImportExtensionDataOnPackageInitialization
{
    public function __construct(
        private Registry $registry,
    ) {}

    #[AsEventListener]
    public function __invoke(PackageInitializationEvent $event): void
    {
        $package = $event->getPackage();
        $extensionKey = $event->getExtensionKey();
        $importFolder = $package->getPackagePath() . 'Initialisation/Files';
        $registryKey = $extensionKey . ':Initialisation/Files';
        if ($this->registry->get('extensionDataImport', $registryKey) || !file_exists($importFolder)) {
            return;
        }
        $destinationAbsolutePath = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . $extensionKey);
        if (!file_exists($destinationAbsolutePath) && GeneralUtility::isAllowedAbsPath($destinationAbsolutePath)) {
            GeneralUtility::mkdir($destinationAbsolutePath);
        }
        GeneralUtility::copyDirectory($importFolder, $destinationAbsolutePath);
        $this->registry->set('extensionDataImport', $registryKey, 1);
        $event->addStorageEntry(__CLASS__, $destinationAbsolutePath);
    }
}
