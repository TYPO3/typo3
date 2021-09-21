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

namespace TYPO3\CMS\Extbase\Mvc;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Dispatcher implements SingletonInterface
{
    /**
     * @var ObjectManagerInterface A reference to the object manager
     * @deprecated since v11, will be removed in v12
     */
    protected $objectManager;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * Constructs the global dispatcher
     *
     * @param ObjectManagerInterface $objectManager A reference to the object manager
     * @param ContainerInterface $container
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ContainerInterface $container,
        EventDispatcherInterface $eventDispatcher
    ) {
        // @deprecated since v11, will be removed in v12
        $this->objectManager = $objectManager;
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Dispatches a request to a controller and initializes the security framework.
     *
     * @param RequestInterface $request The request to dispatch
     * @return ResponseInterface
     * @throws Exception\InfiniteLoopException
     */
    public function dispatch(RequestInterface $request): ResponseInterface
    {
        $dispatchLoopCount = 0;
        $isDispatched = false;
        // @deprecated since v11, will be changed in v12 to: while (!$isDispatched) {
        while (!$isDispatched || !$request->isDispatched()) {
            if ($dispatchLoopCount++ > 99) {
                throw new InfiniteLoopException('Could not ultimately dispatch the request after ' . $dispatchLoopCount . ' iterations. Most probably, a @' . IgnoreValidation::class . ' annotation is missing on re-displaying a form with validation errors.', 1217839467);
            }
            $controller = $this->resolveController($request);
            try {
                $response = $controller->processRequest($request);
                if ($response instanceof ForwardResponse) {
                    // The controller action returned an extbase internal Forward response:
                    // Another action should be dispatched.
                    $request = static::buildRequestFromCurrentRequestAndForwardResponse($request, $response);
                } elseif ($response instanceof RedirectResponse) {
                    // The controller action returned a core HTTP redirect response.
                    // Dispatching ends here and response is sent to client.
                    $isDispatched = true;
                } else {
                    // Some casual response returned by action.
                    // Dispatching ends here and response is sent to client.
                    $isDispatched = true;
                }
            } catch (StopActionException $ignoredException) {
                // @deprecated since v11, will be removed in v12
                $isDispatched = true;
                $response = $ignoredException->getResponse();
            }
        }

        $this->eventDispatcher->dispatch(new AfterRequestDispatchedEvent($request, $response));
        return $response;
    }

    /**
     * Finds and instantiates a controller that matches the current request.
     * If no controller can be found, an instance of NotFoundControllerInterface is returned.
     *
     * @param RequestInterface $request The request to dispatch
     * @return Controller\ControllerInterface
     * @throws Exception\InvalidControllerException
     */
    protected function resolveController(RequestInterface $request)
    {
        $controllerObjectName = $request->getControllerObjectName();
        if ($this->container->has($controllerObjectName)) {
            $controller = $this->container->get($controllerObjectName);
        } else {
            // @deprecated since v11, will be removed in v12.
            $controller = $this->objectManager->get($controllerObjectName);
        }
        if (!$controller instanceof ControllerInterface) {
            throw new InvalidControllerException(
                'Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerInterface.',
                1476109646
            );
        }
        return $controller;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @todo: make this a private method again as soon as the tests, that fake the dispatching of requests, are refactored.
     */
    public static function buildRequestFromCurrentRequestAndForwardResponse(Request $currentRequest, ForwardResponse $forwardResponse): Request
    {
        $extbaseAttribute = clone $currentRequest->getAttribute('extbase');
        $request = $currentRequest->withAttribute('extbase', $extbaseAttribute);

        // @deprecated since v11, will be removed in v12.
        $request->setDispatched(false);
        $request->setControllerActionName($forwardResponse->getActionName());

        if ($forwardResponse->getControllerName() !== null) {
            $request->setControllerName($forwardResponse->getControllerName());
        }

        if ($forwardResponse->getExtensionName() !== null) {
            $request->setControllerExtensionName($forwardResponse->getExtensionName());
        }

        if ($forwardResponse->getArguments() !== null) {
            $request->setArguments($forwardResponse->getArguments());
        }

        $request->setOriginalRequest($currentRequest);
        $request->setOriginalRequestMappingResults($forwardResponse->getArgumentsValidationResult());

        return $request;
    }
}
