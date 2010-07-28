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
 * A multi action controller. This is by far the most common base class for Controllers.
 *
 * @package Extbase
 * @subpackage MVC\Controller
 * @version $ID:$
 * @api
 */
class Tx_Extbase_MVC_Controller_ActionController extends Tx_Extbase_MVC_Controller_AbstractController {

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * By default a Fluid TemplateView is provided, if a template is available,
	 * then a view with the same name as the current action will be looked up.
	 * If none is available the $defaultViewObjectName will be used and finally
	 * an EmptyView will be created.
	 * @var Tx_Extbase_MVC_View_ViewInterface
	 * @api
	 */
	protected $view = NULL;

	/**
	 * Pattern after which the view object name is built if no Fluid template
	 * is found.
	 * @var string
	 * @api
	 */
	protected $viewObjectNamePattern = 'Tx_@extension_View_@controller_@action@format';

	/**
	 * The default view object to use if neither a Fluid template nor an action
	 * specific view object could be found.
	 * @var string
	 * @api
	 */
	protected $defaultViewObjectName = NULL;

	/**
	 * Name of the action method
	 * @var string
	 * @api
	 */
	protected $actionMethodName = 'indexAction';

	/**
	 * Name of the special error action method which is called in case of errors
	 * @var string
	 * @api
	 */
	protected $errorMethodName = 'errorAction';

