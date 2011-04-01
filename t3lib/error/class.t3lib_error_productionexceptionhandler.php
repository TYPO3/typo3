<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * A quite exception handler which catches but ignores any exception.
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_error
 */
class t3lib_error_ProductionExceptionHandler extends t3lib_error_AbstractExceptionHandler {

	/**
	 * Constructs this exception handler - registers itself as the default exception handler.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		set_exception_handler(array($this, 'handleException'));
	}

	/**
	 * Echoes an exception for the web.
	 *
	 * @param Exception $exception The exception
	 * @return void
	 */
	public function echoExceptionWeb(Exception $exception) {
		$this->sendStatusHeader($exception);

		$this->writeLogEntries($exception, self::CONTEXT_WEB);

		if ($exception->getCode() > 0) {
			$moreInformationLink = '<p>More information regarding this error might be available <a href="'
								  . TYPO3_URL_EXCEPTION . $exception->getCode() . '" target="_blank">online</a>.</p>';
		} else {
			$moreInformationLink = '';
		}

		$title = 'Oops, an error occurred!';

		if (method_exists($exception, 'getTitle') && strlen($exception->getTitle()) > 0) {
			$title = $exception->getTitle();
		}
			// we use a nice-looking title for our visitors instead of the exception's class name
		$messageObj = t3lib_div::makeInstance('t3lib_message_ErrorPageMessage', $exception->getMessage() . $moreInformationLink, $title);
		$messageObj->output();
	}

	/**
	 * Echoes an exception for the command line.
	 *
	 * @param Exception $exception The exception
	 * @return void
	 */
	public function echoExceptionCLI(Exception $exception) {
		$this->writeLogEntries($exception, self::CONTEXT_CLI);
		exit(1);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/class.t3lib_error_productionexceptionhandler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/class.t3lib_error_productionexceptionhandler.php']);
}

?>