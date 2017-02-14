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
     * @param \Throwable $exception The throwable object.
     */
    public function handleException(\Throwable $exception);

    /**
     * Formats and echoes the exception as XHTML.
     *
     * @param \Throwable $exception The throwable object.
     */
    public function echoExceptionWeb(\Throwable $exception);

    /**
     * Formats and echoes the exception for the command line
     *
     * @param \Throwable $exception The throwable object.
     */
    public function echoExceptionCLI(\Throwable $exception);
}
