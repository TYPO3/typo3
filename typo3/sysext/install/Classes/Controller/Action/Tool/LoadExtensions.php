<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Core\Utility;

/**
 * Load Extensions
 */
class LoadExtensions extends Action\AbstractAction implements Action\ActionInterface {

	protected $protocolFile = '';

	public function __construct() {
		$this->protocolFile = PATH_typo3 . 'sysext/install/Resources/Public/LoadExtensions.txt';
	}

	/**
	 * Handle this action
	 *
	 * @return string content
	 */
	public function handle() {
		$this->initialize();
		return $this->checkLoadedExtensions();
	}

	/**
	 * Entry method which
	 *
	 * @return string
	 */
	protected function checkLoadedExtensions() {
		$getVars = Utility\GeneralUtility::_GET('install');
		if (isset($getVars['loadExtensions']) && isset($getVars['loadExtensions']['forceCheck']) && ($getVars['loadExtensions']['forceCheck'] == 1)) {
			$this->deleteProtocolFile();
		}
		$this->loadExtensions($this->getExtensionsToLoad());
		return 'OK';
	}

	protected function deleteProtocolFile() {
		if(file_exists($this->protocolFile)) {
			unlink($this->protocolFile);
		}
	}

	protected function getExtensionsToLoad() {
		$extensionsToLoad = array();
		$GLOBALS['TYPO3_LOADED_EXT'] = Utility\ExtensionManagementUtility::loadTypo3LoadedExtensionInformation(FALSE);
		$extensionsToExclude = $this->getExtensionsToExclude();
		foreach($GLOBALS['TYPO3_LOADED_EXT'] as $key => $extension) {
			if ($extension['type'] === 'L' && !in_array($key, $extensionsToExclude)) {
				$extensionsToLoad[$key] = $extension;
			}
		}
		return $extensionsToLoad;
	}

	protected function getExtensionsToExclude() {
		$exclude = Utility\GeneralUtility::getUrl($this->protocolFile);
		return explode(',', (string)$exclude);
	}

	protected function loadExtensions($extensions) {
		foreach($extensions as $extensionKey => $extension) {
			$this->writeCurrentExtensionToFile($extensionKey);
			$this->loadExtTablesForExtension($extensionKey, $extension);
			$this->loadExtLocalconfForExtension($extensionKey, $extension);
			$this->removeCurrentExtensionFromFile($extensionKey);
		}
	}

	protected function loadExtTablesForExtension($extensionKey, $extension) {
		// In general it is recommended to not rely on it to be globally defined in that
		// scope, but we can not prohibit this without breaking backwards compatibility
		global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
		global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
		global $PAGES_TYPES, $TBE_STYLES, $FILEICONS;
		global $_EXTKEY;
		// Load each ext_tables.php file of loaded extensions
		$_EXTKEY = $extensionKey;
		if (is_array($extension) && $extension['ext_tables.php']) {
			// $_EXTKEY and $_EXTCONF are available in ext_tables.php
			// and are explicitly set in cached file as well
			$_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
			require $extension['ext_tables.php'];
			Utility\ExtensionManagementUtility::loadNewTcaColumnsConfigFiles();
		}
	}

	protected function loadExtLocalconfForExtension($extensionKey, $extension) {
		// This is the main array meant to be manipulated in the ext_localconf.php files
		// In general it is recommended to not rely on it to be globally defined in that
		// scope but to use $GLOBALS['TYPO3_CONF_VARS'] instead.
		// Nevertheless we define it here as global for backwards compatibility.
		global $TYPO3_CONF_VARS;
		$_EXTKEY = $extensionKey;
		if (is_array($extension) && $extension['ext_localconf.php']) {
			// $_EXTKEY and $_EXTCONF are available in ext_localconf.php
			// and are explicitly set in cached file as well
			$_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
			require $extension['ext_localconf.php'];
		}
	}

	protected function writeCurrentExtensionToFile($extensionKey) {
		$incompatibleExtensions = array_filter(explode(',', (string)Utility\GeneralUtility::getUrl($this->protocolFile)));
		$incompatibleExtensions = array_merge($incompatibleExtensions, array($extensionKey));
		Utility\GeneralUtility::writeFile($this->protocolFile, implode(',', $incompatibleExtensions));
	}

	protected function removeCurrentExtensionFromFile($extensionKey) {
		$extensionsInFile = array_filter(explode(',', (string)Utility\GeneralUtility::getUrl($this->protocolFile)));
		$extensionsByKey = array_flip($extensionsInFile);
		unset($extensionsByKey[$extensionKey]);
		$extensionsForFile = array_flip($extensionsByKey);
		Utility\GeneralUtility::writeFile($this->protocolFile, implode(',', $extensionsForFile));
	}
}
?>