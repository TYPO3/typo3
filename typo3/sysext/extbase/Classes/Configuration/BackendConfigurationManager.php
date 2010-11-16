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
 * A general purpose configuration manager used in backend mode.
 *
 * @package Extbase
 * @subpackage Configuration
 * @version $ID:$
 */
class Tx_Extbase_Configuration_BackendConfigurationManager extends Tx_Extbase_Configuration_AbstractConfigurationManager {

	/**
	 * @var array
	 */
	protected $typoScriptSetupCache = NULL;

	/**
	 * Transfers the request to an Extbase backend module, calling
	 * a given controller/action.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $extensionName if specified, the configuration for the given extension will be returned (plugin.tx_extensionname)
	 * @param string $pluginName if specified, the configuration for the given plugin will be returned (plugin.tx_extensionname_pluginname)
	 * @return string The module rendered view
	 */
	public function getConfiguration($configurationType, $extensionName = NULL, $pluginName = NULL) {
		$frameworkConfiguration = array();
		$frameworkConfiguration['persistence']['storagePid'] = self::DEFAULT_BACKEND_STORAGE_PID;
		$controllerAction = $this->resolveControllerAction($this->configuration['name']);
		$setup = $this->getTypoScriptSetup();
		$frameworkConfiguration = array(
			'pluginName' => $this->configuration['name'],
			'extensionName' => $this->configuration['extensionName'],
			'controller' => $controllerAction['controllerName'],
			'action' => $controllerAction['actionName'],
			'switchableControllerActions' => array(),
			'settings' => $this->resolveTyposcriptReference($setup, 'settings'),
			'persistence' => $this->resolveTyposcriptReference($setup, 'persistence'),
			'view' => $this->resolveTyposcriptReference($setup, 'view'),
			'_LOCAL_LANG' => $this->resolveTyposcriptReference($setup, '_LOCAL_LANG'),
		);

		foreach ($this->configuration['controllerActions'] as $controller => $actions) {
			// Add an "extObj" action for the default controller to handle external
			// SCbase modules which add function menu entries
			$actions .= ',extObj';
			$frameworkConfiguration['switchableControllerActions'][$i++] = array(
				'controller' => $controller,
				'actions' => $actions,
			);
		}

		$extbaseConfiguration = $setup['config.']['tx_extbase.'];
		if (is_array($extbaseConfiguration)) {
			$extbaseConfiguration = Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($extbaseConfiguration);
			$frameworkConfiguration = t3lib_div::array_merge_recursive_overrule($frameworkConfiguration, $extbaseConfiguration);
		}

		return $frameworkConfiguration;
	}

	/**
	 * Resolves the controller and action to use for current call.
	 * This takes into account any function menu that has being called.
	 *
	 * @param string $module The name of the module
	 * @return array The controller/action pair to use for current call
	 */
	protected function resolveControllerAction($module) {
		$configuration = $GLOBALS['TBE_MODULES']['_configuration'][$module];
		$fallbackControllerAction = $this->getFallbackControllerAction($configuration);

			// Extract dispatcher settings from request
		$argumentPrefix = strtolower('tx_' . $configuration['extensionName'] . '_' . $configuration['name']);
		$dispatcherParameters = t3lib_div::_GPmerged($argumentPrefix);
		$dispatcherControllerAction = $this->getDispatcherControllerAction($configuration, $dispatcherParameters);

			// Extract module function settings from request
		$moduleFunctionControllerAction = $this->getModuleFunctionControllerAction($module, $fallbackControllerAction['controllerName']);

			// Dispatcher controller/action has precedence over default controller/action
		$controllerAction = t3lib_div::array_merge_recursive_overrule($fallbackControllerAction, $dispatcherControllerAction, FALSE, FALSE);
			// Module function controller/action has precedence
		$controllerAction = t3lib_div::array_merge_recursive_overrule($controllerAction, $moduleFunctionControllerAction, FALSE, FALSE);

		return $controllerAction;
	}

	/**
	 * Returns the fallback controller/action pair to be used when request does not contain
	 * any controller/action to be used or the provided parameters are not valid.
	 *
	 * @param array $configuration The module configuration
	 * @return array The controller/action pair
	 */
	protected function getFallbackControllerAction($configuration) {
			// Extract module settings from its registration in ext_tables.php
		$controllers = array_keys($configuration['controllerActions']);
		$defaultController = array_shift($controllers);
		$actions = t3lib_div::trimExplode(',', $configuration['controllerActions'][$defaultController], TRUE);
		$defaultAction = $actions[0];

		return array(
			'controllerName' => $defaultController,
			'actionName' => $defaultAction,
		);
	}

