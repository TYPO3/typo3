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
	protected $extensionName;

	/**
	 * The default controller name
	 *
	 * @var string
	 */
	protected $defaultControllerName;

	/**
	 * The default format of the response object
	 *
	 * @var string
	 */
	protected $defaultFormat = 'html';

	/**
	 * The allowed actions of the controller. This actions can be called via $_GET and $_POST.
	 *
	 * @var array
	 */
	protected $allowedControllerActions = array();

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Extbase_Service_ExtensionService
	 */
	protected $extensionService;

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
	 * @param Tx_Extbase_Service_ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(Tx_Extbase_Service_ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
	}

	/**
	 * @return void
	 */
	protected function loadDefaultValues() {
		$configuration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (empty($configuration['extensionName'])) {
			throw new Tx_Extbase_MVC_Exception('"extensionName" is not properly configured. Request can\'t be dispatched!', 1289843275);
		}
		if (empty($configuration['pluginName'])) {
			throw new Tx_Extbase_MVC_Exception('"pluginName" is not properly configured. Request can\'t be dispatched!', 1289843277);
		}
		$this->extensionName = $configuration['extensionName'];
		$this->pluginName = $configuration['pluginName'];
		$this->defaultControllerName = current(array_keys($configuration['controllerConfiguration']));

		$this->allowedControllerActions = array();
		foreach ($configuration['controllerConfiguration'] as $controllerName => $controllerActions) {
			$this->allowedControllerActions[$controllerName] = $controllerActions['actions'];
		}
		if (!empty($configuration['format'])) {
			$this->defaultFormat = $configuration['format'];
		}
	}

	/**
	 * Builds a web request object from the raw HTTP information and the configuration
	 *
	 * @return Tx_Extbase_MVC_Web_Request The web request as an object
	 */
	public function build() {
		$this->loadDefaultValues();
		$pluginNamespace = $this->extensionService->getPluginNamespace($this->extensionName, $this->pluginName);
		$parameters = t3lib_div::_GPmerged($pluginNamespace);

		$controllerName = $this->resolveControllerName($parameters);
		$actionName = $this->resolveActionName($controllerName, $parameters);

		$request = $this->objectManager->create('Tx_Extbase_MVC_Web_Request');
		$request->setPluginName($this->pluginName);
		$request->setControllerExtensionName($this->extensionName);
		$request->setControllerName($controllerName);
		$request->setControllerActionName($actionName);
		$request->setRequestUri(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseUri(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		$request->setMethod((isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : NULL);

		if (is_string($parameters['format']) && (strlen($parameters['format']))) {
			$request->setFormat(filter_var($parameters['format'], FILTER_SANITIZE_STRING));
		} else {
			$request->setFormat($this->defaultFormat);
		}

		foreach ($parameters as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}

		return $request;
	}

	/**
	 * Returns the current ControllerName extracted from given $parameters.
	 * If no controller is specified, the defaultControllerName will be returned.
	 * If that's not available, an exception is thrown.
	 *
	 * @param array $parameters
	 * @return string
	 * @throws Tx_Extbase_MVC_Exception if the controller could not be resolved
	 */
	protected function resolveControllerName(array $parameters) {
		if (!isset($parameters['controller']) || strlen($parameters['controller']) === 0) {
			if (strlen($this->defaultControllerName) === 0) {
				throw new Tx_Extbase_MVC_Exception(
					'The default controller can not be determined. Please check for Tx_Extbase_Utility_Extension::configurePlugin() in your ext_localconf.php.',
					1316104317
				);
			}
			return $this->defaultControllerName;
		}
		$allowedControllerNames = array_keys($this->allowedControllerActions);
		if (!in_array($parameters['controller'], $allowedControllerNames)) {
			$configuration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
			if (isset($configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) && (boolean)$configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) {
				throw new t3lib_error_http_PageNotFoundException(
					'The requested resource was not found',
					1313857897
				);
			} elseif (isset($configuration['mvc']['callDefaultActionIfActionCantBeResolved']) && (boolean)$configuration['mvc']['callDefaultActionIfActionCantBeResolved']) {
				return $this->defaultControllerName;
			}
			throw new Tx_Extbase_MVC_Exception_InvalidControllerName(
				'The controller "' . $parameters['controller'] . '" is not allowed by this plugin. Please check for Tx_Extbase_Utility_Extension::configurePlugin() in your ext_localconf.php.',
				1313855173
			);
		}
		return filter_var($parameters['controller'], FILTER_SANITIZE_STRING);
	}

	/**
	 * Returns the current actionName extracted from given $parameters.
	 * If no action is specified, the defaultActionName will be returned.
	 * If that's not available or the specified action is not defined in the current plugin, an exception is thrown.
	 *
	 * @param $controllerName
	 * @param array $parameters
	 * @return string
	 * @throws t3lib_error_http_PageNotFoundException|Tx_Extbase_MVC_Exception|Tx_Extbase_MVC_Exception_InvalidActionName if the action could not be resolved
	 */
	protected function resolveActionName($controllerName, array $parameters) {
		$defaultActionName = is_array($this->allowedControllerActions[$controllerName]) ? current($this->allowedControllerActions[$controllerName]) : '';
		if (!isset($parameters['action']) || strlen($parameters['action']) === 0) {
			if (strlen($defaultActionName) === 0) {
				throw new Tx_Extbase_MVC_Exception(
					'The default action can not be determined for controller "' . $controllerName . '". Please check Tx_Extbase_Utility_Extension::configurePlugin() in your ext_localconf.php.',
					1295479651
				);
			}
			return $defaultActionName;
		}
		$actionName = $parameters['action'];
		$allowedActionNames = $this->allowedControllerActions[$controllerName];
		if (!in_array($actionName, $allowedActionNames)) {
			$configuration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
			if (isset($configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) && (boolean)$configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) {
				throw new t3lib_error_http_PageNotFoundException(
					'The requested resource was not found',
					1313857897
				);
			} elseif (isset($configuration['mvc']['callDefaultActionIfActionCantBeResolved']) && (boolean)$configuration['mvc']['callDefaultActionIfActionCantBeResolved']) {
				return $defaultActionName;
			}
			throw new Tx_Extbase_MVC_Exception_InvalidActionName(
				'The action "' . $actionName . '" (controller "' . $controllerName . '") is not allowed by this plugin. Please check Tx_Extbase_Utility_Extension::configurePlugin() in your ext_localconf.php.',
				1313855175
			);
		}
		return filter_var($actionName, FILTER_SANITIZE_STRING);
	}


}
?>