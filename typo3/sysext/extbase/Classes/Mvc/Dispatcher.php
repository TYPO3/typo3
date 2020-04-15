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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Event\Mvc\AfterRequestDispatchedEvent;
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
        $this->objectManager = $objectManager;
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Dispatches a request to a controller and initializes the security framework.
     *
     * @param RequestInterface $request The request to dispatch
     * @param ResponseInterface $response The response, to be modified by the controller
     * @throws Exception\InfiniteLoopException
     */
    public function dispatch(RequestInterface $request, ResponseInterface $response)
    {
        $dispatchLoopCount = 0;
        while (!$request->isDispatched()) {
            if ($dispatchLoopCount++ > 99) {
                throw new InfiniteLoopException('Could not ultimately dispatch the request after ' . $dispatchLoopCount . ' iterations. Most probably, a @' . IgnoreValidation::class . ' annotation is missing on re-displaying a form with validation errors.', 1217839467);
            }
            $controller = $this->resolveController($request);
            try {
                $controller->processRequest($request, $response);
            } catch (StopActionException $ignoredException) {
            }
        }

        $this->eventDispatcher->dispatch(new AfterRequestDispatchedEvent($request, $response));
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
}
