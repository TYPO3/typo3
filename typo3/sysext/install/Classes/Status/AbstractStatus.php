<?php
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
     * @var string Title
     */
    protected $title = '';

    /**
     * @var string Status message
     */
    protected $message = '';

    /**
     * @return string The severity
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * @return string The title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title The title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get status message
     *
     * @return string Status message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set status message
     *
     * @param string $message Status message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}
