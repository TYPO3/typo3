<?php
namespace TYPO3\CMS\Core\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
/**
 * This class encapsulates cli specific bootstrap methods.
 *
 * This script is internal code and subject to change.
 * DO NOT use it in own code, or be prepared your code might
 * break in future core versions.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class CliBootstrap {

	/**
	 * Check the script is called from a cli environment.
	 *
	 * @return void
	 * @internal This is not a public API method, do not use in own extensions
	 */
	static public function checkEnvironmentOrDie() {
		if (substr(php_sapi_name(), 0, 3) === 'cgi') {
			self::initializeCgiCompatibilityLayerOrDie();
		} elseif (php_sapi_name() !== 'cli') {
			die('Not called from a command line interface (e.g. a shell or scheduler).' . chr(10));
		}
	}

	/**
	 * Check and define cli parameters.
	 * First argument is a key that points to the script configuration.
	 * If it is not set or not valid, the script exits with an error message.
	 *
	 * @return void
	 * @internal This is not a public API method, do not use in own extensions
	 */
	static public function initializeCliKeyOrDie() {
		if (!isset($_SERVER['argv'][1]) || !is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$_SERVER['argv'][1]])) {
			if (!isset($_SERVER['argv'][1])) {
				$message = 'This script must have a \'cliKey\' as first argument.';
			} else {
				$message = 'The supplied \'cliKey\' is not valid.';
			}
			$message .= ' Valid keys are:

';
			$cliKeys = array_keys($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']);
			asort($cliKeys);
			foreach ($cliKeys as $key => $value) {
				$message .= '  ' . $value . LF;
			}
			fwrite(STDERR, $message . LF);
			die(1);
		}
		define('TYPO3_cliKey', $_SERVER['argv'][1]);
		define('TYPO3_cliInclude', \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][TYPO3_cliKey][0]));
		$GLOBALS['MCONF']['name'] = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][TYPO3_cliKey][1];
		// This is a compatibility layer: Some cli scripts rely on this, like ext:phpunit cli
		$GLOBALS['temp_cliScriptPath'] = array_shift($_SERVER['argv']);
		$GLOBALS['temp_cliKey'] = array_shift($_SERVER['argv']);
		array_unshift($_SERVER['argv'], $GLOBALS['temp_cliScriptPath']);
	}

	/**
	 * Set up cgi sapi as de facto cli, but check no HTTP
	 * environment variables are set.
	 *
	 * @return void
	 */
	static protected function initializeCgiCompatibilityLayerOrDie() {
		// Sanity check: Ensure we're running in a shell or cronjob (and NOT via HTTP)
		$checkEnvVars = array('HTTP_USER_AGENT', 'HTTP_HOST', 'SERVER_NAME', 'REMOTE_ADDR', 'REMOTE_PORT', 'SERVER_PROTOCOL');
		foreach ($checkEnvVars as $var) {
			if (array_key_exists($var, $_SERVER)) {
				echo 'SECURITY CHECK FAILED! This script cannot be used within your browser!' . chr(10);
				echo 'If you are sure that we run in a shell or cronjob, please unset' . chr(10);
				echo 'environment variable ' . $var . ' (usually using \'unset ' . $var . '\')' . chr(10);
				echo 'before starting this script.' . chr(10);
				die;
			}
		}
		// Mimic CLI API in CGI API (you must use the -C/-no-chdir and the -q/--no-header switches!)
		ini_set('html_errors', 0);
		ini_set('implicit_flush', 1);
		ini_set('max_execution_time', 0);
		define(STDIN, fopen('php://stdin', 'r'));
		define(STDOUT, fopen('php://stdout', 'w'));
		define(STDERR, fopen('php://stderr', 'w'));
	}

}


?>