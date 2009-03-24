<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Represents a web request.
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
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
	 * @var string
	 */
	protected $requestURI;

	/**
	 * @var string The base URI for this request - ie. the host and path leading to the index.php
	 */
	protected $baseURI;

	/**
	 * Sets the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @throws TX_EXTMVC_Exception_InvalidRequestMethod if the request method is not supported
	 */
	public function setMethod($method) {
		if ($method === '' || (strtoupper($method) !== $method)) throw new TX_EXTMVC_Exception_InvalidRequestMethod('The request method "' . $method . '" is not supported.', 1217778382);
		$this->method = $method;
	}

	/**
	 * Returns the name of the request method
	 *
	 * @return string Name of the request method
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Sets the request URI
	 *
	 * @param string $requestURI URI of this web request
	 * @return void
	 */
	public function setRequestURI($requestURI) {
		$this->requestURI = $requestURI;
	}

	/**
	 * Returns the request URI
	 *
	 * @return F3_FLOW3_Property_DataType_URI URI of this web request
	 */
	public function getRequestURI() {
		return $this->requestURI;
	}

	/**
	 * Sets the base URI for this request.
	 *
	 * @param string $baseURI New base URI
	 * @return void
	 */
	public function setBaseURI($baseURI) {
		$this->baseURI = $baseURI;
	}

	/**
	 * Returns the base URI
	 *
	 * @return F3_FLOW3_Property_DataType_URI Base URI of this web request
	 */
	public function getBaseURI() {
		return $this->baseURI;
	}
}
?>