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
 */
class Tx_Extbase_Configuration_Manager {

	/**
	 * Default backend storage PID
	 */
	const DEFAULT_BACKEND_STORAGE_PID = 0;

	/**
	 * Storage for the settings, loaded by loadSettings()
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
	 */
	public function getSettings($extensionName) {
		if (empty($this->settings[$extensionName])) {
			$this->loadSettings($extensionName);
		}
		return $settings = $this->settings[$extensionName];
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
	 */
	protected function loadSettings($extensionName) {
		$settings = array();
		foreach ($this->configurationSources as $configurationSource) {
			$settings = t3lib_div::array_merge_recursive_overrule($settings, $configurationSource->load($extensionName));
		}
		$this->settings[$extensionName] = $settings;
	}

	/**
	 * Loads the Extbase Framework configuration.
	 *
	 * The Extbase framework configuration HAS TO be retrieved using this method, as they are come from different places than the normal settings.
	 * Framework configuration is, in contrast to normal settings, needed for the Extbase framework to operate correctly.
	 *
	 * @param array $pluginConfiguration The current incoming extbase configuration
	 * @param tslib_cObj $cObj The current Content Object
	 * @return array the Extbase framework configuration
	 */
	public function getFrameworkConfiguration($pluginConfiguration, $cObj) {
		$frameworkConfiguration = array();
		$frameworkConfiguration['persistence']['storagePid'] = $this->getDefaultStoragePageId($cObj);
		$frameworkConfiguration['contentObjectData'] = $cObj->data;

		// TODO Support BE modules by parsing the file "manually" and all files EXT:myext/Configuration/Objects/setup.txt
		$extbaseConfiguration = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_extbase.'];
		if (is_array($extbaseConfiguration)) {
			$extbaseConfiguration = Tx_Extbase_Configuration_Manager::postProcessSettings($extbaseConfiguration);
			$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, $extbaseConfiguration);
		}

		if (isset($pluginConfiguration['persistence'])) {
			$pluginConfiguration = $this->resolveTyposcriptReference($pluginConfiguration, 'persistence');
		}
		$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, self::postProcessSettings($pluginConfiguration));
		return $frameworkConfiguration;
	}

	/**
	 * Resolves the TypoScript reference for $pluginConfiguration[$setting].
	 * In case the setting is a string and starts with "<", we know that this is a TypoScript reference which
	 * needs to be resolved separately.
	 *
	 * @param array $pluginConfiguration The whole plugin configuration
	 * @param string $setting The key inside the $pluginConfiguration to check
	 * @return array The modified plugin configuration
	 */
	protected function resolveTyposcriptReference($pluginConfiguration, $setting) {
		if (is_string($pluginConfiguration[$setting]) && substr($pluginConfiguration[$setting], 0, 1) === '<') {
			$typoScriptParser = t3lib_div::makeInstance('t3lib_TSparser');
			$key = trim(substr($pluginConfiguration[$setting], 1));
			list(, $newValue) = $typoScriptParser->getVal($key,$GLOBALS['TSFE']->tmpl->setup);

			unset($pluginConfiguration[$setting]);
			$pluginConfiguration[$setting . '.'] = $newValue;
		}
		return $pluginConfiguration;
	}

	/**
	 * Extracts the default storage PID from $this->cObj->data['pages'].
	 * If this one is empty, tries to use $this->cObj->data['storage_pid'].
	 * If this one is empty, tries to use $this->cObj->parentRecord->data['storage_pid'].
	 * If all three are empty, uses getStorageSiterootPids() in FE, and 0 in BE.
	 *
	 * @param tslib_cObj $cObj The current Content Object
	 * @return string a comma separated list of integers to be used to fetch records from.
	 */
	protected function getDefaultStoragePageId($cObj) {
		if (is_string($cObj->data['pages']) && strlen($cObj->data['pages']) > 0) {
			return $cObj->data['pages'];
		}

		if ($cObj->data['storage_pid'] > 0) {
			return $cObj->data['storage_pid'];
		}

		if ($cObj->parentRecord->data['storage_pid'] > 0) {
			return $cObj->parentRecord->data['storage_pid'];
		}
		if (TYPO3_MODE === 'FE') {
			$storageSiterootPids = $GLOBALS['TSFE']->getStorageSiterootPids();
			if (isset($storageSiterootPids['_STORAGE_PID'])) {
				return $storageSiterootPids['_STORAGE_PID'];
			}
		}
		return self::DEFAULT_BACKEND_STORAGE_PID;
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
		// TODO Check if the t3lib_div::removeDotsFromTS() fits for this purpose (using rtrim() for removing trailing dots)
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