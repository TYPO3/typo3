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

namespace TYPO3\CMS\Extensionmanager\Utility;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception;
use TYPO3\CMS\Extensionmanager\Exception\MissingExtensionDependencyException;
use TYPO3\CMS\Extensionmanager\Exception\MissingVersionDependencyException;
use TYPO3\CMS\Extensionmanager\Exception\UnresolvedDependencyException;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;

/**
 * Utility for dealing with dependencies
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class DependencyUtility implements SingletonInterface
{
    /**
     * @var ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var ListUtility
     */
    protected $listUtility;

    /**
     * @var EmConfUtility
     */
    protected $emConfUtility;

    /**
     * @var ExtensionManagementService
     */
    protected $managementService;

    /**
     * @var array
     */
    protected $availableExtensions = [];

    /**
     * @var array
     */
    protected $dependencyErrors = [];

    /**
     * @var bool
     */
    protected $skipDependencyCheck = false;

    /**
     * @param ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param ListUtility $listUtility
     */
    public function injectListUtility(ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * @param EmConfUtility $emConfUtility
     */
    public function injectEmConfUtility(EmConfUtility $emConfUtility)
    {
        $this->emConfUtility = $emConfUtility;
    }

    /**
     * @param ExtensionManagementService $managementService
     */
    public function injectManagementService(ExtensionManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    /**
     * Setter for available extensions
     * gets available extensions from list utility if not already done
     */
    protected function setAvailableExtensions()
    {
        $this->availableExtensions = $this->listUtility->getAvailableExtensions();
    }

    /**
     * @param bool $skipDependencyCheck
     */
    public function setSkipDependencyCheck($skipDependencyCheck)
    {
        $this->skipDependencyCheck = $skipDependencyCheck;
    }

    /**
     * Checks dependencies for special cases (currently typo3 and php)
     *
     * @param Extension $extension
     */
    public function checkDependencies(Extension $extension)
    {
        $this->dependencyErrors = [];
        $dependencies = $extension->getDependencies();
        foreach ($dependencies as $dependency) {
            /** @var Dependency $dependency */
            $identifier = $dependency->getIdentifier();
            try {
                if (in_array($identifier, Dependency::$specialDependencies)) {
                    if ($this->skipDependencyCheck) {
                        continue;
                    }
                    if ($identifier === 'typo3') {
                        $this->checkTypo3Dependency($dependency, VersionNumberUtility::getNumericTypo3Version());
                    }
                    if ($identifier === 'php') {
                        $this->checkPhpDependency($dependency, PHP_VERSION);
                    }
                } elseif ($dependency->getType() === 'depends') {
                    $this->checkExtensionDependency($dependency);
                }
            } catch (UnresolvedDependencyException $e) {
                if (in_array($identifier, Dependency::$specialDependencies)) {
                    $extensionKey = $extension->getExtensionKey();
                } else {
                    $extensionKey = $identifier;
                }
                if (!isset($this->dependencyErrors[$extensionKey])) {
                    $this->dependencyErrors[$extensionKey] = [];
                }
                $this->dependencyErrors[$extensionKey][] = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Returns TRUE if a dependency error was found
     *
     * @return bool
     */
    public function hasDependencyErrors()
    {
        return !empty($this->dependencyErrors);
    }

    /**
     * Return the dependency errors
     *
     * @return array
     */
    public function getDependencyErrors(): array
    {
        return $this->dependencyErrors;
    }

    /**
     * Returns true if current TYPO3 version fulfills extension requirements
     *
     * @param Dependency $dependency
     * @param string $version
     * @return bool
     * @throws Exception\UnresolvedTypo3DependencyException
     */
    protected function checkTypo3Dependency(Dependency $dependency, string $version): bool
    {
        if ($dependency->getIdentifier() === 'typo3') {
            if (!($dependency->getLowestVersion() === '') && version_compare($version, $dependency->getLowestVersion()) === -1) {
                throw new Exception\UnresolvedTypo3DependencyException(
                    'Your TYPO3 version is lower than this extension requires. It requires TYPO3 versions ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion(),
                    1399144499
                );
            }
            if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), $version) === -1) {
                throw new Exception\UnresolvedTypo3DependencyException(
                    'Your TYPO3 version is higher than this extension requires. It requires TYPO3 versions ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion(),
                    1399144521
                );
            }
        } else {
            throw new Exception\UnresolvedTypo3DependencyException(
                'checkTypo3Dependency can only check TYPO3 dependencies. Found dependency with identifier "' . $dependency->getIdentifier() . '"',
                1399144551
            );
        }
        return true;
    }

    /**
     * Returns true if current php version fulfills extension requirements
     *
     * @param Dependency $dependency
     * @param string $version
     * @throws Exception\UnresolvedPhpDependencyException
     * @return bool
     */
    protected function checkPhpDependency(Dependency $dependency, string $version): bool
    {
        if ($dependency->getIdentifier() === 'php') {
            if (!($dependency->getLowestVersion() === '') && version_compare($version, $dependency->getLowestVersion()) === -1) {
                throw new Exception\UnresolvedPhpDependencyException(
                    'Your PHP version is lower than necessary. You need at least PHP version ' . $dependency->getLowestVersion(),
                    1377977857
                );
            }
            if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), $version) === -1) {
                throw new Exception\UnresolvedPhpDependencyException(
                    'Your PHP version is higher than allowed. You can use PHP versions ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion(),
                    1377977856
                );
            }
        } else {
            throw new Exception\UnresolvedPhpDependencyException(
                'checkPhpDependency can only check PHP dependencies. Found dependency with identifier "' . $dependency->getIdentifier() . '"',
                1377977858
            );
        }
        return true;
    }

    /**
     * Main controlling function for checking dependencies
     * Dependency check is done in the following way:
     * - installed extension in matching version ? - return true
     * - available extension in matching version ? - mark for installation
     * - remote (TER) extension in matching version? - mark for download
     *
     * @todo handle exceptions / markForUpload
     * @param Dependency $dependency
     * @throws Exception\MissingVersionDependencyException
     * @return bool
     */
    protected function checkExtensionDependency(Dependency $dependency)
    {
        $extensionKey = $dependency->getIdentifier();
        $extensionIsLoaded = $this->isDependentExtensionLoaded($extensionKey);
        if ($extensionIsLoaded === true) {
            if ($this->skipDependencyCheck || $this->isLoadedVersionCompatible($dependency)) {
                return true;
            }
            $extension = $this->listUtility->getExtension($extensionKey);
            $loadedVersion = $extension->getPackageMetaData()->getVersion();
            if (version_compare($loadedVersion, $dependency->getHighestVersion()) === -1) {
                try {
                    $this->downloadExtensionFromRemote($extensionKey, $dependency);
                } catch (UnresolvedDependencyException $e) {
                    throw new MissingVersionDependencyException(
                        'The extension ' . $extensionKey . ' is installed in version ' . $loadedVersion
                            . ' but needed in version ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion() . ' and could not be fetched from TER',
                        1396302624
                    );
                }
            } else {
                throw new MissingVersionDependencyException(
                    'The extension ' . $extensionKey . ' is installed in version ' . $loadedVersion .
                    ' but needed in version ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion(),
                    1430561927
                );
            }
        } else {
            $extensionIsAvailable = $this->isDependentExtensionAvailable($extensionKey);
            if ($extensionIsAvailable === true) {
                $isAvailableVersionCompatible = $this->isAvailableVersionCompatible($dependency);
                if ($isAvailableVersionCompatible) {
                    $unresolvedDependencyErrors = $this->dependencyErrors;
                    $this->managementService->markExtensionForInstallation($extensionKey);
                    $this->dependencyErrors = array_merge($unresolvedDependencyErrors, $this->dependencyErrors);
                } else {
                    $extension = $this->listUtility->getExtension($extensionKey);
                    $availableVersion = $extension->getPackageMetaData()->getVersion();
                    if (version_compare($availableVersion, $dependency->getHighestVersion()) === -1) {
                        try {
                            $this->downloadExtensionFromRemote($extensionKey, $dependency);
                        } catch (MissingExtensionDependencyException $e) {
                            if (!$this->skipDependencyCheck) {
                                throw new MissingVersionDependencyException(
                                    'The extension ' . $extensionKey . ' is available in version ' . $availableVersion
                                    . ' but is needed in version ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion() . ' and could not be fetched from TER',
                                    1430560390
                                );
                            }
                        }
                    } else {
                        if (!$this->skipDependencyCheck) {
                            throw new MissingVersionDependencyException(
                                'The extension ' . $extensionKey . ' is available in version ' . $availableVersion
                                . ' but is needed in version ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion(),
                                1430562374
                            );
                        }
                        // Dependency check is skipped and the local version has to be installed
                        $this->managementService->markExtensionForInstallation($extensionKey);
                    }
                }
            } else {
                $unresolvedDependencyErrors = $this->dependencyErrors;
                $this->downloadExtensionFromRemote($extensionKey, $dependency);
                $this->dependencyErrors = array_merge($unresolvedDependencyErrors, $this->dependencyErrors);
            }
        }

        return false;
    }

    /**
     * Handles checks to find a compatible extension version from TER to fulfill given dependency
     *
     * @param string $extensionKey
     * @param Dependency $dependency
     * @throws MissingExtensionDependencyException
     */
    protected function downloadExtensionFromRemote(string $extensionKey, Dependency $dependency)
    {
        if (!$this->isExtensionDownloadableFromRemote($extensionKey)) {
            if (!$this->skipDependencyCheck) {
                if ($this->extensionRepository->countAll() > 0) {
                    throw new MissingExtensionDependencyException(
                        'The extension ' . $extensionKey . ' is not available from TER.',
                        1399161266
                    );
                }
                throw new MissingExtensionDependencyException(
                    'The extension ' . $extensionKey . ' could not be checked. Please update your Extension-List from TYPO3 Extension Repository (TER).',
                    1430580308
                );
            }
            return;
        }

        $isDownloadableVersionCompatible = $this->isDownloadableVersionCompatible($dependency);
        if (!$isDownloadableVersionCompatible) {
            if (!$this->skipDependencyCheck) {
                throw new MissingVersionDependencyException(
                    'No compatible version found for extension ' . $extensionKey,
                    1399161284
                );
            }
            return;
        }

        $latestCompatibleExtensionByDependency = $this->getLatestCompatibleExtensionByDependency($dependency);
        if (!$latestCompatibleExtensionByDependency instanceof Extension) {
            if (!$this->skipDependencyCheck) {
                throw new MissingExtensionDependencyException(
                    'Could not resolve dependency for "' . $dependency->getIdentifier() . '"',
                    1399161302
                );
            }
            return;
        }

        if ($this->isDependentExtensionLoaded($extensionKey)) {
            $this->managementService->markExtensionForUpdate($latestCompatibleExtensionByDependency);
        } else {
            $this->managementService->markExtensionForDownload($latestCompatibleExtensionByDependency);
        }
    }

    /**
     * @param string $extensionKey
     * @return bool
     */
    protected function isDependentExtensionLoaded($extensionKey)
    {
        return ExtensionManagementUtility::isLoaded($extensionKey);
    }

    /**
     * @param Dependency $dependency
     * @return bool
     */
    protected function isLoadedVersionCompatible(Dependency $dependency): bool
    {
        $extensionVersion = ExtensionManagementUtility::getExtensionVersion($dependency->getIdentifier());
        return $dependency->isVersionCompatible($extensionVersion);
    }

    /**
     * Checks whether the needed extension is available
     * (not necessarily installed, but present in system)
     *
     * @param string $extensionKey
     * @return bool
     */
    protected function isDependentExtensionAvailable(string $extensionKey): bool
    {
        $this->setAvailableExtensions();
        return array_key_exists($extensionKey, $this->availableExtensions);
    }

    /**
     * Checks whether the available version is compatible
     *
     * @param Dependency $dependency
     * @return bool
     */
    protected function isAvailableVersionCompatible(Dependency $dependency): bool
    {
        $this->setAvailableExtensions();
        $extensionData = $this->emConfUtility->includeEmConf(
            $dependency->getIdentifier(),
            $this->availableExtensions[$dependency->getIdentifier()]['packagePath'] ?? ''
        );
        return $dependency->isVersionCompatible($extensionData['version']);
    }

    /**
     * Checks whether a ter extension with $extensionKey exists
     *
     * @param string $extensionKey
     * @return bool
     */
    protected function isExtensionDownloadableFromRemote(string $extensionKey): bool
    {
        return $this->extensionRepository->countByExtensionKey($extensionKey) > 0;
    }

    /**
     * Checks whether a compatible version of the extension exists in TER
     *
     * @param Dependency $dependency
     * @return bool
     */
    protected function isDownloadableVersionCompatible(Dependency $dependency): bool
    {
        $count = $this->extensionRepository->countByVersionRangeAndExtensionKey(
            $dependency->getIdentifier(),
            $dependency->getLowestVersionAsInteger(),
            $dependency->getHighestVersionAsInteger()
        );
        return !empty($count);
    }

    /**
     * Get the latest compatible version of an extension that's
     * compatible with the current core and PHP version.
     *
     * @param iterable $extensions
     * @return Extension|null
     */
    protected function getCompatibleExtension(iterable $extensions): ?Extension
    {
        foreach ($extensions as $extension) {
            /** @var Extension $extension */
            $this->checkDependencies($extension);
            $extensionKey = $extension->getExtensionKey();

            if (isset($this->dependencyErrors[$extensionKey])) {
                // reset dependencyErrors and continue with next version
                unset($this->dependencyErrors[$extensionKey]);
                continue;
            }

            return $extension;
        }

        return null;
    }

    /**
     * Get the latest compatible version of an extension that
     * fulfills the given dependency from TER
     *
     * @param Dependency $dependency
     * @return Extension|null
     */
    protected function getLatestCompatibleExtensionByDependency(Dependency $dependency): ?Extension
    {
        $compatibleDataSets = $this->extensionRepository->findByVersionRangeAndExtensionKeyOrderedByVersion(
            $dependency->getIdentifier(),
            $dependency->getLowestVersionAsInteger(),
            $dependency->getHighestVersionAsInteger()
        );
        return $this->getCompatibleExtension($compatibleDataSets);
    }
}
