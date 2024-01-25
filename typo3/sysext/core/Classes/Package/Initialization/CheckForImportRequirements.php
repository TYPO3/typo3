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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Package\Exception\ImportRequirementsException;
use TYPO3\CMS\Impexp\Initialization\ImportContentOnPackageInitialization;
use TYPO3\CMS\Impexp\Initialization\ImportSiteConfigurationsOnPackageInitialization;

/**
 * Listener to check import requirements in case an extension contains data to be imported
 */
final class CheckForImportRequirements implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ListenerProvider $listenerProvider
    ) {}

    #[AsEventListener]
    public function __invoke(PackageInitializationEvent $event): void
    {
        $packagePath = $event->getPackage()->getPackagePath();
        $importFiles = [];
        foreach (['t3d', 'xml'] as $importFileExtension) {
            if (file_exists(($importFile = $packagePath . 'Initialisation/data.' . $importFileExtension))) {
                $importFiles[] = $importFile;
            }
        }
        $siteInitialisationDirectoryExists = is_dir($packagePath . 'Initialisation/Site');

        if ($importFiles === [] && !$siteInitialisationDirectoryExists) {
            return;
        }

        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($listener instanceof \Closure || !isset($listener[0])) {
                continue;
            }
            if (($importFiles !== [] && $listener[0] instanceof ImportContentOnPackageInitialization)
                || ($siteInitialisationDirectoryExists && $listener[0] instanceof ImportSiteConfigurationsOnPackageInitialization)
            ) {
                return;
            }
        }

        // Add a exception which might be thrown by other listeners
        $missingImportComponentException = new ImportRequirementsException(
            $event->getExtensionKey() . ' contains data to be imported, but the required component is not installed. Make sure to define corresponding requirements.',
            1706287389
        );

        $this->logger->warning(
            $missingImportComponentException->getMessage(),
            [
                'exception' => $missingImportComponentException,
                'extensionKey' => $event->getExtensionKey(),
                'packageKey' => $event->getPackage()->getPackageKey(),
                'importFiles' => $importFiles,
                'siteInitialisationDirectoryExists' => $siteInitialisationDirectoryExists,
            ]
        );
        $event->addStorageEntry(
            __CLASS__,
            [
                'exception' => $missingImportComponentException,
                'importFiles' => $importFiles,
                'siteInitialisationDirectoryExists' => $siteInitialisationDirectoryExists,
            ]
        );
    }
}
