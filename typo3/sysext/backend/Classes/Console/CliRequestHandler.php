<?php
namespace TYPO3\CMS\Backend\Console;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Console\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command Line Interface Request Handler dealing with "cliKey"-based Commands from the cli_dispatch.phpsh script.
 * Picks up requests only when coming from the CLI mode.
 * Resolves the "cliKey" which is registered inside $TYPO3_CONF_VARS[SC_OPTIONS][GLOBAL][cliKeys]
 * and includes the CLI-based script or exits if no valid "cliKey" is found.
 */
class CliRequestHandler implements RequestHandlerInterface {

	/**
	 * Instance of the current TYPO3 bootstrap
	 * @var Bootstrap
	 */
	protected $bootstrap;

	/**
	 * Constructor handing over the bootstrap
	 *
	 * @param Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles any commandline request
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $request
	 * @return void
	 */
	public function handleRequest(\Symfony\Component\Console\Input\InputInterface $request) {
		$commandLineKey = $this->getCommandLineKeyOrDie();
		$commandLineScript = $this->getIncludeScriptByCommandLineKey($commandLineKey);

		$this->boot();

		try {
			include($commandLineScript);
		} catch (\Exception $e) {
			fwrite(STDERR, $e->getMessage() . LF);
			exit(99);
		}
	}

	/**
	 * Execute TYPO3 bootstrap
	 */
	protected function boot() {
		// Evaluate the constant for skipping the BE user check for the bootstrap
		if (defined('TYPO3_PROCEED_IF_NO_USER') && TYPO3_PROCEED_IF_NO_USER) {
			$proceedIfNoUserIsLoggedIn = TRUE;
		} else {
			$proceedIfNoUserIsLoggedIn = FALSE;
		}

		$this->bootstrap
			->loadExtensionTables(TRUE)
			->initializeBackendUser()
			->initializeBackendAuthentication($proceedIfNoUserIsLoggedIn)
			->initializeLanguageObject();

		// Make sure output is not buffered, so command-line output and interaction can take place
		GeneralUtility::flushOutputBuffers();
	}

	/**
	 * Check CLI parameters.
	 * First argument is a key that points to the script configuration.
	 * If it is not set or not valid, the script exits with an error message.
	 *
	 * @return string the CLI key in use
	 */
	protected function getCommandLineKeyOrDie() {
		$cliKey = $_SERVER['argv'][1];
		$errorMessage = '';
		if (empty($cliKey)) {
			$errorMessage = 'This script must have a \'cliKey\' as first argument.';
		} elseif (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$cliKey])) {
			$errorMessage = 'The supplied \'cliKey\' is not valid.';
		}

		// exit with an error message
		if (!empty($errorMessage)) {
			$errorMessage .= ' Valid keys are:

';
			$cliKeys = array_keys($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']);
			asort($cliKeys);
			foreach ($cliKeys as $key => $value) {
				$errorMessage .= '  ' . $value . LF;
			}
			fwrite(STDERR, $errorMessage . LF);
			die(1);
		}

		return $cliKey;
	}

	/**
	 * Define cli-related parameters and return the include script.
	 *
	 * @param string $cliKey the CLI key
	 * @return string the absolute path to the include script
	 */
	protected function getIncludeScriptByCommandLineKey($cliKey) {
		list($commandLineScript, $commandLineName) = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$cliKey];
		$commandLineScript = GeneralUtility::getFileAbsFileName($commandLineScript);
		// Note: These constants are not in use anymore
		define('TYPO3_cliKey', $cliKey);
		define('TYPO3_cliInclude', $commandLineScript);
		$GLOBALS['MCONF']['name'] = $commandLineName;
		// This is a compatibility layer: Some cli scripts rely on this, like ext:phpunit cli
		$GLOBALS['temp_cliScriptPath'] = array_shift($_SERVER['argv']);
		$GLOBALS['temp_cliKey'] = array_shift($_SERVER['argv']);
		array_unshift($_SERVER['argv'], $GLOBALS['temp_cliScriptPath']);
		return $commandLineScript;
	}

	/**
	 * This request handler can handle any CLI request.
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $request
	 * @return bool If the request is a CLI request, TRUE otherwise FALSE
	 */
	public function canHandleRequest(\Symfony\Component\Console\Input\InputInterface $request) {
		return defined('TYPO3_cliMode') && (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE) && (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI);
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the request.
	 *
	 * @return int The priority of the request handler.
	 */
	public function getPriority() {
		return 50;
	}
}
