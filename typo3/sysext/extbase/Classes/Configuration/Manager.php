<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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

/**
 * A general purpose configuration manager
 *
 * Should NOT be singleton, as a new configuration manager is needed per plugin.
 *
 * @package Extbase
 * @subpackage Configuration
 * @version $ID:$
 * @internal
 */
class Tx_Extbase_Configuration_Manager {

	const DEFAULT_STORAGE_PID = 0;

	/**
	 * Storage for the settings, loaded by loadGlobalSettings()
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * The configuration source instances used for loading the raw configuration
	 *
	 * @var array
	 */
	protected $configurationSources = array();

	/**
	 * Constructs the configuration manager
	 *
	 * @param array $configurationSources An array of configuration sources
	 */
	public function __construct($configurationSources = NULL) {
		if (is_array($configurationSources)) {
			$this->configurationSources = $configurationSources;
		}
	}

	/**
	 * Returns an array with the settings defined for the specified extension.
	 *
	 * @param string $extensionName Name of the extension to return the settings for
	 * @return array The settings of the specified extension
	 * @internal
	 */
	public function getSettings($extensionName) {
		if (empty($this->settings[$extensionName])) {
			$this->loadGlobalSettings($extensionName);
		}
		return $settings = $this->settings[$extensionName];
	}

	/**
	 * Loads the Extbase core settings.
	 *
	 * The Extbase settings can be retrieved like any other setting through the
	 * getSettings() method but need to be loaded separately because they are
	 * needed way earlier in the bootstrap than the package's settings.
	 *
	 * @param array $configuration The current incoming extbase configuration
	 * @param tslib_cObj $cObj The current Content Object
	 * @return void
	 */
	public function loadExtbaseSettings($configuration, $cObj) {
		$settings = array();
		$settings['storagePid'] = $this->getDefaultStoragePageId($cObj);
		$settings['contentObjectData'] = $cObj->data;
		// TODO Support BE modules by parsing the file "manually" and all files EXT:myext/Configuration/Objects/setup.txt
		$extbaseConfiguration = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_extbase.'];
		if (is_array($extbaseConfiguration)) {
			$extbaseConfiguration = Tx_Extbase_Configuration_Manager::postProcessSettings($extbaseConfiguration);
		} else {
			$extbaseConfiguration = array();
		}
		$settings = t3lib_div::array_merge_recursive_overrule($settings, $extbaseConfiguration);
		$settings = t3lib_div::array_merge_recursive_overrule($settings, self::postProcessSettings($configuration));

		$this->settings['Extbase'] = $settings;
	}

	/**
	 * Extracts the default storage PID from $this->cObj->data['pages']. ONLY ALLOWS ONE STORAGE PID!
	 * If this one is empty, tries to use $this->cObj->data['storage_pid'].
	 * If this one is empty, tries to use $this->cObj->parentRecord->data['storage_pid']. If all tree  are empty, uses current page.
	 *
	 * @param tslib_cObj $cObj The current Content Object
	 * @return integer
	 * @throws InvalidArgumentException if more than one storage page ID is given
	 */
	protected function getDefaultStoragePageId($cObj) {
		if (is_string($cObj->data['pages'])) {
			if (count(explode(',', $cObj->data['pages'])) > 1) {
				// TODO Should we take the first pid after explode?
				throw new InvalidArgumentException('More than one storage page ID given. This is currently not supported.', 1247597243);
			}
			return (int)$cObj->data['pages'];
		}

		if ($cObj->data['storage_pid'] > 0) {
			return (int)$cObj->data['storage_pid'];
		}

		if ($cObj->parentRecord->data['storage_pid'] > 0) {
			return (int)$cObj->parentRecord->data['storage_pid'];
		}
		// FIXME Take $GLOBALS['TSFE']->getStorageSiterootPids(); as default for FE and 0 for BE
		return self::DEFAULT_STORAGE_PID;
	}

	/**
	 * Loads the settings defined in the specified extensions and merges them with
	 * those potentially existing in the global configuration folders.
	 *
	 * The result is stored in the configuration manager's settings registry
	 * and can be retrieved with the getSettings() method.
	 *
	 * @param string $extensionName
	 * @return void
	 * @see getSettings()
	 * @internal
	 */
	protected function loadGlobalSettings($extensionName) {
		$settings = array();
		foreach ($this->configurationSources as $configurationSource) {
			$settings = t3lib_div::array_merge_recursive_overrule($settings, $configurationSource->load($extensionName));
		}
		$this->settings[$extensionName] = $settings;
	}

	/**
	 * Removes all trailing dots recursively from TS settings array
	 * TODO Explain why we remove the dots.
	 *
	 * @param array $setup The settings array
	 * @return void
	 */
	public static function postProcessSettings(array $settings) {
		$processedSettings = array();
		foreach ($settings as $key => $value) {
			if (substr($key, -1) === '.') {
				$keyWithoutDot = substr($key, 0, -1);
				$processedSettings[$keyWithoutDot] = self::postProcessSettings($value);
				if (array_key_exists($keyWithoutDot, $settings)) {
					$processedSettings[$keyWithoutDot]['_typoScriptNodeValue'] = $settings[$keyWithoutDot];
					unset($settings[$keyWithoutDot]);
				}
			} else {
				$keyWithDot = $key . '.';
				if (array_key_exists($keyWithDot, $settings)) {
					$processedSettings[$key] = self::postProcessSettings($settings[$keyWithDot]);
					$processedSettings[$key]['_typoScriptNodeValue'] = $value;
					unset($settings[$keyWithDot]);
				} else {
					$processedSettings[$key] = $value;
				}
			}
		}
		return $processedSettings;
	}

}
?>