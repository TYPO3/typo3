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
 * A multi action controller
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_Extbase_MVC_Controller_ActionController extends Tx_Extbase_MVC_Controller_AbstractController {

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var boolean If initializeView() should be called on an action invocation.
	 */
	protected $initializeView = TRUE;

	/**
	 * By default a view with the same name as the current action is provided. Contains NULL if none was found.
	 * @var Tx_Extbase_MVC_View_AbstractView
	 */
	protected $view = NULL;

	/**
	 * By default $this->viewObjectNamePattern is used to find a matching view object.
	 * If no custom view class can be found, $this->defaultViewObjectName will be used.
	 * @var string
	 */
	protected $standardViewObjectName = 'Tx_Fluid_View_TemplateView';

	/**
	 * Pattern after which the view object name is built
	 *
	 * @var string
	 */
	// SK: Decision: Do we support "format"?
	protected $viewObjectNamePattern = 'Tx_@extension_View_@controller_@action';

	/**
	 * Name of the action method
	 * @var string
	 */
	protected $actionMethodName = 'indexAction';
	
	/**
	 * Name of the special error action method which is called in case of errors
	 * @var string
	 */
	protected $errorMethodName = 'errorAction';

	/**
	 * Injects the reflection service
	 *
	 * @param Tx_Extbase_Reflection_Service $reflectionService
	 * @return void
	 * @internal
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}
	
	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * If your controller only supports certain request types, either
	 * replace / modify the supporteRequestTypes property or override this
	 * method.
	 *
	 * @param Tx_Extbase_MVC_Request $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 */
	public function canProcessRequest(Tx_Extbase_MVC_Request $request) {
		return parent::canProcessRequest($request);
		
	}

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param Tx_Extbase_MVC_Request $request The request object
	 * @param Tx_Extbase_MVC_Response $response The response, modified by this handler
	 * @return void
	 */
	public function processRequest(Tx_Extbase_MVC_Request $request, Tx_Extbase_MVC_Response $response) {
		if (!$this->canProcessRequest($request)) throw new Tx_Extbase_MVC_Exception_UnsupportedRequestType(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes) , 1187701131);
		
		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->actionMethodName = $this->resolveActionMethodName();
		if ($this->initializeView) $this->initializeView();
		
		$this->initializeActionMethodArguments();
		$this->initializeControllerArgumentsBaseValidators();
		$this->initializeActionMethodValidators();

		$this->initializeAction();
		$actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
		if (method_exists($this, $actionInitializationMethodName)) {
			call_user_func(array($this, $actionInitializationMethodName));
		}

		$this->mapRequestArgumentsToControllerArguments();
		$this->callActionMethod();
	}
	
	/**
	 * Implementation of the arguments initilization in the action controller:
	 * Automatically registers arguments of the current action
	 *
	 * Don't override this method - use initializeArguments() instead.
	 *
	 * @return void
	 * @see initializeArguments()
	 * @internal
	 */
	protected function initializeActionMethodArguments() {
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->actionMethodName);
		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = 'Text';
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);

			$this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}
	}

	/**
	 * Detects and registers any additional validators for arguments which were
	 * specified in the @validate annotations of an action method
	 *
	 * @return void
	 * @internal
	 */
	protected function initializeActionMethodValidators() {
		$validatorChains = $this->validatorResolver->buildMethodArgumentsValidatorChains(get_class($this), $this->actionMethodName);
		foreach ($validatorChains as $argumentName => $validatorChain) {
			if (!isset($this->arguments[$argumentName])) throw new Tx_Extbase_MVC_Exception_NoSuchArgument('Found custom validation rule for non existing argument "' . $argumentName . '" in ' . get_class($this) . '->' . $this->actionMethodName . '().', 1239853108);
			$this->arguments[$argumentName]->setValidator($validatorChain);
		}
	}

	/**
	 * Determines the action method and assures that the method exists.
	 *
	 * @return string The action method name
	 * @throws Tx_Extbase_Exception_NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function resolveActionMethodName() {
		$actionMethodName = $this->request->getControllerActionName() . 'Action';
		if (!method_exists($this, $actionMethodName)) throw new Tx_Extbase_Exception_NoSuchAction('An action "' . $actionMethodName . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
		return $actionMethodName;
	}

	/**
	 * Calls the specified action method and passes the arguments.
	 *
	 * If the action returns a string, it is appended to the content in the
	 * response object. If the action doesn't return anything and a valid
	 * view exists, the view is rendered automatically.
	 *
	 * @param string $actionMethodName Name of the action method to call
	 * @return void
	 * @internal
	 */
	protected function callActionMethod() {
		$argumentsAreValid = TRUE;
		$preparedArguments = array();
		foreach ($this->arguments as $argument) {
			$this->preProcessArgument($argument);
			$preparedArguments[] = $argument->getValue();
		}

		if ($this->argumentsMappingResults->hasErrors()) {
			$actionResult = call_user_func(array($this, $this->errorMethodName));
		} else {
			$actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
		}
		if ($actionResult === NULL && $this->view instanceof Tx_Extbase_MVC_View_ViewInterface) {
			$this->response->appendContent($this->view->render());
		} elseif (is_string($actionResult) && strlen($actionResult) > 0) {
			$this->response->appendContent($actionResult);
		}
	}
	
	/**
	 * This is a template method to process unvalid arguments. Overwrite this method in your concrete controller.
	 *
	 * @param Tx_Extbase_MVC_Controller_Argument $argument The argument
	 * @return void
	 */
	protected function preProcessArgument(Tx_Extbase_MVC_Controller_Argument $argument) {
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 * By default, this method tries to locate a view with a name matching
	 * the current action.
	 *
	 * @return void
	 */
	protected function initializeView() {
		$this->view = t3lib_div::makeInstance($this->resolveViewObjectName());
		$this->view->setRequest($this->request);
	}

	/**
	 * Determines the fully qualified view object name.
	 *
	 * @return string The fully qualified view object name
	 */
	protected function resolveViewObjectName() {
		$viewObjectName = str_replace('@extension', $this->request->getControllerExtensionName(), $this->viewObjectNamePattern);
		$viewObjectName = str_replace('@controller', $this->request->getControllerName(), $viewObjectName);
		$viewObjectName = str_replace('@action', ucfirst($this->request->getControllerActionName()), $viewObjectName);
		if (!class_exists($viewObjectName)) {
			if (class_exists($this->standardViewObjectName)) {
				$viewObjectName = $this->standardViewObjectName;
			} else {
				$viewObjectName = 'Tx_Extbase_View_EmptyView';
			}
		}
		return $viewObjectName;
	}

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 */
	protected function initializeAction() {
	}

	/**
	 * A special action which is called if the originally intended action could
	 * not be called, for example if the arguments were not valid.
	 *
	 * @return string
	 */
	protected function errorAction() {
		$message = 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
		foreach ($this->argumentsMappingResults->getErrors() as $error) {
			$message .= 'Error:   ' . $error . PHP_EOL;
		}
		foreach ($this->argumentsMappingResults->getWarnings() as $warning) {
			$message .= 'Warning: ' . $warning . PHP_EOL;
		}
		return $message;
	}
}
?>