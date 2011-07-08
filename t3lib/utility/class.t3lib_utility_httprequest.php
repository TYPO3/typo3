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


include_once(PATH_typo3 . 'contrib/HTTP/Request2.php');

/**
 * HTTP Request Utility class
 *
 * Extends HTTP_Request2 and sets TYPO3 environment defaults
 *
 * @author	Philipp Gampe <dev.typo3@philippgampe.info>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_utility_httpRequest extends HTTP_Request2 {

	/**
	 * Set default values as defined in localconf.php or config_default.php
	 *
	 * @return void
	 */
	public function setDefaultValues() {
			// set a branded user-agent
		$this->setHeader('user-agent', TYPO3_user_agent);

			// set defaults from localconf
		$this->setConfig(array(
			'follow_redirects' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['httpFollowRedirects'],
			'proxy_host' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['httpProxyHost'],
			'max_redirects' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['httpMaxRedirects'],
			'proxy_port' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['httpProxyPort'],
			'connect_timeout' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['httpConnectionTimeout'],
			'proxy_user' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['httpProxyUser'],
			'timeout' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['httpTimeout'],
			'proxy_password' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['httpProxyPass'],
			'proxy_auth_scheme' => ($GLOBALS['TYPO3_CONF_VARS']['SYS']['httpProxyMethod'] === 'digest') ?
				HTTP_Request2::AUTH_DIGEST : HTTP_Request2::AUTH_BASIC
			));

		$this->setAdapter(!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse']) ? 'curl' : 'socket');
	}

	/**
	 * @param string|Net_Url2 $url Request URL
	 * @param sting $method Request Method (GET, HEAD or POST). Redirects reset this to GET unless "strict_redirects" is set.
	 * @param array $config Configuration for this request instance
	 * @link http://pear.php.net/manual/en/package.http.http-request2.config.php
	 */
	public function __construct($url = null, $method = self::METHOD_GET, array $config = array()) {

		parent::__construct($url, $method);
		$this->setDefaultValues();
		$this->setConfig($config);
	}

}

?>