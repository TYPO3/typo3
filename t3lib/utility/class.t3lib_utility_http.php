<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
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
 * HTTP Utility class
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_utility_Http {

		// HTTP Headers, see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for Details
	const HTTP_STATUS_100 = 'HTTP/1.1 100 Continue';
	const HTTP_STATUS_101 = 'HTTP/1.1 101 Switching Protocols';

	const HTTP_STATUS_200 = 'HTTP/1.1 200 OK';
	const HTTP_STATUS_201 = 'HTTP/1.1 201 Created';
	const HTTP_STATUS_202 = 'HTTP/1.1 202 Accepted';
	const HTTP_STATUS_203 = 'HTTP/1.1 203 Non-Authoritative Information';
	const HTTP_STATUS_204 = 'HTTP/1.1 204 No Content';
	const HTTP_STATUS_205 = 'HTTP/1.1 205 Reset Content';
	const HTTP_STATUS_206 = 'HTTP/1.1 206 Partial Content';

	const HTTP_STATUS_300 = 'HTTP/1.1 300 Multiple Choices';
	const HTTP_STATUS_301 = 'HTTP/1.1 301 Moved Permanently';
	const HTTP_STATUS_302 = 'HTTP/1.1 302 Found';
	const HTTP_STATUS_303 = 'HTTP/1.1 303 See Other';
	const HTTP_STATUS_304 = 'HTTP/1.1 304 Not Modified';
	const HTTP_STATUS_305 = 'HTTP/1.1 305 Use Proxy';
	const HTTP_STATUS_307 = 'HTTP/1.1 307 Temporary Redirect';

	const HTTP_STATUS_400 = 'HTTP/1.1 400 Bad Request';
	const HTTP_STATUS_401 = 'HTTP/1.1 401 Unauthorized';
	const HTTP_STATUS_402 = 'HTTP/1.1 402 Payment Required';
	const HTTP_STATUS_403 = 'HTTP/1.1 403 Forbidden';
	const HTTP_STATUS_404 = 'HTTP/1.1 404 Not Found';
	const HTTP_STATUS_405 = 'HTTP/1.1 405 Method Not Allowed';
	const HTTP_STATUS_406 = 'HTTP/1.1 406 Not Acceptable';
	const HTTP_STATUS_407 = 'HTTP/1.1 407 Proxy Authentication Required';
	const HTTP_STATUS_408 = 'HTTP/1.1 408 Request Timeout';
	const HTTP_STATUS_409 = 'HTTP/1.1 409 Conflict';
	const HTTP_STATUS_410 = 'HTTP/1.1 410 Gone';
	const HTTP_STATUS_411 = 'HTTP/1.1 411 Length Required';
	const HTTP_STATUS_412 = 'HTTP/1.1 412 Precondition Failed';
	const HTTP_STATUS_413 = 'HTTP/1.1 413 Request Entity Too Large';
	const HTTP_STATUS_414 = 'HTTP/1.1 414 Request-URI Too Long';
	const HTTP_STATUS_415 = 'HTTP/1.1 415 Unsupported Media Type';
	const HTTP_STATUS_416 = 'HTTP/1.1 416 Requested Range Not Satisfiable';
	const HTTP_STATUS_417 = 'HTTP/1.1 417 Expectation Failed';

	const HTTP_STATUS_500 = 'HTTP/1.1 500 Internal Server Error';
	const HTTP_STATUS_501 = 'HTTP/1.1 501 Not Implemented';
	const HTTP_STATUS_502 = 'HTTP/1.1 502 Bad Gateway';
	const HTTP_STATUS_503 = 'HTTP/1.1 503 Service Unavailable';
	const HTTP_STATUS_504 = 'HTTP/1.1 504 Gateway Timeout';
	const HTTP_STATUS_505 = 'HTTP/1.1 505 Version Not Supported';

	/**
	 * Sends a redirect header response and exits. Additionaly the URL is
	 * checked and if needed corrected to match the format required for a
	 * Location redirect header. By default the HTTP status code sent is
	 * a 'HTTP/1.1 303 See Other'.
	 *
	 * @param	string	The target URL to redirect to
	 * @param	string	An optional HTTP status header. Default is 'HTTP/1.1 303 See Other'
	 */
	public static function redirect($url, $httpStatus = self::HTTP_STATUS_303) {
		header($httpStatus);
		header('Location: ' . t3lib_div::locationHeaderUrl($url));

		exit;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/utility/class.t3lib_utility_http.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/utility/class.t3lib_utility_http.php']);
}

?>