<?php

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

namespace TYPO3\CMS\Core\Error;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\Http\AbstractClientErrorException;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An exception handler which catches any exception and
 * renders an error page without backtrace (Web) or a slim
 * message on CLI.
 */
class ProductionExceptionHandler extends AbstractExceptionHandler
{
    /**
     * Default title for error messages
     *
     * @var string
     */
    protected $defaultTitle = 'Oops, an error occurred!';

    /**
     * Default message for error messages
     *
     * @var string
     */
    protected $defaultMessage = '';

    /**
     * Constructs this exception handler - registers itself as the default exception handler.
     */
    public function __construct()
    {
        $callable = [$this, 'handleException'];
        if (is_callable($callable)) {
            set_exception_handler($callable);
        }
    }

    /**
     * Echoes an exception for the web.
     *
     * @param \Throwable $exception The throwable object.
     */
    public function echoExceptionWeb(\Throwable $exception)
    {
        $this->sendStatusHeaders($exception);
        $this->writeLogEntries($exception, self::CONTEXT_WEB);
        echo GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
            $this->getTitle($exception),
            $this->getMessage($exception),
            AbstractMessage::ERROR,
            $this->discloseExceptionInformation($exception) ? $exception->getCode() : 0,
            503
        );
    }

    /**
     * Echoes an exception for the command line.
     *
     * @param \Throwable $exception The throwable object.
     */
    public function echoExceptionCLI(\Throwable $exception)
    {
        $filePathAndName = $exception->getFile();
        $exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
        $this->writeLogEntries($exception, self::CONTEXT_CLI);
        echo LF . 'Uncaught TYPO3 Exception ' . $exceptionCodeNumber . $exception->getMessage() . LF;
        echo 'thrown in file ' . $filePathAndName . LF;
        echo 'in line ' . $exception->getLine() . LF . LF;
        die(1);
    }

    /**
     * Determines, whether Exception details should be outputted
     *
     * @param \Throwable $exception The throwable object.
     * @return bool
     */
    protected function discloseExceptionInformation(\Throwable $exception)
    {
        // Allow message to be shown in production mode if the exception is about
        // trusted host configuration.  By doing so we do not disclose
        // any valuable information to an attacker but avoid confusions among TYPO3 admins
        // in production context.
        if ($exception->getCode() === 1396795884) {
            return true;
        }
        // Show client error messages 40x in every case
        if ($exception instanceof AbstractClientErrorException) {
            return true;
        }
        // Only show errors if a BE user is authenticated
        $backendUser = $this->getBackendUser();
        if ($backendUser instanceof BackendUserAuthentication) {
            return ($backendUser->user['uid'] ?? 0) > 0;
        }
        return false;
    }

    /**
     * Returns the title for the error message
     *
     * @param \Throwable $exception The throwable object.
     * @return string
     */
    protected function getTitle(\Throwable $exception)
    {
        if ($this->discloseExceptionInformation($exception) && method_exists($exception, 'getTitle') && $exception->getTitle() !== '') {
            return $exception->getTitle();
        }
        return $this->defaultTitle;
    }

    /**
     * Returns the message for the error message
     *
     * @param \Throwable $exception The throwable object.
     * @return string
     */
    protected function getMessage(\Throwable $exception)
    {
        if ($this->discloseExceptionInformation($exception)) {
            return $exception->getMessage();
        }
        return $this->defaultMessage;
    }
}
