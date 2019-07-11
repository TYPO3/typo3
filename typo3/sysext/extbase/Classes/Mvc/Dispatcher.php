<?php
namespace TYPO3\CMS\Extbase\Mvc;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher as SignalSlotDispatcher;

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

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Dispatcher implements \TYPO3\CMS\Core\SingletonInterface
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
     * @var SignalSlotDispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * Constructs the global dispatcher
     *
     * @param ObjectManagerInterface $objectManager A reference to the object manager
     * @param ContainerInterface $container
     * @param SignalSlotDispatcher $signalSlotDispatcher
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ContainerInterface $container,
        SignalSlotDispatcher $signalSlotDispatcher
    ) {
        $this->objectManager = $objectManager;
        $this->container = $container;
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Dispatches a request to a controller and initializes the security framework.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request to dispatch
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, to be modified by the controller
     * @throws Exception\InfiniteLoopException
     */
    public function dispatch(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        $dispatchLoopCount = 0;
        while (!$request->isDispatched()) {
            if ($dispatchLoopCount++ > 99) {
                throw new \TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException('Could not ultimately dispatch the request after ' . $dispatchLoopCount . ' iterations. Most probably, a @' . \TYPO3\CMS\Extbase\Annotation\IgnoreValidation::class . ' annotation is missing on re-displaying a form with validation errors.', 1217839467);
            }
            $controller = $this->resolveController($request);
            try {
                $controller->processRequest($request, $response);
            } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $ignoredException) {
            }
        }
        $this->emitAfterRequestDispatchSignal($request, $response);
    }

    /**
     * Emits a signal after a request was dispatched
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    protected function emitAfterRequestDispatchSignal(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterRequestDispatch', [$request, $response]);
    }

    /**
     * Finds and instanciates a controller that matches the current request.
     * If no controller can be found, an instance of NotFoundControllerInterface is returned.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request to dispatch
     * @throws Exception\InvalidControllerException
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface
     */
    protected function resolveController(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request)
    {
        $controllerObjectName = $request->getControllerObjectName();
        if ($this->container->has($controllerObjectName)) {
            $controller = $this->container->get($controllerObjectName);
        } else {
            $controller = $this->objectManager->get($controllerObjectName);
        }
        if (!$controller instanceof \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException(
                'Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerInterface.',
                1476109646
            );
        }
        return $controller;
    }
}
