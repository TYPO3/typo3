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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_ControllerInterface.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_Arguments.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Exception/TX_EXTMVC_Exception_StopAction.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Validation/TX_EXTMVC_Validation_ValidatorResolver.php');

/**
 * An abstract base class for Controllers
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class TX_EXTMVC_Controller_AbstractController implements TX_EXTMVC_Controller_ControllerInterface {

	/**
	 * @var string Key of the extension this controller belongs to
	 */
	protected $extensionKey;

	/**
	 * Contains the settings of the current extension
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * @var TX_EXTMVC_Request The current request
	 */
	protected $request;

	/**
	 * @var TX_EXTMVC_Response The response which will be returned by this action controller
	 */
	protected $response;

	/**
	 * @var TX_EXTMVC_Controller_Arguments Arguments passed to the controller
	 */
	protected $arguments;
	
	/**
	 * @var TX_EXTMVC_Controller_Arguments Arguments passed to the controller
	 */
	protected $validator;
	
	/**
	 * @var array Validation errors
	 */
	protected $errors = array();
	
	/**
	 * @var array Validation warnings
	 */
	protected $warnings = array();
	
	/**
	 * @var array An array of allowed arguemnt names (or regular expressions matching those)
	 */
	protected $allowedArguments = array('.*');

	/**
	 * @var array An array of names of the required arguments (or regular expressions matching those)
	 */
	protected $requiredArguments = array();

	/**
	 * Constructs the controller.
	 *
	 * @param F3_FLOW3_Object_FactoryInterface $objectFactory A reference to the Object Factory
	 * @param F3_FLOW3_Package_ManagerInterface $packageManager A reference to the Package Manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->arguments = t3lib_div::makeInstance('TX_EXTMVC_Controller_Arguments');
		list(, $this->extensionKey) = explode('_', strtolower(get_class($this)));
		// debug($this->extensionKey));
		// $this->extension = $packageManager->getPackage($this->extensionKey);
	}

	/**
	 * Injects the settings of the extension.
	 *
	 * @param array $settings Settings container of the current extension
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param TX_EXTMVC_Request $request The request object
	 * @param TX_EXTMVC_Response $response The response, modified by this handler
	 * @return void
	 * @throws TX_EXTMVC_Exception_UnsupportedRequestType if the controller doesn't support the current request type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(TX_EXTMVC_Request $request, TX_EXTMVC_Response $response) {
		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->initializeArguments();
		$this->mapRequestArgumentsToLocalArguments();
	}
	
	/**
	 * Initializes (registers / defines) arguments of this controller.
	 *
	 * Override this method to add arguments which can later be accessed
	 * by the action methods.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeArguments() {
	}

	/**
	 * Forwards the request to another controller.
	 *
	 * @return void
	 * @throws TX_EXTMVC_Exception_StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forward($actionName, $controllerName = NULL, $extensionKey = NULL, TX_EXTMVC_Controller_Arguments $arguments = NULL) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerActionName($actionName);
		if ($controllerName !== NULL) $this->request->setControllerName($controllerName);
		if ($extensionKey !== NULL) $this->request->setControllerExtensionKey($extensionKey);
		if ($arguments !== NULL) $this->request->setArguments($arguments);
		throw new TX_EXTMVC_Exception_StopAction();
	}

	/**
	 * Redirects the web request to another uri.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
	 *
	 * @param mixed $uri Either a string representation of a URI or a F3_FLOW3_Property_DataType_URI object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @throws TX_EXTMVC_Exception_UnsupportedRequestType If the request is not a web request
	 * @throws TX_EXTMVC_Exception_StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function redirect($uri, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof TX_EXTMVC_Web_Request) throw new TX_EXTMVC_Exception_UnsupportedRequestType('redirect() only supports web requests.', 1220539734);

		$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
		$this->response->setContent('<html><head><meta http-equiv="refresh" content="' . intval($delay) . ';url=' . $escapedUri . '"/></head></html>');
		$this->response->setStatus($statusCode);
		$this->response->setHeader('Location', (string)$uri);
		throw new TX_EXTMVC_Exception_StopAction();
	}

	/**
	 * Sends the specified HTTP status immediately.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
	 *
	 * @param integer $statusCode The HTTP status code
	 * @param string $statusMessage A custom HTTP status message
	 * @param string $content Body content which further explains the status
	 * @throws TX_EXTMVC_Exception_UnsupportedRequestType If the request is not a web request
	 * @throws TX_EXTMVC_Exception_StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwStatus($statusCode, $statusMessage = NULL, $content = NULL) {
		if (!$this->request instanceof TX_EXTMVC_Web_Request) throw new TX_EXTMVC_Exception_UnsupportedRequestType('throwStatus() only supports web requests.', 1220539739);

		$this->response->setStatus($statusCode, $statusMessage);
		if ($content === NULL) $content = $this->response->getStatus();
		$this->response->setContent($content);
		throw new TX_EXTMVC_Exception_StopAction();
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function mapRequestArgumentsToLocalArguments() {
		$this->setAllowedArguments(array_merge($this->arguments->getArgumentNames(), $this->arguments->getArgumentShortNames()));
		$this->validator = new TX_EXTMVC_Controller_ArgumentsValidator($this->arguments);
		foreach ($this->request->getArguments() as $requestArgument) {
			if ($this->isAllowedArgument($requestArgument)) {
				$argumentName = $requestArgument->getName();
				$argumentValue = $requestArgument->getValue();
				if (!$this->setArgumentValue($argumentName, $argumentValue)) {					
					if ($this->isRequiredArgument($requestArgument)) {
						$this->errors[] = 'The argument "' . $argumentName . '" could not be set, but was marked as required.';
					} else {
						$this->warnings[] = 'The argument "' . $argumentName . '" could not be set.';
					}
				}
			} else {
				$this->warnings[] = 'The argument "' . $requestArgument->getName() . '" was not allowed not be set.';
			}
		}
		
		if(!$isValid) {
			// TODO Do something with the warnings and error messages
			// debug($this->errors);
			// debug($this->warnings);
		}
	}
	
	/**
	 * Defines the property names which are allowed for mapping.
	 *
	 * @param  array $allowedArguments: An array of allowed argument names.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setAllowedArguments(array $allowedArguments) {
		$this->allowedArguments = $allowedArguments;
	}

	/**
	 * Returns an array of strings defining the allowed properties for mapping.
	 *
	 * @return array The allowed properties
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getAllowedArguments() {
		return $this->allowedArguments;
	}
	
	/**
	 * Checks if the give argument is among the allowed arguments. Each entry in this array may be a regular expression.
	 *
	 * @param TX_EXTMVC_Controller_Argument $argument The Argument to check for
	 * @return boolean TRUE if the argument is allowed, else FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 * @see map()
	 */
	protected function isAllowedArgument(TX_EXTMVC_Controller_Argument $argument) {
		if (current($this->allowedArguments) === '.*') return TRUE;

		$isAllowed = FALSE;
		foreach ($this->allowedArguments as $allowedArgumentName) {
			if (preg_match('/^' . $allowedArgumentName . '$/', $argument->getName()) === 1) {
				$isAllowed = TRUE;
				break;
			}
		}
		return $isAllowed;
	}

	/**
	 * Checks if the given argument is required
	 *
	 * @param TX_EXTMVC_Controller_Argument $argument The argument to check for
	 * @return boolean TRUE if the property is required, else FALSE
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function isRequiredArgument($argument) {
		if ($this->arguments[$argument->getName()] instanceof TX_EXTMVC_Controller_Argument) {
			return (boolean)$this->arguments[$argument->getName()]->isRequired();
		} else {
			return FALSE;
		}
	}

	
	/**
	 * Sets the given value of the specified property at the target object.
	 *
	 * @param string $propertyName Name of the property to set
	 * @param string $propertyValue Value of the property
	 * @return boolean Returns TRUE, if the setting was successfull
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @see map()
	 */
	protected function setArgumentValue($argumentName, $argumentValue) {
		if ($this->validator !== NULL) {
			$validatorErrors = $this->createNewValidationErrorsObject();			
			if (!$this->validator->isValidArgument($argumentName, $argumentValue, $validatorErrors)) {
				foreach ($validatorErrors as $error) {
					$this->errors[] = $error;
				}
				return FALSE;
			}
		}
		$argument = $this->arguments[$argumentName];
		$argument->setValue($argumentValue);
		return TRUE;
	}
	
	/**
	 * This is a factory method to get a clean validation errors object
	 *
	 * @return TX_EXTMVC_Validation_Errors An empty errors object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewValidationErrorsObject() {
		return t3lib_div::makeInstance('TX_EXTMVC_Validation_Errors');
	}


		
}

?>