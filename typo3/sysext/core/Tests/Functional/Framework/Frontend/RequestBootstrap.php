<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Bootstrap for direct CLI Request
 */
class RequestBootstrap {

	/**
	 * @return void
	 */
	static public function setGlobalVariables() {
		if (empty($_SERVER['argv'][1]) || ($requestArguments = json_decode($_SERVER['argv'][1], TRUE)) === FALSE) {
			die('No JSON encoded arguments given');
		}

		if (empty($requestArguments['documentRoot'])) {
			die('No documentRoot given');
		}

		if (empty($requestArguments['requestUrl']) || ($requestUrlParts = parse_url($requestArguments['requestUrl'])) === FALSE) {
			die('No valid request URL given');
		}

		// Populating $_GET and $_REQUEST is query part is set:
		if (isset($requestUrlParts['query'])) {
			parse_str($requestUrlParts['query'], $_GET);
			parse_str($requestUrlParts['query'], $_REQUEST);
		}

		// Populating $_POST
		$_POST = array();
		// Populating $_COOKIE
		$_COOKIE = array();

		// Setting up the server environment
		$_SERVER = array();
		$_SERVER['DOCUMENT_ROOT'] = $requestArguments['documentRoot'];
		$_SERVER['HTTP_USER_AGENT'] = 'TYPO3 Functional Test Request';
		$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = $requestUrlParts['host'];
		$_SERVER['SERVER_ADDR'] = $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['SCRIPT_FILENAME'] = $_SERVER['_'] = $_SERVER['PATH_TRANSLATED'] = $requestArguments['documentRoot'] . '/index.php';
		$_SERVER['QUERY_STRING'] = (isset($requestUrlParts['query']) ? $requestUrlParts['query'] : '');
		$_SERVER['REQUEST_URI'] = $requestUrlParts['path'] . (isset($requestUrlParts['query']) ? '?' . $requestUrlParts['query'] : '');
		$_SERVER['REQUEST_METHOD'] = 'GET';

		// Define a port if used in the URL:
		if (isset($requestUrlParts['port'])) {
			$_SERVER['SERVER_PORT'] = $requestUrlParts['port'];
		}
		// Define HTTPS disposal:
		if ($requestUrlParts['scheme'] === 'https') {
			$_SERVER['HTTPS'] = 'on';
		}

		if (!is_dir($_SERVER['DOCUMENT_ROOT'])) {
			die('Document root directory "' . $_SERVER['SCRIPT_FILENAME'] . '" does not exist');
		}

		if (!is_file($_SERVER['SCRIPT_FILENAME'])) {
			die('Script file "' . $_SERVER['SCRIPT_FILENAME'] . '" does not exist');
		}
	}

	/**
	 * @return void
	 */
	static public function executeAndOutput() {
		global $TT, $TSFE, $TYPO3_CONF_VARS, $BE_USER, $TYPO3_MISC;

		$result = array('status' => 'failure', 'content' => NULL, 'error' => NULL);

		ob_start();
		try {
			chdir($_SERVER['DOCUMENT_ROOT']);
			include($_SERVER['SCRIPT_FILENAME']);
			$result['status'] = 'success';
			$result['content'] = ob_get_contents();
		} catch(\Exception $exception) {
			$result['error'] = $exception->__toString();
		}
		ob_end_clean();

		echo json_encode($result);
	}

}
