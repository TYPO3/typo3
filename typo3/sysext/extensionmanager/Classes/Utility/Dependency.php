<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog <susanne.moog@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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

/**
 * Utility for dealing with dependencies
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Utility_Dependency implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var Tx_Extensionmanager_Utility_List
	 */
	protected $listUtility;

	/**
	 * @var Tx_Extensionmanager_Utility_EmConf
	 */
	protected $emConfUtility;

	/**
	 * @var Tx_Extensionmanager_Service_Management
	 */
	protected $managementService;

	/**
	 * @var array
	 */
	protected $availableExtensions = array();

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_List $listUtility
	 * @return void
	 */
	public function injectListUtility(Tx_Extensionmanager_Utility_List $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_EmConf $emConfUtility
	 * @return void
	 */
	public function injectEmConfUtility(Tx_Extensionmanager_Utility_EmConf $emConfUtility) {
		$this->emConfUtility = $emConfUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Service_Management $managementService
	 * @return void
	 */
	public function injectManagementService(Tx_Extensionmanager_Service_Management $managementService) {
		$this->managementService = $managementService;
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
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return void
	 */
	public function buildExtensionDependenciesTree($extension) {
		$dependencies = $extension->getDependencies();
		$this->checkDependencies($dependencies);
	}

	/**
	 * @param string $dependencies
	 * @return SplObjectStorage
	 */
	public function convertDependenciesToObjects($dependencies) {
		$unserializedDependencies = unserialize($dependencies);
		$dependenciesObject = new SplObjectStorage();
		foreach ($unserializedDependencies as $dependencyType => $dependencyValues) {
			foreach ($dependencyValues as $dependency => $versions) {
				if ($dependencyType && $dependency) {
					list($highest, $lowest) = t3lib_utility_VersionNumber::convertVersionsStringToVersionNumbers($versions);
					/** @var $dependencyObject Tx_Extensionmanager_Domain_Model_Dependency */
					$dependencyObject = $this->objectManager->create('Tx_Extensionmanager_Domain_Model_Dependency');
					$dependencyObject->setType($dependencyType);
					$dependencyObject->setIdentifier($dependency);
					$dependencyObject->setLowestVersion($lowest);
					$dependencyObject->setHighestVersion($highest);
					$dependenciesObject->attach($dependencyObject);
					unset($dependencyObject);
				}
			}
		}
		return $dependenciesObject;
	}

	/**
	 * Checks dependencies for special cases (currently typo3 and php)
	 *
	 * @param SplObjectStorage $dependencies
	 * @return boolean
	 */
	protected function checkDependencies(SplObjectStorage $dependencies) {
		$dependenciesToResolve = FALSE;
		foreach ($dependencies as $dependency) {
			$identifier = strtolower($dependency->getIdentifier());
			if (in_array($identifier, Tx_Extensionmanager_Domain_Model_Dependency::$specialDependencies)) {
				$methodname = 'check' .  ucfirst($identifier) . 'Dependency';
				try {
					$this->{$methodname}($dependency);
				} catch (Tx_Extensionmanager_Exception_ExtensionManager $e) {
					$this->errors[] = array(
						'identifier' => $identifier,
						'message' => $e->getMessage()
					);
				}
			} else {
				if ($dependency->getType() === 'depends') {
					$dependenciesToResolve = !((bool)$this->checkExtensionDependency($dependency));
				}
			}
		}
		return $dependenciesToResolve;
	}

	/**
	 * Returns true if current TYPO3 version fulfills extension requirements
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return boolean
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 */
	protected function checkTypo3Dependency(Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
		$lowerCaseIdentifier = strtolower($dependency->getIdentifier());
		if ($lowerCaseIdentifier === 'typo3') {
			if (
				!($dependency->getLowestVersion() === '') &&
				version_compare(t3lib_utility_VersionNumber::getNumericTypo3Version(), $dependency->getLowestVersion()) === -1
			) {
				throw new Tx_Extensionmanager_Exception_ExtensionManager(
					'Your TYPO3 version is lower than necessary. You need at least TYPO3 version ' .
						$dependency->getLowestVersion()
				);
			}
			if (
				!($dependency->getHighestVersion() === '') &&
				version_compare($dependency->getHighestVersion(), t3lib_utility_VersionNumber::getNumericTypo3Version()) === -1
			) {
				throw new Tx_Extensionmanager_Exception_ExtensionManager(
					'Your TYPO3 version is higher than allowed. You can use TYPO3 versions ' .
						$dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion()
				);
			}
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				'checkTypo3Dependency can only check TYPO3 dependencies. Found dependency with identifier "' .
					$dependency->getIdentifier() . '"'
			);
		}
		return TRUE;
	}

	/**
	 * Returns true if current php version fulfills extension requirements
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return boolean
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 */
	protected function checkPhpDependency(Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
		$lowerCaseIdentifier = strtolower($dependency->getIdentifier());
		if ($lowerCaseIdentifier === 'php') {
			if (!($dependency->getLowestVersion() === '') && version_compare(PHP_VERSION, $dependency->getLowestVersion()) === -1) {
				throw new Tx_Extensionmanager_Exception_ExtensionManager(
					'Your PHP version is lower than necessary. You need at least PHP version ' .
						$dependency->getLowestVersion()
				);
			}
			if (!($dependency->getHighestVersion() === '') && version_compare($dependency->getHighestVersion(), PHP_VERSION) === -1) {
				throw new Tx_Extensionmanager_Exception_ExtensionManager(
					'Your PHP version is higher than allowed. You can use PHP versions ' .
						$dependency->getLowestVersion() . ' - ' . $dependency->getHighestVersion()
				);
			}
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				'checkPhpDependency can only check PHP dependencies. Found dependency with identifier "' .
					$dependency->getIdentifier() . '"'
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
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return boolean
	 */
	protected function checkExtensionDependency(Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
		$extensionKey = $dependency->getIdentifier();
		$extensionIsLoaded = $this->isDependentExtensionLoaded($extensionKey);
		if ($extensionIsLoaded === TRUE) {
			$isLoadedVersionCompatible = $this->isLoadedVersionCompatible($dependency);
			if ($isLoadedVersionCompatible === TRUE) {
				return TRUE;
			} else {
				$this->getExtensionFromTer($extensionKey, $dependency);
			}
		} else {
			$extensionIsAvailable = $this->isDependentExtensionAvailable($extensionKey);
			if ($extensionIsAvailable === TRUE) {
				$isAvailableVersionCompatible = $this->isAvailableVersionCompatible($dependency);
				if ($isAvailableVersionCompatible) {
					$this->managementService->markExtensionForInstallation($extensionKey);
				} else {
					$this->getExtensionFromTer($extensionKey, $dependency);
				}
			} else {
				$this->getExtensionFromTer($extensionKey, $dependency);
			}
		}
		return FALSE;
	}

	/**
	 * Handles checks to find a compatible extension version from TER
	 * to fulfill given dependency
	 *
	 * @todo unit tests
	 * @param string $extensionKey
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	protected function getExtensionFromTer($extensionKey, Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
		$isExtensionDownloadableFromTer = $this->isExtensionDownloadableFromTer($extensionKey);
		if ($isExtensionDownloadableFromTer === TRUE) {
			$isDownloadableVersionCompatible = $this->isDownloadableVersionCompatible($dependency);
			if ($isDownloadableVersionCompatible === TRUE) {
				$latestCompatibleExtensionByIntegerVersionDependency = $this->getLatestCompatibleExtensionByIntegerVersionDependency(
					$dependency
				);
				if ($latestCompatibleExtensionByIntegerVersionDependency instanceof Tx_Extensionmanager_Domain_Model_Extension) {
					if ($this->isDependentExtensionLoaded($extensionKey)) {
						$this->managementService->markExtensionForUpdate($latestCompatibleExtensionByIntegerVersionDependency);
					} else {
						$this->managementService->markExtensionForDownload($latestCompatibleExtensionByIntegerVersionDependency);
					}
				} else {
					throw new Tx_Extensionmanager_Exception_ExtensionManager(
						'Something went wrong.'
					);
				}
			} else {
				throw new Tx_Extensionmanager_Exception_ExtensionManager(
					'No compatible version found for extension ' . $extensionKey
				);
			}
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				'The extension ' . $extensionKey . ' is not available from TER.'
			);
		}
	}

	/**
	 * @param string $extensionKey
	 * @return bool
	 */
	protected function isDependentExtensionLoaded($extensionKey) {
		return t3lib_extMgm::isLoaded($extensionKey);
	}

	/**
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return boolean
	 */
	protected function isLoadedVersionCompatible(Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
		$extensionVersion = t3lib_extMgm::getExtensionVersion($dependency->getIdentifier());
		return $this->isVersionCompatible($extensionVersion, $dependency);
	}

	/**
	 * @param string $version
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return boolean
	 */
	protected function isVersionCompatible($version, Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
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
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return boolean
	 */
	protected function isAvailableVersionCompatible(Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
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
		return ($this->extensionRepository->countByExtensionKey($extensionKey) > 0);
	}

	/**
	 * Checks whether a compatible version of the extension exists in TER
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return boolean
	 */
	protected function isDownloadableVersionCompatible(Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
		$versions = $this->getLowestAndHighestIntegerVersions($dependency);
		return (
			count(
				$this->extensionRepository->countByVersionRangeAndExtensionKey(
					$dependency->getIdentifier(),
					$versions['lowestIntegerVersion'],
					$versions['highestIntegerVersion']
				)
			)
			> 0
		);
	}

	/**
	 * Get the latest compatible version of an extension that
	 * fulfills the given dependency from TER
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return Tx_Extensionmanager_Domain_Model_Extension
	 */
	protected function getLatestCompatibleExtensionByIntegerVersionDependency(
		Tx_Extensionmanager_Domain_Model_Dependency $dependency
	) {
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
	 * @param Tx_Extensionmanager_Domain_Model_Dependency $dependency
	 * @return array
	 */
	protected function getLowestAndHighestIntegerVersions(Tx_Extensionmanager_Domain_Model_Dependency $dependency) {
		$lowestVersion = $dependency->getLowestVersion();
		$lowestVersionInteger = $lowestVersion ? t3lib_utility_VersionNumber::convertVersionNumberToInteger($lowestVersion) : 0;
		$highestVersion = $dependency->getHighestVersion();
		$highestVersionInteger = $highestVersion ? t3lib_utility_VersionNumber::convertVersionNumberToInteger($highestVersion) : 0;

		return array (
			'lowestIntegerVersion' => $lowestVersionInteger,
			'highestIntegerVersion' => $highestVersionInteger
		);
	}

	public function findInstalledExtensionsThatDependOnMe($extensionKey) {
		$availableExtensions = $this->listUtility->getAvailableExtensions();
		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensions($availableExtensions);
		$availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(
			$availableAndInstalledExtensions
		);
		$dependentExtensions = array();
		foreach ($availableAndInstalledExtensions as $availableAndInstalledExtensionKey => $availableAndInstalledExtension) {
			if (
				isset($availableAndInstalledExtension['installed']) &&
				$availableAndInstalledExtension['installed'] === TRUE
			) {
				if (
					is_array($availableAndInstalledExtension['constraints']) &&
					is_array($availableAndInstalledExtension['constraints']['depends']) &&
					array_key_exists($extensionKey, $availableAndInstalledExtension['constraints']['depends'])
				) {
					$dependentExtensions[] = $availableAndInstalledExtensionKey;
				}
			}
		}
		return $dependentExtensions;
	}

}
?>