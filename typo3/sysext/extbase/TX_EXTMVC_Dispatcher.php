<?php
declare(ENCODING = 'utf-8');


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
 * @version $Id:$
 */

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Dispatcher {

	/**
	 * @var F3_FLOW3_Object_ManagerInterface A reference to the object manager
	 */
	protected $objectManager;

	/**
	 * @var F3_FLOW3_Security_ContextHolderInterface A reference to the security contextholder
	 */
	protected $securityContextHolder;

	/**
	 * @var F3_FLOW3_Security_Auhtorization_FirewallInterface A reference to the firewall
	 */
	protected $firewall;

	/**
	 * @var F3_FLOW3_Configuration_Manager A reference to the configuration manager
	 */
	protected $configurationManager;

	/**
	 * Constructs the global dispatcher
	 *
	 * @param F3_FLOW3_Object_ManagerInterface $objectManager A reference to the object manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Object_ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the security context holder
	 *
	 * @param F3_FLOW3_Security_ContextHolderInterface $securityContextHolder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSecurityContextHolder(F3_FLOW3_Security_ContextHolderInterface $securityContextHolder) {
		$this->securityContextHolder = $securityContextHolder;
	}

	/**
	 * Injects the authorization firewall
	 *
	 * @param F3_FLOW3_Security_Authorization_FirewallInterface $firewall
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectFirewall(F3_FLOW3_Security_Authorization_FirewallInterface $firewall) {
		$this->firewall = $firewall;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param F3_FLOW3_Configuration_Manager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(F3_FLOW3_Configuration_Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Dispatches a request to a controller and initializes the security framework.
	 *
	 * @param String $content The content
	 * @param array $configuration The TS configuration array
	 * @return String $content The processed content
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>	
	 */
	public function dispatch(TX_EXTMVC_Request $request, TX_EXTMVC_Response $response) {
		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			$dispatchLoopCount ++;
			if ($dispatchLoopCount > 99) throw new TX_EXTMVC_Exception_InfiniteLoop('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);

			$settings = $this->configurationManager->getSettings('FLOW3');
			if ($settings['security']['enable'] === TRUE) {
				$this->securityContextHolder->initializeContext($request);
				$this->firewall->blockIllegalRequests($request);
			}

			try {
				$controller = $this->getPreparedController($request, $response);
				$controller->processRequest($request, $response);
			} catch (TX_EXTMVC_Exception_StopAction $ignoredException) {
			}
		}
	}

	/**
	 * Resolves, prepares and returns the controller which is specified in the request object.
	 *
	 * @param TX_EXTMVC_Request $request The current request
	 * @param TX_EXTMVC_Response $response The current response
	 * @return TX_EXTMVC_Controller_RequestHandlingController The controller
	 * @throws TX_EXTMVC_Exception_NoSuchController, TX_EXTMVC_Exception_InvalidController
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Implement proper mechanism for handling authentication exceptions
	 */
	protected function getPreparedController(TX_EXTMVC_Request $request, TX_EXTMVC_Response $response) {
		$controllerObjectName = $request->getControllerObjectName();

		try {
			$controller = $this->objectManager->getObject($controllerObjectName);
		} catch (F3_FLOW3_Security_Exception_AuthenticationRequired $exception) {
			if (!$request instanceof TX_EXTMVC_Web_Request) throw $exception;
			$request->setDispatched(TRUE);

			$settings = $this->configurationManager->getSettings('FLOW3');
			$uri = (string)$request->getBaseURI() . $settings['security']['loginPageURIForDemoPurposes'];
			$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
			$response->setContent('<html><head><meta http-equiv="refresh" content="0;url=' . $escapedUri . '"/></head></html>');
			$response->setStatus(303);
			$response->setHeader('Location', (string)$uri);
			throw new TX_EXTMVC_Exception_StopAction();
		}

		if (!$controller instanceof TX_EXTMVC_Controller_RequestHandlingController) throw new TX_EXTMVC_Exception_InvalidController('Invalid controller "' . $controllerObjectName . '". The controller must be a valid request handling controller.', 1202921619);

		$controller->setSettings($this->configurationManager->getSettings($request->getControllerPackageKey()));
		return $controller;
	}
}
?>