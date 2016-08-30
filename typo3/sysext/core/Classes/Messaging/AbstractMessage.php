<?php
namespace TYPO3\CMS\Core\Messaging;

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
 * A class used for any kind of messages.
 */
abstract class AbstractMessage
{
    const NOTICE = -2;
    const INFO = -1;
    const OK = 0;
    const WARNING = 1;
    const ERROR = 2;

    /**
     * The message's title
     *
     * @var string
     */
    protected $title = '';

    /**
     * The message
     *
     * @var string
     */
    protected $message = '';

    /**
     * The message's severity
     *
     * @var int
     */
    protected $severity = self::OK;

    /**
     * Gets the message's title.
     *
     * @return string The message's title.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the message's title
     *
     * @param string $title The message's title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = (string)$title;
    }

    /**
     * Gets the message.
     *
     * @return string The message.
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the message
     *
     * @param string $message The message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = (string)$message;
    }

    /**
     * Gets the message' severity.
     *
     * @return int The message' severity, must be one of AbstractMessage::INFO or similar contstants
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * Sets the message' severity
     *
     * @param int $severity The severity, must be one of AbstractMessage::INFO or similar constants
     * @return void
     */
    public function setSeverity($severity = self::OK)
    {
        $this->severity = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($severity, self::NOTICE, self::ERROR, self::OK);
    }

    /**
     * Creates a string representation of the message. Useful for command
     * line use.
     *
     * @return string A string representation of the message.
     */
    public function __toString()
    {
        $severities = [
            self::INFO => 'INFO',
            self::OK => 'OK',
            self::WARNING => 'WARNING',
            self::ERROR => 'ERROR'
        ];
        $title = '';
        if (!empty($this->title)) {
            $title = ' - ' . $this->title;
        }
        return $severities[$this->severity] . $title . ': ' . $this->message;
    }
}
