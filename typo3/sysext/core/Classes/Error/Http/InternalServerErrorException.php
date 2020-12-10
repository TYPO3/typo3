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

namespace TYPO3\CMS\Core\Error\Http;

use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Exception for Error 500 - Internal Server Error
 */
class InternalServerErrorException extends AbstractServerErrorException
{
    /**
     * @var array HTTP Status Header lines
     */
    protected $statusHeaders = [HttpUtility::HTTP_STATUS_500];

    /**
     * @var string Title of the message
     */
    protected $title = 'Internal Server Error (500)';

    /**
     * @var string Error Message
     */
    protected $message = 'This page is currently not available due to server errors.';

    /**
     * Constructor for this Status Exception
     *
     * @param string $message Error Message
     * @param int $code Exception Code
     */
    public function __construct($message = null, $code = 0)
    {
        if (!empty($message)) {
            $this->message = $message;
        }
        parent::__construct($this->statusHeaders, $this->message, $this->title, $code);
    }
}
