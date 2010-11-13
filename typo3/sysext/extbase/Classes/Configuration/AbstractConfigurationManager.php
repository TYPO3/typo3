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
abstract class Tx_Extbase_Configuration_AbstractConfigurationManager implements t3lib_Singleton {

	/**
	 * Default backend storage PID
	 */
	const DEFAULT_BACKEND_STORAGE_PID = 0;

	/**
	 * Storage of the raw TypoScript configuration
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @var tslib_cObj
	 */
	protected $contentObject;

	/**
	 * The TypoScript parser
	 *
	 * @var t3lib_TSparser
	 */
	protected $typoScriptParser;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * name of the extension this Configuration Manager instance belongs to
	 * @var string
	 */
	protected $extensionName;

	/**
	 * name of the plugin this Configuration Manager instance belongs to
	 * @var string
	 */
	protected $pluginName;

	/**
	 * 1st level configuration cache
	 *
	 * @var array
	 */
	protected $configurationCache = array();

	/**
	 * @param Tx_Extbase_Object_ManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->typoScriptParser = t3lib_div::makeInstance('t3lib_TSparser');
	}

	/**
	 * @param tslib_cObj $contentObject
	 * @return void
	 */
	public function setContentObject(tslib_cObj $contentObject = NULL) {
		$this->contentObject = $contentObject;
	}

	/**
	 * @return tslib_cObj
	 */
	public function getContentObject() {
		return $this->contentObject;
	}

	/**
	 * Sets the specified raw configuration coming from the outside.
	 * Note that this is a low level method and only makes sense to be used by Extbase internally.
	 *
	 * @param array $configuration The new configuration
	 * @return void
	 */
	public function setConfiguration(array $configuration = array()) {
		// reset 1st level cache
		$this->configurationCache = array();

		$this->extensionName = $configuration['extensionName'];
		$this->pluginName = $configuration['pluginName'];
		$this->configuration = $configuration;
	}

	/**
	 * Loads the Extbase Framework configuration.
	 *
	 * The Extbase framework configuration HAS TO be retrieved using this method, as they are come from different places than the normal settings.
	 * Framework configuration is, in contrast to normal settings, needed for the Extbase framework to operate correctly.
	 *
	 * @param string $extensionName if specified, the configuration for the given extension will be returned (plugin.tx_extensionname)
	 * @param string $pluginName if specified, the configuration for the given plugin will be returned (plugin.tx_extensionname_pluginname)
	 * @return array the Extbase framework configuration
	 */
	public function getConfiguration($extensionName = NULL, $pluginName = NULL) {
		// 1st level cache
		if ($extensionName !== NULL) {
			$configurationCacheKey = strtolower($extensionName);
			if ($pluginName !== NULL) {
				$configurationCacheKey .= '_' . strtolower($pluginName);
			}
		} else {
			$configurationCacheKey = strtolower($this->extensionName . '_' . $this->pluginName);
		}
		if (isset($this->configurationCache[$configurationCacheKey])) {
			return $this->configurationCache[$configurationCacheKey];
		}

		$frameworkConfiguration = array();
		$frameworkConfiguration['persistence']['storagePid'] = self::DEFAULT_BACKEND_STORAGE_PID;

		$setup = $this->getTypoScriptSetup();
		if ($extensionName !== NULL) {
			$pluginConfiguration = $setup['plugin.']['tx_' . strtolower($extensionName) . '.'];
			if ($pluginName !== NULL) {
				$pluginSignature = strtolower($extensionName . '_' . $pluginName);
				if (is_array($setup['plugin.']['tx_' . $pluginSignature . '.'])) {
					$pluginConfiguration = t3lib_div::array_merge_recursive_overrule($pluginConfiguration, $setup['plugin.']['tx_' . $pluginSignature . '.']);
				}
			}
		} else {
			$pluginConfiguration = $this->configuration;
		}
		$extbaseConfiguration = $setup['config.']['tx_extbase.'];
		if (is_array($extbaseConfiguration)) {
			$extbaseConfiguration = Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($extbaseConfiguration);
			$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, $extbaseConfiguration);
		}

		if (isset($pluginConfiguration['settings'])) {
			$pluginConfiguration = $this->resolveTyposcriptReference($pluginConfiguration, 'settings');
		}
		if (!is_array($pluginConfiguration['settings.'])) $pluginConfiguration['settings.'] = array(); // We expect that the settings are arrays on various places
		if (isset($pluginConfiguration['persistence'])) {
			$pluginConfiguration = $this->resolveTyposcriptReference($pluginConfiguration, 'persistence');
		}
		if (isset($pluginConfiguration['view'])) {
			$pluginConfiguration = $this->resolveTyposcriptReference($pluginConfiguration, 'view');
		}
		if (isset($pluginConfiguration['_LOCAL_LANG'])) {
			$pluginConfiguration = $this->resolveTyposcriptReference($pluginConfiguration, '_LOCAL_LANG');
		}
		$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($pluginConfiguration));

		// only load context specific configuration when retrieving configuration of the current plugin
		if ($extensionName === NULL || ($extensionName === $this->extensionName && $pluginName === $this->pluginName)) {
			$frameworkConfiguration = $this->getContextSpecificFrameworkConfiguration($frameworkConfiguration);
		}

		// 1st level cache
		$this->configurationCache[$configurationCacheKey] = $frameworkConfiguration;
		return $frameworkConfiguration;
	}

	/**
	 * The context specific configuration returned by this method
	 * will override the framework configuration which was
	 * obtained from TypoScript. This can be used f.e. to override the storagePid
	 * with the value set inside the Plugin Instance.
	 *
	 * WARNING: Make sure this method ALWAYS returns an array!
	 *
	 * @param array $frameworkConfiguration The framework configuration until now
	 * @return array context specific configuration which will override the configuration obtained by TypoScript
	 */
	abstract protected function getContextSpecificFrameworkConfiguration($frameworkConfiguration);

	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the TypoScript setup
	 */
	abstract protected function getTypoScriptSetup();

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
			$setup = $this->getTypoScriptSetup();
			list(, $newValue) = $this->typoScriptParser->getVal($key, $setup);

			unset($pluginConfiguration[$setting]);
			$pluginConfiguration[$setting . '.'] = $newValue;
		}
		return $pluginConfiguration;
	}
}
?>