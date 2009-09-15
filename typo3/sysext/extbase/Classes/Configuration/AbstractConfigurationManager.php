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
 * Abstract base class for a general purpose configuration manager
 *
 * @package Extbase
 * @subpackage Configuration
 * @version $ID:$
 */
abstract class Tx_Extbase_Configuration_AbstractConfigurationManager {

	/**
	 * Default backend storage PID
	 */
	const DEFAULT_BACKEND_STORAGE_PID = 0;

	/**
	 * The TypoScript parser
	 *
	 * @var t3lib_TSparser
	 */
	protected $typoScriptParser;

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
		$this->typoScriptParser = t3lib_div::makeInstance('t3lib_TSparser');
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
		return $this->settings[$extensionName];
	}

	/**
	 * Loads the Extbase Framework configuration.
	 *
	 * The Extbase framework configuration HAS TO be retrieved using this method, as they are come from different places than the normal settings.
	 * Framework configuration is, in contrast to normal settings, needed for the Extbase framework to operate correctly.
	 *
	 * @param array $pluginConfiguration The current incoming extbase configuration
	 * @return array the Extbase framework configuration
	 */
	public function getFrameworkConfiguration($pluginConfiguration) {
		$frameworkConfiguration = array();
		$frameworkConfiguration['persistence']['storagePid'] = self::DEFAULT_BACKEND_STORAGE_PID;

		$setup = $this->loadTypoScriptSetup();
		$extbaseConfiguration = $setup['config.']['tx_extbase.'];
		if (is_array($extbaseConfiguration)) {
			$extbaseConfiguration = Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($extbaseConfiguration);
			$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, $extbaseConfiguration);
		}

		if (isset($pluginConfiguration['settings'])) {
			$pluginConfiguration = $this->resolveTyposcriptReference($pluginConfiguration, 'settings');
		}
		if (isset($pluginConfiguration['persistence'])) {
			$pluginConfiguration = $this->resolveTyposcriptReference($pluginConfiguration, 'persistence');
		}
		if (isset($pluginConfiguration['view'])) {
			$pluginConfiguration = $this->resolveTyposcriptReference($pluginConfiguration, 'view');
		}
		$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($pluginConfiguration));

		return $frameworkConfiguration;
	}

	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the TypoScript setup
	 */
	abstract public function loadTypoScriptSetup();

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
			$key = trim(substr($pluginConfiguration[$setting], 1));
			$setup = $this->loadTypoScriptSetup();
			list(, $newValue) = $this->typoScriptParser->getVal($key, $setup);

			unset($pluginConfiguration[$setting]);
			$pluginConfiguration[$setting . '.'] = $newValue;
		}
		return $pluginConfiguration;
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


}
?>
