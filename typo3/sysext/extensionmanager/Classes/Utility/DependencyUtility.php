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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception;

/**
 * Utility for dealing with dependencies
 */
class DependencyUtility implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
     */
    protected $listUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility
     */
    protected $emConfUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService
     */
    protected $managementService;

    /**
     * @var array
     */
    protected $availableExtensions = [];

    /**
     * @var string
     */
    protected $localExtensionStorage = '';

    /**
     * @var array
     */
    protected $dependencyErrors = [];

    /**
     * @var bool
     */
    protected $skipDependencyCheck = false;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
     */
    public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility $emConfUtility
     */
    public function injectEmConfUtility(\TYPO3\CMS\Extensionmanager\Utility\EmConfUtility $emConfUtility)
    {
        $this->emConfUtility = $emConfUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService
     */
    public function injectManagementService(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    /**
     * @param string $localExtensionStorage
     * @return void
     */
    public function setLocalExtensionStorage($localExtensionStorage)
    {
        $this->localExtensionStorage = $localExtensionStorage;
    }

    /**
     * Setter for available extensions
     * gets available extensions from list utility if not already done
     *
     * @return void
     */
    protected function setAvailableExtensions()
    {
        $this->availableExtensions = $this->listUtility->getAvailableExtensions();
    }

    /**
     * @param bool $skipDependencyCheck
     * @return void
     */
    public function setSkipDependencyCheck($skipDependencyCheck)
    {
        $this->skipDependencyCheck = $skipDependencyCheck;
    }

    /**
     * Checks dependencies for special cases (currently typo3 and php)
     *
     * @param Extension $extension
     * @return void
     */
    public function checkDependencies(Extension $extension)
    {
        $this->dependencyErrors = [];
        $dependencies = $extension->getDependencies();
        foreach ($dependencies as $dependency) {
            /** @var Dependency $dependency */
            $identifier = strtolower($dependency->getIdentifier());
            try {
                if (in_array($identifier, Dependency::$specialDependencies)) {
                    if (!$this->skipDependencyCheck) {
                        $methodName = 'check' . ucfirst($identifier) . 'Dependency';
                        $this->{$methodName}($dependency);
                    }
                } else {
                    if ($dependency->getType() === 'depends') {
                        $this->checkExtensionDependency($dependency);
                    }
                }
            } catch (Exception\UnresolvedDependencyException $e) {
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
                    'message' => $e->getMessage()
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
    public function getDependencyErrors()
    {
        return $this->dependencyErrors;
    }

    /**
     * Returns true if current TYPO3 version fulfills extension requirements
     *
     * @param Dependency $dependency
     * @throws Exception\UnresolvedTypo3DependencyException
     * @return bool
     */
    protected function checkTypo3Dependency(Dependency $dependency)
    {
        $lowerCaseIdentifier = strtolower($dependency->getIdentifier());
        if ($lowerCaseIdentifier === 'typo3') {
            if (!($dependency->getLowestVersion() === '') && version_compare(VersionNumberUtility::getNumericTypo3Version(), $dependency->getLowestVersion()) === -1) {
                throw new Exception\UnresolvedTypo3DependencyException(
                    'Your TYPO3 version is lower than this extension requires. It requires TYPO3 versions ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion(),
                    1399144499
                );
            }
            if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), VersionNumberUtility::getNumericTypo3Version()) === -1) {
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
     * @throws Exception\UnresolvedPhpDependencyException
     * @return bool
     */
    protected function checkPhpDependency(Dependency $dependency)
    {
        $lowerCaseIdentifier = strtolower($dependency->getIdentifier());
        if ($lowerCaseIdentifier === 'php') {
            if (!($dependency->getLowestVersion() === '') && version_compare(PHP_VERSION, $dependency->getLowestVersion()) === -1) {
                throw new Exception\UnresolvedPhpDependencyException(
                    'Your PHP version is lower than necessary. You need at least PHP version ' . $dependency->getLowestVersion(),
                     1377977857
                );
            }
            if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), PHP_VERSION) === -1) {
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
            $isLoadedVersionCompatible = $this->isLoadedVersionCompatible($dependency);
            if ($isLoadedVersionCompatible === true || $this->skipDependencyCheck) {
                return true;
            }
            $extension = $this->listUtility->getExtension($extensionKey);
            $loadedVersion = $extension->getPackageMetaData()->getVersion();
            if (version_compare($loadedVersion, $dependency->getHighestVersion()) === -1) {
                try {
                    $this->getExtensionFromRepository($extensionKey, $dependency);
                } catch (Exception\UnresolvedDependencyException $e) {
                    throw new Exception\MissingVersionDependencyException(
                        'The extension ' . $extensionKey . ' is installed in version ' . $loadedVersion
                            . ' but needed in version ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion() . ' and could not be fetched from TER',
                        1396302624
                    );
                }
            } else {
                throw new Exception\MissingVersionDependencyException(
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
                            $this->getExtensionFromRepository($extensionKey, $dependency);
                        } catch (Exception\MissingExtensionDependencyException $e) {
                            if (!$this->skipDependencyCheck) {
                                throw new Exception\MissingVersionDependencyException(
                                    'The extension ' . $extensionKey . ' is available in version ' . $availableVersion
                                    . ' but is needed in version ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion() . ' and could not be fetched from TER',
                                    1430560390
                                );
                            }
                        }
                    } else {
                        if (!$this->skipDependencyCheck) {
                            throw new Exception\MissingVersionDependencyException(
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
                $this->getExtensionFromRepository($extensionKey, $dependency);
                $this->dependencyErrors = array_merge($unresolvedDependencyErrors, $this->dependencyErrors);
            }
        }

        return false;
    }

    /**
     * Get an extension from a repository
     * (might be in the extension itself or the TER)
     *
     * @param string $extensionKey
     * @param Dependency $dependency
     * @return void
     * @throws Exception\UnresolvedDependencyException
     */
    protected function getExtensionFromRepository($extensionKey, Dependency $dependency)
    {
        if (!$this->getExtensionFromInExtensionRepository($extensionKey)) {
            $this->getExtensionFromTer($extensionKey, $dependency);
        }
    }

    /**
     * Gets an extension from the in extension repository
     * (the local extension storage)
     *
     * @param string $extensionKey
     * @return bool
     */
    protected function getExtensionFromInExtensionRepository($extensionKey)
    {
        if ($this->localExtensionStorage !== '' && is_dir($this->localExtensionStorage)) {
            $extList = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs($this->localExtensionStorage);
            if (in_array($extensionKey, $extList)) {
                $this->managementService->markExtensionForCopy($extensionKey, $this->localExtensionStorage);
                return true;
            }
        }
        return false;
    }

    /**
     * Handles checks to find a compatible extension version from TER to fulfill given dependency
     *
     * @todo unit tests
     * @param string $extensionKey
     * @param Dependency $dependency
     * @throws Exception\UnresolvedDependencyException
     * @return void
     */
    protected function getExtensionFromTer($extensionKey, Dependency $dependency)
    {
        $isExtensionDownloadableFromTer = $this->isExtensionDownloadableFromTer($extensionKey);
        if (!$isExtensionDownloadableFromTer) {
            if (!$this->skipDependencyCheck) {
                if ($this->extensionRepository->countAll() > 0) {
                    throw new Exception\MissingExtensionDependencyException(
                        'The extension ' . $extensionKey . ' is not available from TER.',
                        1399161266
                    );
                } else {
                    throw new Exception\MissingExtensionDependencyException(
                        'The extension ' . $extensionKey . ' could not be checked. Please update your Extension-List from TYPO3 Extension Repository (TER).',
                        1430580308
                    );
                }
            }
            return;
        }

        $isDownloadableVersionCompatible = $this->isDownloadableVersionCompatible($dependency);
        if (!$isDownloadableVersionCompatible) {
            if (!$this->skipDependencyCheck) {
                throw new Exception\MissingVersionDependencyException(
                    'No compatible version found for extension ' . $extensionKey,
                    1399161284
                );
            }
            return;
        }

        $latestCompatibleExtensionByIntegerVersionDependency = $this->getLatestCompatibleExtensionByIntegerVersionDependency($dependency);
        if (!$latestCompatibleExtensionByIntegerVersionDependency instanceof Extension) {
            if (!$this->skipDependencyCheck) {
                throw new Exception\MissingExtensionDependencyException(
                    'Could not resolve dependency for "' . $dependency->getIdentifier() . '"',
                    1399161302
                );
            }
            return;
        }

        if ($this->isDependentExtensionLoaded($extensionKey)) {
            $this->managementService->markExtensionForUpdate($latestCompatibleExtensionByIntegerVersionDependency);
        } else {
            $this->managementService->markExtensionForDownload($latestCompatibleExtensionByIntegerVersionDependency);
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
    protected function isLoadedVersionCompatible(Dependency $dependency)
    {
        $extensionVersion = ExtensionManagementUtility::getExtensionVersion($dependency->getIdentifier());
        return $this->isVersionCompatible($extensionVersion, $dependency);
    }

    /**
     * @param string $version
     * @param Dependency $dependency
     * @return bool
     */
    protected function isVersionCompatible($version, Dependency $dependency)
    {
        if (!($dependency->getLowestVersion() === '') && version_compare($version, $dependency->getLowestVersion()) === -1) {
            return false;
        }
        if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), $version) === -1) {
            return false;
        }
        return true;
    }

    /**
     * Checks whether the needed extension is available
     * (not necessarily installed, but present in system)
     *
     * @param string $extensionKey
     * @return bool
     */
    protected function isDependentExtensionAvailable($extensionKey)
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
    protected function isAvailableVersionCompatible(Dependency $dependency)
    {
        $this->setAvailableExtensions();
        $extensionData = $this->emConfUtility->includeEmConf($this->availableExtensions[$dependency->getIdentifier()]);
        return $this->isVersionCompatible($extensionData['version'], $dependency);
    }

    /**
     * Checks whether a ter extension with $extensionKey exists
     *
     * @param string $extensionKey
     * @return bool
     */
    protected function isExtensionDownloadableFromTer($extensionKey)
    {
        return $this->extensionRepository->countByExtensionKey($extensionKey) > 0;
    }

    /**
     * Checks whether a compatible version of the extension exists in TER
     *
     * @param Dependency $dependency
     * @return bool
     */
    protected function isDownloadableVersionCompatible(Dependency $dependency)
    {
        $versions = $this->getLowestAndHighestIntegerVersions($dependency);
        $count = $this->extensionRepository->countByVersionRangeAndExtensionKey(
            $dependency->getIdentifier(), $versions['lowestIntegerVersion'], $versions['highestIntegerVersion']
        );
        return !empty($count);
    }

    /**
     * Get the latest compatible version of an extension that
     * fulfills the given dependency from TER
     *
     * @param Dependency $dependency
     * @return Extension
     */
    protected function getLatestCompatibleExtensionByIntegerVersionDependency(Dependency $dependency)
    {
        $versions = $this->getLowestAndHighestIntegerVersions($dependency);
        $compatibleDataSets = $this->extensionRepository->findByVersionRangeAndExtensionKeyOrderedByVersion(
            $dependency->getIdentifier(),
            $versions['lowestIntegerVersion'],
            $versions['highestIntegerVersion']
        );
        return $compatibleDataSets->getFirst();
    }

    /**
     * Return array of lowest and highest version of dependency as integer
     *
     * @param Dependency $dependency
     * @return array
     */
    protected function getLowestAndHighestIntegerVersions(Dependency $dependency)
    {
        $lowestVersion = $dependency->getLowestVersion();
        $lowestVersionInteger = $lowestVersion ? VersionNumberUtility::convertVersionNumberToInteger($lowestVersion) : 0;
        $highestVersion = $dependency->getHighestVersion();
        $highestVersionInteger = $highestVersion ? VersionNumberUtility::convertVersionNumberToInteger($highestVersion) : 0;
        return [
            'lowestIntegerVersion' => $lowestVersionInteger,
            'highestIntegerVersion' => $highestVersionInteger
        ];
    }

    /**
     * @param string $extensionKey
     * @return array
     */
    public function findInstalledExtensionsThatDependOnMe($extensionKey)
    {
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $dependentExtensions = [];
        foreach ($availableAndInstalledExtensions as $availableAndInstalledExtensionKey => $availableAndInstalledExtension) {
            if (isset($availableAndInstalledExtension['installed']) && $availableAndInstalledExtension['installed'] === true) {
                if (is_array($availableAndInstalledExtension['constraints']) && is_array($availableAndInstalledExtension['constraints']['depends']) && array_key_exists($extensionKey, $availableAndInstalledExtension['constraints']['depends'])) {
                    $dependentExtensions[] = $availableAndInstalledExtensionKey;
                }
            }
        }
        return $dependentExtensions;
    }

    /**
     * Get extensions (out of a given list) that are suitable for the current TYPO3 version
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array $extensions List of extensions to check
     * @return array List of extensions suitable for current TYPO3 version
     */
    public function getExtensionsSuitableForTypo3Version($extensions)
    {
        $suitableExtensions = [];
        /** @var Extension $extension */
        foreach ($extensions as $extension) {
            /** @var Dependency $dependency */
            foreach ($extension->getDependencies() as $dependency) {
                if ($dependency->getIdentifier() === 'typo3') {
                    try {
                        if ($this->checkTypo3Dependency($dependency)) {
                            array_push($suitableExtensions, $extension);
                        }
                    } catch (Exception\UnresolvedTypo3DependencyException $e) {
                    }
                    break;
                }
            }
        }
        return $suitableExtensions;
    }
}