	/**
	 * Injects the reflection service
	 *
	 * @param Tx_Extbase_Reflection_Service $reflectionService
	 * @return void
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

		$this->uriBuilder = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Routing_UriBuilder');
		$this->uriBuilder->setRequest($request);

		$this->actionMethodName = $this->resolveActionMethodName();

		$this->initializeActionMethodArguments();
		$this->initializeActionMethodValidators();

		$this->initializeAction();
		$actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
		if (method_exists($this, $actionInitializationMethodName)) {
			call_user_func(array($this, $actionInitializationMethodName));
		}

		$this->mapRequestArgumentsToControllerArguments();
		$this->checkRequestHash();
		$this->view = $this->resolveView();
		if ($this->view !== NULL) $this->initializeView($this->view);
		$this->callActionMethod();
	}

	/**
	 * Implementation of the arguments initilization in the action controller:
	 * Automatically registers arguments of the current action
	 *
	 * Don't override this method - use initializeAction() instead.
	 *
	 * @return void
	 * @see initializeArguments()
	 */
	protected function initializeActionMethodArguments() {
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->actionMethodName);

		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) throw new Tx_Extbase_MVC_Exception_InvalidArgumentType('The argument type for parameter "' . $parameterName . '" could not be detected.', 1253175643);

			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);

			$this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}
	}

	/**
	 * Adds the needed valiators to the Arguments:
	 * - Validators checking the data type from the @param annotation
	 * - Custom validators specified with @validate.
	 *
	 * In case @dontvalidate is NOT set for an argument, the following two
	 * validators are also added:
	 * - Model-based validators (@validate annotations in the model)
	 * - Custom model validator classes
	 *
	 * @return void
	 */
	protected function initializeActionMethodValidators() {
		$parameterValidators = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($this), $this->actionMethodName);

		$dontValidateAnnotations = array();
		$methodTagsValues = $this->reflectionService->getMethodTagsValues(get_class($this), $this->actionMethodName);
		if (isset($methodTagsValues['dontvalidate'])) {
			$dontValidateAnnotations = $methodTagsValues['dontvalidate'];
		}

		foreach ($this->arguments as $argument) {
			$validator = $parameterValidators[$argument->getName()];

			if (array_search('$' . $argument->getName(), $dontValidateAnnotations) === FALSE) {
				$baseValidatorConjunction = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
				if ($baseValidatorConjunction !== NULL) {
					$validator->addValidator($baseValidatorConjunction);
				}
			}
			$argument->setValidator($validator);
		}
	}

	/**
	 * Resolves and checks the current action method name
	 *
	 * @return string Method name of the current action
	 * @throws Tx_Extbase_MVC_Exception_NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function resolveActionMethodName() {
		$actionMethodName = $this->request->getControllerActionName() . 'Action';
		if (!method_exists($this, $actionMethodName)) throw new Tx_Extbase_MVC_Exception_NoSuchAction('An action "' . $actionMethodName . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
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
	 * @api
	 */
	protected function callActionMethod() {
		$argumentsAreValid = TRUE;
		$preparedArguments = array();
		foreach ($this->arguments as $argument) {
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
	 * Prepares a view for the current action and stores it in $this->view.
	 * By default, this method tries to locate a view with a name matching
	 * the current action.
	 *
	 * @return void
	 * @api
	 */
	protected function resolveView() {
		$view = $this->objectManager->getObject('Tx_Fluid_View_TemplateView');
		$controllerContext = $this->buildControllerContext();
		$view->setControllerContext($controllerContext);

		// Template Path Override
		$extbaseFrameworkConfiguration = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
		if (isset($extbaseFrameworkConfiguration['view']['templateRootPath']) && strlen($extbaseFrameworkConfiguration['view']['templateRootPath']) > 0) {
			$view->setTemplateRootPath(t3lib_div::getFileAbsFileName($extbaseFrameworkConfiguration['view']['templateRootPath']));
		}
		if (isset($extbaseFrameworkConfiguration['view']['layoutRootPath']) && strlen($extbaseFrameworkConfiguration['view']['layoutRootPath']) > 0) {
			$view->setLayoutRootPath(t3lib_div::getFileAbsFileName($extbaseFrameworkConfiguration['view']['layoutRootPath']));
		}
		if (isset($extbaseFrameworkConfiguration['view']['partialRootPath']) && strlen($extbaseFrameworkConfiguration['view']['partialRootPath']) > 0) {
			$view->setPartialRootPath(t3lib_div::getFileAbsFileName($extbaseFrameworkConfiguration['view']['partialRootPath']));
		}

		if ($view->hasTemplate() === FALSE) {
			$viewObjectName = $this->resolveViewObjectName();
			if (class_exists($viewObjectName) === FALSE) $viewObjectName = 'Tx_Extbase_MVC_View_EmptyView';
			$view = $this->objectManager->getObject($viewObjectName);
			$view->setControllerContext($controllerContext);
		}
		if (method_exists($view, 'injectSettings')) {
			$view->injectSettings($this->settings);
		}
		$view->initializeView(); // In FLOW3, solved through Object Lifecycle methods, we need to call it explicitely
		$view->assign('settings', $this->settings); // same with settings injection.
		return $view;
	}

	/**
	 * Determines the fully qualified view object name.
	 *
	 * @return mixed The fully qualified view object name or FALSE if no matching view could be found.
	 * @api
	 */
	protected function resolveViewObjectName() {
		$possibleViewName = $this->viewObjectNamePattern;
		$extensionName = $this->request->getControllerExtensionName();
		$possibleViewName = str_replace('@extension', $extensionName, $possibleViewName);
		$possibleViewName = str_replace('@controller', $this->request->getControllerName(), $possibleViewName);
		$possibleViewName = str_replace('@action', ucfirst($this->request->getControllerActionName()), $possibleViewName);

		$viewObjectName = str_replace('@format', ucfirst($this->request->getFormat()), $possibleViewName);
		if (class_exists($viewObjectName) === FALSE) {
			$viewObjectName = str_replace('@format', '', $possibleViewName);
		}
		if (class_exists($viewObjectName) === FALSE && $this->defaultViewObjectName !== NULL) {
			$viewObjectName = $this->defaultViewObjectName;
		}
		return $viewObjectName;
	}

	/**
	 * Initializes the view before invoking an action method.
	 *
	 * Override this method to solve assign variables common for all actions
	 * or prepare the view in another way before the action is called.
	 *
	 * @param Tx_Extbase_View_ViewInterface $view The view to be initialized
	 * @return void
	 * @api
	 */
	protected function initializeView(Tx_Extbase_MVC_View_ViewInterface $view) {
	}

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 * @api
	 */
	protected function initializeAction() {
	}

	/**
	 * A special action which is called if the originally intended action could
	 * not be called, for example if the arguments were not valid.
	 *
	 * The default implementation sets a flash message, request errors and forwards back
	 * to the originating action. This is suitable for most actions dealing with form input.
	 *
	 * We clear the page cache by default on an error as well, as we need to make sure the
	 * data is re-evaluated when the user changes something.
	 *
	 * @return string
	 * @api
	 */
	protected function errorAction() {
		$this->request->setErrors($this->argumentsMappingResults->getErrors());
		$this->clearCacheOnError();

		$errorFlashMessage = $this->getErrorFlashMessage();
		if ($errorFlashMessage !== FALSE) {
			$this->flashMessages->add($errorFlashMessage);
		}

		if ($this->request->hasArgument('__referrer')) {
			$referrer = $this->request->getArgument('__referrer');
			$this->forward($referrer['actionName'], $referrer['controllerName'], $referrer['extensionName'], $this->request->getArguments());
		}

		$message = 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
		foreach ($this->argumentsMappingResults->getErrors() as $error) {
			$message .= 'Error:   ' . $error->getMessage() . PHP_EOL;
		}
		foreach ($this->argumentsMappingResults->getWarnings() as $warning) {
			$message .= 'Warning: ' . $warning->getMessage() . PHP_EOL;
		}
		return $message;
	}

	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * @return string|boolean The flash message or FALSE if no flash message should be set
	 * @api
	 */
	protected function getErrorFlashMessage() {
		return 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '()';
	}

	/**
	 * Checks the request hash (HMAC), if arguments have been touched by the property mapper.
	 *
	 * In case the @dontverifyrequesthash-Annotation has been set, this suppresses the exception.
	 *
	 * @return void
	 * @throws Tx_Extbase_MVC_Exception_InvalidOrNoRequestHash In case request hash checking failed
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function checkRequestHash() {
		if (!($this->request instanceof Tx_Extbase_MVC_Web_Request)) return; // We only want to check it for now for web requests.
		if ($this->request->isHmacVerified()) return; // all good

		$verificationNeeded = FALSE;
		foreach ($this->arguments as $argument) {
			if ($argument->getOrigin() == Tx_Extbase_MVC_Controller_Argument::ORIGIN_NEWLY_CREATED
			 || $argument->getOrigin() == Tx_Extbase_MVC_Controller_Argument::ORIGIN_PERSISTENCE_AND_MODIFIED) {
				$verificationNeeded = TRUE;
			}
		}
		if ($verificationNeeded) {
			$methodTagsValues = $this->reflectionService->getMethodTagsValues(get_class($this), $this->actionMethodName);
			if (!isset($methodTagsValues['dontverifyrequesthash'])) {
				throw new Tx_Extbase_MVC_Exception_InvalidOrNoRequestHash('Request hash (HMAC) checking failed. The parameter __hmac was invalid or not set, and objects were modified.', 1255082824);
			}
		}
	}

	/**
	 * Clear cache of current page on error. Needed because we want a re-evaluation of the data.
	 * Better would be just do delete the cache for the error action, but that is not possible right now.
	 *
	 * @return void
	 */
	protected function clearCacheOnError() {
		$extbaseSettings = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
		if (isset($extbaseSettings['persistence']['enableAutomaticCacheClearing']) && $extbaseSettings['persistence']['enableAutomaticCacheClearing'] === '1') {
			if (isset($GLOBALS['TSFE'])) {
				$pageUid = $GLOBALS['TSFE']->id;
				Tx_Extbase_Utility_Cache::clearPageCache(array($pageUid));
			}
		}
	}

}
?>