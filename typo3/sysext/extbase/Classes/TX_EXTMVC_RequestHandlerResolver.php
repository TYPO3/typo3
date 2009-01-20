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
 * Analyzes the raw request and delivers a request handler which can handle it.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandlerResolver {

	/**
	 * @var F3_FLOW3_ObjectManagerInterface Reference to the object manager
	 */
	protected $objectManager;

	/**
	 * The FLOW3 settings
	 * @var array
	 */
	protected $configuration;

	/**
	 * Constructs the Request Handler Resolver
	 *
	 * @param array $settings The FLOW3 settings
	 * @param F3_FLOW3_ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(array $settings, F3_FLOW3_Object_ManagerInterface $objectManager) {
		$this->settings = $settings;
		$this->objectManager = $objectManager;
	}

	/**
	 * Analyzes the raw request and tries to find a request handler which can handle
	 * it. If none is found, an exception is thrown.
	 *
	 * @return TX_EXTMVC_RequestHandler A request handler
	 * @throws TX_EXTMVC_Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveRequestHandler() {
		$availableRequestHandlerClassNames = $this->settings['mvc']['availableRequestHandlers'];

		$suitableRequestHandlers = array();
		foreach ($availableRequestHandlerClassNames as $requestHandlerClassName) {
			$requestHandler = $this->objectManager->getObject($requestHandlerClassName);
			if ($requestHandler->canHandleRequest()) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) throw new LogicException('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		if (count($suitableRequestHandlers) == 0) throw new TX_EXTMVC_Exception('No suitable request handler found.', 1205414233);
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}
}

?>