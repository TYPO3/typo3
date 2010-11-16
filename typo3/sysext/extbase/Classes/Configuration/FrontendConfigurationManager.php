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
 * A general purpose configuration manager used in frontend mode.
 *
 * Should NOT be singleton, as a new configuration manager is needed per plugin.
 *
 * @package Extbase
 * @subpackage Configuration
 * @version $ID:$
 */
class Tx_Extbase_Configuration_FrontendConfigurationManager extends Tx_Extbase_Configuration_AbstractConfigurationManager {

	/**
	 * Loads the Extbase Framework configuration.
	 *
	 * The Extbase framework configuration HAS TO be retrieved using this method, as they are come from different places than the normal settings.
	 * Framework configuration is, in contrast to normal settings, needed for the Extbase framework to operate correctly.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $extensionName if specified, the configuration for the given extension will be returned (plugin.tx_extensionname)
	 * @param string $pluginName if specified, the configuration for the given plugin will be returned (plugin.tx_extensionname_pluginname)
	 * @return array the Extbase framework configuration
	 */
	public function getConfiguration($configurationType, $extensionName = NULL, $pluginName = NULL) {
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
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the raw TypoScript setup
	 */
	public function getTypoScriptSetup() {
		return $GLOBALS['TSFE']->tmpl->setup;
	}

	/**
	 * Get context specific framework configuration.
	 * - Overrides storage PID with setting "Startingpoint"
	 * - merge flexform configuration, if needed
	 *
	 * @param array $frameworkConfiguration The framework configuration to modify
	 * @return array the modified framework configuration
	 */
	protected function getContextSpecificFrameworkConfiguration(array $frameworkConfiguration) {
		$frameworkConfiguration = $this->overrideStoragePidIfStartingPointIsSet($frameworkConfiguration);
		$frameworkConfiguration = $this->overrideConfigurationFromPlugin($frameworkConfiguration);
		$frameworkConfiguration = $this->overrideConfigurationFromFlexform($frameworkConfiguration);

		return $frameworkConfiguration;
	}

	/**
	 * Overrides the storage PID settings, in case the "Startingpoint" settings
	 * is set in the plugin configuration.
	 *
	 * @param array $frameworkConfiguration the framework configurations
	 * @return array the framework configuration with overriden storagePid
	 */
	protected function overrideStoragePidIfStartingPointIsSet(array $frameworkConfiguration) {
		$pages = $this->contentObject->data['pages'];
		if (is_string($pages) && strlen($pages) > 0) {
			$list = array();
			if($this->contentObject->data['recursive'] > 0) {
				$explodedPages = t3lib_div::trimExplode(',', $pages);
				foreach($explodedPages as $pid) {
					$list[] = trim($this->contentObject->getTreeList($pid, $this->contentObject->data['recursive']), ',');
				}
			}
			if (count($list) > 0) {
				$pages = $pages . ',' . implode(',', $list);
			}
			$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, array(
				'persistence' => array(
					'storagePid' => $pages
				)
			));
		}
		return $frameworkConfiguration;
	}

