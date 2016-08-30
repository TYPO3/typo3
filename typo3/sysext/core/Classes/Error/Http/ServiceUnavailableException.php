<?php
namespace TYPO3\CMS\Core\Error\Http;

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
 * Exception for Error 503 - Service Unavailable
 */
class ServiceUnavailableException extends AbstractServerErrorException
{
    /**
     * @var array HTTP Status Header lines
     */
    protected $statusHeaders = [\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_503];

    /**
     * @var string Title of the message
     */
    protected $title = 'Service Unavailable (503)';

    /**
     * @var string Error Message
     */
    protected $message = 'This page is currently not available.';

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
