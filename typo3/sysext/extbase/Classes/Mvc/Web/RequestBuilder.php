<?php
namespace TYPO3\CMS\Extbase\Mvc\Web;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 */
class RequestBuilder implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * This is the vendor name of the extension
	 *
	 * @var string
	 */
	protected $vendorName;

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
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 */
	protected $extensionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
	 */
	protected $environmentService;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
	 * @return void
	 */
	public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService) {
		$this->environmentService = $environmentService;
	}

	/**
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception
	 * @return void
	 */
	protected function loadDefaultValues() {
		$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (empty($configuration['extensionName'])) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception('"extensionName" is not properly configured. Request can\'t be dispatched!', 1289843275);
		}
		if (empty($configuration['pluginName'])) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception('"pluginName" is not properly configured. Request can\'t be dispatched!', 1289843277);
		}
		if (!empty($configuration['vendorName'])) {
			$this->vendorName = $configuration['vendorName'];
		} else {
			$this->vendorName = NULL;
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
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Request The web request as an object
	 */
	public function build() {
		$this->loadDefaultValues();
		$pluginNamespace = $this->extensionService->getPluginNamespace($this->extensionName, $this->pluginName);
		$parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged($pluginNamespace);
		$files = $this->untangleFilesArray($_FILES);
		if (isset($files[$pluginNamespace]) && is_array($files[$pluginNamespace])) {
			$parameters = \TYPO3\CMS\Extbase\Utility\ArrayUtility::arrayMergeRecursiveOverrule($parameters, $files[$pluginNamespace]);
		}
		$controllerName = $this->resolveControllerName($parameters);
		$actionName = $this->resolveActionName($controllerName, $parameters);
		/** @var $request \TYPO3\CMS\Extbase\Mvc\Web\Request */
		$request = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request');
		if ($this->vendorName !== NULL) {
			$request->setControllerVendorName($this->vendorName);
		}
		$request->setPluginName($this->pluginName);
		$request->setControllerExtensionName($this->extensionName);
		$request->setControllerName($controllerName);
		$request->setControllerActionName($actionName);
		$request->setRequestUri(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseUri(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
		$request->setMethod($this->environmentService->getServerRequestMethod());
		if (is_string($parameters['format']) && strlen($parameters['format'])) {
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
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception if the controller could not be resolved
	 * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
	 * @return string
	 */
	protected function resolveControllerName(array $parameters) {
		if (!isset($parameters['controller']) || strlen($parameters['controller']) === 0) {
			if (strlen($this->defaultControllerName) === 0) {
				throw new \TYPO3\CMS\Extbase\Mvc\Exception('The default controller for extension "' . $this->extensionName . '" and plugin "' . $this->pluginName . '" can not be determined. Please check for TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1316104317);
			}
			return $this->defaultControllerName;
		}
		$allowedControllerNames = array_keys($this->allowedControllerActions);
		if (!in_array($parameters['controller'], $allowedControllerNames)) {
			$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
			if (isset($configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) && (boolean) $configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) {
				throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException('The requested resource was not found', 1313857897);
			} elseif (isset($configuration['mvc']['callDefaultActionIfActionCantBeResolved']) && (boolean) $configuration['mvc']['callDefaultActionIfActionCantBeResolved']) {
				return $this->defaultControllerName;
			}
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException('The controller "' . $parameters['controller'] . '" is not allowed by this plugin. Please check for TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1313855173);
		}
		return filter_var($parameters['controller'], FILTER_SANITIZE_STRING);
	}

	/**
	 * Returns the current actionName extracted from given $parameters.
	 * If no action is specified, the defaultActionName will be returned.
	 * If that's not available or the specified action is not defined in the current plugin, an exception is thrown.
	 *
	 * @param string $controllerName
	 * @param array $parameters
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception
	 * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
	 * @return string
	 */
	protected function resolveActionName($controllerName, array $parameters) {
		$defaultActionName = is_array($this->allowedControllerActions[$controllerName]) ? current($this->allowedControllerActions[$controllerName]) : '';
		if (!isset($parameters['action']) || strlen($parameters['action']) === 0) {
			if (strlen($defaultActionName) === 0) {
				throw new \TYPO3\CMS\Extbase\Mvc\Exception('The default action can not be determined for controller "' . $controllerName . '". Please check TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1295479651);
			}
			return $defaultActionName;
		}
		$actionName = $parameters['action'];
		$allowedActionNames = $this->allowedControllerActions[$controllerName];
		if (!in_array($actionName, $allowedActionNames)) {
			$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
			if (isset($configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) && (boolean) $configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved']) {
				throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException('The requested resource was not found', 1313857897);
			} elseif (isset($configuration['mvc']['callDefaultActionIfActionCantBeResolved']) && (boolean) $configuration['mvc']['callDefaultActionIfActionCantBeResolved']) {
				return $defaultActionName;
			}
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException('The action "' . $actionName . '" (controller "' . $controllerName . '") is not allowed by this plugin. Please check TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility::configurePlugin() in your ext_localconf.php.', 1313855175);
		}
		return filter_var($actionName, FILTER_SANITIZE_STRING);
	}

	/**
	 * Transforms the convoluted _FILES superglobal into a manageable form.
	 *
	 * @param array $convolutedFiles The _FILES superglobal
	 * @return array Untangled files
	 * @see TYPO3\FLOW3\Utility\Environment
	 */
	protected function untangleFilesArray(array $convolutedFiles) {
		$untangledFiles = array();
		$fieldPaths = array();
		foreach ($convolutedFiles as $firstLevelFieldName => $fieldInformation) {
			if (!is_array($fieldInformation['error'])) {
				$fieldPaths[] = array($firstLevelFieldName);
			} else {
				$newFieldPaths = $this->calculateFieldPaths($fieldInformation['error'], $firstLevelFieldName);
				array_walk($newFieldPaths, function (&$value, $key) {
					$value = explode('/', $value);
				});
				$fieldPaths = array_merge($fieldPaths, $newFieldPaths);
			}
		}
		foreach ($fieldPaths as $fieldPath) {
			if (count($fieldPath) === 1) {
				$fileInformation = $convolutedFiles[$fieldPath[0]];
			} else {
				$fileInformation = array();
				foreach ($convolutedFiles[$fieldPath[0]] as $key => $subStructure) {
					$fileInformation[$key] = \TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($subStructure, array_slice($fieldPath, 1));
				}
			}
			$untangledFiles = \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($untangledFiles, $fieldPath, $fileInformation);
		}
		return $untangledFiles;
	}

	/**
	 * Returns an array of all possibles "field paths" for the given array.
	 *
	 * @param array $structure The array to walk through
	 * @param string $firstLevelFieldName
	 * @return array An array of paths (as strings) in the format "key1/key2/key3" ...
	 */
	protected function calculateFieldPaths(array $structure, $firstLevelFieldName = NULL) {
		$fieldPaths = array();
		if (is_array($structure)) {
			foreach ($structure as $key => $subStructure) {
				$fieldPath = ($firstLevelFieldName !== NULL ? $firstLevelFieldName . '/' : '') . $key;
				if (is_array($subStructure)) {
					foreach ($this->calculateFieldPaths($subStructure) as $subFieldPath) {
						$fieldPaths[] = $fieldPath . '/' . $subFieldPath;
					}
				} else {
					$fieldPaths[] = $fieldPath;
				}
			}
		}
		return $fieldPaths;
	}
}

?>