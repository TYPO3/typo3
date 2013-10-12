<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Susanne Moog <typo3@susanne-moog.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility;

/**
 * Load Extensions
 *
 * The idea is to load ext_localconf and ext_tables of extensions one-by-one
 * until one of those files throws a fatal. The javascript will then recognise
 * the fatal and initiates another run that will leave out the fataling extension
 * to check the rest.
 */
class ExtensionCompatibilityTester extends AbstractAjaxAction {

	/**
	 * Store extension loading protocol
	 *
	 * @var string
	 */
	protected $protocolFile = '';

	/**
	 * Construct this class
	 * set default protocol file location
	 */
	public function __construct() {
		$this->protocolFile = PATH_site . 'typo3temp/ExtensionCompatibilityTester.txt';
	}

	/**
	 * Main entry point for checking extensions to load,
	 * setting up the checks (deleting protocol), and returning
	 * OK if process run through without errors
	 *
	 * @return string "OK" if process ran through without errors
	 */
	protected function executeAction() {
		$getVars = Utility\GeneralUtility::_GET('install');
		if (isset($getVars['extensionCompatibilityTester']) && isset($getVars['extensionCompatibilityTester']['forceCheck']) && ($getVars['extensionCompatibilityTester']['forceCheck'] == 1)) {
			$this->deleteProtocolFile();
		}
		$this->tryToLoadExtLocalconfAndExtTablesOfExtensions($this->getExtensionsToLoad());
		return 'OK';
	}

	/**
	 * Delete the protocol file if it exists
	 *
	 * @return void
	 */
	protected function deleteProtocolFile() {
		if (file_exists($this->protocolFile)) {
			unlink($this->protocolFile);
		}
	}

	/**
	 * Get extensions that should be loaded.
	 * Fills the TYPO3_LOADED_EXT array.
	 * Only considers local extensions
	 *
	 * @return array
	 */
	protected function getExtensionsToLoad() {
		$extensionsToLoad = array();
		$extensionsToExclude = $this->getExtensionsToExclude();
		foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $key => $extension) {
			if (!in_array($key, $extensionsToExclude)) {
				$extensionsToLoad[$key] = $extension;
			}
		}
		return $extensionsToLoad;
	}

	/**
	 * Gets extensions already known to be incompatible
	 * This class is recursively called, and this method is needed
	 * to not run into the same errors twice.
	 *
	 * @return array
	 */
	protected function getExtensionsToExclude() {
		$exclude = Utility\GeneralUtility::getUrl($this->protocolFile);
		return Utility\GeneralUtility::trimExplode(',', (string)$exclude);
	}

	/**
	 * Tries to load the ext_localconf and ext_tables files of all non-core extensions
	 * Writes current extension name to file and deletes it again when inclusion was
	 * successful.
	 *
	 * @param array $extensions
	 * @return void
	 */
	protected function tryToLoadExtLocalconfAndExtTablesOfExtensions(array $extensions) {
		foreach ($extensions as $extensionKey => $extension) {
			$this->writeCurrentExtensionToFile($extensionKey);
			$this->loadExtLocalconfForExtension($extensionKey, $extension);
			$this->removeCurrentExtensionFromFile($extensionKey);
		}
		Utility\ExtensionManagementUtility::loadBaseTca(FALSE);
		foreach ($extensions as $extensionKey => $extension) {
			$this->writeCurrentExtensionToFile($extensionKey);
			$this->loadExtTablesForExtension($extensionKey, $extension);
			$this->removeCurrentExtensionFromFile($extensionKey);
		}
	}

	/**
	 * Loads ext_tables.php for a single extension. Method is a modified copy of
	 * the original bootstrap method.
	 *
	 * @param string $extensionKey
	 * @param \ArrayAccess $extension
	 * @return void
	 */
	protected function loadExtTablesForExtension($extensionKey, \ArrayAccess $extension) {
		// In general it is recommended to not rely on it to be globally defined in that
		// scope, but we can not prohibit this without breaking backwards compatibility
		global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
		global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
		global $PAGES_TYPES, $TBE_STYLES, $FILEICONS;
		global $_EXTKEY;
		// Load each ext_tables.php file of loaded extensions
		$_EXTKEY = $extensionKey;
		if (isset($extension['ext_tables.php']) && $extension['ext_tables.php']) {
			// $_EXTKEY and $_EXTCONF are available in ext_tables.php
			// and are explicitly set in cached file as well
			$_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
			require $extension['ext_tables.php'];
			Utility\ExtensionManagementUtility::loadNewTcaColumnsConfigFiles();
		}
	}

	/**
	 * Loads ext_localconf.php for a single extension. Method is a modified copy of
	 * the original bootstrap method.
	 *
	 * @param string $extensionKey
	 * @param \ArrayAccess $extension
	 * @return void
	 */
	protected function loadExtLocalconfForExtension($extensionKey, \ArrayAccess $extension) {
		// This is the main array meant to be manipulated in the ext_localconf.php files
		// In general it is recommended to not rely on it to be globally defined in that
		// scope but to use $GLOBALS['TYPO3_CONF_VARS'] instead.
		// Nevertheless we define it here as global for backwards compatibility.
		global $TYPO3_CONF_VARS;
		$_EXTKEY = $extensionKey;
		if (isset($extension['ext_localconf.php']) && $extension['ext_localconf.php']) {
			// $_EXTKEY and $_EXTCONF are available in ext_localconf.php
			// and are explicitly set in cached file as well
			$_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
			require $extension['ext_localconf.php'];
		}
	}

	/**
	 * Writes $extensionKey to the protocol file by adding it comma separated at
	 * the end of the file.
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	protected function writeCurrentExtensionToFile($extensionKey) {
		$incompatibleExtensions = array_filter(Utility\GeneralUtility::trimExplode(',', (string)Utility\GeneralUtility::getUrl($this->protocolFile)));
		$incompatibleExtensions = array_merge($incompatibleExtensions, array($extensionKey));
		Utility\GeneralUtility::writeFile($this->protocolFile, implode(', ', $incompatibleExtensions));
	}

	/**
	 * Removes $extensionKey from protocol file.
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	protected function removeCurrentExtensionFromFile($extensionKey) {
		$extensionsInFile = array_filter(Utility\GeneralUtility::trimExplode(',', (string)Utility\GeneralUtility::getUrl($this->protocolFile)));
		$extensionsByKey = array_flip($extensionsInFile);
		unset($extensionsByKey[$extensionKey]);
		$extensionsForFile = array_flip($extensionsByKey);
		Utility\GeneralUtility::writeFile($this->protocolFile, implode(', ', $extensionsForFile));
	}
}
