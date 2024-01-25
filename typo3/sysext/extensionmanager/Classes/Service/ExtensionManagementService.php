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

namespace TYPO3\CMS\Extensionmanager\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Event\BeforePackageActivationEvent;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Service class for managing multiple step processes (dependencies for example)
 */
class ExtensionManagementService implements SingletonInterface
{
    protected DependencyUtility $dependencyUtility;
    protected InstallUtility $installUtility;

    protected bool $automaticInstallationEnabled = true;
    protected bool $skipDependencyCheck = false;

    public function __construct(
        protected readonly RemoteRegistry $remoteRegistry,
        protected readonly FileHandlingUtility $fileHandlingUtility,
        protected readonly DownloadQueue $downloadQueue,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function injectDependencyUtility(DependencyUtility $dependencyUtility)
    {
        $this->dependencyUtility = $dependencyUtility;
    }

    public function injectInstallUtility(InstallUtility $installUtility)
    {
        $this->installUtility = $installUtility;
    }

    public function markExtensionForInstallation(string $extensionKey): void
    {
        // We have to check for dependencies of the extension first, before marking it for installation
        // because this extension might have dependencies, which need to be installed first
        $this->installUtility->reloadAvailableExtensions();
        $extension = $this->getExtension($extensionKey);
        $this->dependencyUtility->checkDependencies($extension);
        $this->downloadQueue->addExtensionToInstallQueue($extension);
    }

    /**
     * Mark an extension for download
     */
    public function markExtensionForDownload(Extension $extension): void
    {
        // We have to check for dependencies of the extension first, before marking it for download
        // because this extension might have dependencies, which need to be downloaded and installed first
        $this->dependencyUtility->checkDependencies($extension);
        if (!$this->dependencyUtility->hasDependencyErrors()) {
            $this->downloadQueue->addExtensionToQueue($extension);
        }
    }

    public function markExtensionForUpdate(Extension $extension): void
    {
        // We have to check for dependencies of the extension first, before marking it for download
        // because this extension might have dependencies, which need to be downloaded and installed first
        $this->dependencyUtility->checkDependencies($extension);
        $this->downloadQueue->addExtensionToQueue($extension, 'update');
    }

    /**
     * Enables or disables the dependency check for system environment (PHP, TYPO3) before extension installation
     */
    public function setSkipDependencyCheck(bool $skipDependencyCheck): void
    {
        $this->skipDependencyCheck = $skipDependencyCheck;
    }

    public function setAutomaticInstallationEnabled(bool $automaticInstallationEnabled): void
    {
        $this->automaticInstallationEnabled = $automaticInstallationEnabled;
    }

    /**
     * Install the extension
     *
     * @return array{
     *     downloaded?: array<string, Extension>,
     *     updated?: array<string, Extension>,
     *     installed?: array<string, string>,
     * }|false Returns FALSE if dependencies cannot be resolved, otherwise array with installation information
     */
    public function installExtension(Extension $extension): array|false
    {
        $this->downloadMainExtension($extension);
        if (!$this->checkDependencies($extension)) {
            return false;
        }

        $downloadedDependencies = [];
        $updatedDependencies = [];
        $installQueue = [];

        // First resolve all dependencies and the sub-dependencies until all queues are empty as new extensions might be
        // added each time
        // Extensions have to be installed in reverse order. Extensions which were added at last are dependencies of
        // earlier ones and need to be available before
        while (!$this->downloadQueue->isQueueEmpty('download')
            || !$this->downloadQueue->isQueueEmpty('update')
        ) {
            $installQueue = array_merge($this->downloadQueue->resetExtensionInstallStorage(), $installQueue);
            // Get download and update information
            $queue = $this->downloadQueue->resetExtensionQueue();
            if (!empty($queue['download'])) {
                $downloadedDependencies = array_merge($downloadedDependencies, $this->downloadDependencies($queue['download']));
            }
            $installQueue = array_merge($this->downloadQueue->resetExtensionInstallStorage(), $installQueue);
            if ($this->automaticInstallationEnabled) {
                if (!empty($queue['update'])) {
                    $this->downloadDependencies($queue['update']);
                    $updatedDependencies = array_merge($updatedDependencies, $this->uninstallDependenciesToBeUpdated($queue['update']));
                }
                $installQueue = array_merge($this->downloadQueue->resetExtensionInstallStorage(), $installQueue);
            }
        }

        // If there were any dependency errors we have to abort here
        if ($this->dependencyUtility->hasDependencyErrors()) {
            return false;
        }

        // Attach extension to install queue
        $this->downloadQueue->addExtensionToInstallQueue($extension);
        $installQueue += $this->downloadQueue->resetExtensionInstallStorage();
        $installedDependencies = [];
        if ($this->automaticInstallationEnabled) {
            $installedDependencies = $this->installDependencies($installQueue);
        }

        return array_merge($downloadedDependencies, $updatedDependencies, $installedDependencies);
    }

    /**
     * Returns the unresolved dependency errors
     */
    public function getDependencyErrors(): array
    {
        return $this->dependencyUtility->getDependencyErrors();
    }

    public function getExtension(string $extensionKey): Extension
    {
        return Extension::createFromExtensionArray(
            $this->installUtility->enrichExtensionWithDetails($extensionKey)
        );
    }

    /**
     * Checks if an extension is available in the system
     */
    public function isAvailable(string $extensionKey): bool
    {
        return $this->installUtility->isAvailable($extensionKey);
    }

    public function reloadPackageInformation(string $extensionKey): void
    {
        $this->installUtility->reloadPackageInformation($extensionKey);
    }

    /**
     * Check dependencies for an extension and its required extensions
     *
     * @return bool Returns TRUE if all dependencies can be resolved, otherwise FALSE
     */
    protected function checkDependencies(Extension $extension): bool
    {
        $this->dependencyUtility->setSkipDependencyCheck($this->skipDependencyCheck);
        $this->dependencyUtility->checkDependencies($extension);

        return !$this->dependencyUtility->hasDependencyErrors();
    }

    /**
     * Uninstall extensions that will be updated
     * This is not strictly necessary but cleaner all in all
     *
     * @param Extension[] $updateQueue
     * @return array{updated?: array<string, Extension>}
     */
    protected function uninstallDependenciesToBeUpdated(array $updateQueue): array
    {
        $resolvedDependencies = [];
        foreach ($updateQueue as $extensionToUpdate) {
            $this->installUtility->uninstall($extensionToUpdate->getExtensionKey());
            $resolvedDependencies['updated'][$extensionToUpdate->getExtensionKey()] = $extensionToUpdate;
        }
        return $resolvedDependencies;
    }

    /**
     * Install dependent extensions
     *
     * @return array{installed?: array<string, string>}
     */
    protected function installDependencies(array $installQueue): array
    {
        if (empty($installQueue)) {
            return [];
        }
        $this->eventDispatcher->dispatch(new BeforePackageActivationEvent($installQueue));
        $resolvedDependencies = [];
        $extensionKeys = array_keys($installQueue);
        $this->installUtility->install(...$extensionKeys);
        foreach ($extensionKeys as $extensionKey) {
            if (!isset($resolvedDependencies['installed']) || !is_array($resolvedDependencies['installed'])) {
                $resolvedDependencies['installed'] = [];
            }
            $resolvedDependencies['installed'][$extensionKey] = $extensionKey;
        }
        return $resolvedDependencies;
    }

    /**
     * Download dependencies
     * expects an array of extension objects to download
     *
     * @param Extension[] $downloadQueue
     * @return array{downloaded?: array<string, Extension>}
     */
    protected function downloadDependencies(array $downloadQueue): array
    {
        $resolvedDependencies = [];
        foreach ($downloadQueue as $extensionToDownload) {
            $this->rawDownload($extensionToDownload);
            $this->downloadQueue->removeExtensionFromQueue($extensionToDownload);
            $resolvedDependencies['downloaded'][$extensionToDownload->getExtensionKey()] = $extensionToDownload;
            $this->markExtensionForInstallation($extensionToDownload->getExtensionKey());
        }
        return $resolvedDependencies;
    }

    /**
     * Get and resolve dependencies
     */
    public function getAndResolveDependencies(Extension $extension): array
    {
        $this->dependencyUtility->setSkipDependencyCheck($this->skipDependencyCheck);
        $this->dependencyUtility->checkDependencies($extension);
        $installQueue = $this->downloadQueue->getExtensionInstallStorage();
        if ($installQueue !== []) {
            $installQueue = ['install' => $installQueue];
        }
        return array_merge($this->downloadQueue->getExtensionQueue(), $installQueue);
    }

    /**
     * Downloads the extension the user wants to install
     * This is separated from downloading the dependencies
     * as an extension is able to provide its own dependencies
     */
    public function downloadMainExtension(Extension $extension): void
    {
        // The extension object has a uid if the extension is not present in the system
        // or an update of a present extension is triggered.
        if ($extension->getUid()) {
            $this->rawDownload($extension);
        }
    }

    protected function rawDownload(Extension $extension): void
    {
        if (
            Environment::isComposerMode()
            || (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('extensionmanager', 'offlineMode')
        ) {
            throw new ExtensionManagerException('Extension Manager is in offline mode. No TER connection available.', 1437078620);
        }

        $remoteIdentifier = $extension->getRemoteIdentifier();

        if ($this->remoteRegistry->hasRemote($remoteIdentifier)) {
            $this->remoteRegistry
                ->getRemote($remoteIdentifier)
                ->downloadExtension(
                    $extension->getExtensionKey(),
                    $extension->getVersion(),
                    $this->fileHandlingUtility,
                    $extension->getMd5hash()
                );
        }
    }
}
