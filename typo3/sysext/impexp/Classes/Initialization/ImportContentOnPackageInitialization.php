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

namespace TYPO3\CMS\Impexp\Initialization;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Package\Initialization\CheckForImportRequirements;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Impexp\Utility\ImportExportUtility;

/**
 * Listener to import a T3D or XML file after package activation
 */
final class ImportContentOnPackageInitialization implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Registry $registry,
        private readonly ImportExportUtility $importExportUtility,
    ) {}

    #[AsEventListener(after: CheckForImportRequirements::class)]
    public function __invoke(PackageInitializationEvent $event): void
    {
        $packagePath = $event->getPackage()->getPackagePath();
        $registryKeyPrefix = $event->getExtensionKey();
        $registryKeysToCheck = [
            $registryKeyPrefix . ':Initialisation/data.t3d',
            $registryKeyPrefix . ':Initialisation/dataImported',
        ];
        foreach ($registryKeysToCheck as $registryKeyToCheck) {
            if ($this->registry->get('extensionDataImport', $registryKeyToCheck)) {
                // Data was imported before -> early return
                return;
            }
        }
        $importFileToUse = null;
        $possibleImportFiles = [
            $packagePath . 'Initialisation/data.t3d',
            $packagePath . 'Initialisation/data.xml',
        ];
        foreach ($possibleImportFiles as $possibleImportFile) {
            if (!file_exists($possibleImportFile)) {
                continue;
            }
            $importFileToUse = $possibleImportFile;
        }
        if ($importFileToUse === null) {
            return;
        }
        try {
            $importResult = $this->importExportUtility->importT3DFile($importFileToUse, 0);
            $this->registry->set('extensionDataImport', $registryKeyPrefix . ':Initialisation/dataImported', 1);
            $event->addStorageEntry(__CLASS__, [
                'importResult' => $importResult,
                'importFileToUse' => $importFileToUse,
                'import' => $this->importExportUtility->getImport(),
            ]);
        } catch (\ErrorException $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);
        }
    }
}
