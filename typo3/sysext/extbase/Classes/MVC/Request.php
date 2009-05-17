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
 * Represents a generic request.
 *
 * @package Extbase
 * @subpackage MVC
 * @version $ID:$
 * @scope prototype
 */
class Tx_Extbase_MVC_Request {

	const PATTERN_MATCH_FORMAT = '/^[a-z0-9]{1,5}$/';

	/**
	 * Pattern after which the controller object name is built
	 *
	 * @var string
	 */
	protected $controllerObjectNamePattern = 'Tx_@extension_Controller_@controllerController';

	/**
	 * @var string Pattern after which the view object name is built
	 */
	protected $viewObjectNamePattern = 'Tx_@extension_View_@controller@action';

	/**
	 * @var string Key of the plugin which identifies the plugin. It must be a string containing [a-z0-9]
	 */
	protected $pluginName = '';

	/**
	 * @var string Name of the extension which is supposed to handle this request. This is the extension name converted to UpperCamelCase
	 */
	protected $controllerExtensionName = 'Extbase';

	/**
	 * @var string Name of the controller which is supposed to handle this request.
	 */
	protected $controllerName = 'Standard';

	/**
	 * @var string Name of the action the controller is supposed to take.
	 */
	protected $controllerActionName = 'index';

	/**
	 * @var ArrayObject The arguments for this request
	 */
	protected $arguments = array();

	/**
	 * @var boolean If this request has been changed and needs to be dispatched again
	 */
	protected $dispatched = FALSE;

	/**
	 * Constructs this request
	 *
	 */
	public function __construct() {
	}

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
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
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Returns the object name of the controller defined by the extension name and
	 * controller name
	 *
	 * @return string The controller's Object Name
	 * @throws Tx_Extbase_Exception_NoSuchController if the controller does not exist
	 */
	public function getControllerObjectName() {
		$lowercaseObjectName = str_replace('@extension', $this->controllerExtensionName, $this->controllerObjectNamePattern);
		$lowercaseObjectName = str_replace('@controller', $this->controllerName, $lowercaseObjectName);
		// TODO implement getCaseSensitiveObjectName()
		$objectName = $lowercaseObjectName;
		if ($objectName === FALSE) throw new Tx_Extbase_Exception_NoSuchController('The controller object "' . $lowercaseObjectName . '" does not exist.', 1220884009);

		return $objectName;
	}

	/**
	 * Sets the pattern for building the controller object name.
	 *
	 * The pattern may contain the placeholders "@extension" and "@controller" which will be substituted
	 * by the real extension name and controller name.
	 *
	 * @param string $pattern The pattern
	 * @return void
	 */
	public function setControllerObjectNamePattern($pattern) {
		$this->controllerObjectNamePattern = $pattern;
	}

	/**
	 * Returns the pattern for building the controller object name.
	 *
	 * @return string $pattern The pattern
	 */
	public function getControllerObjectNamePattern() {
		return $this->controllerObjectNamePattern;
	}

	/**
	 * Sets the pattern for building the view object name
	 *
	 * @param string $pattern The view object name pattern, eg. F3_@extension_View::@controller@action
	 * @return void
	 */
	public function setViewObjectNamePattern($pattern) {
		if (!is_string($pattern)) throw new InvalidArgumentException('The view object name pattern must be a valid string, ' . gettype($pattern) . ' given.', 1221563219);
		$this->viewObjectNamePattern = $pattern;
	}

	/**
	 * Returns the View Object Name Pattern
	 *
	 * @return string The pattern
	 */
	public function getViewObjectNamePattern() {
		return $this->viewObjectNamePattern;
	}

	/**
	 * Returns the view's (possible) object name according to the defined view object
	 * name pattern and the specified values for extension, controller, action and format.
	 *
	 * If no valid view object name could be resolved, FALSE is returned
	 *
	 * @return mixed Either the view object name or FALSE
	 */
	public function getViewObjectName() {
		$viewObjectName = $this->viewObjectNamePattern;
		$viewObjectName = str_replace('@extension', $this->controllerExtensionName, $viewObjectName);
		$viewObjectName = str_replace('@controller', $this->controllerName, $viewObjectName);
		$viewObjectName = str_replace('@action', ucfirst($this->controllerActionName), $viewObjectName);
		return $viewObjectName;
	}

	/**
	 * Sets the plugin name.
	 *
	 * @param string $extensionName The plugin name.
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
	 */
	public function getPluginName() {
		return $this->pluginName;
	}

	/**
	 * Sets the extension name of the controller.
	 *
	 * @param string $controllerExtensionName The extension name.
	 * @return void
	 * @throws Tx_Extbase_Exception_InvalidExtensionName if the extension name is not valid
	 */
	public function setControllerExtensionName($controllerExtensionName = NULL) {
		if ($controllerExtensionName !== NULL) {
			$this->controllerExtensionName = $controllerExtensionName;
		}
	}

	/**
	 * Returns the extension name of the specified controller.
	 *
	 * @return string The extension name
	 */
	public function getControllerExtensionName() {
		return $this->controllerExtensionName;
	}

	/**
	 * Returns the extension name of the specified controller.
	 *
	 * @return string The extension name
	 */
	public function getControllerExtensionKey() {
		return t3lib_div::camelCaseToLowerCaseUnderscored($this->controllerExtensionName);
	}

	/**
	 * Sets the name of the controller which is supposed to handle the request.
	 * Note: This is not the object name of the controller!
	 *
	 * @param string $controllerName Name of the controller
	 * @return void
	 */
	public function setControllerName($controllerName = NULL) {
		if (!is_string($controllerName) && $controllerName !== NULL) throw new Tx_Extbase_Exception_InvalidControllerName('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		if (strpos($controllerName, '_') !== FALSE) throw new Tx_Extbase_Exception_InvalidControllerName('The controller name must not contain underscores.', 1217846412);
		if ($controllerName !== NULL) {
			$this->controllerName = $controllerName;
		}
	}

	/**
	 * Returns the object name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Object name of the controller
	 */
	public function getControllerName() {
		return $this->controllerName;
	}

	/**
	 * Sets the name of the action contained in this request.
	 *
	 * Note that the action name must start with a lower case letter.
	 *
	 * @param string $actionName: Name of the action to execute by the controller
	 * @return void
	 * @throws Tx_Extbase_Exception_InvalidActionName if the action name is not valid
	 */
	public function setControllerActionName($actionName = NULL) {
		if (!is_string($actionName) && $actionName !== NULL) throw new Tx_Extbase_Exception_InvalidActionName('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		if (($actionName{0} !== strtolower($actionName{0})) && $actionName !== NULL) throw new Tx_Extbase_Exception_InvalidActionName('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
		if ($actionName !== NULL) {
			$this->controllerActionName = $actionName;
		}
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 */
	public function getControllerActionName() {
		return $this->controllerActionName;
	}

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @return void
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || strlen($argumentName) == 0) throw new Tx_Extbase_Exception_InvalidArgumentName('Invalid argument name.', 1210858767);
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments array and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Returns an array of arguments and their values
	 *
	 * @return array Associative array of arguments and their values (which may be arguments and values as well)
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @throws Tx_Extbase_Exception_NoSuchArgument if such an argument does not exist
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new Tx_Extbase_Exception_NoSuchArgument('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}
}
?>