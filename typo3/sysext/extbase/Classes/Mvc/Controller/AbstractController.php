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
 * An abstract base class for Controllers
 *
 * @api
 */
abstract class AbstractController implements \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var string Key of the extension this controller belongs to
	 */
	protected $extensionName;

	/**
	 * Contains the settings of the current extension
	 *
	 * @var array
	 * @api
	 */
	protected $settings;

	/**
	 * The current request.
	 *
	 * @var \TYPO3\CMS\Extbase\Mvc\Request
	 * @api
	 */
	protected $request;

	/**
	 * The response which will be returned by this action controller
	 *
	 * @var \TYPO3\CMS\Extbase\Mvc\Response
	 * @api
	 */
	protected $response;

	/**
	 * @var \TYPO3\CMS\Extbase\Property\Mapper
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	protected $deprecatedPropertyMapper;

	/**
	 * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\Arguments Arguments passed to the controller
	 */
	protected $arguments;

	/**
	 * The results of the mapping of request arguments to controller arguments
	 *
	 * @var \TYPO3\CMS\Extbase\Property\MappingResults
	 * @api
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	protected $argumentsMappingResults;

	/**
	 * An array of supported request types. By default only web requests are supported.
	 * Modify or replace this array if your specific controller supports certain
	 * (additional) request types.
	 *
	 * @var array
	 */
	protected $supportedRequestTypes = array('TYPO3\\CMS\\Extbase\\Mvc\\Request');

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 * @api
	 */
	protected $controllerContext;

	/**
	 * @return ControllerContext
	 * @api
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer
	 * @api
	 */
	protected $flashMessageContainer;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * Constructs the controller.
	 */
	public function __construct() {
		$className = get_class($this);
		if (strpos($className, '\\') !== FALSE) {
			$classNameParts = explode('\\', $className, 4);
			// Skip vendor and product name for core classes
			if (strpos($className, 'TYPO3\\CMS\\') === 0) {
				$this->extensionName = $classNameParts[2];
			} else {
				$this->extensionName = $classNameParts[1];
			}
		} else {
			list(, $this->extensionName) = explode('_', $className);
		}
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->settings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
	}

	/**
	 * Injects the property mapper
	 *
	 * @param \TYPO3\CMS\Extbase\Property\Mapper $deprecatedPropertyMapper The property mapper
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function injectDeprecatedPropertyMapper(\TYPO3\CMS\Extbase\Property\Mapper $deprecatedPropertyMapper) {
		$this->deprecatedPropertyMapper = $deprecatedPropertyMapper;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->arguments = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments');
	}

	/**
	 * Injects the validator resolver
	 *
	 * @param \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver
	 * @return void
	 */
	public function injectValidatorResolver(\TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * Injects the flash messages container
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer $flashMessageContainer
	 * @return void
	 */
	public function injectFlashMessageContainer(\TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer $flashMessageContainer) {
		$this->flashMessageContainer = $flashMessageContainer;
	}

	/**
	 * Injects the signal slot dispatcher
	 *
	 * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
	 */
	public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
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
	 * @api
	 */
	public function canProcessRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request) {
		foreach ($this->supportedRequestTypes as $supportedRequestType) {
			if ($request instanceof $supportedRequestType) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @api
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		if (!$this->canProcessRequest($request)) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes), 1187701131);
		}
		$response->setRequest($request);
		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;
		$this->uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		$this->uriBuilder->setRequest($request);
		$this->initializeControllerArgumentsBaseValidators();
		$this->mapRequestArgumentsToControllerArguments();
		$this->controllerContext = $this->buildControllerContext();
	}

	/**
	 * Initialize the controller context
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext ControllerContext to be passed to the view
	 * @api
	 */
	protected function buildControllerContext() {
		/** @var $controllerContext \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext */
		$controllerContext = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext');
		$controllerContext->setRequest($this->request);
		$controllerContext->setResponse($this->response);
		if ($this->arguments !== NULL) {
			$controllerContext->setArguments($this->arguments);
		}
		if ($this->argumentsMappingResults !== NULL) {
			$controllerContext->setArgumentsMappingResults($this->argumentsMappingResults);
		}
		$controllerContext->setUriBuilder($this->uriBuilder);

		$controllerContext->setFlashMessageContainer($this->flashMessageContainer);
		return $controllerContext;
	}

	/**
	 * Forwards the request to another action and / or controller.
	 *
	 * Request is directly transfered to the other action / controller
	 * without the need for a new request.
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $extensionName Name of the extension containing the controller to forward to. If not specified, the current extension is assumed.
	 * @param array $arguments Arguments to pass to the target action
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @see redirect()
	 * @api
	 */
	public function forward($actionName, $controllerName = NULL, $extensionName = NULL, array $arguments = NULL) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerActionName($actionName);
		if ($controllerName !== NULL) {
			$this->request->setControllerName($controllerName);
		}
		if ($extensionName !== NULL) {
			$this->request->setControllerExtensionName($extensionName);
		}
		if ($arguments !== NULL) {
			$this->request->setArguments($arguments);
		}
		throw new \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException();
	}

	/**
	 * Redirects the request to another action and / or controller.
	 *
	 * Redirect will be sent to the client which then performs another request to the new URI.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception
	 * if used with other request types.
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $extensionName Name of the extension containing the controller to forward to. If not specified, the current extension is assumed.
	 * @param array $arguments Arguments to pass to the target action
	 * @param integer $pageUid Target page uid. If NULL, the current page uid is used
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException If the request is not a web request
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @see forward()
	 * @api
	 */
	protected function redirect($actionName, $controllerName = NULL, $extensionName = NULL, array $arguments = NULL, $pageUid = NULL, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof \TYPO3\CMS\Extbase\Mvc\Web\Request) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException('redirect() only supports web requests.', 1220539734);
		}
		if ($controllerName === NULL) {
			$controllerName = $this->request->getControllerName();
		}
		$this->uriBuilder->reset()->setTargetPageUid($pageUid)->setCreateAbsoluteUri(TRUE);
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
			$this->uriBuilder->setAbsoluteUriScheme('https');
		}
		$uri = $this->uriBuilder->uriFor($actionName, $arguments, $controllerName, $extensionName);
		$this->redirectToUri($uri, $delay, $statusCode);
	}

	/**
	 * Redirects the web request to another uri.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
	 *
	 * @param mixed $uri A string representation of a URI
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException If the request is not a web request
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @api
	 */
	protected function redirectToUri($uri, $delay = 0, $statusCode = 303) {
		if (!$this->request instanceof \TYPO3\CMS\Extbase\Mvc\Web\Request) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException('redirect() only supports web requests.', 1220539734);
		}

		$this->objectManager->get('TYPO3\CMS\Extbase\Service\CacheService')->clearCachesOfRegisteredPageIds();

		$uri = $this->addBaseUriIfNecessary($uri);
		$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
		$this->response->setContent('<html><head><meta http-equiv="refresh" content="' . intval($delay) . ';url=' . $escapedUri . '"/></head></html>');
		$this->response->setStatus($statusCode);
		$this->response->setHeader('Location', (string) $uri);
		throw new \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException();
	}

	/**
	 * Adds the base uri if not already in place.
	 *
	 * @param string $uri The URI
	 * @return string
	 */
	protected function addBaseUriIfNecessary($uri) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl((string) $uri);
	}

	/**
	 * Sends the specified HTTP status immediately.
	 *
	 * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
	 *
	 * @param integer $statusCode The HTTP status code
	 * @param string $statusMessage A custom HTTP status message
	 * @param string $content Body content which further explains the status
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException If the request is not a web request
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @api
	 */
	public function throwStatus($statusCode, $statusMessage = NULL, $content = NULL) {
		if (!$this->request instanceof \TYPO3\CMS\Extbase\Mvc\Web\Request) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException('throwStatus() only supports web requests.', 1220539739);
		}
		$this->response->setStatus($statusCode, $statusMessage);
		if ($content === NULL) {
			$content = $this->response->getStatus();
		}
		$this->response->setContent($content);
		throw new \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException();
	}

	/**
	 * Collects the base validators which were defined for the data type of each
	 * controller argument and adds them to the argument's validator chain.
	 *
	 * @return void
	 */
	public function initializeControllerArgumentsBaseValidators() {
		foreach ($this->arguments as $argument) {
			$validator = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
			if ($validator !== NULL) {
				$argument->setValidator($validator);
			}
		}
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @throws Exception\RequiredArgumentMissingException
	 * @return void
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		if ($this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			foreach ($this->arguments as $argument) {
				$argumentName = $argument->getName();
				if ($this->request->hasArgument($argumentName)) {
					$argument->setValue($this->request->getArgument($argumentName));
				} elseif ($argument->isRequired()) {
					throw new \TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException('Required argument "' . $argumentName . '" is not set.', 1298012500);
				}
			}
		} else {
			// @deprecated since Extbase 1.4, will be removed two versions after Extbase 6.1
			$optionalPropertyNames = array();
			$allPropertyNames = $this->arguments->getArgumentNames();
			foreach ($allPropertyNames as $propertyName) {
				if ($this->arguments[$propertyName]->isRequired() === FALSE) {
					$optionalPropertyNames[] = $propertyName;
				}
			}
			/** @var $validator ArgumentsValidator */
			$validator = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ArgumentsValidator');
			$this->deprecatedPropertyMapper->mapAndValidate($allPropertyNames, $this->request->getArguments(), $this->arguments, $optionalPropertyNames, $validator);
			$this->argumentsMappingResults = $this->deprecatedPropertyMapper->getMappingResults();
		}
	}
}

?>