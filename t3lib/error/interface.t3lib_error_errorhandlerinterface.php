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
 * Error handler interface for TYPO3
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_error
 */
interface t3lib_error_ErrorHandlerInterface {

	/**
	 * Registers this class as default error handler
	 *
	 * @param integer	 The integer representing the E_* error level which should be
	 *					 handled by the registered error handler.
	 * @return void
	 */
	public function __construct($errorHandlerErrors);

	/**
	 * Defines which error levels should result in an exception thrown.
	 *
	 * @param integer	 The integer representing the E_* error level to handle as exceptions
	 * @return void
	 */
	public function setExceptionalErrors($exceptionalErrors);

	/**
	 * Handles an error.
	 * If the error is registered as exceptionalError it will by converted into an exception, to be handled
	 * by the configured exceptionhandler. Additionall the error message is written to the configured logs.
	 * If TYPO3_MODE is 'BE' the error message is also added to the flashMessageQueue, in FE the error message
	 * is displayed in the admin panel (as TsLog message)
	 *
	 * @param integer	 The error level - one of the E_* constants
	 * @param string	 The error message
	 * @param string	 Name of the file the error occurred in
	 * @param integer	 Line number where the error occurred
	 * @return void
	 * @throws t3lib_error_Exception with the data passed to this method if the error is registered as exceptionalError
	 */
	public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine);
}

?>