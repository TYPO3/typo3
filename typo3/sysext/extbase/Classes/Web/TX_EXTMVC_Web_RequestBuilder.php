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
 * Builds a web request object from the raw HTTP information
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestBuilder {

	/**
	 * @var F3_FLOW3_Object_FactoryInterface $objectFactory: A reference to the Object Factory
	 */
	protected $objectFactory;

	/**
	 * @var F3_FLOW3_Utility_Environment
	 */
	protected $environment;

	/**
	 * @var F3_FLOW3_Configuration_Manager
	 */
	protected $configurationManager;

	/**
	 * @var TX_EXTMVC_Web_RouterInterface
	 */
	protected $router;

	/**
	 * Constructs this Web Request Builder
	 *
	 * @param F3_FLOW3_Object_FactoryInterface $objectFactory A reference to the object factory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Object_FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the server environment
	 *
	 * @param F3_FLOW3_Utility_Environment $environment The environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(F3_FLOW3_Utility_Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param F3_FLOW3_Configuration_Manager $configurationManager A reference to the configuration manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(F3_FLOW3_Configuration_Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects a router for routing the web request
	 *
	 * @param TX_EXTMVC_Web_Routing_RouterInterface $router A router which routes the web request to a controller and action
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectRouter(TX_EXTMVC_Web_Routing_RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return TX_EXTMVC_Web_Request The web request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$request = $this->objectFactory->create('F3_FLOW3_MVC_Web_Request');
		$request->injectEnvironment($this->environment);
		$request->setRequestURI($this->environment->getRequestURI());
		$request->setMethod($this->environment->getRequestMethod());

		$routesConfiguration = $this->configurationManager->getSpecialConfiguration(F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);
		$this->router->route($request);

		return $request;
	}
}
?>