	/**
	 * Overrides configuration settings from the plugin typoscript (plugin.tx_myext_pi1.)
	 *
	 * @param array the framework configuration
	 * @return array the framework configuration with overridden data from typoscript
	 */
	protected function overrideConfigurationFromPlugin(array $frameworkConfiguration) {
		$setup = $this->getTypoScriptSetup();
		$pluginSignature = strtolower($frameworkConfiguration['extensionName'] . '_' . $frameworkConfiguration['pluginName']);
		$pluginConfiguration = $setup['plugin.']['tx_' . $pluginSignature . '.'];
		if (is_array($pluginConfiguration)) {
			$pluginConfiguration = Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($pluginConfiguration);
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'settings');
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'persistence');
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'view');
		}
		return $frameworkConfiguration;
	}

	/**
	 * Overrides configuration settings from flexforms.
	 * This merges the whole flexform data, and overrides switchable controller actions.
	 *
	 * @param array the framework configuration
	 * @return array the framework configuration with overridden data from flexform
	 */
	protected function overrideConfigurationFromFlexform(array $frameworkConfiguration) {
		if (strlen($this->contentObject->data['pi_flexform']) > 0) {
			$flexformConfiguration = $this->convertFlexformContentToArray($this->contentObject->data['pi_flexform']);

			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexformConfiguration, 'settings');
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexformConfiguration, 'persistence');
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexformConfiguration, 'view');

			$frameworkConfiguration = $this->overrideSwitchableControllerActionsFromFlexform($frameworkConfiguration, $flexformConfiguration);
		}
		return $frameworkConfiguration;
	}

	/**
	 * Parses the FlexForm content recursivly and converts it to an array
	 * The resulting array will be multi-dimensional, as a value "bla.blubb"
	 * results in two levels, and a value "bla.blubb.bla" results in three levels.
	 *
	 * Note: multi-language FlexForms are not supported yet
	 *
	 * @param string $flexFormContent FlexForm xml string
	 * @return array the processed array
	 */
	protected function convertFlexformContentToArray($flexFormContent) {
		$settings = array();
		$languagePointer = 'lDEF';
		$valuePointer = 'vDEF';

		$flexFormArray = t3lib_div::xml2array($flexFormContent);
		$flexFormArray = isset($flexFormArray['data']) ? $flexFormArray['data'] : array();
		foreach(array_values($flexFormArray) as $languages) {
			if (!is_array($languages[$languagePointer])) {
				continue;
			}
			foreach($languages[$languagePointer] as $valueKey => $valueDefinition) {
				if (strpos($valueKey, '.') === false) {
					$settings[$valueKey] = $valueDefinition[$valuePointer];
				} else {
					$valueKeyParts = explode('.', $valueKey);
					$currentNode =& $settings;
					foreach ($valueKeyParts as $valueKeyPart) {
						$currentNode =& $currentNode[$valueKeyPart];
					}
					$currentNode = $valueDefinition[$valuePointer];
				}
			}
		}
		return $settings;
	}

	/**
	 * Merge a configuration into the framework configuration.
	 *
	 * @param array $frameworkConfiguration the framework configuration to merge the data on
	 * @param array $configuration The configuration
	 * @param string $configurationPartName The name of the configuration part which should be merged.
	 * @return array the processed framework configuration
	 */
	protected function mergeConfigurationIntoFrameworkConfiguration(array $frameworkConfiguration, array $configuration, $configurationPartName) {
		if (is_array($frameworkConfiguration[$configurationPartName]) && is_array($configuration[$configurationPartName])) {
			$frameworkConfiguration[$configurationPartName] = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration[$configurationPartName], $configuration[$configurationPartName]);
		}
		return $frameworkConfiguration;
	}


	/**
	 * Overrides the switchable controller actions from the flexform.
	 *
	 * @param array $frameworkConfiguration The original framework configuration
	 * @param array $flexformConfiguration The full flexform configuration
	 * @return array the modified framework configuration, if needed
	 * @todo: Check that the controller has been before inside the switchableControllerActions.
	 */
	protected function overrideSwitchableControllerActionsFromFlexform(array $frameworkConfiguration, array $flexformConfiguration) {
		if (!isset($flexformConfiguration['switchableControllerActions']) || is_array($flexformConfiguration['switchableControllerActions'])) {
			return $frameworkConfiguration;
		}

		// As "," is the flexform field value delimiter, we need to use ";" as in-field delimiter. That's why we need to replace ; by  , first.
		$switchableControllerActionPartsFromFlexform = t3lib_div::trimExplode(',', str_replace(';', ',', $flexformConfiguration['switchableControllerActions']), TRUE);

		$newSwitchableControllerActionsFromFlexform = array();
		foreach ($switchableControllerActionPartsFromFlexform as $switchableControllerActionPartFromFlexform) {
			list($controller, $action) = explode('->', $switchableControllerActionPartFromFlexform);
			if (empty($controller) || empty($action)) {
				throw new Tx_Extbase_Configuration_Exception_ParseError('Controller or action were empty when overriding switchableControllerActions from flexform.', 1257146403);
			}
			$newSwitchableControllerActionsFromFlexform[$controller][] = $action;
		}

		if (count($newSwitchableControllerActionsFromFlexform)) {
			$this->overrideSwitchableControllerActions($frameworkConfiguration, $newSwitchableControllerActionsFromFlexform);
		}
		return $frameworkConfiguration;
	}
}
?>