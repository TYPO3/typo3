<?php
namespace TYPO3\CMS\Reports;

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
 * A class representing a certain status
 */
class Status
{
    const NOTICE = -2;
    const INFO = -1;
    const OK = 0;
    const WARNING = 1;
    const ERROR = 2;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected $severity;

    /**
     * Construct a status
     *
     * All values must be given as constructor arguments.
     * All strings should be localized.
     *
     * @param string $title Status title, eg. "Deprecation log"
     * @param string $value Status value, eg. "Disabled"
     * @param string $message Optional message further describing the title/value combination
     * 			Example:, eg "The deprecation log is important and does foo, to disable it do bar"
     * @param int $severity A severity level. Use one of the constants above!
     */
    public function __construct($title, $value, $message = '', $severity = self::OK)
    {
        $this->title = (string)$title;
        $this->value = (string)$value;
        $this->message = (string)$message;
        $this->severity = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($severity, self::NOTICE, self::ERROR, self::OK);
    }

    /**
     * Gets the status' title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Gets the status' value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Gets the status' message (if any)
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Gets the status' severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * Creates a string representation of a status.
     *
     * @return string String representation of this status.
     */
    public function __toString()
    {
        $severity = [
            self::NOTICE => 'NOTE',
            self::INFO => 'INFO',
            self::OK => 'OK',
            self::WARNING => 'WARN',
            self::ERROR => 'ERR'
        ];
        // Max length 80 characters
        $stringRepresentation = str_pad(('[' . $severity[$this->severity] . ']'), 7) . str_pad($this->title, 40) . ' - ' . substr($this->value, 0, 30);
        return $stringRepresentation;
    }
}
