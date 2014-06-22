<?php
namespace TYPO3\CMS\Core\Error;

/**
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
/**
 * Contract for an exception handler
 *
 * This file is a backport from FLOW3
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
interface ExceptionHandlerInterface
{
	/**
	 * Constructs this exception handler - registers itself as the default exception handler.
	 */
	public function __construct();

	/**
	 * Handles the given exception
	 *
	 * @param \Exception $exception: The exception object
	 * @return void
	 */
	public function handleException(\Exception $exception);

	/**
	 * Formats and echoes the exception as XHTML.
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 */
	public function echoExceptionWeb(\Exception $exception);

	/**
	 * Formats and echoes the exception for the command line
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 */
	public function echoExceptionCLI(\Exception $exception);

}
