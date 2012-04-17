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
	 * Default title for error messages
	 * @var string
	 */
	protected $defaultTitle = 'Oops, an error occurred!';

	/**
	 * Default message for error messages
	 * @var string
	 */
	protected $defaultMessage = '';

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
		$this->sendStatusHeaders($exception);

		$this->writeLogEntries($exception, self::CONTEXT_WEB);

		$messageObj = t3lib_div::makeInstance(
			't3lib_message_ErrorPageMessage',
			$this->getMessage($exception),
			$this->getTitle($exception)
		);
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

	/**
	 * Determines, whether Exception details should be outputted
	 *
	 * @param Exception $exception The exception
	 * @return bool
	 */
	protected function discloseExceptionInformation(Exception $exception) {
			// Show client error messages 40x in every case
		if ($exception instanceof t3lib_error_http_AbstractClientErrorException) {
			return TRUE;
		}

			// only show errors in FE, if a BE user is authenticated
		if (TYPO3_MODE === 'FE') {
			return $GLOBALS['TSFE']->beUserLogin;
		}

		return TRUE;
	}

	/**
	 * Returns the title for the error message
	 *
	 * @param Exception $exception Exception causing the error
	 * @return string
	 */
	protected function getTitle(Exception $exception) {
		if ($this->discloseExceptionInformation($exception)
			&& method_exists($exception, 'getTitle')
			&& strlen($exception->getTitle()) > 0) {

			return htmlspecialchars($exception->getTitle());
		} else {
			return $this->defaultTitle;
		}
	}

	/**
	 * Returns the message for the error message
	 *
	 * @param Exception $exception Exception causing the error
	 * @return string
	 */
	protected function getMessage(Exception $exception) {
		if ($this->discloseExceptionInformation($exception)) {
				// Exception has an error code given
			if ($exception->getCode() > 0) {
				$moreInformationLink = '<p>More information regarding this error might be available <a href="' .
					TYPO3_URL_EXCEPTION . $exception->getCode() . '" target="_blank">online</a>.</p>';
			} else {
				$moreInformationLink = '';
			}
			return htmlspecialchars($exception->getMessage()) . $moreInformationLink;
		} else {
			return $this->defaultMessage;
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/class.t3lib_error_productionexceptionhandler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/class.t3lib_error_productionexceptionhandler.php']);
}

?>