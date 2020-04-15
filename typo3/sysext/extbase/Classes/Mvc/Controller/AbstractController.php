<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * An abstract base class for Controllers
 * @deprecated since TYPO3 10.2, will be removed in version 11.0
 */
abstract class AbstractController implements ControllerInterface
{
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'signalSlotDispatcher' => 'Property ' . self::class . '::$signalSlotDispatcher is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'objectManager' => 'Property ' . self::class . '::$objectManager is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'uriBuilder' => 'Property ' . self::class . '::$uriBuilder is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'settings' => 'Property ' . self::class . '::$settings is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'request' => 'Property ' . self::class . '::$request is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'response' => 'Property ' . self::class . '::$response is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'arguments' => 'Property ' . self::class . '::$arguments is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'validatorResolver' => 'Property ' . self::class . '::$validatorResolver is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'supportedRequestTypes' => 'Property ' . self::class . '::$supportedRequestTypes is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'controllerContext' => 'Property ' . self::class . '::$controllerContext is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
        'configurationManager' => 'Property ' . self::class . '::$configurationManager is deprecated since TYPO3 10.2 and will be removed in TYPO3 11.0',
    ];

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    private $signalSlotDispatcher;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    private $uriBuilder;

    /**
     * Contains the settings of the current extension
     *
     * @var array
     */
    private $settings;

    /**
     * The current request.
     *
     * @var \TYPO3\CMS\Extbase\Mvc\RequestInterface
     */
    private $request;

    /**
     * The response which will be returned by this action controller
     *
     * @var \TYPO3\CMS\Extbase\Mvc\ResponseInterface
     */
    private $response;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver
     */
    private $validatorResolver;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\Arguments Arguments passed to the controller
     */
    private $arguments;

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver
     */
    public function injectValidatorResolver(ValidatorResolver $validatorResolver)
    {
        $this->validatorResolver = $validatorResolver;
    }

    /**
     * An array of supported request types. By default only web requests are supported.
     * Modify or replace this array if your specific controller supports certain
     * (additional) request types.
     *
     * @var array
     */
    private $supportedRequestTypes = [\TYPO3\CMS\Extbase\Mvc\Request::class];

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    private $controllerContext;

    /**
     * @return ControllerContext
     */
    public function getControllerContext()
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        return $this->controllerContext;
    }

    /**
     * @var ConfigurationManagerInterface
     */
    private $configurationManager;

    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
    }

    /**
     * Injects the object manager
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->arguments = $this->objectManager->get(Arguments::class);
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody The message
     * @param string $messageTitle Optional message title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session (default) or not
     * @throws \InvalidArgumentException if the message body is no string
     * @see \TYPO3\CMS\Core\Messaging\FlashMessage
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1243258395);
        }
        /* @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            (string)$messageBody,
            (string)$messageTitle,
            $severity,
            $storeInSession
        );
        $this->controllerContext->getFlashMessageQueue()->enqueue($flashMessage);
    }

    /**
     * Checks if the current request type is supported by the controller.
     *
     * If your controller only supports certain request types, either
     * replace / modify the supportedRequestTypes property or override this
     * method.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The current request
     * @return bool TRUE if this request type is supported, otherwise FALSE
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    public function canProcessRequest(RequestInterface $request)
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        foreach ($this->supportedRequestTypes as $supportedRequestType) {
            if ($request instanceof $supportedRequestType) {
                return true;
            }
        }
        return false;
    }

    /**
     * Processes a general request. The result can be returned by altering the given response.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this handler
     * @throws UnsupportedRequestTypeException if the controller doesn't support the current request type
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        if (!$this->canProcessRequest($request)) {
            throw new UnsupportedRequestTypeException(static::class . ' does not support requests of type "' . get_class($request) . '". Supported types are: ' . implode(' ', $this->supportedRequestTypes), 1187701132);
        }
        if ($response instanceof Response && $request instanceof WebRequest) {
            $response->setRequest($request);
        }
        $this->request = $request;
        $this->request->setDispatched(true);
        $this->response = $response;
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
        $this->uriBuilder->setRequest($request);
        $this->initializeControllerArgumentsBaseValidators();
        $this->mapRequestArgumentsToControllerArguments();
        $this->controllerContext = $this->buildControllerContext();
    }

    /**
     * Initialize the controller context
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext ControllerContext to be passed to the view
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    protected function buildControllerContext()
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext */
        $controllerContext = $this->objectManager->get(ControllerContext::class);
        $controllerContext->setRequest($this->request);
        $controllerContext->setResponse($this->response);
        if ($this->arguments !== null) {
            $controllerContext->setArguments($this->arguments);
        }
        $controllerContext->setUriBuilder($this->uriBuilder);

        return $controllerContext;
    }

    /**
     * Forwards the request to another action and / or controller.
     *
     * Request is directly transferred to the other action / controller
     * without the need for a new request.
     *
     * @param string $actionName Name of the action to forward to
     * @param string|null $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string|null $extensionName Name of the extension containing the controller to forward to. If not specified, the current extension is assumed.
     * @param array|null $arguments Arguments to pass to the target action
     * @throws StopActionException
     * @see redirect()
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    public function forward($actionName, $controllerName = null, $extensionName = null, array $arguments = null)
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        $this->request->setDispatched(false);
        if ($this->request instanceof WebRequest) {
            $this->request->setControllerActionName($actionName);
            if ($controllerName !== null) {
                $this->request->setControllerName($controllerName);
            }
            if ($extensionName !== null) {
                $this->request->setControllerExtensionName($extensionName);
            }
        }
        if ($arguments !== null) {
            $this->request->setArguments($arguments);
        }
        throw new StopActionException('forward', 1476045801);
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
     * @param string|null $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string|null $extensionName Name of the extension containing the controller to forward to. If not specified, the current extension is assumed.
     * @param array|null $arguments Arguments to pass to the target action
     * @param int|null $pageUid Target page uid. If NULL, the current page uid is used
     * @param int $delay (optional) The delay in seconds. Default is no delay.
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
     * @throws UnsupportedRequestTypeException If the request is not a web request
     * @throws StopActionException
     * @see forward()
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    protected function redirect($actionName, $controllerName = null, $extensionName = null, array $arguments = null, $pageUid = null, $delay = 0, $statusCode = 303)
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        if (!$this->request instanceof WebRequest) {
            throw new UnsupportedRequestTypeException('redirect() only supports web requests.', 1220539734);
        }
        if ($controllerName === null) {
            $controllerName = $this->request->getControllerName();
        }
        $this->uriBuilder->reset()->setCreateAbsoluteUri(true);
        if (MathUtility::canBeInterpretedAsInteger($pageUid)) {
            $this->uriBuilder->setTargetPageUid((int)$pageUid);
        }
        if (GeneralUtility::getIndpEnv('TYPO3_SSL')) {
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
     * @param int $delay (optional) The delay in seconds. Default is no delay.
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
     * @throws UnsupportedRequestTypeException If the request is not a web request
     * @throws StopActionException
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    protected function redirectToUri($uri, $delay = 0, $statusCode = 303)
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        if (!$this->request instanceof WebRequest) {
            throw new UnsupportedRequestTypeException('redirect() only supports web requests.', 1220539735);
        }

        $this->objectManager->get(CacheService::class)->clearCachesOfRegisteredPageIds();

        $uri = $this->addBaseUriIfNecessary($uri);
        $escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
        $this->response->setContent('<html><head><meta http-equiv="refresh" content="' . (int)$delay . ';url=' . $escapedUri . '"/></head></html>');
        if ($this->response instanceof Response) {
            $this->response->setStatus($statusCode);
            $this->response->setHeader('Location', (string)$uri);
        }
        // Avoid caching the plugin when we issue a redirect response
        // This means that even when an action is configured as cachable
        // we avoid the plugin to be cached, but keep the page cache untouched
        $contentObject = $this->configurationManager->getContentObject();
        if ($contentObject->getUserObjectType() === ContentObjectRenderer::OBJECTTYPE_USER) {
            $contentObject->convertToUserIntObject();
        }

        throw new StopActionException('redirectToUri', 1476045828);
    }

    /**
     * Adds the base uri if not already in place.
     *
     * @param string $uri The URI
     * @return string
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    protected function addBaseUriIfNecessary($uri)
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        return GeneralUtility::locationHeaderUrl((string)$uri);
    }

    /**
     * Sends the specified HTTP status immediately.
     *
     * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
     *
     * @param int $statusCode The HTTP status code
     * @param string $statusMessage A custom HTTP status message
     * @param string $content Body content which further explains the status
     * @throws UnsupportedRequestTypeException If the request is not a web request
     * @throws StopActionException
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    public function throwStatus($statusCode, $statusMessage = null, $content = null)
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        if (!$this->request instanceof WebRequest) {
            throw new UnsupportedRequestTypeException('throwStatus() only supports web requests.', 1220539739);
        }
        if ($this->response instanceof Response) {
            $this->response->setStatus($statusCode, $statusMessage);
            if ($content === null) {
                $content = $this->response->getStatus();
            }
        }
        $this->response->setContent($content);
        throw new StopActionException('throwStatus', 1476045871);
    }

    /**
     * Collects the base validators which were defined for the data type of each
     * controller argument and adds them to the argument's validator chain.
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    public function initializeControllerArgumentsBaseValidators()
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument */
        foreach ($this->arguments as $argument) {
            $validator = $this->validatorResolver->getBaseValidatorConjunction($argument->getDataType());
            if ($validator !== null) {
                $argument->setValidator($validator);
            }
        }
    }

    /**
     * Maps arguments delivered by the request object to the local controller arguments.
     *
     * @throws Exception\RequiredArgumentMissingException
     * @deprecated since TYPO3 10.2 and will be removed in version 11.0
     */
    protected function mapRequestArgumentsToControllerArguments()
    {
        trigger_error(
            __METHOD__ . ' is deprecated since TYPO3 10.2 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );

        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument */
        foreach ($this->arguments as $argument) {
            $argumentName = $argument->getName();
            if ($this->request->hasArgument($argumentName)) {
                $argument->setValue($this->request->getArgument($argumentName));
            } elseif ($argument->isRequired()) {
                throw new RequiredArgumentMissingException('Required argument "' . $argumentName . '" is not set for ' . $this->request->getControllerObjectName() . '->' . $this->request->getControllerActionName() . '.', 1298012500);
            }
        }
    }
}
