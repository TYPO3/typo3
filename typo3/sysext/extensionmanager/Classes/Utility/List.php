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
 * action controller.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Controller
 */
class Tx_Extensionmanager_Utility_List implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	public $objectManager;

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @var Tx_Extensionmanager_Utility_EmConf
	 */
	public $emConfUtility;

	/**
	 * @param Tx_Extensionmanager_Utility_EmConf $emConfUtility
	 */
	public function injectEmConfUtility(Tx_Extensionmanager_Utility_EmConf $emConfUtility) {
		$this->emConfUtility = $emConfUtility;
	}
	/**
	 * Returns the list of available (installed) extensions
	 *
	 * @return array Array with two arrays, list array (all extensions with info) and category index
	 * @see getInstExtList()
	 */
	public function getAvailableExtensions() {
		$extensions = array();
		$paths = array(
			'System' => PATH_typo3 . 'sysext/',
			'Global' => PATH_typo3 . 'ext/',
			'Local' => PATH_typo3conf . 'ext/'
		);
		foreach($paths as $installationType => $path) {
			try {
				if(is_dir($path)) {
					$extList = t3lib_div::get_dirs($path);
					if(is_array($extList)) {
						foreach($extList as $extKey) {
							$extensions[$extKey] = array(
								'siteRelPath' => str_replace(PATH_site, '', $path . $extKey),
								'type' => $installationType,
								'key' => $extKey
							);
						}
					}
				}
			} catch(Exception $e) {
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
		foreach($GLOBALS['TYPO3_LOADED_EXT'] as $extKey => $properties) {
			if(array_key_exists($extKey, $availableExtensions)) {
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
	public function enrichExtensionsWithEmConfInformation(array $extensions) {
		foreach($extensions as $extensionKey => $properties) {
			$emconf = $this->emConfUtility->includeEmConf($properties);
			if($emconf) {
				$extensions[$extensionKey] = array_merge($emconf, $properties);
			} else {
				unset($extensions[$extensionKey]);
			}
		}
		return $extensions;
	}
}

?>
