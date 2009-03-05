<?php

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
 * Represents a web request.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *
 * @scope prototype
 */
class TX_EXTMVC_Web_Request extends TX_EXTMVC_Request {

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'html';

	/**
	 * @var string Contains the request method
	 */
	protected $method = 'GET';

	/**
	 * @var F3_FLOW3_Utility_Environment
	 */
	protected $environment;

	/**
	 * @var F3_FLOW3_Property_DataType_URI The request URI
	 */
	protected $requestURI;

	/**
	 * @var F3_FLOW3_Property_DataType_URI The base URI for this request - ie. the host and path leading to the index.php
	 */
	protected $baseURI;

	/**
	 * Sets the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws TX_EXTMVC_Exception_InvalidRequestMethod if the request method is not supported
	 */
	public function setMethod($method) {
		if ($method === '' || (strtoupper($method) !== $method)) throw new TX_EXTMVC_Exception_InvalidRequestMethod('The request method "' . $method . '" is not supported.', 1217778382);
		$this->method = $method;
	}

	/**
	 * Returns the name of the request method
	 *
	 * @return string Name of the request method - one of the F3_FLOW3_Utility_Environment::REQUEST_METHOD_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Sets the request URI
	 *
	 * @param string $requestURI URI of this web request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRequestURI($requestURI) {
		$this->requestURI = clone $requestURI;
		$this->baseURI = $this->detectBaseURI($requestURI);
	}

	/**
	 * Returns the request URI
	 *
	 * @return F3_FLOW3_Property_DataType_URI URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRequestURI() {
		return $this->requestURI;
	}

	/**
	 * Sets the base URI for this request.
	 *
	 * @param string $baseURI New base URI
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBaseURI($baseURI) {
		$this->baseURI = clone $baseURI;
	}

	/**
	 * Returns the base URI
	 *
	 * @return F3_FLOW3_Property_DataType_URI Base URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseURI() {
		return $this->baseURI;
	}

	/**
	 * Tries to detect the base URI of this request and returns it.
	 *
	 * @param string $requestURI URI of this web request
	 * @return string The detected base URI
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function detectBaseURI($requestURI) {
		$baseURI = clone $requestURI;
		$baseURI->setQuery(NULL);
		$baseURI->setFragment(NULL);

		$requestPathSegments = explode('/', $this->environment->getScriptRequestPathAndName());
		array_pop($requestPathSegments);
		$baseURI->setPath(implode('/', $requestPathSegments) . '/');
		return $baseURI;
	}
}
?>