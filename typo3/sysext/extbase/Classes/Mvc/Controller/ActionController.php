<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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
 * A multi action controller. This is by far the most common base class for Controllers.
 *
 * @api
 */
class ActionController extends \TYPO3\CMS\Extbase\Mvc\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\CacheService
	 */
	protected $cacheService;

	/**
	 * The current view, as resolved by resolveView()
	 *
	 * @var \TYPO3\CMS\Extbase\Mvc\View\ViewInterface
	 * @api
	 */
	protected $view = NULL;

	/**
	 * Pattern after which the view object name is built if no Fluid template
	 * is found.
	 *
	 * @var string
	 * @api
	 */
	protected $viewObjectNamePattern = 'Tx_@extension_View_@controller_@action@format';

	/**
	 * A list of formats and object names of the views which should render them.
	 *
	 * Example:
	 *
	 * array('html' => 'Tx_MyExtension_View_MyHtmlView', 'json' => 'F3...
	 *
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array();

	/**
	 * The default view object to use if none of the resolved views can render
	 * a response for the current request.
	 *
	 * @var string
	 * @api
	 */
	protected $defaultViewObjectName = 'TYPO3\\CMS\\Fluid\\View\\TemplateView';

	/**
	 * Name of the action method
	 *
	 * @var string
	 * @api
	 */
	protected $actionMethodName = 'indexAction';

	/**
	 * Name of the special error action method which is called in case of errors
	 *
	 * @var string
	 * @api
	 */
	protected $errorMethodName = 'errorAction';

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService
	 * @api
	 */
	protected $mvcPropertyMappingConfigurationService;

	/**
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\CacheService $cacheService
	 * @return void
	 */
	public function injectCacheService(\TYPO3\CMS\Extbase\Service\CacheService $cacheService) {
		$this->cacheService = $cacheService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService
	 */
	public function injectMvcPropertyMappingConfigurationService(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService) {
		$this->mvcPropertyMappingConfigurationService = $mvcPropertyMappingConfigurationService;
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * If your controller only supports certain request types, either
	 * replace / modify the supporteRequestTypes property or override this
	 * method.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 */
	public function canProcessRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request) {
		return parent::canProcessRequest($request);
	}

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this handler
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
	 * @return void
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		if (!$this->canProcessRequest($request)) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes), 1187701131);
		}
		if ($response instanceof \TYPO3\CMS\Extbase\Mvc\Web\Response) {
			$response->setRequest($request);
		}
		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;
		$this->uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		$this->uriBuilder->setRequest($request);
		$this->actionMethodName = $this->resolveActionMethodName();
		$this->initializeActionMethodArguments();
		$this->initializeActionMethodValidators();
		$this->mvcPropertyMappingConfigurationService->initializePropertyMappingConfigurationFromRequest($request, $this->arguments);
		$this->initializeAction();
		$actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
		if (method_exists($this, $actionInitializationMethodName)) {
			call_user_func(array($this, $actionInitializationMethodName));
		}
		$this->mapRequestArgumentsToControllerArguments();
		$this->checkRequestHash();
		$this->controllerContext = $this->buildControllerContext();
		$this->view = $this->resolveView();
		if ($this->view !== NULL) {
			$this->initializeView($this->view);
		}
		$this->callActionMethod();
	}

	/**
	 * Implementation of the arguments initilization in the action controller:
	 * Automatically registers arguments of the current action
	 *
	 * Don't override this method - use initializeAction() instead.
	 *
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
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
			if ($dataType === NULL) {
				throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException('The argument type for parameter $' . $parameterName . ' of method ' . get_class($this) . '->' . $this->actionMethodName . '() could not be detected.', 1253175643);
			}
			$defaultValue = isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL;
			$this->arguments->addNewArgument($parameterName, $dataType, $parameterInfo['optional'] === FALSE, $defaultValue);
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
		// TODO: still needs to be modified
		$parameterValidators = $this->validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($this), $this->actionMethodName);
		$dontValidateAnnotations = array();
		if (!$this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			// If the rewritten property mapper is *enabled*, we do not support @dontvalidate annotation, thus $dontValidateAnnotations stays empty.
			$methodTagsValues = $this->reflectionService->getMethodTagsValues(get_class($this), $this->actionMethodName);
			if (isset($methodTagsValues['dontvalidate'])) {
				$dontValidateAnnotations = $methodTagsValues['dontvalidate'];
			}
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
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function resolveActionMethodName() {
		$actionMethodName = $this->request->getControllerActionName() . 'Action';
		if (!method_exists($this, $actionMethodName)) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException('An action "' . $actionMethodName . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
		}
		return $actionMethodName;
	}

	/**
	 * Calls the specified action method and passes the arguments.
	 *
	 * If the action returns a string, it is appended to the content in the
	 * response object. If the action doesn't return anything and a valid
	 * view exists, the view is rendered automatically.
	 *
	 * @return void
	 * @api
	 */
	protected function callActionMethod() {
		if ($this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			// enabled since Extbase 1.4.0.
			$preparedArguments = array();
			foreach ($this->arguments as $argument) {
				$preparedArguments[] = $argument->getValue();
			}
			$validationResult = $this->arguments->getValidationResults();
			if (!$validationResult->hasErrors()) {
				$actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
			} else {
				$methodTagsValues = $this->reflectionService->getMethodTagsValues(get_class($this), $this->actionMethodName);
				$ignoreValidationAnnotations = array();
				if (isset($methodTagsValues['ignorevalidation'])) {
					$ignoreValidationAnnotations = $methodTagsValues['ignorevalidation'];
				}
				// if there exists more errors than in ignoreValidationAnnotations_=> call error method
				// else => call action method
				$shouldCallActionMethod = TRUE;
				foreach ($validationResult->getSubResults() as $argumentName => $subValidationResult) {
					if (!$subValidationResult->hasErrors()) {
						continue;
					}
					if (array_search('$' . $argumentName, $ignoreValidationAnnotations) !== FALSE) {
						continue;
					}
					$shouldCallActionMethod = FALSE;
				}
				if ($shouldCallActionMethod) {
					$this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeCallActionMethod', array('controllerName' => get_class($this), 'actionMethodName' => $this->actionMethodName, 'preparedArguments' => $preparedArguments));
					$actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
				} else {
					$actionResult = call_user_func(array($this, $this->errorMethodName));
				}
			}
		} else {
			// @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
			$preparedArguments = array();
			foreach ($this->arguments as $argument) {
				$preparedArguments[] = $argument->getValue();
			}
			if ($this->argumentsMappingResults->hasErrors()) {
				$actionResult = call_user_func(array($this, $this->errorMethodName));
			} else {
				$this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeCallActionMethod', array('controllerName' => get_class($this), 'actionMethodName' => $this->actionMethodName, 'preparedArguments' => $preparedArguments));
				$actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
			}
		}
		if ($actionResult === NULL && $this->view instanceof \TYPO3\CMS\Extbase\Mvc\View\ViewInterface) {
			$this->response->appendContent($this->view->render());
		} elseif (is_string($actionResult) && strlen($actionResult) > 0) {
			$this->response->appendContent($actionResult);
		} elseif (is_object($actionResult) && method_exists($actionResult, '__toString')) {
			$this->response->appendContent((string) $actionResult);
		}
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 * By default, this method tries to locate a view with a name matching
	 * the current action.
	 *
	 * @return string
	 * @api
	 */
	protected function resolveView() {
		$viewObjectName = $this->resolveViewObjectName();
		if ($viewObjectName !== FALSE) {
			/** @var $view \TYPO3\CMS\Extbase\Mvc\View\ViewInterface */
			$view = $this->objectManager->get($viewObjectName);
			$this->setViewConfiguration($view);
			if ($view->canRender($this->controllerContext) === FALSE) {
				unset($view);
			}
		}
		if (!isset($view) && $this->defaultViewObjectName != '') {
			/** @var $view \TYPO3\CMS\Extbase\Mvc\View\ViewInterface */
			$view = $this->objectManager->get($this->defaultViewObjectName);
			$this->setViewConfiguration($view);
			if ($view->canRender($this->controllerContext) === FALSE) {
				unset($view);
			}
		}
		if (!isset($view)) {
			$view = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\View\\NotFoundView');
			$view->assign('errorMessage', 'No template was found. View could not be resolved for action "' . $this->request->getControllerActionName() . '"');
		}
		$view->setControllerContext($this->controllerContext);
		if (method_exists($view, 'injectSettings')) {
			$view->injectSettings($this->settings);
		}
		$view->initializeView();
		// In FLOW3, solved through Object Lifecycle methods, we need to call it explicitely
		$view->assign('settings', $this->settings);
		// same with settings injection.
		return $view;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
	 * @return void
	 */
	protected function setViewConfiguration(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		// Template Path Override
		$extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (isset($extbaseFrameworkConfiguration['view']['templateRootPath']) && strlen($extbaseFrameworkConfiguration['view']['templateRootPath']) > 0 && method_exists($view, 'setTemplateRootPath')) {
			$view->setTemplateRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($extbaseFrameworkConfiguration['view']['templateRootPath']));
		}
		if (isset($extbaseFrameworkConfiguration['view']['layoutRootPath']) && strlen($extbaseFrameworkConfiguration['view']['layoutRootPath']) > 0 && method_exists($view, 'setLayoutRootPath')) {
			$view->setLayoutRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($extbaseFrameworkConfiguration['view']['layoutRootPath']));
		}
		if (isset($extbaseFrameworkConfiguration['view']['partialRootPath']) && strlen($extbaseFrameworkConfiguration['view']['partialRootPath']) > 0 && method_exists($view, 'setPartialRootPath')) {
			$view->setPartialRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($extbaseFrameworkConfiguration['view']['partialRootPath']));
		}
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
		$format = $this->request->getFormat();
		$viewObjectName = str_replace('@format', ucfirst($this->request->getFormat()), $possibleViewName);
		if (class_exists($viewObjectName) === FALSE) {
			$viewObjectName = str_replace('@format', '', $possibleViewName);
		}
		if (class_exists($viewObjectName) === FALSE && isset($this->viewFormatToObjectNameMap[$format])) {
			$viewObjectName = $this->viewFormatToObjectNameMap[$format];
		}
		return class_exists($viewObjectName) ? $viewObjectName : FALSE;
	}

	/**
	 * Initializes the view before invoking an action method.
	 *
	 * Override this method to solve assign variables common for all actions
	 * or prepare the view in another way before the action is called.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view The view to be initialized
	 * @return void
	 * @api
	 */
	protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
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
		$this->clearCacheOnError();
		if ($this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			$errorFlashMessage = $this->getErrorFlashMessage();
			if ($errorFlashMessage !== FALSE) {
				$this->controllerContext->getFlashMessageQueue()->addMessage(new \TYPO3\CMS\Core\Messaging\FlashMessage($errorFlashMessage, '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR));
			}
			$referringRequest = $this->request->getReferringRequest();
			if ($referringRequest !== NULL) {
				$originalRequest = clone $this->request;
				$this->request->setOriginalRequest($originalRequest);
				$this->request->setOriginalRequestMappingResults($this->arguments->getValidationResults());
				$this->forward($referringRequest->getControllerActionName(), $referringRequest->getControllerName(), $referringRequest->getControllerExtensionName(), $referringRequest->getArguments());
			}
			$message = 'An error occurred while trying to call ' . get_class($this) . '->' . $this->actionMethodName . '().' . PHP_EOL;
			foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyPath => $errors) {
				foreach ($errors as $error) {
					$message .= 'Error for ' . $propertyPath . ':  ' . $error->render() . PHP_EOL;
				}
			}
			return $message;
		} else {
			// @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
			$this->request->setErrors($this->argumentsMappingResults->getErrors());
			$errorFlashMessage = $this->getErrorFlashMessage();
			if ($errorFlashMessage !== FALSE) {
				$this->controllerContext->getFlashMessageQueue()->addMessage(new \TYPO3\CMS\Core\Messaging\FlashMessage($errorFlashMessage, '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR));
			}
			$referrer = $this->request->getInternalArgument('__referrer');
			if ($referrer !== NULL) {
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
	}

	/**
	 * A template method for displaying custom error flash messages, or to
	 * display no flash message at all on errors. Override this to customize
	 * the flash message in your action controller.
	 *
	 * @return string The flash message or FALSE if no flash message should be set
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
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidOrNoRequestHashException In case request hash checking failed
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	protected function checkRequestHash() {
		if ($this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			// If the new property mapper is enabled, the request hash is not needed anymore.
			return;
		}
		if (!$this->request instanceof \TYPO3\CMS\Extbase\Mvc\Web\Request) {
			return;
		}
		// We only want to check it for now for web requests.
		if ($this->request->isHmacVerified()) {
			return;
		}
		// all good
		$verificationNeeded = FALSE;
		foreach ($this->arguments as $argument) {
			if ($argument->getOrigin() == \TYPO3\CMS\Extbase\Mvc\Controller\Argument::ORIGIN_NEWLY_CREATED || $argument->getOrigin() == \TYPO3\CMS\Extbase\Mvc\Controller\Argument::ORIGIN_PERSISTENCE_AND_MODIFIED) {
				$verificationNeeded = TRUE;
			}
		}
		if ($verificationNeeded) {
			$methodTagsValues = $this->reflectionService->getMethodTagsValues(get_class($this), $this->actionMethodName);
			if (!isset($methodTagsValues['dontverifyrequesthash'])) {
				throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidOrNoRequestHashException('Request hash (HMAC) checking failed. The parameter __hmac was invalid or not set, and objects were modified.', 1255082824);
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
		$extbaseSettings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (isset($extbaseSettings['persistence']['enableAutomaticCacheClearing']) && $extbaseSettings['persistence']['enableAutomaticCacheClearing'] === '1') {
			if (isset($GLOBALS['TSFE'])) {
				$pageUid = $GLOBALS['TSFE']->id;
				$this->cacheService->clearPageCache(array($pageUid));
			}
		}
	}
}

?>