<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Represents a generic request.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class TX_EXTMVC_Request {

	const PATTERN_MATCH_FORMAT = '/^[a-z0-9]{1,5}$/';

	/**
	 * Pattern after which the controller object name is built
	 *
	 * @var string
	 */
	protected $controllerObjectNamePattern = 'TX_@extension_Controller_@controllerController';

	/**
	 * Pattern after which the view object name is built
	 *
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TX_@extension_View_@controller@action';

	/**
	 * Extension key of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerExtensionKey = 'EXTMVC';

	/**
	 * @var string Object name of the controller which is supposed to handle this request.
	 */
	protected $controllerName = 'Default';

	/**
	 * @var string Name of the action the controller is supposed to take.
	 */
	protected $controllerActionName = 'index';

	/**
	 * @var ArrayObject The arguments for this request
	 */
	protected $arguments;

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'txt';

	/**
	 * @var boolean If this request has been changed and needs to be dispatched again
	 */
	protected $dispatched = FALSE;

	/**
	 * Constructs this request
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->arguments = new ArrayObject;
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Returns the object name of the controller defined by the extension key and
	 * controller name
	 *
	 * @return string The controller's Object Name
	 * @throws TX_EXTMVC_Exception_NoSuchController if the controller does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerObjectName() {
		$lowercaseObjectName = str_replace('@extension', $this->controllerExtensionKey, $this->controllerObjectNamePattern);
		$lowercaseObjectName = str_replace('@controller', $this->controllerName, $lowercaseObjectName);
		$objectName = $lowercaseObjectName; //$this->objectManager->getCaseSensitiveObjectName($lowercaseObjectName); // TODO implement getCaseSensitiveObjectName() 
		if ($objectName === FALSE) throw new TX_EXTMVC_Exception_NoSuchController('The controller object "' . $lowercaseObjectName . '" does not exist.', 1220884009);

		return $objectName;
	}

	/**
	 * Sets the pattern for building the controller object name.
	 *
	 * The pattern may contain the placeholders "@extension" and "@controller" which will be substituted
	 * by the real extension key and controller name.
	 *
	 * @param string $pattern The pattern
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerObjectNamePattern($pattern) {
		$this->controllerObjectNamePattern = $pattern;
	}

	/**
	 * Returns the pattern for building the controller object name.
	 *
	 * @return string $pattern The pattern
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerObjectNamePattern() {
		return $this->controllerObjectNamePattern;
	}

	/**
	 * Sets the pattern for building the view object name
	 *
	 * @param string $pattern The view object name pattern, eg. F3_@extension_View::@controller@action
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setViewObjectNamePattern($pattern) {
		if (!is_string($pattern)) throw new InvalidArgumentException('The view object name pattern must be a valid string, ' . gettype($pattern) . ' given.', 1221563219);
		$this->viewObjectNamePattern = $pattern;
	}

	/**
	 * Returns the View Object Name Pattern
	 *
	 * @return string The pattern
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getViewObjectNamePattern() {
		return $this->viewObjectNamePattern;
	}

	/**
	 * Returns the view's (possible) object name according to the defined view object
	 * name pattern and the specified values for package, controller, action and format.
	 *
	 * If no valid view object name could be resolved, FALSE is returned
	 *
	 * @return mixed Either the view object name or FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getViewObjectName() {
		$possibleViewName = $this->viewObjectNamePattern;
		$possibleViewName = str_replace('@extension', $this->controllerExtensionKey, $possibleViewName);
		$possibleViewName = str_replace('@controller', $this->controllerName, $possibleViewName);
		$possibleViewName = str_replace('@action', ucfirst($this->controllerActionName), $possibleViewName);

		$viewObjectName = $possibleViewName;
		// $viewObjectName = str_replace('@format', $this->format, $possibleViewName); //$this->objectManager->getCaseSensitiveObjectName(str_replace('@format', $this->format, $possibleViewName)); // TODO
		// if ($viewObjectName === FALSE) {
		// 	$viewObjectName = str_replace('@format', '', $possibleViewName); //$this->objectManager->getCaseSensitiveObjectName(str_replace('@format', '', $possibleViewName));
		// }
		return $viewObjectName;
	}

	/**
	 * Sets the extension key of the controller.
	 *
	 * @param string $extensionKey The extension key.
	 * @return void
	 * @throws TX_EXTMVC_Exception_InvalidExtensionKey if the extension key is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerExtensionKey($extensionKey) {
		$upperCamelCasedExtensionKey = $extensionKey; //$this->packageManager->getCaseSensitiveExtensionKey($extensionKey);  // TODO implement getCaseSensitiveExtensionKey() 
		if ($upperCamelCasedExtensionKey === FALSE) throw new TX_EXTMVC_Exception_InvalidExtensionKey('"' . $extensionKey . '" is not a valid extension key.', 1217961104);
		$this->controllerExtensionKey = $upperCamelCasedExtensionKey;
	}

	/**
	 * Returns the extension key of the specified controller.
	 *
	 * @return string The extension key
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerExtensionKey() {
		return $this->controllerExtensionKey;
	}

	/**
	 * Sets the name of the controller which is supposed to handle the request.
	 * Note: This is not the object name of the controller!
	 *
	 * @param string $controllerName Name of the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerName($controllerName) {
		if (!is_string($controllerName)) throw new TX_EXTMVC_Exception_InvalidControllerName('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		if (strpos($controllerName, '_') !== FALSE) throw new TX_EXTMVC_Exception_InvalidControllerName('The controller name must not contain underscores.', 1217846412);
		$this->controllerName = $controllerName;
	}

	/**
	 * Returns the object name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Object name of the controller
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @throws TX_EXTMVC_Exception_InvalidActionName if the action name is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerActionName($actionName) {
		if (!is_string($actionName)) throw new TX_EXTMVC_Exception_InvalidActionName('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		if ($actionName{0} !== strtolower($actionName{0})) throw new TX_EXTMVC_Exception_InvalidActionName('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
		$this->controllerActionName = $actionName;
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || strlen($argumentName) == 0) throw new TX_EXTMVC_Exception_InvalidArgumentName('Invalid argument name.', 1210858767);
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments ArrayObject and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param ArrayObject $arguments An ArrayObject of argument names and their values
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArguments(_ArrayObject $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Returns an ArrayObject of arguments and their values
	 *
	 * @return ArrayObject ArrayObject of arguments and their values (which may be arguments and values as well)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Sets the requested representation format
	 *
	 * @param string $format The desired format, something like "html", "xml", "png", "json" or the like.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFormat($format) {
		if (!preg_match(self::PATTERN_MATCH_FORMAT, $format)) throw new TX_EXTMVC_Exception_InvalidFormat('An invalid request format (' . $format . ') was given.', 1218015038);
		$this->format = $format;
	}

	/**
	 * Returns the requested representation format
	 *
	 * @return string The desired format, something like "html", "xml", "png", "json" or the like.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws TX_EXTMVC_Exception_NoSuchArgument if such an argument does not exist
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new TX_EXTMVC_Exception_NoSuchArgument('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}
}
?>
