<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Philipp Gampe <dev.typo3@philippgampe.info>
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

require_once(PATH_typo3 . 'contrib/pear/HTTP/Request2.php');

/**
 * HTTP Request Utility class
 *
 * Extends HTTP_Request2 and sets TYPO3 environment defaults
 *
 * @author	Philipp Gampe <dev.typo3@philippgampe.info>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_service_HttpRequest extends HTTP_Request2 {

	/**
	 * Default constructor - sets TYPO3 defaults
	 *
	 * @param string|Net_Url2 $url Request URL
	 * @param string $method Request Method (GET, HEAD or POST). Redirects reset this to GET unless "strict_redirects" is set.
	 * @param array $config Configuration for this request instance
	 * @link http://pear.php.net/manual/en/package.http.http-request2.config.php
	 */
	public function __construct($url = NULL, $method = self::METHOD_GET, array $config = array()) {

		parent::__construct($url, $method);
		$this->setConfiguration($config);
	}

	/**
	* Sets the configuration
	* Merges default values with provided $config and overrides all not provided values
	* with there defaults from localconf.php or config_default.php.
	*
	* @param array $config Configuration options which override the default configuration
	* @return void
	* @link http://pear.php.net/manual/en/package.http.http-request2.config.php
	*/
	public function setConfiguration(array $config = array()) {
		// set a branded user-agent
		$this->setHeader('user-agent', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']);

		// set defaults from localconf.php or config_default.php
		$default = array(
			'adapter' => 			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['adapter'],
			'connect_timeout' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['connect_timeout'],
			'timeout' => 			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'],
			'protocol_version' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['protocol_version'],

			'follow_redirects' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['follow_redirects'],
			'max_redirects' => 		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['max_redirects'],
			'strict_redirects' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['strict_redirects'],

			'proxy_host' => 		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'],
			'proxy_port' => 		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port'],
			'proxy_user' => 		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user'],
			'proxy_password' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_password'],
			'proxy_auth_scheme' => ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] === 'digest') ? 'digest' : 'basic',

			'ssl_verify_peer' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer'],
			'ssl_verify_host' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host'],
			'ssl_cafile' => 		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_cafile'],
			'ssl_capath' => 		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_capath'],
			'ssl_local_cert' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_local_cert'],
			'ssl_passphrase' => 	$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_passphrase'],
			);

		$configuration = array_merge($default, $config);

		$this->setConfig($configuration);

	}

	/**
	 * Default send method
	 * Wrapped with try/catch to convert exceptions to TYPO3 style exceptions
	 *
	 * @throws t3lib_exception
	 * @return HTTP_Request2_Response
	 */
	public function send() {
		try {
			return parent::send();
		} catch (HTTP_Request2_Exception $e) {
			throw new t3lib_exception($e);
		}
	}

}

?>