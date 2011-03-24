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
 * Contract for an exception handler
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_error
 */
interface t3lib_error_ExceptionHandlerInterface {

	/**
	 * Constructs this exception handler - registers itself as the default exception handler.
	 */
	public function __construct();

	/**
	 * Handles the given exception
	 *
	 * @param Exception $exception: The exception object
	 * @return void
	 */
	public function handleException(Exception $exception);

	/**
	 * Formats and echoes the exception as XHTML.
	 *
	 * @param  Exception $exception The exception object
	 * @return void
	 */
	public function echoExceptionWeb(Exception $exception);

	/**
	 * Formats and echoes the exception for the command line
	 *
	 * @param Exception $exception The exception object
	 * @return void
	 */
	public function echoExceptionCLI(Exception $exception);

}

?>