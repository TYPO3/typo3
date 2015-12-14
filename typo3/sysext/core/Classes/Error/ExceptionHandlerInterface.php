<?php
namespace TYPO3\CMS\Core\Error;

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

/**
 * Contract for an exception handler
 *
 * This file is a backport from TYPO3 Flow
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
     * @param \Exception|\Throwable $exception The exception(PHP 5.x) or throwable(PHP >= 7.0) object.
     * @return void
     * @TODO #72293 This will change to \Throwable only if we are >= PHP7.0 only
     */
    public function handleException($exception);

    /**
     * Formats and echoes the exception as XHTML.
     *
     * @param \Exception|\Throwable $exception The exception(PHP 5.x) or throwable(PHP >= 7.0) object.
     * @return void
     * @TODO #72293 This will change to \Throwable only if we are >= PHP7.0 only
     */
    public function echoExceptionWeb($exception);

    /**
     * Formats and echoes the exception for the command line
     *
     * @param \Exception|\Throwable $exception The exception(PHP 5.x) or throwable(PHP >= 7.0) object.
     * @return void
     * @TODO #72293 This will change to \Throwable only if we are >= PHP7.0 only
     */
    public function echoExceptionCLI($exception);
}
