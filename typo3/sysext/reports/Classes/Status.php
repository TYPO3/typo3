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

namespace TYPO3\CMS\Reports;

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * A class representing a certain status
 */
class Status
{
    /**
     * @deprecated Use \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::NOTICE instead
     */
    public const NOTICE = -2;
    /**
     * @deprecated Use \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::INFO instead
     */
    public const INFO = -1;
    /**
     * @deprecated Use \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK instead
     */
    public const OK = 0;
    /**
     * @deprecated Use \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING instead
     */
    public const WARNING = 1;
    /**
     * @deprecated Use \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR instead
     */
    public const ERROR = 2;

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

    protected ContextualFeedbackSeverity $severity;

    /**
     * Construct a status
     *
     * All values must be given as constructor arguments.
     * All strings should be localized.
     *
     * @param string $title Status title, eg. "Deprecation log"
     * @param string $value Status value, eg. "Disabled"
     * @param string $message Optional message further describing the title/value combination
     *        Example:, eg "The deprecation log is important and does foo, to disable it do bar"
     * @param value-of<ContextualFeedbackSeverity>|ContextualFeedbackSeverity $severity A severity level. Use one of the constants above!
     *
     * @todo: Change $severity to allow ContextualFeedbackSeverity only in v13
     */
    public function __construct($title, $value, $message = '', int|ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK)
    {
        $this->title = (string)$title;
        $this->value = (string)$value;
        $this->message = (string)$message;
        if (is_int($severity)) {
            // @deprecated int type for $severity deprecated in v12, will change to Severity only in v13.
            $severity = ContextualFeedbackSeverity::transform($severity) ?? ContextualFeedbackSeverity::OK;
        }
        $this->severity = $severity;
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

    public function getSeverity(): ContextualFeedbackSeverity
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
        // Max length 80 characters
        $stringRepresentation = str_pad('[' . $this->severity->name . ']', 7) . str_pad($this->title, 40) . ' - ' . substr($this->value, 0, 30);
        return $stringRepresentation;
    }
}
