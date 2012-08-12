<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012
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
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Utility_List implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	public $objectManager;

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @var Tx_Extensionmanager_Utility_EmConf
	 */
	public $emConfUtility;

	/**
	 * Inject emConfUtility
	 *
	 * @param Tx_Extensionmanager_Utility_EmConf $emConfUtility
	 * @return void
	 */
	public function injectEmConfUtility(Tx_Extensionmanager_Utility_EmConf $emConfUtility) {
		$this->emConfUtility = $emConfUtility;
	}

	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ExtensionRepository
	 */
	public $extensionRepository;

	/**
	 * @var Tx_Extensionmanager_Utility_Install
	 */
	protected $installUtility;

	/**
	 * @param Tx_Extensionmanager_Utility_Install $installUtility
	 * @return void
	 */
	public function injectInstallUtility(Tx_Extensionmanager_Utility_Install $installUtility) {
		$this->installUtility = $installUtility;
	}

	/**
	 * Inject emConfUtility
	 *
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}
	/**
	 * Returns the list of available (installed) extensions
	 *
	 * @return array Array with two sub-arrays, list array (all extensions with info) and category index
	 * @see getInstExtList()
	 */
	public function getAvailableExtensions() {
		$extensions = array();
		$paths = Tx_Extensionmanager_Domain_Model_Extension::returnInstallPaths();
		foreach ($paths as $installationType => $path) {
			try {
				if (is_dir($path)) {
					$extList = t3lib_div::get_dirs($path);
					if (is_array($extList)) {
						foreach ($extList as $extKey) {
							$extensions[$extKey] = array(
								'siteRelPath' => str_replace(PATH_site, '', $path . $extKey),
								'type' => $installationType,
								'key' => $extKey
							);
						}
					}
				}
			} catch (Exception $e) {
				t3lib_div::sysLog($e->getMessage(), 'extensionmanager');
			}
		}
		return $extensions;
	}

	/**
	 * Reduce the available extensions list to only installed extensions
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
				$terObject = $this->extensionRepository->findOneByExtensionKeyAndVersion(
					$extensionKey,
					$extensions[$extensionKey]['version']
				);
				if ($terObject instanceof Tx_Extensionmanager_Domain_Model_Extension) {
					$extensions[$extensionKey]['terObject'] = $terObject;
					$extensions[$extensionKey]['updateAvailable'] = $this->installUtility->isUpdateAvailable($terObject);
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
