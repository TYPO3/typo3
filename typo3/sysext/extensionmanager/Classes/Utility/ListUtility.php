<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <typo3@susannemoog.de>
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
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility for dealing with extension list related functions
 *
 * @TODO: Refactor this API class:
 * - The methods depend on each other, they take each others result, that could be done internally
 * - There is no good wording to distinguish existing and loaded extensions
 * - The name 'listUtility' is not good, the methods could be moved to some 'extensionInformationUtility', or a repository?
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ListUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility
	 * @inject
	 */
	protected $emConfUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 * @inject
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 * @inject
	 */
	protected $installUtility;

	/**
	 * @var \TYPO3\CMS\Core\Package\PackageManager
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * Returns the list of available, but not necessarily loaded extensions
	 *
	 * @return array Array with two sub-arrays, list array (all extensions with info) and category index
	 * @see getInstExtList()
	 */
	public function getAvailableExtensions() {
		$this->emitPackagesMayHaveChanged();
		$extensions = array();
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			$installationType = $this->getInstallTypeForPackage($package);
			$extensions[$package->getPackageKey()] = array(
				'siteRelPath' => str_replace(PATH_site, '', $package->getPackagePath()),
				'type' => $installationType,
				'key' => $package->getPackageKey(),
				'ext_icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon($package->getPackagePath()),
			);
		}
		return $extensions;
	}

	/**
	 * Emits packages may have changed signal
	 */
	protected function emitPackagesMayHaveChanged() {
		$this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
	}

	/**
	 * Returns "System", "Global" or "Local" based on extension position in filesystem.
	 *
	 * @param PackageInterface $package
	 * @return string
	 */
	protected function getInstallTypeForPackage(PackageInterface $package) {
		foreach (\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnInstallPaths() as $installType => $installPath) {
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
	public function getAvailableAndInstalledExtensions(array $availableExtensions) {
		foreach (array_keys($this->packageManager->getActivePackages()) as $extKey) {
			if (isset($availableExtensions[$extKey])) {
				$availableExtensions[$extKey]['installed'] = TRUE;
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
	public function enrichExtensionsWithEmConfAndTerInformation(array $extensions) {
		foreach ($extensions as $extensionKey => $properties) {
			$emconf = $this->emConfUtility->includeEmConf($properties);
			if ($emconf) {
				$extensions[$extensionKey] = array_merge($emconf, $properties);
				$terObject = $this->extensionRepository->findOneByExtensionKeyAndVersion($extensionKey, $extensions[$extensionKey]['version']);
				if ($terObject instanceof \TYPO3\CMS\Extensionmanager\Domain\Model\Extension) {
					$extensions[$extensionKey]['terObject'] = $terObject;
					$extensions[$extensionKey]['updateAvailable'] = $this->installUtility->isUpdateAvailable($terObject);
					$extensions[$extensionKey]['updateToVersion'] = $this->extensionRepository->findHighestAvailableVersion($extensionKey);
				}
			} else {
				unset($extensions[$extensionKey]);
			}
		}
		return $extensions;
	}

	/**
	 * Gets all available and installed extension with additional information
	 * from em_conf and TER (if available)
	 *
	 * @return array
	 */
	public function getAvailableAndInstalledExtensionsWithAdditionalInformation() {
		$availableExtensions = $this->getAvailableExtensions();
		$availableAndInstalledExtensions = $this->getAvailableAndInstalledExtensions($availableExtensions);
		return $this->enrichExtensionsWithEmConfAndTerInformation($availableAndInstalledExtensions);
	}
}
