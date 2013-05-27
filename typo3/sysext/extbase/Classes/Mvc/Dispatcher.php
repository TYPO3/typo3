<?php
namespace TYPO3\CMS\Extbase\Mvc;

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
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 */
class Dispatcher implements \TYPO3\CMS\Core\SingletonInterface {

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
	protected $settings = array();

	/**
	 * Constructs the global dispatcher
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the Reflection Service
	 *
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the SignalSlotDispatcher Service
	 *
	 * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
	 */
	public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request to dispatch
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, to be modified by the controller
	 * @throws Exception\InfiniteLoopException
	 * @return void
	 */
	public function dispatch(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			if ($dispatchLoopCount++ > 99) {
				throw new \TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException('Could not ultimately dispatch the request after ' . $dispatchLoopCount . ' iterations. Most probably, a @dontvalidate annotation is missing on re-displaying a form with validation errors.', 1217839467);
			}
			$controller = $this->resolveController($request);
			try {
				$controller->processRequest($request, $response);
			} catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $ignoredException) {
			}
		}
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterRequestDispatch', array('request' => $request, 'response' => $response));
	}

	/**
	 * Finds and instanciates a controller that matches the current request.
	 * If no controller can be found, an instance of NotFoundControllerInterface is returned.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request to dispatch
	 * @throws Exception\InvalidControllerException
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface
	 */
	protected function resolveController(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request) {
		$controllerObjectName = $request->getControllerObjectName();
		$controller = $this->objectManager->get($controllerObjectName);
		if (!$controller instanceof \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException('Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerInterface.', 1202921619);
		}
		return $controller;
	}
}

?>