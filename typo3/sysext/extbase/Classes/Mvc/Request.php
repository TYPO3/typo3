<?php
namespace TYPO3\CMS\Extbase\Mvc;

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

use TYPO3\CMS\Core\Utility\ClassNamingUtility;

/**
 * Represents a generic request.
 *
 * @api
 */
class Request implements \TYPO3\CMS\Extbase\Mvc\RequestInterface {

	const PATTERN_MATCH_FORMAT = '/^[a-z0-9]{1,5}$/';

	/**
	 * Pattern after which the controller object name is built
	 *
	 * @var string
	 */
	protected $controllerObjectNamePattern = 'Tx_@extension_@subpackage_Controller_@controllerController';

	/**
	 * Pattern after which the namespaced controller object name is built
	 *
	 * @var string
	 */
	protected $namespacedControllerObjectNamePattern = '@vendor\@extension\@subpackage\Controller\@controllerController';

	/**
	 * @var string Key of the plugin which identifies the plugin. It must be a string containing [a-z0-9]
	 */
	protected $pluginName = '';

	/**
	 * @var string Name of the extension which is supposed to handle this request. This is the extension name converted to UpperCamelCase
	 */
	protected $controllerExtensionName = NULL;

	/**
	 * @var string vendor prefix
	 */
	protected $controllerVendorName = NULL;

	/**
	 * Subpackage key of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerSubpackageKey = NULL;

	/**
	 * @var string Object name of the controller which is supposed to handle this request.
	 */
	protected $controllerName = 'Standard';

	/**
	 * @var string Name of the action the controller is supposed to take.
	 */
	protected $controllerActionName = 'index';

	/**
	 * @var array The arguments for this request
	 */
	protected $arguments = array();

	/**
	 * Framework-internal arguments for this request, such as __referrer.
	 * All framework-internal arguments start with double underscore (__),
	 * and are only used from within the framework. Not for user consumption.
	 * Internal Arguments can be objects, in contrast to public arguments
	 *
	 * @var array
	 */
	protected $internalArguments = array();

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'txt';

	/**
	 * @var boolean If this request has been changed and needs to be dispatched again
	 */
	protected $dispatched = FALSE;

	/**
	 * If this request is a forward because of an error, the original request gets filled.
	 *
	 * @var \TYPO3\CMS\Extbase\Mvc\Request
	 */
	protected $originalRequest = NULL;

	/**
	 * If the request is a forward because of an error, these mapping results get filled here.
	 *
	 * @var \TYPO3\CMS\Extbase\Error\Result
	 */
	protected $originalRequestMappingResults = NULL;

	/**
	 * @var array Errors that occured during this request
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	protected $errors = array();

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 *
	 * @return void
	 * @api
	 */
	public function setDispatched($flag) {
		$this->dispatched = $flag ? TRUE : FALSE;
	}

