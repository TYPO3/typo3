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
 * Builds a web request.
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 *
 * @scope prototype
 */
class Tx_Extbase_MVC_Web_RequestBuilder {
	
	/**
	 * This is a unique key for a plugin (not the extension key!)
	 *
	 * @var string
	 **/
	protected $pluginKey = 'plugin';
	
	/**
	 * The name of the extension (in UpperCamelCase)
	 *
	 * @var string
	 **/
	protected $extensionName = 'Extbase';
	
	/**
	 * The default controller name
	 *
	 * @var string
	 **/
	protected $defaultControllerName = 'Default';
	
	/**
	 * The default action of the default controller
	 *
	 * @var string
	 **/
	protected $defaultActionName = 'index';

	/**
	 * The allowed actions of the controller. This actions can be called via $_GET and $_POST.
	 *
	 * @var array
	 **/
	protected $allowedControllerActions;
	
	public function initialize($configuration) {
		$this->pluginKey = $configuration['pluginKey'];
		if (!empty($configuration['extensionName']) && is_string($configuration['extensionName'])) {
			$this->extensionName = $configuration['extensionName'];
		}
		if (!empty($configuration['controllers.']) && is_array($configuration['controllers.'])) {
			$defaultControllerConfiguration = current($configuration['controllers.']);
			if (!empty($defaultControllerConfiguration['controllerName']) && is_string($defaultControllerConfiguration['controllerName'])) {
				$this->defaultControllerName = $defaultControllerConfiguration['controllerName'];
				$defaultControllerActions = t3lib_div::trimExplode(',', $defaultControllerConfiguration['actions'], TRUE);
				if (!empty($defaultControllerActions[0]) && is_string($defaultControllerActions[0])) {
					$this->defaultActionName = $defaultControllerActions[0];
				}
			}
			$allowedControllerActions = array();
			foreach ($configuration['controllers.'] as $controllerConfiguration) {
				$controllerActions = t3lib_div::trimExplode(',', $controllerConfiguration['actions']);
				foreach ($controllerActions as $actionName) {
					$allowedControllerActions[$controllerConfiguration['controllerName']][] = $actionName;
				}
			}
			$this->allowedControllerActions = $allowedControllerActions;
		}
	}

	/**
	 * Builds a web request object from the raw HTTP information and the configuration
	 *
	 * @return Tx_Extbase_MVC_Web_Request The web request as an object
	 */
	public function build() {
		$parameters = t3lib_div::_GET('tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginKey));
		if (is_string($parameters['controller']) && array_key_exists($parameters['controller'], $this->allowedControllerActions)) {
			$controllerName = filter_var($parameters['controller'], FILTER_SANITIZE_STRING);
			$allowedActions = $this->allowedControllerActions[$controllerName];
			if (is_string($parameters['action']) && is_array($allowedActions) && in_array($parameters['action'], $allowedActions)) {
				$actionName = filter_var($parameters['action'], FILTER_SANITIZE_STRING);
			} else {;
				$actionName = $this->defaultActionName;
			}
		} else {
			$controllerName = $this->defaultControllerName;
			$actionName = $this->defaultActionName;
		}


		$request = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Request');
		$request->setPluginKey($this->pluginKey);
		$request->setExtensionName($this->extensionName);
		$request->setControllerName($controllerName);
		$request->setControllerActionName($actionName);
		$request->setRequestURI(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		// TODO Revise the GParrayMerged method
		foreach (t3lib_div::GParrayMerged('tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginKey)) as $key => $value) {
			$request->setArgument($key, $value);
		}
		return $request;
	}


}
?>