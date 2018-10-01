<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Utility for dealing with extension list related functions
 *
 * @TODO: Refactor this API class:
 * - The methods depend on each other, they take each others result, that could be done internally
 * - There is no good wording to distinguish existing and loaded extensions
 * - The name 'listUtility' is not good, the methods could be moved to some 'extensionInformationUtility', or a repository?
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class ListUtility implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility
     */
    protected $emConfUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
     */
    protected $installUtility;

    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     */
    protected $packageManager;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var array
     */
    protected $availableExtensions;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility $emConfUtility
     */
    public function injectEmConfUtility(\TYPO3\CMS\Extensionmanager\Utility\EmConfUtility $emConfUtility)
    {
        $this->emConfUtility = $emConfUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility
     */
    public function injectInstallUtility(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility)
    {
        $this->installUtility = $installUtility;
    }

    /**
     * @param \TYPO3\CMS\Core\Package\PackageManager $packageManager
     */
    public function injectPackageManager(\TYPO3\CMS\Core\Package\PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Returns the list of available, but not necessarily loaded extensions
     *
     * @return array[] All extensions with info
     */
    public function getAvailableExtensions()
    {
        if ($this->availableExtensions === null) {
            $this->availableExtensions = [];
            $this->emitPackagesMayHaveChangedSignal();
            foreach ($this->packageManager->getAvailablePackages() as $package) {
                $installationType = $this->getInstallTypeForPackage($package);
                $this->availableExtensions[$package->getPackageKey()] = [
                    'siteRelPath' => str_replace(Environment::getPublicPath() . '/', '', $package->getPackagePath()),
                    'type' => $installationType,
                    'key' => $package->getPackageKey(),
                    'ext_icon' => ExtensionManagementUtility::getExtensionIcon($package->getPackagePath()),
                ];
            }
        }

        return $this->availableExtensions;
    }

    /**
     * Reset and reload the available extensions
     */
    public function reloadAvailableExtensions()
    {
        $this->availableExtensions = null;
        $this->packageManager->scanAvailablePackages();
        $this->getAvailableExtensions();
    }

    /**
     * @param string $extensionKey
     * @return \TYPO3\CMS\Core\Package\PackageInterface
     * @throws \TYPO3\CMS\Core\Package\Exception\UnknownPackageException if the specified package is unknown
     */
    public function getExtension($extensionKey)
    {
        return $this->packageManager->getPackage($extensionKey);
    }

    /**
     * Emits packages may have changed signal
     */
    protected function emitPackagesMayHaveChangedSignal()
    {
        $this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
    }

    /**
     * Returns "System", "Global" or "Local" based on extension position in filesystem.
     *
     * @param PackageInterface $package
     * @return string
     */
    protected function getInstallTypeForPackage(PackageInterface $package)
    {
        foreach (Extension::returnInstallPaths() as $installType => $installPath) {
            if (GeneralUtility::isFirstPartOfStr($package->getPackagePath(), $installPath)) {
                return $installType;
            }
        }
        return '';
    }

    /**
     * Enrich the output of getAvailableExtensions() with an array key installed = 1 if an extension is loaded.
     *
     * @param array $availableExtensions
     * @return array
     */
    public function getAvailableAndInstalledExtensions(array $availableExtensions)
    {
        foreach ($this->packageManager->getActivePackages() as $extKey => $_) {
            if (isset($availableExtensions[$extKey])) {
                $availableExtensions[$extKey]['installed'] = true;
            }
        }
        return $availableExtensions;
    }

    /**
     * Adds the information from the emconf array to the extension information
     *
     * @param array $extensions
     * @return array
     */
    public function enrichExtensionsWithEmConfInformation(array $extensions)
    {
        foreach ($extensions as $extensionKey => $properties) {
            $emconf = $this->emConfUtility->includeEmConf($properties);
            if ($emconf) {
                $extensions[$extensionKey] = array_merge($emconf, $properties);
            } else {
                unset($extensions[$extensionKey]);
            }
        }
        return $extensions;
    }

    /**
     * Adds the information from the emconf array and TER to the extension information
     *
     * @param array $extensions
     * @return array
     */
    public function enrichExtensionsWithEmConfAndTerInformation(array $extensions)
    {
        $extensions = $this->enrichExtensionsWithEmConfInformation($extensions);
        foreach ($extensions as $extensionKey => $properties) {
            $terObject = $this->getExtensionTerData($extensionKey, $extensions[$extensionKey]['version'] ?? '');
            if ($terObject !== null) {
                $extensions[$extensionKey]['terObject'] = $terObject;
                $extensions[$extensionKey]['updateAvailable'] = false;
                $extensions[$extensionKey]['updateToVersion'] = null;
                $extensionToUpdate = $this->installUtility->getUpdateableVersion($terObject);
                if ($extensionToUpdate !== false) {
                    $extensions[$extensionKey]['updateAvailable'] = true;
                    $extensions[$extensionKey]['updateToVersion'] = $extensionToUpdate;
                }
            }
        }
        return $extensions;
    }

    /**
     * Tries to find given extension with given version in TER data.
     * If extension is found but not the given version, we return TER data from highest version with version data set to
     * given one.
     *
     * @param string $extensionKey Key of the extension
     * @param string $version String representation of version number
     * @return Extension|null Extension TER object or NULL if nothing found
     */
    protected function getExtensionTerData($extensionKey, $version)
    {
        $terObject = $this->extensionRepository->findOneByExtensionKeyAndVersion($extensionKey, $version);
        if (!$terObject instanceof Extension) {
            // Version unknown in TER data, try to find extension
            $terObject = $this->extensionRepository->findHighestAvailableVersion($extensionKey);
            if ($terObject instanceof Extension) {
                // Found in TER now, set version information to the known ones, so we can look if there is a newer one
                // Use a cloned object, otherwise wrong information is stored in persistenceManager
                $terObject = clone $terObject;
                $terObject->setVersion($version);
                $terObject->setIntegerVersion(
                    VersionNumberUtility::convertVersionNumberToInteger($terObject->getVersion())
                );
            } else {
                $terObject = null;
            }
        }

        return $terObject;
    }

    /**
     * Adds information about icon size to the extension information
     *
     * @param array $extensions
     * @return array
     */
    public function enrichExtensionsWithIconInformation(array $extensions)
    {
        foreach ($extensions as &$properties) {
            $extIconPath = Environment::getPublicPath() . '/' . $properties['siteRelPath'] . $properties['ext_icon'];
            if (@is_file($extIconPath)) {
                $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $extIconPath);
                $properties['ext_icon_width'] = $imageInfo->getWidth();
                $properties['ext_icon_height'] = $imageInfo->getHeight();
            } else {
                $properties['ext_icon_width'] = 0;
                $properties['ext_icon_height'] = 0;
            }
        }
        unset($properties);
        return $extensions;
    }

    /**
     * Gets all available and installed extension with additional information
     * from em_conf and TER (if available)
     *
     * @return array
     */
    public function getAvailableAndInstalledExtensionsWithAdditionalInformation()
    {
        $availableExtensions = $this->getAvailableExtensions();
        $availableAndInstalledExtensions = $this->getAvailableAndInstalledExtensions($availableExtensions);
        $availableAndInstalledExtensions = $this->enrichExtensionsWithIconInformation($availableAndInstalledExtensions);
        return $this->enrichExtensionsWithEmConfAndTerInformation($availableAndInstalledExtensions);
    }
}
