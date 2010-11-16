<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Jochen Rau <jochen.rau@typoplanet.de>
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
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 */
class Tx_Extbase_MVC_Dispatcher implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface A reference to the object manager
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructs the global dispatcher
	 *
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager A reference to the object manager
	 */
	public function __construct(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the Reflection Service
	 *
	 * @param Tx_Extbase_Reflection_Service $reflectionService
	 * @return void
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param Tx_Extbase_MVC_RequestInterface $request The request to dispatch
	 * @param Tx_Extbase_MVC_ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 */
	public function dispatch(Tx_Extbase_MVC_RequestInterface $request, Tx_Extbase_MVC_ResponseInterface $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			if ($dispatchLoopCount++ > 99) throw new Tx_Extbase_MVC_Exception_InfiniteLoop('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);
			$controller = $this->resolveController($request);
			try {
				$controller->processRequest($request, $response);
			} catch (Tx_Extbase_MVC_Exception_StopAction $ignoredException) {
			}
		}
	}

	/**
	 * Finds and instanciates a controller that matches the current request.
	 * If no controller can be found, an instance of NotFoundControllerInterface is returned.
	 *
	 * @param Tx_Extbase_MVC_RequestInterface $request The request to dispatch
	 * @return Tx_Extbase_MVC_Controller_ControllerInterface
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function resolveController(Tx_Extbase_MVC_RequestInterface $request) {
		$controllerObjectName = $request->getControllerObjectName();
		$controller = $this->objectManager->get($controllerObjectName);
		if (!$controller instanceof Tx_Extbase_MVC_Controller_ControllerInterface) {
			throw new Tx_Extbase_MVC_Exception_InvalidController('Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the Tx_Extbase_MVC_Controller_ControllerInterface.', 1202921619);
		}
		return $controller;
	}

}
?>