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
	 * @var Tx_Extbase_Service_FlexFormService
	 */
	protected $flexFormService;

	/**
	 * @param Tx_Extbase_Service_FlexFormService $flexFormService
	 * @return void
	 */
	public function injectFlexFormService(Tx_Extbase_Service_FlexFormService $flexFormService) {
		$this->flexFormService = $flexFormService;
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
	 * Returns the TypoScript configuration found in plugin.tx_yourextension_yourplugin
	 * merged with the global configuration of your extension from plugin.tx_yourextension
	 *
	 * @param string $extensionName
	 * @param string $pluginName
	 * @return array
	 */
	protected function getPluginConfiguration($extensionName, $pluginName) {
		$setup = $this->getTypoScriptSetup();
		$pluginConfiguration = array();
		if (is_array($setup['plugin.']['tx_' . strtolower($extensionName) . '.'])) {
			$pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.']['tx_' . strtolower($extensionName) . '.']);
		}
		$pluginSignature = strtolower($extensionName . '_' . $pluginName);
		if (is_array($setup['plugin.']['tx_' . $pluginSignature . '.'])) {
			$pluginConfiguration = t3lib_div::array_merge_recursive_overrule($pluginConfiguration, $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.']['tx_' . $pluginSignature . '.']));
		}
		return $pluginConfiguration;
	}

	/**
	 * Returns the configured controller/action pairs of the specified plugin in the format
	 * array(
	 *  'Controller1' => array('action1', 'action2'),
	 *  'Controller2' => array('action3', 'action4')
	 * )
	 *
	 * @param string $extensionName
	 * @param string $pluginName
	 * @return array
	 */
	protected function getSwitchableControllerActions($extensionName, $pluginName) {
		$switchableControllerActions = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'];
		if (!is_array($switchableControllerActions)) {
			$switchableControllerActions = array();
		}
		return $switchableControllerActions;
	}

	/**
	 * Get context specific framework configuration.
	 * - Overrides storage PID with setting "Startingpoint"
	 * - merge flexForm configuration, if needed
	 *
	 * @param array $frameworkConfiguration The framework configuration to modify
	 * @return array the modified framework configuration
	 */
	protected function getContextSpecificFrameworkConfiguration(array $frameworkConfiguration) {
		$frameworkConfiguration = $this->overrideStoragePidIfStartingPointIsSet($frameworkConfiguration);
		$frameworkConfiguration = $this->overrideConfigurationFromPlugin($frameworkConfiguration);
		$frameworkConfiguration = $this->overrideConfigurationFromFlexForm($frameworkConfiguration);

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
			$pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($pluginConfiguration);
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'settings');
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'persistence');
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'view');
		}
		return $frameworkConfiguration;
	}

	/**
	 * Overrides configuration settings from flexForms.
	 * This merges the whole flexForm data, and overrides switchable controller actions.
	 *
	 * @param array the framework configuration
	 * @return array the framework configuration with overridden data from flexForm
	 */
	protected function overrideConfigurationFromFlexForm(array $frameworkConfiguration) {
		if (strlen($this->contentObject->data['pi_flexform']) > 0) {
			$flexFormConfiguration = $this->flexFormService->convertFlexFormContentToArray($this->contentObject->data['pi_flexform']);

			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexFormConfiguration, 'settings');
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexFormConfiguration, 'persistence');
			$frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexFormConfiguration, 'view');

			$frameworkConfiguration = $this->overrideSwitchableControllerActionsFromFlexForm($frameworkConfiguration, $flexFormConfiguration);
		}
		return $frameworkConfiguration;
	}

	/**
	 * Parses the flexForm content and converts it to an array
	 * The resulting array will be multi-dimensional, as a value "bla.blubb"
	 * results in two levels, and a value "bla.blubb.bla" results in three levels.
	 *
	 * Note: multi-language flexForms are not supported yet
	 *
	 * @param string $flexFormContent flexForm xml string
	 * @return array the processed array
	 * @deprecated since Extbase 1.4; will be removed in Extbase 1.6
	 */
	protected function convertFlexformContentToArray($flexFormContent) {
		t3lib_div::logDeprecatedFunction();
		return $this->flexFormService->convertFlexFormContentToArray($flexFormContent);
	}

	/**
	 * Parses a flexForm node recursively and takes care of sections etc
	 * @param array $nodeArray The flexForm node to parse
	 * @param string $valuePointer The valuePointer to use for value retrieval
	 * @deprecated since Extbase 1.4; will be removed in Extbase 1.6
	 */
	protected function walkFlexformNode($nodeArray, $valuePointer = 'vDEF') {
		t3lib_div::logDeprecatedFunction();
		return $this->flexFormService->walkFlexFormNode($nodeArray, $valuePointer);
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
	 * Overrides the switchable controller actions from the flexForm.
	 *
	 * @param array $frameworkConfiguration The original framework configuration
	 * @param array $flexFormConfiguration The full flexForm configuration
	 * @return array the modified framework configuration, if needed
	 */
	protected function overrideSwitchableControllerActionsFromFlexForm(array $frameworkConfiguration, array $flexFormConfiguration) {
		if (!isset($flexFormConfiguration['switchableControllerActions']) || is_array($flexFormConfiguration['switchableControllerActions'])) {
			return $frameworkConfiguration;
		}

			// As "," is the flexForm field value delimiter, we need to use ";" as in-field delimiter. That's why we need to replace ; by  , first.
			// The expected format is: "Controller1->action2;Controller2->action3;Controller2->action1"
		$switchableControllerActionPartsFromFlexForm = t3lib_div::trimExplode(',', str_replace(';', ',', $flexFormConfiguration['switchableControllerActions']), TRUE);

		$newSwitchableControllerActionsFromFlexForm = array();
		foreach ($switchableControllerActionPartsFromFlexForm as $switchableControllerActionPartFromFlexForm) {
			list($controller, $action) = t3lib_div::trimExplode('->', $switchableControllerActionPartFromFlexForm);
			if (empty($controller) || empty($action)) {
				throw new Tx_Extbase_Configuration_Exception_ParseError('Controller or action were empty when overriding switchableControllerActions from flexForm.', 1257146403);
			}
			$newSwitchableControllerActionsFromFlexForm[$controller][] = $action;
		}
		if (count($newSwitchableControllerActionsFromFlexForm) > 0) {
			$this->overrideSwitchableControllerActions($frameworkConfiguration, $newSwitchableControllerActionsFromFlexForm);
		}
		return $frameworkConfiguration;
	}
}
?>