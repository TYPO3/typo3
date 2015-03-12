<?php
namespace TYPO3\CMS\Frontend;

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
use TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Frontend\Utility\EidUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Core\RequestHandlerInterface;

/**
 * Lightweight alternative to the regular RequestHandler used when $_GET[eID] is set.
 * In the future, logic from the EidUtility will be moved to this class.
 */
class EidRequestHandler implements RequestHandlerInterface {

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
	 * Handles a frontend request based on the _GP "eID" variable.
	 *
	 * @return void
	 */
	public function handleRequest() {
		// Timetracking started
		$configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']);
		if (empty($configuredCookieName)) {
			$configuredCookieName = 'be_typo_user';
		}
		if ($_COOKIE[$configuredCookieName]) {
			$GLOBALS['TT'] = new TimeTracker();
		} else {
			$GLOBALS['TT'] = new NullTimeTracker();
		}

		$GLOBALS['TT']->start();

		// Hook to preprocess the current request
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'] as $hookFunction) {
				$hookParameters = array();
				GeneralUtility::callUserFunction($hookFunction, $hookParameters, $hookParameters);
			}
			unset($hookFunction);
			unset($hookParameters);
		}

		// Remove any output produced until now
		$this->bootstrap->endOutputBufferingAndCleanPreviousOutput();
		require EidUtility::getEidScriptPath();
		$this->bootstrap->shutdown();
		exit;
	}

	/**
	 * This request handler can handle any frontend request.
	 *
	 * @return bool If the request is not an eID request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return GeneralUtility::_GP('eID') ? TRUE : FALSE;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return int The priority of the request handler.
	 */
	public function getPriority() {
		return 80;
	}
}
