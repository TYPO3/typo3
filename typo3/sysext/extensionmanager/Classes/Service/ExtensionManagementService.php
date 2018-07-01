<?php
namespace TYPO3\CMS\Extensionmanager\Service;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Service class for managing multiple step processes (dependencies for example)
 */
class ExtensionManagementService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue
     */
    protected $downloadQueue;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility
     */
    protected $dependencyUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
     */
    protected $installUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility
     */
    protected $extensionModelUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility
     */
    protected $downloadUtility;

    /**
     * @var bool
     */
    protected $automaticInstallationEnabled = true;

    /**
     * @var bool
     */
    protected $skipDependencyCheck = false;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue $downloadQueue
     */
    public function injectDownloadQueue(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue $downloadQueue)
    {
        $this->downloadQueue = $downloadQueue;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility
     */
    public function injectDependencyUtility(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility)
    {
        $this->dependencyUtility = $dependencyUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility
     */
    public function injectInstallUtility(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility)
    {
        $this->installUtility = $installUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility $extensionModelUtility
     */
    public function injectExtensionModelUtility(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility $extensionModelUtility)
    {
        $this->extensionModelUtility = $extensionModelUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility $downloadUtility
     */
    public function injectDownloadUtility(\TYPO3\CMS\Extensionmanager\Utility\DownloadUtility $downloadUtility)
    {
        $this->downloadUtility = $downloadUtility;
    }

    /**
     * @param string $extensionKey
     */
    public function markExtensionForInstallation($extensionKey)
    {
        // We have to check for dependencies of the extension first, before marking it for installation
        // because this extension might have dependencies, which need to be installed first
        $this->installUtility->reloadAvailableExtensions();
        $extension = $this->getExtension($extensionKey);
        $this->dependencyUtility->checkDependencies($extension);
        $this->downloadQueue->addExtensionToInstallQueue($extension);
    }

    /**
     * Mark an extension for copy
     *
     * @param string $extensionKey
     * @param string $sourceFolder
     */
    public function markExtensionForCopy($extensionKey, $sourceFolder)
    {
        $this->downloadQueue->addExtensionToCopyQueue($extensionKey, $sourceFolder);
    }

    /**
     * Mark an extension for download
     *
     * @param Extension $extension
     */
    public function markExtensionForDownload(Extension $extension)
    {
        // We have to check for dependencies of the extension first, before marking it for download
        // because this extension might have dependencies, which need to be downloaded and installed first
        $this->dependencyUtility->checkDependencies($extension);
        if (!$this->dependencyUtility->hasDependencyErrors()) {
            $this->downloadQueue->addExtensionToQueue($extension);
        }
    }

    /**
     * @param Extension $extension
     */
    public function markExtensionForUpdate(Extension $extension)
    {
        // We have to check for dependencies of the extension first, before marking it for download
        // because this extension might have dependencies, which need to be downloaded and installed first
        $this->dependencyUtility->checkDependencies($extension);
        $this->downloadQueue->addExtensionToQueue($extension, 'update');
    }

    /**
     * Enables or disables the dependency check for system environment (PHP, TYPO3) before extension installation
     *
     * @param bool $skipDependencyCheck
     */
    public function setSkipDependencyCheck($skipDependencyCheck)
    {
        $this->skipDependencyCheck = $skipDependencyCheck;
    }

    /**
     * @param bool $automaticInstallationEnabled
     */
    public function setAutomaticInstallationEnabled($automaticInstallationEnabled)
    {
        $this->automaticInstallationEnabled = (bool)$automaticInstallationEnabled;
    }

    /**
     * Install the extension
     *
     * @param Extension $extension
     * @return bool|array Returns FALSE if dependencies cannot be resolved, otherwise array with installation information
     */
    public function installExtension(Extension $extension)
    {
        $this->downloadExtension($extension);
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
        while (!$this->downloadQueue->isCopyQueueEmpty()
            || !$this->downloadQueue->isQueueEmpty('download')
            || !$this->downloadQueue->isQueueEmpty('update')
        ) {
            // First copy all available extension
            // This might change other queues again
            $copyQueue = $this->downloadQueue->resetExtensionCopyStorage();
            if (!empty($copyQueue)) {
                $this->copyDependencies($copyQueue);
            }
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
     *
     * @return array
     */
    public function getDependencyErrors()
    {
        return $this->dependencyUtility->getDependencyErrors();
    }

    /**
     * @param string $extensionKey
     * @return Extension
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function getExtension($extensionKey)
    {
        return $this->extensionModelUtility->mapExtensionArrayToModel(
            $this->installUtility->enrichExtensionWithDetails($extensionKey)
        );
    }

    /**
     * Checks if an extension is available in the system
     *
     * @param string $extensionKey
     * @return bool
     */
    public function isAvailable($extensionKey)
    {
        return $this->installUtility->isAvailable($extensionKey);
    }

    /**
     * @param string $extensionKey
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException if the package isn't available
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException if an invalid package key was passed
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException if an invalid package path was passed
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackageManifestException if no extension configuration file could be found
     */
    public function reloadPackageInformation($extensionKey)
    {
        $this->installUtility->reloadPackageInformation($extensionKey);
    }

    /**
     * Download an extension
     *
     * @param Extension $extension
     */
    protected function downloadExtension(Extension $extension)
    {
        $this->downloadMainExtension($extension);
        $this->setInExtensionRepository($extension->getExtensionKey());
    }

    /**
     * Check dependencies for an extension and its required extensions
     *
     * @param Extension $extension
     * @return bool Returns TRUE if all dependencies can be resolved, otherwise FALSE
     */
    protected function checkDependencies(Extension $extension)
    {
        $this->dependencyUtility->setSkipDependencyCheck($this->skipDependencyCheck);
        $this->dependencyUtility->checkDependencies($extension);

        return !$this->dependencyUtility->hasDependencyErrors();
    }

    /**
     * Sets the path to the repository in an extension
     * (Initialisation/Extensions) depending on the extension
     * that is currently installed
     *
     * @param string $extensionKey
     */
    protected function setInExtensionRepository($extensionKey)
    {
        $paths = Extension::returnInstallPaths();
        $path = $paths[$this->downloadUtility->getDownloadPath()];
        $localExtensionStorage = $path . $extensionKey . '/Initialisation/Extensions/';
        $this->dependencyUtility->setLocalExtensionStorage($localExtensionStorage);
    }

    /**
     * Copies locally provided extensions to typo3conf/ext
     *
     * @param array $copyQueue
     */
    protected function copyDependencies(array $copyQueue)
    {
        $installPaths = Extension::returnAllowedInstallPaths();
        foreach ($copyQueue as $extensionKey => $sourceFolder) {
            $destination = $installPaths['Local'] . $extensionKey;
            GeneralUtility::mkdir($destination);
            GeneralUtility::copyDirectory($sourceFolder . $extensionKey, $destination);
            $this->markExtensionForInstallation($extensionKey);
            $this->downloadQueue->removeExtensionFromCopyQueue($extensionKey);
        }
    }

    /**
     * Uninstall extensions that will be updated
     * This is not strictly necessary but cleaner all in all
     *
     * @param Extension[] $updateQueue
     * @return array
     */
    protected function uninstallDependenciesToBeUpdated(array $updateQueue)
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
     * @param array $installQueue
     * @return array
     */
    protected function installDependencies(array $installQueue)
    {
        if (empty($installQueue)) {
            return [];
        }
        $this->emitWillInstallExtensionsSignal($installQueue);
        $resolvedDependencies = [];
        $this->installUtility->install(...array_keys($installQueue));
        foreach ($installQueue as $extensionKey => $_) {
            $this->emitHasInstalledExtensionSignal($extensionKey);
            if (!is_array($resolvedDependencies['installed'])) {
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
     * @return array
     */
    protected function downloadDependencies(array $downloadQueue)
    {
        $resolvedDependencies = [];
        foreach ($downloadQueue as $extensionToDownload) {
            $this->downloadUtility->download($extensionToDownload);
            $this->downloadQueue->removeExtensionFromQueue($extensionToDownload);
            $resolvedDependencies['downloaded'][$extensionToDownload->getExtensionKey()] = $extensionToDownload;
            $this->markExtensionForInstallation($extensionToDownload->getExtensionKey());
        }
        return $resolvedDependencies;
    }

    /**
     * Get and resolve dependencies
     *
     * @param Extension $extension
     * @return array
     */
    public function getAndResolveDependencies(Extension $extension)
    {
        $this->dependencyUtility->setSkipDependencyCheck($this->skipDependencyCheck);
        $this->dependencyUtility->checkDependencies($extension);
        $installQueue = $this->downloadQueue->getExtensionInstallStorage();
        if (is_array($installQueue) && !empty($installQueue)) {
            $installQueue = ['install' => $installQueue];
        }
        return array_merge($this->downloadQueue->getExtensionQueue(), $installQueue);
    }

    /**
     * Downloads the extension the user wants to install
     * This is separated from downloading the dependencies
     * as an extension is able to provide it's own dependencies
     *
     * @param Extension $extension
     */
    public function downloadMainExtension(Extension $extension)
    {
        // The extension object has a uid if the extension is not present in the system
        // or an update of a present extension is triggered.
        if ($extension->getUid()) {
            $this->downloadUtility->download($extension);
        }
    }

    /**
     * @param array $installQueue
     */
    protected function emitWillInstallExtensionsSignal(array $installQueue)
    {
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'willInstallExtensions', [$installQueue]);
    }

    /**
     * @param string $extensionKey
     */
    protected function emitHasInstalledExtensionSignal($extensionKey)
    {
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'hasInstalledExtensions', [$extensionKey]);
    }

    /**
     * Get the SignalSlot dispatcher
     *
     * @return Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        if (!isset($this->signalSlotDispatcher)) {
            $this->signalSlotDispatcher = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(Dispatcher::class);
        }
        return $this->signalSlotDispatcher;
    }
}
