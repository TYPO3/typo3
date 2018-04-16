<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class representing flash messages.
 */
class FlashMessage extends AbstractMessage
{
    /**
     * Defines whether the message should be stored in the session (to survive redirects) or only for one request (default)
     *
     * @var bool
     */
    protected $storeInSession = false;

    /**
     * @var string The message severity class names
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    protected $classes = [
        self::NOTICE => 'notice',
        self::INFO => 'info',
        self::OK => 'success',
        self::WARNING => 'warning',
        self::ERROR => 'danger'
    ];

    /**
     * @var string The message severity icon names
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    protected $icons = [
        self::NOTICE => 'lightbulb-o',
        self::INFO => 'info',
        self::OK => 'check',
        self::WARNING => 'exclamation',
        self::ERROR => 'times'
    ];

    /**
     * Constructor for a flash message
     *
     * @param string $message The message.
     * @param string $title Optional message title.
     * @param int $severity Optional severity, must be either of one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session or only for one request (default)
     */
    public function __construct($message, $title = '', $severity = self::OK, $storeInSession = false)
    {
        $this->setMessage($message);
        $this->setTitle($title);
        $this->setSeverity($severity);
        $this->setStoreInSession($storeInSession);
    }

    /**
     * Gets the message's storeInSession flag.
     *
     * @return bool TRUE if message should be stored in the session, otherwise FALSE.
     */
    public function isSessionMessage()
    {
        return $this->storeInSession;
    }

    /**
     * Sets the message's storeInSession flag
     *
     * @param bool $storeInSession The persistence flag
     */
    public function setStoreInSession($storeInSession)
    {
        $this->storeInSession = (bool)$storeInSession;
    }

    /**
     * Gets the message severity class name
     *
     * @return string The message severity class name
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getClass()
    {
        GeneralUtility::logDeprecatedFunction();
        return 'alert-' . $this->classes[$this->severity];
    }

    /**
     * Gets the message severity icon name
     *
     * @return string The message severity icon name
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getIconName()
    {
        GeneralUtility::logDeprecatedFunction();
        return $this->icons[$this->severity];
    }
}
