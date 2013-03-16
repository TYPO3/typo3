<?php
namespace TYPO3\CMS\Core\Http;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Philipp Gampe <philipp.gampe@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once('HTTP/Request2.php');

/**
 * HTTP Request Utility class
 *
 * Extends \HTTP_Request2 and sets TYPO3 environment defaults
 *
 * @author Philipp Gampe <philipp.gampe@typo3.org>
 */
class HttpRequest extends \HTTP_Request2 {

	/**
	 * Default constructor - sets TYPO3 defaults
	 *
	 * @param string|\Net_Url2 $url Request URL
	 * @param string $method Request Method (GET, HEAD or POST). Redirects reset this to GET unless "strict_redirects" is set.
	 * @param array $config Configuration for this request instance
	 * @link http://pear.php.net/manual/en/package.http.http-request2.config.php
	 */
	public function __construct($url = NULL, $method = self::METHOD_GET, array $config = array()) {
		parent::__construct($url, $method);
		$this->setConfiguration($config);
	}

	/**
	 * Sets the configuration for this object instance.
	 * Merges default values with provided $config and overrides all
	 * not provided values with those from $TYPO3_CONF_VARS
	 *
	 * @param array $config Configuration options which override the default configuration
	 * @return void
	 * @see http://pear.php.net/manual/en/package.http.http-request2.config.php
	 */
	public function setConfiguration(array $config = array()) {
		// set a branded user-agent
		$this->setHeader('user-agent', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']);
		$default = array(
			'adapter' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['adapter'],
			'connect_timeout' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['connect_timeout'],
			'timeout' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'],
			'protocol_version' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['protocol_version'],
			'follow_redirects' => (bool) $GLOBALS['TYPO3_CONF_VARS']['HTTP']['follow_redirects'],
			'max_redirects' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['max_redirects'],
			'strict_redirects' => (bool) $GLOBALS['TYPO3_CONF_VARS']['HTTP']['strict_redirects'],
			'proxy_host' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'],
			'proxy_port' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port'],
			'proxy_user' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user'],
			'proxy_password' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_password'],
			'proxy_auth_scheme' => $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'],
			'ssl_verify_peer' => (bool) $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer'],
			'ssl_verify_host' => (bool) $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host'],
			// we have to deal with Install Tool limitations and set this to NULL if it is empty
			'ssl_cafile' => empty($GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_cafile']) ? NULL : $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_cafile'],
			'ssl_capath' => empty($GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_capath']) ? NULL : $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_capath'],
			'ssl_local_cert' => empty($GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_local_cert']) ? NULL : $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_local_cert'],
			'ssl_passphrase' => empty($GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_passphrase']) ? NULL : $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_passphrase']
		);
		$configuration = array_merge($default, $config);
		$this->setConfig($configuration);
	}

	/**
	 * Downloads chunk by chunk to file instead of saving the whole response into memory.
	 * $response->getBody() will be empty.
	 * An existing file will be overridden.
	 *
	 * @param string $directory The absolute path to the directory in which the file is saved.
	 * @param string $filename The filename - if not set, it is determined automatically.
	 * @return \HTTP_Request2_Response The response with empty body.
	 */
	public function download($directory, $filename = '') {
		$isAttached = FALSE;
		// Do not store the body in memory
		$this->setConfig('store_body', FALSE);
		// Check if we already attached an instance of download. If so, just reuse it.
		foreach ($this->observers as $observer) {
			if ($observer instanceof \TYPO3\CMS\Core\Http\Observer\Download) {
				/** @var \TYPO3\CMS\Core\Http\Observer\Download $attached */
				$observer->setDirectory($directory);
				$observer->setFilename($filename);
				$isAttached = TRUE;
			}
		}
		if (!$isAttached) {
			/** @var \TYPO3\CMS\Core\Http\Observer\Download $observer */
			$observer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Http\\Observer\\Download', $directory, $filename);
			$this->attach($observer);
		}
		return $this->send();
	}

}

?>