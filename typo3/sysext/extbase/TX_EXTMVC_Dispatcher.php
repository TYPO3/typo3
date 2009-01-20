<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: F3_FLOW3_MVC_Dispatcher.php 1749 2009-01-15 15:06:30Z k-fish $
 */

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: F3_FLOW3_MVC_Dispatcher.php 1749 2009-01-15 15:06:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Dispatcher {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface A reference to the object manager
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Security\ContextHolderInterface A reference to the security contextholder
	 */
	protected $securityContextHolder;

	/**
	 * @var \F3\FLOW3\Security\Auhtorization\FirewallInterface A reference to the firewall
	 */
	protected $firewall;

	/**
	 * @var \F3\FLOW3\Configuration\Manager A reference to the configuration manager
	 */
	protected $configurationManager;

	/**
	 * Constructs the global dispatcher
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the object manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the security context holder
	 *
	 * @param \F3\FLOW3\Security\ContextHolderInterface $securityContextHolder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSecurityContextHolder(\F3\FLOW3\Security\ContextHolderInterface $securityContextHolder) {
		$this->securityContextHolder = $securityContextHolder;
	}

	/**
	 * Injects the authorization firewall
	 *
	 * @param \F3\FLOW3\Security\Authorization\FirewallInterface $firewall
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectFirewall(\F3\FLOW3\Security\Authorization\FirewallInterface $firewall) {
		$this->firewall = $firewall;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \F3\FLOW3\Configuration\Manager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request to dispatch
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The response, to be modified by the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function dispatch(\F3\FLOW3\MVC\Request $request, \F3\FLOW3\MVC\Response $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			$dispatchLoopCount ++;
			if ($dispatchLoopCount > 99) throw new \F3\FLOW3\MVC\Exception\InfiniteLoop('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);

			$settings = $this->configurationManager->getSettings('FLOW3');
			if ($settings['security']['enable'] === TRUE) {
				$this->securityContextHolder->initializeContext($request);
				$this->firewall->blockIllegalRequests($request);
			}

			try {
				$controller = $this->getPreparedController($request, $response);
				$controller->processRequest($request, $response);
			} catch (\F3\FLOW3\MVC\Exception\StopAction $ignoredException) {
			}
		}
	}

	/**
	 * Resolves, prepares and returns the controller which is specified in the request object.
	 *
	 * @param \F3\FLOW3\MVC\Request $request The current request
	 * @param \F3\FLOW3\MVC\Response $response The current response
	 * @return \F3\FLOW3\MVC\Controller\RequestHandlingController The controller
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchController, \F3\FLOW3\MVC\Exception\InvalidController
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Implement proper mechanism for handling authentication exceptions
	 */
	protected function getPreparedController(\F3\FLOW3\MVC\Request $request, \F3\FLOW3\MVC\Response $response) {
		$controllerObjectName = $request->getControllerObjectName();

		try {
			$controller = $this->objectManager->getObject($controllerObjectName);
		} catch (\F3\FLOW3\Security\Exception\AuthenticationRequired $exception) {
			if (!$request instanceof \F3\FLOW3\MVC\Web\Request) throw $exception;
			$request->setDispatched(TRUE);

			$settings = $this->configurationManager->getSettings('FLOW3');
			$uri = (string)$request->getBaseURI() . $settings['security']['loginPageURIForDemoPurposes'];
			$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
			$response->setContent('<html><head><meta http-equiv="refresh" content="0;url=' . $escapedUri . '"/></head></html>');
			$response->setStatus(303);
			$response->setHeader('Location', (string)$uri);
			throw new \F3\FLOW3\MVC\Exception\StopAction();
		}

		if (!$controller instanceof \F3\FLOW3\MVC\Controller\RequestHandlingController) throw new \F3\FLOW3\MVC\Exception\InvalidController('Invalid controller "' . $controllerObjectName . '". The controller must be a valid request handling controller.', 1202921619);

		$controller->setSettings($this->configurationManager->getSettings($request->getControllerPackageKey()));
		return $controller;
	}
}
?>