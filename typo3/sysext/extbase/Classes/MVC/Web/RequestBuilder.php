<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * @package Extbase
 * @subpackage MVC\Web
 * @version $ID:$
 *
 * @scope prototype
 */
class Tx_Extbase_MVC_Web_RequestBuilder implements t3lib_Singleton {
	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * This is a unique key for a plugin (not the extension key!)
	 *
	 * @var string
	 */
	protected $pluginName = 'plugin';

	/**
	 * The name of the extension (in UpperCamelCase)
	 *
	 * @var string
	 */
	protected $extensionName = 'Extbase';

	/**
	 * The default controller name
	 *
	 * @var string
	 */
	protected $defaultControllerName = 'Standard';

	/**
	 * The default action of the default controller
	 *
	 * @var string
	 */
	protected $defaultActionName = 'index';

	/**
	 * The allowed actions of the controller. This actions can be called via $_GET and $_POST.
	 *
	 * @var array
	 */
	protected $allowedControllerActions;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 */
	protected $configurationManager;

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects the object manager
	 *
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @return void
	 */
	protected function loadDefaultValues() {
		$configuration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (!empty($configuration['pluginName'])) {
			$this->pluginName = $configuration['pluginName'];
		}
		if (!empty($configuration['extensionName'])) {
			$this->extensionName = $configuration['extensionName'];
		}
		if (!empty($configuration['controller'])) {
			$this->defaultControllerName = $configuration['controller'];
		} elseif (is_array($configuration['switchableControllerActions'])) {
			$firstControllerActions = current($configuration['switchableControllerActions']);
			$this->defaultControllerName = $firstControllerActions['controller'];
		}
		if (!empty($configuration['action'])) {
			$this->defaultActionName = $configuration['action'];
		} elseif (is_array($configuration['switchableControllerActions'])) {
			$firstControllerActions = current($configuration['switchableControllerActions']);
			$this->defaultActionName = array_shift(t3lib_div::trimExplode(',', $firstControllerActions['actions'], TRUE));
		}
		$allowedControllerActions = array();
		if (is_array($configuration['switchableControllerActions'])) {
			foreach ($configuration['switchableControllerActions'] as $controller => $controllerConfiguration) {
				$controllerActions = t3lib_div::trimExplode(',', $controllerConfiguration['actions'], TRUE);
				foreach ($controllerActions as $actionName) {
					$allowedControllerActions[$controller][] = $actionName;
				}
			}
		}
		$this->allowedControllerActions = $allowedControllerActions;
	}

	/**
	 * Builds a web request object from the raw HTTP information and the configuration
	 *
	 * @return Tx_Extbase_MVC_Web_Request The web request as an object
	 */
	public function build() {
		$this->loadDefaultValues();
		$pluginNamespace = Tx_Extbase_Utility_Extension::getPluginNamespace($this->extensionName, $this->pluginName);
		$parameters = t3lib_div::_GPmerged($pluginNamespace);

		if (is_string($parameters['controller']) && array_key_exists($parameters['controller'], $this->allowedControllerActions)) {
			$controllerName = filter_var($parameters['controller'], FILTER_SANITIZE_STRING);
			$allowedActions = $this->allowedControllerActions[$controllerName];
			if (is_string($parameters['action']) && is_array($allowedActions) && in_array($parameters['action'], $allowedActions)) {
				$actionName = filter_var($parameters['action'], FILTER_SANITIZE_STRING);
			} else {
				$actionName = $this->defaultActionName;
			}
		} else {
			$controllerName = $this->defaultControllerName;
			$actionName = $this->defaultActionName;
		}

		$request = $this->objectManager->create('Tx_Extbase_MVC_Web_Request');
		$request->setPluginName($this->pluginName);
		$request->setControllerExtensionName($this->extensionName);
		$request->setControllerName($controllerName);
		$request->setControllerActionName($actionName);
		$request->setRequestURI(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		$request->setMethod((isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : NULL);

		if (is_string($parameters['format']) && (strlen($parameters['format']))) {
			$request->setFormat(filter_var($parameters['format'], FILTER_SANITIZE_STRING));
		}

		foreach ($parameters as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}

		return $request;
	}


}
?>