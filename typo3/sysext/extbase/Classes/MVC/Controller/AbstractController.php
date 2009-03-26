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
 * An abstract base class for Controllers
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
abstract class Tx_ExtBase_MVC_Controller_AbstractController implements Tx_ExtBase_MVC_Controller_ControllerInterface {

	/**
	 * @var string Key of the extension this controller belongs to
	 */
	protected $extensionName;

	/**
	 * Contains the settings of the current extension
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * @var Tx_ExtBase_MVC_Request The current request
	 */
	protected $request;

	/**
	 * @var Tx_ExtBase_MVC_Response The response which will be returned by this action controller
	 */
	protected $response;

	/**
	 * @var Tx_ExtBase_MVC_Controller_Arguments Arguments passed to the controller
	 */
	protected $arguments;

	/**
	 * Actions that schould not be cached (changes the invocated dispatcher to a USER_INT cObject)
	 * @var array
	 */
	protected $nonCachableActions = array();

	/**
	 * Constructs the controller.
	 *
	 * @param F3_FLOW3_Object_FactoryInterface $objectFactory A reference to the Object Factory
	 * @param F3_FLOW3_Package_ManagerInterface $packageManager A reference to the Package Manager
	 */
	public function __construct() {
		// SK: Set $this->extensionName, could be done the same way as it is done in Fluid
		$this->arguments = t3lib_div::makeInstance('Tx_ExtBase_MVC_Controller_Arguments');
	}

	/**
	 * Injects the settings of the extension.
	 *
	 * @param array $settings Settings container of the current extension
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param Tx_ExtBase_MVC_Request $request The request object
	 * @param Tx_ExtBase_MVC_Response $response The response, modified by this handler
	 * @return void
	 * @throws Tx_ExtBase_Exception_UnsupportedRequestType if the controller doesn't support the current request type
	 */
	public function processRequest(Tx_ExtBase_MVC_Request $request, Tx_ExtBase_MVC_Response $response) {
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
	 */
	protected function initializeArguments() {
	}

	/**
	 * Forwards the request to another controller.
	 *
	 * @return void
	 * @throws Tx_ExtBase_Exception_StopAction
	 */
	public function forward($actionName, $controllerName = NULL, $extensionName = NULL, Tx_ExtBase_MVC_Controller_Arguments $arguments = NULL) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerActionName($actionName);
		if ($controllerName !== NULL) $this->request->setControllerName($controllerName);
		if ($extensionName !== NULL) $this->request->setExtensionName($extensionName);
		if ($arguments !== NULL) $this->request->setArguments($arguments);
		throw new Tx_ExtBase_Exception_StopAction();
	}

	/**
	 * Redirects the web request to another uri.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
	 *
	 * @param mixed $uri Either a string representation of a URI or a F3_FLOW3_Property_DataType_URI object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @throws Tx_ExtBase_Exception_UnsupportedRequestType If the request is not a web request
	 * @throws Tx_ExtBase_Exception_StopAction
	 */
	public function redirect($uri, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof Tx_ExtBase_MVC_Web_Request) throw new Tx_ExtBase_Exception_UnsupportedRequestType('redirect() only supports web requests.', 1220539734);

		$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
		$this->response->setContent('<html><head><meta http-equiv="refresh" content="' . intval($delay) . ';url=' . $escapedUri . '"/></head></html>');
		$this->response->setStatus($statusCode);
		throw new Tx_ExtBase_Exception_StopAction();
	}

	/**
	 * Sends the specified HTTP status immediately.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
	 *
	 * @param integer $statusCode The HTTP status code
	 * @param string $statusMessage A custom HTTP status message
	 * @param string $content Body content which further explains the status
	 * @throws Tx_ExtBase_Exception_UnsupportedRequestType If the request is not a web request
	 * @throws Tx_ExtBase_Exception_StopAction
	 */
	public function throwStatus($statusCode, $statusMessage = NULL, $content = NULL) {
		if (!$this->request instanceof Tx_ExtBase_MVC_Web_Request) throw new Tx_ExtBase_Exception_UnsupportedRequestType('throwStatus() only supports web requests.', 1220539739);

		$this->response->setStatus($statusCode, $statusMessage);
		if ($content === NULL) $content = $this->response->getStatus();
		$this->response->setContent($content);
		throw new Tx_ExtBase_Exception_StopAction();
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 */
	protected function mapRequestArgumentsToLocalArguments() {
		$requestArguments = $this->request->getArguments();
		foreach ($this->arguments as $argument) {
			$argumentName = $argument->getName();
			$argumentShortName = $argument->getShortName();
			if (array_key_exists($argumentName, $requestArguments)) {
				$argument->setValue($requestArguments[$argumentName]);
			} elseif ($argumentShortName !== NULL && array_key_exists($argumentShortName, $requestArguments)) {
				$argument->setValue($requestArguments[$argumentShortName]);
			}
		}
	}

}

?>