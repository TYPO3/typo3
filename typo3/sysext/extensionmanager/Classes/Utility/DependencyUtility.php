<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <susanne.moog@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Utility for dealing with dependencies
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class DependencyUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 * @inject
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 * @inject
	 */
	protected $listUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility
	 * @inject
	 */
	protected $emConfUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService
	 * @inject
	 */
	protected $managementService;

	/**
	 * @var array
	 */
	protected $availableExtensions = array();

	/**
	 * @var string
	 */
	protected $localExtensionStorage = '';

	/**
	 * @param string $localExtensionStorage
	 */
	public function setLocalExtensionStorage($localExtensionStorage) {
		$this->localExtensionStorage = $localExtensionStorage;
	}

	/**
	 * Setter for available extensions
	 * gets available extensions from list utility if not already done
	 *
	 * @return void
	 */
	protected function setAvailableExtensions() {
		$this->availableExtensions = $this->listUtility->getAvailableExtensions();
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @throws ExtensionManagerException
	 */
	public function buildExtensionDependenciesTree(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$dependencies = $extension->getDependencies();
		$this->checkDependencies($dependencies);
	}

	/**
	 * Checks dependencies for special cases (currently typo3 and php)
	 *
	 * @param \SplObjectStorage $dependencies
	 * @throws ExtensionManagerException
	 * @return boolean
	 */
	protected function checkDependencies(\SplObjectStorage $dependencies) {
		$dependenciesToResolve = FALSE;
		foreach ($dependencies as $dependency) {
			/** @var Dependency $dependency */
			$identifier = strtolower($dependency->getIdentifier());
			if (in_array($identifier, Dependency::$specialDependencies)) {
				$methodname = 'check' . ucfirst($identifier) . 'Dependency';
				$this->{$methodname}($dependency);
			} else {
				if ($dependency->getType() === 'depends') {
					$dependenciesToResolve = !(bool) $this->checkExtensionDependency($dependency);
				}
			}
		}
		return $dependenciesToResolve;
	}

	/**
	 * Returns true if current TYPO3 version fulfills extension requirements
	 *
	 * @param Dependency $dependency
	 * @return boolean
	 * @throws ExtensionManagerException
	 */
	protected function checkTypo3Dependency(Dependency $dependency) {
		$lowerCaseIdentifier = strtolower($dependency->getIdentifier());
		if ($lowerCaseIdentifier === 'typo3') {
			if (!($dependency->getLowestVersion() === '') && version_compare(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getNumericTypo3Version(), $dependency->getLowestVersion()) === -1) {
				throw new ExtensionManagerException('Your TYPO3 version is lower than necessary. You need at least TYPO3 version ' . $dependency->getLowestVersion());
			}
			if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), \TYPO3\CMS\Core\Utility\VersionNumberUtility::getNumericTypo3Version()) === -1) {
				throw new ExtensionManagerException('Your TYPO3 version is higher than allowed. You can use TYPO3 versions ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion());
			}
		} else {
			throw new ExtensionManagerException('checkTypo3Dependency can only check TYPO3 dependencies. Found dependency with identifier "' . $dependency->getIdentifier() . '"');
		}
		return TRUE;
	}

	/**
	 * Returns true if current php version fulfills extension requirements
	 *
	 * @param Dependency $dependency
	 * @return boolean
	 * @throws ExtensionManagerException
	 */
	protected function checkPhpDependency(Dependency $dependency) {
		$lowerCaseIdentifier = strtolower($dependency->getIdentifier());
		if ($lowerCaseIdentifier === 'php') {
			if (!($dependency->getLowestVersion() === '') && version_compare(PHP_VERSION, $dependency->getLowestVersion()) === -1) {
				throw new ExtensionManagerException(
					'Your PHP version is lower than necessary. You need at least PHP version ' . $dependency->getLowestVersion(),
					 1377977857
				);
			}
			if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), PHP_VERSION) === -1) {
				throw new ExtensionManagerException(
					'Your PHP version is higher than allowed. You can use PHP versions ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion(),
					1377977856
				);
			}
		} else {
			throw new ExtensionManagerException(
				'checkPhpDependency can only check PHP dependencies. Found dependency with identifier "' . $dependency->getIdentifier() . '"',
				1377977858
			);
		}
		return TRUE;
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
	 * @throws ExtensionManagerException
	 * @return boolean
	 */
	protected function checkExtensionDependency(Dependency $dependency) {
		$extensionKey = $dependency->getIdentifier();
		$extensionIsLoaded = $this->isDependentExtensionLoaded($extensionKey);
		if ($extensionIsLoaded === TRUE) {
			$isLoadedVersionCompatible = $this->isLoadedVersionCompatible($dependency);
			if ($isLoadedVersionCompatible === TRUE) {
				return TRUE;
			} else {
				try {
					$this->getExtensionFromRepository($extensionKey, $dependency);
				} catch (ExtensionManagerException $e) {
					throw new ExtensionManagerException('The extension ' . $dependency->getIdentifier() . ' is needed in version ' . $dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion() . ', but could not be fetched from TER', 1396302624);
				}
			}
		} else {
			$extensionIsAvailable = $this->isDependentExtensionAvailable($extensionKey);
			if ($extensionIsAvailable === TRUE) {
				$isAvailableVersionCompatible = $this->isAvailableVersionCompatible($dependency);
				if ($isAvailableVersionCompatible) {
					$this->managementService->markExtensionForInstallation($extensionKey);
				} else {
					$this->getExtensionFromRepository($extensionKey, $dependency);
				}
			} else {
				$this->getExtensionFromRepository($extensionKey, $dependency);
			}
		}
		return FALSE;
	}

	/**
	 * Get an extension from a repository
	 * (might be in the extension itself or the TER)
	 *
	 * @param string $extensionKey
	 * @param Dependency $dependency
	 * @return void
	 */
	protected function getExtensionFromRepository($extensionKey, Dependency $dependency) {
		if (!$this->getExtensionFromInExtensionRepository($extensionKey, $dependency)) {
			$this->getExtensionFromTer($extensionKey, $dependency);
		}
	}

	/**
	 * Gets an extension from the in extension repository
	 * (the local extension storage)
	 *
	 * @param string $extensionKey
	 * @return boolean
	 */
	protected function getExtensionFromInExtensionRepository($extensionKey) {
		if ($this->localExtensionStorage !== '' && is_dir($this->localExtensionStorage)) {
			$extList = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs($this->localExtensionStorage);
			if (in_array($extensionKey, $extList)) {
				$this->managementService->markExtensionForCopy($extensionKey, $this->localExtensionStorage);
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Handles checks to find a compatible extension version from TER to fulfill given dependency
	 *
	 * @todo unit tests
	 * @param string $extensionKey
	 * @param Dependency $dependency
	 * @throws ExtensionManagerException
	 * @return void
	 */
	protected function getExtensionFromTer($extensionKey, Dependency $dependency) {
		if (!$this->isExtensionDownloadableFromTer($extensionKey)) {
			throw new ExtensionManagerException('The extension ' . $extensionKey . ' is not available from TER.');
		}

		if (!$this->isDownloadableVersionCompatible($dependency)) {
			throw new ExtensionManagerException('No compatible version found for extension ' . $extensionKey);
		}

		$latestCompatibleExtensionByIntegerVersionDependency = $this->getLatestCompatibleExtensionByIntegerVersionDependency($dependency);
		if (!$latestCompatibleExtensionByIntegerVersionDependency instanceof \TYPO3\CMS\Extensionmanager\Domain\Model\Extension) {
			throw new ExtensionManagerException('Could not resolve dependency for "' . $dependency->getIdentifier() . '"');
		}

		if ($this->isDependentExtensionLoaded($extensionKey)) {
			$this->managementService->markExtensionForUpdate($latestCompatibleExtensionByIntegerVersionDependency);
		} else {
			$this->managementService->markExtensionForDownload($latestCompatibleExtensionByIntegerVersionDependency);
		}
	}

	/**
	 * @param string $extensionKey
	 * @return boolean
	 */
	protected function isDependentExtensionLoaded($extensionKey) {
		return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey);
	}

	/**
	 * @param Dependency $dependency
	 * @return boolean
	 */
	protected function isLoadedVersionCompatible(Dependency $dependency) {
		$extensionVersion = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion($dependency->getIdentifier());
		return $this->isVersionCompatible($extensionVersion, $dependency);
	}

	/**
	 * @param string $version
	 * @param Dependency $dependency
	 * @return boolean
	 */
	protected function isVersionCompatible($version, Dependency $dependency) {
		if (!($dependency->getLowestVersion() === '') && version_compare($version, $dependency->getLowestVersion()) === -1) {
			return FALSE;
		}
		if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), $version) === -1) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Checks whether the needed extension is available
	 * (not necessarily installed, but present in system)
	 *
	 * @param string $extensionKey
	 * @return boolean
	 */
	protected function isDependentExtensionAvailable($extensionKey) {
		$this->setAvailableExtensions();
		return array_key_exists($extensionKey, $this->availableExtensions);
	}

	/**
	 * Checks whether the available version is compatible
	 *
	 * @param Dependency $dependency
	 * @return boolean
	 */
	protected function isAvailableVersionCompatible(Dependency $dependency) {
		$this->setAvailableExtensions();
		$extensionData = $this->emConfUtility->includeEmConf($this->availableExtensions[$dependency->getIdentifier()]);
		return $this->isVersionCompatible($extensionData['version'], $dependency);
	}

	/**
	 * Checks whether a ter extension with $extensionKey exists
	 *
	 * @param string $extensionKey
	 * @return boolean
	 */
	protected function isExtensionDownloadableFromTer($extensionKey) {
		return $this->extensionRepository->countByExtensionKey($extensionKey) > 0;
	}

	/**
	 * Checks whether a compatible version of the extension exists in TER
	 *
	 * @param Dependency $dependency
	 * @return boolean
	 */
	protected function isDownloadableVersionCompatible(Dependency $dependency) {
		$versions = $this->getLowestAndHighestIntegerVersions($dependency);
		return count($this->extensionRepository->countByVersionRangeAndExtensionKey($dependency->getIdentifier(), $versions['lowestIntegerVersion'], $versions['highestIntegerVersion'])) > 0;
	}

	/**
	 * Get the latest compatible version of an extension that
	 * fulfills the given dependency from TER
	 *
	 * @param Dependency $dependency
	 * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
	 */
	protected function getLatestCompatibleExtensionByIntegerVersionDependency(Dependency $dependency) {
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
	protected function getLowestAndHighestIntegerVersions(Dependency $dependency) {
		$lowestVersion = $dependency->getLowestVersion();
		$lowestVersionInteger = $lowestVersion ? \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($lowestVersion) : 0;
		$highestVersion = $dependency->getHighestVersion();
		$highestVersionInteger = $highestVersion ? \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($highestVersion) : 0;
		return array(
			'lowestIntegerVersion' => $lowestVersionInteger,
			'highestIntegerVersion' => $highestVersionInteger
		);
	}

	public function findInstalledExtensionsThatDependOnMe($extensionKey) {
		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
		$dependentExtensions = array();
		foreach ($availableAndInstalledExtensions as $availableAndInstalledExtensionKey => $availableAndInstalledExtension) {
			if (isset($availableAndInstalledExtension['installed']) && $availableAndInstalledExtension['installed'] === TRUE) {
				if (is_array($availableAndInstalledExtension['constraints']) && is_array($availableAndInstalledExtension['constraints']['depends']) && array_key_exists($extensionKey, $availableAndInstalledExtension['constraints']['depends'])) {
					$dependentExtensions[] = $availableAndInstalledExtensionKey;
				}
			}
		}
		return $dependentExtensions;
	}

}
