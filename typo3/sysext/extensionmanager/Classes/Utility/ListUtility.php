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
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	public $objectManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility
	 */
	public $emConfUtility;

	/**
	 * Inject emConfUtility
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility $emConfUtility
	 * @return void
	 */
	public function injectEmConfUtility(\TYPO3\CMS\Extensionmanager\Utility\EmConfUtility $emConfUtility) {
		$this->emConfUtility = $emConfUtility;
	}

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 */
	public $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 */
	protected $installUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility
	 * @return void
	 */
	public function injectInstallUtility(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility) {
		$this->installUtility = $installUtility;
	}

	/**
	 * Inject emConfUtility
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * Returns the list of available, but not necessarily loaded extensions
	 *
	 * @return array Array with two sub-arrays, list array (all extensions with info) and category index
	 * @see getInstExtList()
	 */
	public function getAvailableExtensions() {
		$extensions = array();
		$paths = \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnInstallPaths();
		foreach ($paths as $installationType => $path) {
			try {
				if (is_dir($path)) {
					$extList = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs($path);
					if (is_array($extList)) {
						foreach ($extList as $extKey) {
							$extensions[$extKey] = array(
								'siteRelPath' => str_replace(PATH_site, '', $path . $extKey),
								'type' => $installationType,
								'key' => $extKey,
								'ext_icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon($path . $extKey . '/')
							);
						}
					}
				}
			} catch (\Exception $e) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($e->getMessage(), 'extensionmanager');
			}
		}
		return $extensions;
	}

	/**
	 * Enrich the output of getAvailableExtensions() with an array key installed = 1 if an extension is loaded.
	 *
	 * @param array $availableExtensions
	 * @return array
	 */
	public function getAvailableAndInstalledExtensions(array $availableExtensions) {
		foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $extKey => $properties) {
			if (array_key_exists($extKey, $availableExtensions)) {
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


?>