	/**
	 * Returns the controller/action pair that was specified by the request if it is valid,
	 * otherwise, will just return a blank controller/action pair meaning the default
	 * controller/action should be used instead.
	 *
	 * @param array $configuration The module configuration
	 * @param array $dispatcherParameters The dispatcher parameters
	 * @return array The controller/action pair
	 */
	protected function getDispatcherControllerAction($configuration, $dispatcherParameters) {
		$controllerAction = array(
			'controllerName' => '',
			'actionName' => '',
		);

		if (!isset($dispatcherParameters['controllerName'])) {
				// Early return: should use fallback controller/action
			return $controllerAction;
		}

			// Extract configured controllers from module's registration in ext_tables.php
		$controllers = array_keys($configuration['controllerActions']);

		$controller = $dispatcherParameters['controllerName'];
		if (in_array($controller, $controllers)) {
				// Update return value as selected controller is valid
			$controllerAction['controllerName'] = $controller;
			$actions = t3lib_div::trimExplode(',', $configuration['controllerActions'][$controller], TRUE);
			if (isset($dispatcherParameters['actionName'])) {
					// Extract configured actions for selected controllers
				$action = $dispatcherParameters['actionName'];
				if (in_array($action, $actions)) {
						// Requested action is valid for selected controller
					$controllerAction['actionName'] = $action;
				} else {
						// Use first action of selected controller as fallback action
					$controllerAction['actionName'] = $actions[0];
				}
			} else {
					// Use first action of selected controller as fallback action
				$controllerAction['actionName'] = $actions[0];
			}
		}

		return $controllerAction;
	}

	/**
	 * Returns the controller/action pair to use if a module function parameter is found
	 * in the request, otherwise, will just return a blank controller/action pair.
	 *
	 * @param string $module The name of the module
	 * @param string $defaultController The module's default controller
	 * @return array The controller/action pair
	 */
	protected function getModuleFunctionControllerAction($module, $defaultController) {
		$controllerAction = array(
			'controllerName' => '',
			'actionName' => '',
		);

		$set = t3lib_div::_GP('SET');
		if (!$set) {
				// Early return
			return $controllerAction;
		}

		$moduleFunction = $set['function'];
		$matches = array();
		if (preg_match('/^(.*)->(.*)$/', $moduleFunction, $matches)) {
			$controllerAction['controllerName'] = $matches[1];
			$controllerAction['actionName'] = $matches[2];
		} else {
				// Support for external SCbase module function rendering
			$functions = $GLOBALS['TBE_MODULES_EXT']['_configuration'][$module]['MOD_MENU']['function'];
			if (isset($functions[$moduleFunction])) {
				$controllerAction['controllerName'] = $defaultController;
				$controllerAction['actionName'] = 'extObj';
			}
		}

		return $controllerAction;
	}

	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the raw TypoScript setup
	 */
	public function getTypoScriptSetup() {
		if ($this->typoScriptSetupCache === NULL) {
			$template = t3lib_div::makeInstance('t3lib_TStemplate');
				// do not log time-performance information
			$template->tt_track = 0;
			$template->init();
				// Get the root line
			$sysPage = t3lib_div::makeInstance('t3lib_pageSelect');
				// get the rootline for the current page
			$rootline = $sysPage->getRootLine($this->getCurrentPageId());
				// This generates the constants/config + hierarchy info for the template.
			$template->runThroughTemplates($rootline, 0);
			$template->generateConfig();
			$this->typoScriptSetupCache = $template->setup;
		}
		return $this->typoScriptSetupCache;
	}

	/**
	 * Returns the page uid of the current page.
	 * If no page is selected, we'll return the uid of the first root page.
	 *
	 * @return integer current page id. If no page is selected current root page id is returned
	 */
	protected function getCurrentPageId() {
		$pageId = (integer)t3lib_div::_GP('id');
		if ($pageId > 0) {
			return $pageId;
		}

			// get root template
		$rootTemplates = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1');
		if (count($rootTemplates) > 0) {
			return $rootTemplates[0]['pid'];
		}

			// get current site root
		$rootPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1');
		if (count($rootPages) > 0) {
			return $rootPages[0]['uid'];
		}

			// fallback
		return self::DEFAULT_BACKEND_STORAGE_PID;
	}

}
?>