	/**
	 * If this request has been dispatched and addressed by the responsible
	 * controller and the response is ready to be sent.
	 *
	 * The dispatcher will try to dispatch the request again if it has not been
	 * addressed yet.
	 *
	 * @return boolean TRUE if this request has been disptached sucessfully
	 * @api
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Returns the object name of the controller defined by the extension name and
	 * controller name
	 *
	 * @return string The controller's Object Name
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchControllerException if the controller does not exist
	 * @api
	 */
	public function getControllerObjectName() {
		if (NULL !== $this->controllerVendorName) {
			// It's safe to assume a namespaced name as namespaced names have to follow PSR-0
			$lowercaseObjectName = str_replace('@extension', $this->controllerExtensionName, $this->namespacedControllerObjectNamePattern);
			$lowercaseObjectName = str_replace('@subpackage', $this->controllerSubpackageKey, $lowercaseObjectName);
			$lowercaseObjectName = str_replace('@controller', $this->controllerName, $lowercaseObjectName);
			$lowercaseObjectName = str_replace('@vendor', $this->controllerVendorName, $lowercaseObjectName);
			$lowercaseObjectName = str_replace('\\\\', '\\', $lowercaseObjectName);
		} else {
			$lowercaseObjectName = str_replace('@extension', $this->controllerExtensionName, $this->controllerObjectNamePattern);
			$lowercaseObjectName = str_replace('@subpackage', $this->controllerSubpackageKey, $lowercaseObjectName);
			$lowercaseObjectName = str_replace('@controller', $this->controllerName, $lowercaseObjectName);
			$lowercaseObjectName = str_replace('__', '_', $lowercaseObjectName);
		}
		// TODO implement getCaseSensitiveObjectName()
		$objectName = $lowercaseObjectName;
		if ($objectName === FALSE) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchControllerException('The controller object "' . $lowercaseObjectName . '" does not exist.', 1220884009);
		}
		return $objectName;
	}

	/**
	 * Explicitly sets the object name of the controller
	 *
	 * @param string $controllerObjectName The fully qualified controller object name
	 *
	 * @return void
	 */
	public function setControllerObjectName($controllerObjectName) {
		$nameParts = ClassNamingUtility::explodeObjectControllerName($controllerObjectName);
		$this->controllerVendorName = isset($nameParts['vendorName']) ? $nameParts['vendorName'] : NULL;
		$this->controllerExtensionName = $nameParts['extensionName'];
		$this->controllerSubpackageKey = isset($nameParts['subpackageKey']) ? $nameParts['subpackageKey'] : NULL;
		$this->controllerName = $nameParts['controllerName'];
	}

	/**
	 * Sets the plugin name.
	 *
	 * @param string|NULL $pluginName
	 *
	 * @return void
	 */
	public function setPluginName($pluginName = NULL) {
		if ($pluginName !== NULL) {
			$this->pluginName = $pluginName;
		}
	}

	/**
	 * Returns the plugin key.
	 *
	 * @return string The plugin key
	 * @api
	 */
	public function getPluginName() {
		return $this->pluginName;
	}

	/**
	 * Sets the extension name of the controller.
	 *
	 * @param string $controllerExtensionName The extension name.
	 *
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException if the extension name is not valid
	 */
	public function setControllerExtensionName($controllerExtensionName) {
		if ($controllerExtensionName !== NULL) {
			$this->controllerExtensionName = $controllerExtensionName;
		}
	}

	/**
	 * Returns the extension name of the specified controller.
	 *
	 * @return string The extension name
	 * @api
	 */
	public function getControllerExtensionName() {
		return $this->controllerExtensionName;
	}

	/**
	 * Returns the extension name of the specified controller.
	 *
	 * @return string The extension key
	 * @api
	 */
	public function getControllerExtensionKey() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($this->controllerExtensionName);
	}

	/**
	 * Sets the subpackage key of the controller.
	 *
	 * @param string $subpackageKey The subpackage key.
	 *
	 * @return void
	 */
	public function setControllerSubpackageKey($subpackageKey) {
		$this->controllerSubpackageKey = $subpackageKey;
	}

	/**
	 * Returns the subpackage key of the specified controller.
	 * If there is no subpackage key set, the method returns NULL
	 *
	 * @return string The subpackage key
	 */
	public function getControllerSubpackageKey() {
		return $this->controllerSubpackageKey;
	}

	/**
	 * Sets the name of the controller which is supposed to handle the request.
	 * Note: This is not the object name of the controller!
	 *
	 * @param string $controllerName Name of the controller
	 *
	 * @throws Exception\InvalidControllerNameException
	 * @return void
	 */
	public function setControllerName($controllerName) {
		if (!is_string($controllerName) && $controllerName !== NULL) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		}
		if (strpos($controllerName, '_') !== FALSE) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException('The controller name must not contain underscores.', 1217846412);
		}
		if ($controllerName !== NULL) {
			$this->controllerName = $controllerName;
		}
	}

	/**
	 * Returns the object name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Object name of the controller
	 * @api
	 */
	public function getControllerName() {
		return $this->controllerName;
	}

	/**
	 * Sets the name of the action contained in this request.
	 *
	 * Note that the action name must start with a lower case letter and is case sensitive.
	 *
	 * @param string $actionName Name of the action to execute by the controller
	 *
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException if the action name is not valid
	 */
	public function setControllerActionName($actionName) {
		if (!is_string($actionName) && $actionName !== NULL) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		}
		if ($actionName[0] !== strtolower($actionName[0]) && $actionName !== NULL) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
		}
		if ($actionName !== NULL) {
			$this->controllerActionName = $actionName;
		}
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @api
	 */
	public function getControllerActionName() {
		$controllerObjectName = $this->getControllerObjectName();
		if ($controllerObjectName !== '' && $this->controllerActionName === strtolower($this->controllerActionName)) {
			$actionMethodName = $this->controllerActionName . 'Action';
			$classMethods = get_class_methods($controllerObjectName);
			if (is_array($classMethods)) {
				foreach ($classMethods as $existingMethodName) {
					if (strtolower($existingMethodName) === strtolower($actionMethodName)) {
						$this->controllerActionName = substr($existingMethodName, 0, -6);
						break;
					}
				}
			}
		}
		return $this->controllerActionName;
	}

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 *
	 * @throws Exception\InvalidArgumentNameException
	 * @return void
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || strlen($argumentName) == 0) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException('Invalid argument name.', 1210858767);
		}
		if ($argumentName[0] === '_' && $argumentName[1] === '_') {
			$this->internalArguments[$argumentName] = $value;
			return;
		}
		switch ($argumentName) {
			case '@extension':
				$this->setControllerExtensionName($value);
				break;
			case '@subpackage':
				$this->setControllerSubpackageKey($value);
				break;
			case '@controller':
				$this->setControllerName($value);
				break;
			case '@action':
				$this->setControllerActionName($value);
				break;
			case '@format':
				$this->setFormat($value);
				break;
			case '@vendor':
				$this->setControllerVendorName($value);
				break;
			default:
				$this->arguments[$argumentName] = $value;
		}
	}

	/**
	 * sets the VendorName
	 *
	 * @param string $vendorName
	 *
	 * @return void
	 */
	public function setControllerVendorName($vendorName) {
		$this->controllerVendorName = $vendorName;
	}

	/**
	 * get the VendorName
	 *
	 * @return string
	 */
	public function getControllerVendorName() {
		return $this->controllerVendorName;
	}

	/**
	 * Sets the whole arguments array and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param array $arguments An array of argument names and their values
	 *
	 * @return void
	 */
	public function setArguments(array $arguments) {
		$this->arguments = array();
		foreach ($arguments as $argumentName => $argumentValue) {
			$this->setArgument($argumentName, $argumentValue);
		}
	}

	/**
	 * Returns an array of arguments and their values
	 *
	 * @return array Associative array of arguments and their values (which may be arguments and values as well)
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 *
	 * @return string Value of the argument
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException if such an argument does not exist
	 * @api
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
		}
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 *
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 * @api
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Sets the requested representation format
	 *
	 * @param string $format The desired format, something like "html", "xml", "png", "json" or the like. Can even be something like "rss.xml".
	 *
	 * @return void
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * Returns the requested representation format
	 *
	 * @return string The desired format, something like "html", "xml", "png", "json" or the like.
	 * @api
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Set errors that occured during the request (e.g. argument mapping errors)
	 *
	 * @param array $errors An array of \TYPO3\CMS\Extbase\Error\Error objects
	 *
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function setErrors(array $errors) {
		$this->errors = $errors;
	}

	/**
	 * Get errors that occured during the request (e.g. argument mapping errors)
	 *
	 * @return array The errors that occured during the request
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Returns the original request. Filled only if a property mapping error occured.
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Request the original request.
	 */
	public function getOriginalRequest() {
		return $this->originalRequest;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Request $originalRequest
	 *
	 * @return void
	 */
	public function setOriginalRequest(\TYPO3\CMS\Extbase\Mvc\Request $originalRequest) {
		$this->originalRequest = $originalRequest;
	}

	/**
	 * Get the request mapping results for the original request.
	 *
	 * @return \TYPO3\CMS\Extbase\Error\Result
	 */
	public function getOriginalRequestMappingResults() {
		if ($this->originalRequestMappingResults === NULL) {
			return new \TYPO3\CMS\Extbase\Error\Result();
		}
		return $this->originalRequestMappingResults;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Error\Result $originalRequestMappingResults
	 */
	public function setOriginalRequestMappingResults(\TYPO3\CMS\Extbase\Error\Result $originalRequestMappingResults) {
		$this->originalRequestMappingResults = $originalRequestMappingResults;
	}

	/**
	 * Get the internal arguments of the request, i.e. every argument starting
	 * with two underscores.
	 *
	 * @return array
	 */
	public function getInternalArguments() {
		return $this->internalArguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 *
	 * @return string Value of the argument, or NULL if not set.
	 */
	public function getInternalArgument($argumentName) {
		if (!isset($this->internalArguments[$argumentName])) {
			return NULL;
		}
		return $this->internalArguments[$argumentName];
	}
}

?>