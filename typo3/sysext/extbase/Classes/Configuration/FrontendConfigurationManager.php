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
	 * @var tslib_cObj
	 */
	protected $contentObject;

	/**
	 * @param tslib_cObj $contentObject
	 * @return void
	 */
	public function setContentObject(tslib_cObj $contentObject) {
		$this->contentObject = $contentObject;
	}

	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the TypoScript setup
	 */
	public function loadTypoScriptSetup() {
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
	protected function getContextSpecificFrameworkConfiguration($frameworkConfiguration) {
		$frameworkConfiguration = $this->overrideStoragePidIfStartingPointIsSet($frameworkConfiguration);
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
	protected function overrideStoragePidIfStartingPointIsSet($frameworkConfiguration) {
		if (is_string($this->contentObject->data['pages'])
		    && strlen($this->contentObject->data['pages']) > 0) {
			$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, array(
				'persistence' => array(
					'storagePid' => $this->contentObject->data['pages']
				)
			));
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

			$frameworkConfiguration = $this->mergeConfigurationPartFromFlexformIntoFrameworkConfiguration($frameworkConfiguration, $flexformConfiguration, 'settings');
			$frameworkConfiguration = $this->mergeConfigurationPartFromFlexformIntoFrameworkConfiguration($frameworkConfiguration, $flexformConfiguration, 'persistence');
			$frameworkConfiguration = $this->mergeConfigurationPartFromFlexformIntoFrameworkConfiguration($frameworkConfiguration, $flexformConfiguration, 'view');

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
	 * Merge a configuration part from the flexform configuration to the framework configuration.
	 *
	 * @param array $frameworkConfiguration the framework configuration to merge the data on
	 * @param array $flexformConfiguration The full flexform configuration
	 * @param string $configurationPartName The name of the configuration part which should be merged.
	 * @return array the processed framework configuration
	 */
	protected function mergeConfigurationPartFromFlexformIntoFrameworkConfiguration($frameworkConfiguration, $flexformConfiguration, $configurationPartName) {
		if (is_array($flexformConfiguration[$configurationPartName])) {
			$frameworkConfiguration[$configurationPartName] = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration[$configurationPartName], $flexformConfiguration[$configurationPartName]);
		}
		return $frameworkConfiguration;
	}


	/**
	 * Overrides the switchable controller actions from the flexform.
	 *
	 * @param $frameworkConfiguration The original framework configuration
	 * @param $flexformConfiguration The full flexform configuration
	 * @return array the modified framework configuration, if needed
	 * @todo: Check that the controller has been before inside the switchableControllerActions.
	 */
	protected function overrideSwitchableControllerActionsFromFlexform($frameworkConfiguration, $flexformConfiguration) {
		if (isset($flexformConfiguration['switchableControllerActions']) && !is_array($flexformConfiguration['switchableControllerActions'])) {

			// As "," is the flexform field value delimiter, we need to use ";" as in-field delimiter. That's why we need to replace ; by  , first.
			$switchableControllerActionPartsFromFlexform = t3lib_div::trimExplode(',', str_replace(';', ',', $flexformConfiguration['switchableControllerActions']));

			$newSwitchableControllerActionsFromFlexform = array();
			foreach ($switchableControllerActionPartsFromFlexform as $switchableControllerActionPartFromFlexform) {
				list($controller, $action) = explode('->', $switchableControllerActionPartFromFlexform);
				if (empty($controller) || empty($action)) {
					throw new Tx_Extbase_Configuration_Exception_ParseError('Controller or action were empty when overriding switchableControllerActions from flexform.', 1257146403);
				}

				$newSwitchableControllerActionsFromFlexform[$controller][] = $action;
			}

			if (count($newSwitchableControllerActionsFromFlexform)) {
				$frameworkConfiguration['switchableControllerActions'] = array();
				foreach ($newSwitchableControllerActionsFromFlexform as $controller => $actions) {
					$frameworkConfiguration['switchableControllerActions'][] = array(
						'controller' => $controller,
						'actions' => implode(',', $actions)
					);
				}

				// We want the first controller/action be the default.
				unset($frameworkConfiguration['controller']);
				unset($frameworkConfiguration['action']);
			}
		}
		return $frameworkConfiguration;
	}
}
?>