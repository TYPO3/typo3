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
 * HTTP Status Exception
 */
class StatusException extends \TYPO3\CMS\Core\Error\Exception
{
    /**
     * @var array HTTP Status Header lines
     */
    protected $statusHeaders;

    /**
     * @var string Title of the message
     */
    protected $title = 'Oops, an error occurred!';

    /**
     * Constructor for this Status Exception
     *
     * @param string|array $statusHeaders HTTP Status header line(s)
     * @param string $title Title of the error message
     * @param string $message Error Message
     * @param int $code Exception Code
     */
    public function __construct($statusHeaders, $message, $title = '', $code = 0)
    {
        if (is_array($statusHeaders)) {
            $this->statusHeaders = $statusHeaders;
        } else {
            $this->statusHeaders = [$statusHeaders];
        }
        $this->title = $title ?: $this->title;
        parent::__construct($message, $code);
    }

    /**
     * Setter for the title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Getter for the title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Getter for the Status Header.
     *
     * @return string
     */
    public function getStatusHeaders()
    {
        return $this->statusHeaders;
    }
}
