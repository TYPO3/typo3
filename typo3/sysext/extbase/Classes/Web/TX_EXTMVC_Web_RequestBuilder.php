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
 * Builds a web request object from the raw HTTP information
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Web_RequestBuilder {

	/**
	 * @var F3_FLOW3_Configuration_Manager
	 */
	protected $configurationManager;

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
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return TX_EXTMVC_Web_Request The web request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$request = t3lib_div::makeInstance('TX_EXTMVC_Web_Request');
		// $request->injectEnvironment($this->environment);
		// $request->setRequestURI($this->environment->getRequestURI());
		// $request->setMethod($this->environment->getRequestMethod());
		// 
		// $routesConfiguration = $this->configurationManager->getSpecialConfiguration(F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_ROUTES);
		// $this->router->setRoutesConfiguration($routesConfiguration);
		// $this->router->route($request);

		return $request;
	}
}
?>