<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Status;

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
 * Abstract status
 */
abstract class AbstractStatus implements StatusInterface
{
    /**
     * @var string Severity
     */
    protected $severity = '';

    /**
     * @var int Severity as number
     */
    protected $severityNumber = -2;

    /**
     * @var string Title
     */
    protected $title = '';

    /**
     * @var string Status message
     */
    protected $message = '';

    /**
     * Default constructor creates severity number from severity string
     */
    public function __construct()
    {
        $this->severityNumber = $this->getSeverityAsNumber($this->severity);
    }

    /**
     * @return string The severity
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    /**
     * @return int Severity as number
     */
    public function getSeverityNumber(): int
    {
        return $this->severityNumber;
    }

    /**
     * @return string The title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title The title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Get status message
     *
     * @return string Status message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set status message
     *
     * @param string $message Status message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return array Json representation of this status
     */
    public function jsonSerialize(): array
    {
        return [
            'severity' => $this->getSeverityNumber(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
        ];
    }

    /**
     * Return the corresponding integer value for given severity string
     *
     * @param string $severity
     * @return int
     */
    protected function getSeverityAsNumber($severity): int
    {
        $number = -2;
        switch (strtolower($severity)) {
            case 'loading':
                $number = -3;
                break;
            case 'notice':
                $number = -2;
                break;
            case 'info':
                $number = -1;
                break;
            case 'ok':
            case 'success':
                $number = 0;
                break;
            case 'warning':
                $number = 1;
                break;
            case 'error':
            case 'danger':
            case 'alert':
            case 'fatal':
                $number = 2;
                break;
        }
        return $number;
    }
}
