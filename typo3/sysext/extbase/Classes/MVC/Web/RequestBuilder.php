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
	 * The default action of the default controller
	 *
	 * @var string
	 */
	protected $defaultActionName;

	/**
	 * The allowed actions of the controller. This actions can be called via $_GET and $_POST.
	 *
	 * @var array
	 */
	protected $allowedControllerActions = array();

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
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
		$this->defaultActionName = current($configuration['controllerConfiguration'][$this->defaultControllerName]['actions']);

		$allowedControllerActions = array();
		foreach ($configuration['controllerConfiguration'] as $controllerName => $controllerActions) {
			$allowedControllerActions[$controllerName] = $controllerActions['actions'];
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
		$pluginNamespace = $this->extensionService->getPluginNamespace($this->extensionName, $this->pluginName);
		$parameters = t3lib_div::_GPmerged($pluginNamespace);

		if (is_string($parameters['controller']) && array_key_exists($parameters['controller'], $this->allowedControllerActions)) {
			$controllerName = filter_var($parameters['controller'], FILTER_SANITIZE_STRING);
		} elseif (!empty($this->defaultControllerName)) {
			$controllerName = $this->defaultControllerName;
		} else {
			throw new Tx_Extbase_MVC_Exception(
				'The default controller can not be determined.<br />'
				. 'Please check for Tx_Extbase_Utility_Extension::configurePlugin() in your ext_localconf.php.',
				1295479650
			);
		}

		$allowedActions = $this->allowedControllerActions[$controllerName];
		if (is_string($parameters['action']) && is_array($allowedActions) && in_array($parameters['action'], $allowedActions)) {
			$actionName = filter_var($parameters['action'], FILTER_SANITIZE_STRING);
		} elseif (!empty($this->defaultActionName)) {
			$actionName = $this->defaultActionName;
		} else {
			throw new Tx_Extbase_MVC_Exception(
				'The default action can not be determined for controller "' . $controllerName . '".<br />'
				. 'Please check Tx_Extbase_Utility_Extension::configurePlugin() in your ext_localconf.php.',
				1295479651
			);
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