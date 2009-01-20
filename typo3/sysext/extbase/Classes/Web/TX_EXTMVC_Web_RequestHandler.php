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
 * A request handler which can handle web requests.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements TX_EXTMVC_RequestHandlerInterface {

	/**
	 * @var F3_FLOW3_Object_FactoryInterface Reference to the object factory
	 */
	protected $objectFactory;

	/**
	 * @var F3_FLOW3_Utility_Environment Reference to the environment utility object
	 */
	protected $utilityEnvironment;

	/**
	 * @var TX_EXTMVC_Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var TX_EXTMVC_Web_RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var TX_EXTMVC_RequestProcessorChainManager
	 */
	protected $requestProcessorChainManager;

	/**
	 * Constructs the Web Request Handler
	 *
	 * @param F3_FLOW3_Object_FactoryInterface $objectFactory A reference to the object factory
	 * @param F3_FLOW3_Utility_Environment $utilityEnvironment A reference to the environment
	 * @param TX_EXTMVC_Dispatcher $dispatcher The request dispatcher
	 * @param TX_EXTMVC_Web_RequestBuilder $requestBuilder The request builder
	 * @param TX_EXTMVC_RequestProcessorChainManager A reference to the request processor chain manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(
			F3_FLOW3_Object_FactoryInterface $objectFactory,
			F3_FLOW3_Utility_Environment $utilityEnvironment,
			TX_EXTMVC_Dispatcher $dispatcher,
			TX_EXTMVC_Web_RequestBuilder $requestBuilder,
			TX_EXTMVC_RequestProcessorChainManager $requestProcessorChainManager) {
		$this->objectFactory = $objectFactory;
		$this->utilityEnvironment = $utilityEnvironment;
		$this->dispatcher = $dispatcher;
		$this->requestBuilder = $requestBuilder;
		$this->requestProcessorChainManager = $requestProcessorChainManager;
	}

	/**
	 * Handles the web request. The response will automatically be sent to the client.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		$this->requestProcessorChainManager->processRequest($request);
		$response = $this->objectFactory->create('F3_FLOW3_MVC_Web_Response');
		$this->dispatcher->dispatch($request, $response);
		$response->send();
	}

	/**
	 * This request handler can handle any web request.
	 *
	 * @return boolean If the request is a web request, TRUE otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequest() {
		switch ($this->utilityEnvironment->getRequestMethod()) {
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_GET :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_POST :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_PUT :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_DELETE :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_OPTIONS :
			case F3_FLOW3_Utility_Environment::REQUEST_METHOD_HEAD :
				return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 100;
	}
}
?>