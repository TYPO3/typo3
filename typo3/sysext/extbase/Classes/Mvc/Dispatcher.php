<?php
namespace TYPO3\CMS\Extbase\Mvc;

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
 */
class Dispatcher implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface A reference to the object manager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Constructs the global dispatcher
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
     */
    public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Dispatches a request to a controller and initializes the security framework.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request to dispatch
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, to be modified by the controller
     * @throws Exception\InfiniteLoopException
     * @return void
     */
    public function dispatch(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        $dispatchLoopCount = 0;
        while (!$request->isDispatched()) {
            if ($dispatchLoopCount++ > 99) {
                throw new \TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException('Could not ultimately dispatch the request after ' . $dispatchLoopCount . ' iterations. Most probably, a @ignorevalidation annotation is missing on re-displaying a form with validation errors.', 1217839467);
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
        $controller = $this->objectManager->get($controllerObjectName);
        if (!$controller instanceof \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException('Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerInterface.', 1202921619);
        }
        return $controller;
    }
}
