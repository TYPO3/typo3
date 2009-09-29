<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
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
 * Global error handler for TYPO3
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_error
 * @version $Id: ErrorHandler.php 3195 2009-09-17 11:27:14Z k-fish $
 */
class t3lib_error_ErrorHandler implements t3lib_error_ErrorHandlerInterface {

	/**
	 * @var array
	 */
	protected $exceptionalErrors = array();

	/**
	 * Defines which error levels result should result in an exception thrown.
	 * Registers this class as default error handler
	 *
	 * @param integer $exceptionalErrors The integer representing the E_* error level to handle as exceptions
	 * @return void
	 */
	public function setErrorHandlerForExceptionalErrors($exceptionalErrors) {
		$this->exceptionalErrors = (int)$exceptionalErrors;
		set_error_handler(array($this, 'handleError'), $this->exceptionalErrors);
	}

	/**
	 * Handles an error by converting it into an exception
	 *
	 * @param integer $errorLevel The error level - one of the E_* constants
	 * @param string $errorMessage The error message
	 * @param string $errorFile Name of the file the error occurred in
	 * @param integer $errorLine Line number where the error occurred
	 * @return void
	 * @throws t3lib_error_Exception with the data passed to this method
	 */
	public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine) {
		if (error_reporting()==0) { return TRUE; }
		$errorLevels = array (
			E_WARNING            => 'Warning',
			E_NOTICE             => 'Notice',
			E_USER_ERROR         => 'User Error',
			E_USER_WARNING       => 'User Warning',
			E_USER_NOTICE        => 'User Notice',
			E_STRICT             => 'Runtime Notice',
			E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
		);

		if ($errorLevel & $this->exceptionalErrors) {
			throw new t3lib_error_Exception($errorLevels[$errorLevel] . ': ' . $errorMessage . ' in ' . $errorFile . ' line ' . $errorLine, 1);
		}
			// Don't execute PHP internal error handler
		return TRUE;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/error/class.t3lib_error_errorhandler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/error/class.t3lib_error_errorhandler.php']);
}

?>