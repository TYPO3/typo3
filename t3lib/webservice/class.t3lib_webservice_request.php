<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  	All rights reserved
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
 * This class encapsulates a request
 *
 * @package TYPO3
 * @subpackage Webservice
 * @scope prototype
 * @entity
 * @api
 */
class t3lib_webservice_request {

	/**
	 * The HTTP accept headers sent by the client
	 *
	 * @var array
	 */
	protected $acceptHeaders = array();

	/**
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * @var array
	 */
	protected $resolvedArguments = array();

	/**
	 * @var string
	 */
	protected $body;


	/**
	 * Contains the request method
	 * @var string
	 */
	protected $method = 'GET';

	/**
	 * The request URI
	 * @var t3lib_webservice_Uri
	 */
	protected $requestUri;

	/**
	 * The base URI for this request
	 *
	 * @var t3lib_webservice_Uri
	 */
	protected $baseUri;

	/**
	 * @param array $resolvedArguments
	 */
	public function __construct(array $resolvedArguments = array()) {
		$this->resolvedArguments = $resolvedArguments;
	}

	/**
	 * Sets $acceptHeaders
	 *
	 * @param array $acceptHeaders
	 */
	public function setAcceptHeaders($acceptHeaders) {
		$this->acceptHeaders = $acceptHeaders;
	}

	/**
	 * Returns $acceptHeaders
	 *
	 * @return array
	 */
	public function getAcceptHeaders() {
		return $this->acceptHeaders;
	}

	/**
	 * Sets the Request URI
	 *
	 * @param t3lib_webservice_uri $requestUri
	 * @return void
	 */
	public function setRequestUri($requestUri) {
		$this->requestUri = $requestUri;
	}

	/**
	 * Returns the request URI
	 *
	 * @return t3lib_webservice_uri URI of this web request
	 * @api
	 */
	public function getRequestUri() {
		return $this->requestUri;
	}

	/**
	 * Sets the Base URI
	 *
	 * @param t3lib_webservice_uri $baseUri
	 * @return void
	 */
	public function setBaseUri(t3lib_webservice_Uri $baseUri) {
		$this->baseUri = $baseUri;
	}

	/**
	 * Returns the base URI
	 *
	 * @return t3lib_webservice_Uri URI of this web request
	 * @api
	 */
	public function getBaseUri() {
		return $this->baseUri;
	}

	/**
	 * Sets the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @throws t3lib_error_http_BadRequestException if the request method is not supported
	 * @api
	 */
	public function setMethod($method) {
		if ($method === '') {
			throw new t3lib_error_http_BadRequestException('The request method "' . $method . '" is not supported.', 1310140095);
		}
		$this->method = $method;
	}

	/**
	 * Returns the name of the request method
	 *
	 * @return string Name of the request method
	 * @api
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @param string $body
	 * @return void
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @return array
	 */
	public function getResolvedArguments() {
		return $this->resolvedArguments;
	}


	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @return void
	 * @throws InvalidArgumentException if the given argument name is no string
	 * @throws InvalidArgumentException if the given argument value is an object
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || strlen($argumentName) === 0) throw new InvalidArgumentException('Invalid argument name (must be a non-empty string).', 1316373903);

		if (is_object($value)) throw new InvalidArgumentException('You are not allowed to store objects in the request arguments. Please convert the object of type "' . get_class($value) . '" given for argument "' . $argumentName . '" to a simple type first.', 1316373910);

		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the specified arguments.
	 * The arguments array will be reset therefore any arguments
	 * which existed before will be overwritten!
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 */
	public function setArguments(array $arguments) {
		$this->arguments = array();
		foreach ($arguments as $key => $value) {
			$this->setArgument($key, $value);
		}
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @api
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new InvalidArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1316373820);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 * @api
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Returns an Array of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

}

?>
