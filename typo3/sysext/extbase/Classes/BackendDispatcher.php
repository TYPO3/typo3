<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Xavier Perseguers <typo3@perseguers.ch>
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
 * Creates a request and dispatches it to the backend controller which was
 * specified by Tx_Extbase_Utility_Module::registerModule() and returns the
 * content to the v4 framework.
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: BackendDispatcher.php 23179 2009-08-08 13:24:24Z xperseguers $
 */
class Tx_Extbase_BackendDispatcher extends Tx_Extbase_Dispatcher {

	/**
	 * Calls an Extbase Backend module.
	 *
	 * @param string $module The name of the module
	 * @return void
	 */
	public function callModule($module) {
		if (!isset($GLOBALS['TBE_EXTBASE_MODULES'][$module])) {
			die('No configuration found for module ' . $module);
		}

		$config = $GLOBALS['TBE_EXTBASE_MODULES'][$module];

			// Check permissions and exit if the user has no permission for entry
		$GLOBALS['BE_USER']->modAccess($config, TRUE);
		if (t3lib_div::_GP('id')) {
				// Check page access
			$id = t3lib_div::_GP('id');
			$permClause = $GLOBALS['BE_USER']->getPagePermsClause(TRUE);
			$access = is_array(t3lib_BEfunc::readPageAccess($id, $permClause));
			if (!$access) {
				t3lib_BEfunc::typo3PrintError('No Access', 'You don\'t have access to this page', 0);
			}
		}

			// Resolve the controller/action to use
		$controllerAction = $this->resolveControllerAction($module);

			// As for SCbase modules, output of the controller/action pair should be echoed
		echo $this->transfer($module, $controllerAction['controller'], $controllerAction['action']);
	}

	/**
	 * Resolves the controller and action to use for current call.
	 * This takes into account any function menu that has being called. 
	 *
	 * @param string $module The name of the module
	 * @return array The controller/action pair to use for current call
	 */
	protected function resolveControllerAction($module) {
		$configuration = $GLOBALS['TBE_EXTBASE_MODULES'][$module];
		$fallbackControllerAction = $this->getFallbackControllerAction($configuration);

			// Extract dispatcher settings from request
		$argumentPrefix = strtolower('tx_' . $configuration['extensionName'] . '_' . $configuration['name']);
		$dispatcherParameters = t3lib_div::_GPmerged($argumentPrefix);
		$dispatcherControllerAction = $this->getDispatcherControllerAction($configuration, $dispatcherParameters);

			// Extract module function settings from request
		$moduleFunctionControllerAction = $this->getModuleFunctionControllerAction($module, $fallbackControllerAction['controller']);	

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
			'controller' => $defaultController,
			'action' => $defaultAction,
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
			'controller' => '',
			'action' => '',
		);

		if (!isset($dispatcherParameters['controller'])) {
				// Early return: should use fallback controller/action
			return $controllerAction;
		}

			// Extract configured controllers from module's registration in ext_tables.php
		$controllers = array_keys($configuration['controllerActions']);

		$controller = $dispatcherParameters['controller'];
		if (in_array($controller, $controllers)) {
				// Update return value as selected controller is valid
			$controllerAction['controller'] = $controller;
			$actions = t3lib_div::trimExplode(',', $configuration['controllerActions'][$controller], TRUE);
			if (isset($dispatcherParameters['action'])) {
					// Extract configured actions for selected controllers
				$action = $dispatcherParameters['action'];
				if (in_array($action, $actions)) {
						// Requested action is valid for selected controller
					$controllerAction['action'] = $action;
				} else {
						// Use first action of selected controller as fallback action
					$controllerAction['action'] = $actions[0];
				}
			} else {
					// Use first action of selected controller as fallback action
				$controllerAction['action'] = $actions[0];
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
			'controller' => '',
			'action' => '',
		);

		$set = t3lib_div::_GP('SET');
		if (!$set) {
				// Early return
			return $controllerAction;
		}

		$moduleFunction = $set['function'];
		$matches = array();
		if (preg_match('/^(.*)->(.*)$/', $moduleFunction, $matches)) {
			$controllerAction['controller'] = $matches[1];
			$controllerAction['action'] = $matches[2];
		} else {
				// Support for external SCbase module function rendering
			$functions = $GLOBALS['TBE_MODULES_EXT'][$module]['MOD_MENU']['function'];
			if (isset($functions[$moduleFunction])) {
				$controllerAction['controller'] = $defaultController;
				$controllerAction['action'] = 'extObj';
			}
		}

		return $controllerAction;
	}

	/**
	 * Transfers the request to an Extbase backend module, calling
	 * a given controller/action.
	 *
	 * @param string $module The name of the module
	 * @param string $controller The controller to use
	 * @param string $action The controller's action to execute
	 * @return string The module rendered view
	 */
	protected function transfer($module, $controller, $action) {
		 $config = $GLOBALS['TBE_EXTBASE_MODULES'][$module];
		 
		 $extbaseConfiguration = array(
			'userFunc' => 'tx_extbase_dispatcher->dispatch',
			'pluginName' => $module,
			'extensionName' => $config['extensionName'],
			'controller' => $controller,
			'action' => $action,
			'switchableControllerActions.' => array(),
			//'persistence' => '< plugin.tx_' . strtolower($config['extensionName']) . '.persistence',
		);
		
		$i = 1;
		foreach ($config['controllerActions'] as $controller => $actions) {
				// Add an "extObj" action for the default controller to handle external
				// SCbase modules which add function menu entries
			if ($i == 1) {
				$actions .= ',extObj'; 
			}
			$extbaseConfiguration['switchableControllerActions.'][$i++ . '.'] = array(
				'controller' => $controller,
				'actions' => $actions,
			);
		}
				
			// BACK_PATH is the path from the typo3/ directory from within the
			// directory containing the controller file. We are using mod.php dispatcher
			// and thus we are already within typo3/ because we call typo3/mod.php
		$GLOBALS['BACK_PATH'] = '';
		
		return $this->dispatch('Here comes Extbase BE Module', $extbaseConfiguration);
	}
	
}
?>