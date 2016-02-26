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
     */
    protected $classes = array(
        self::NOTICE => 'notice',
        self::INFO => 'info',
        self::OK => 'success',
        self::WARNING => 'warning',
        self::ERROR => 'danger'
    );

    /**
     * @var string The message severity icon names
     */
    protected $icons = array(
        self::NOTICE => 'lightbulb-o',
        self::INFO => 'info',
        self::OK => 'check',
        self::WARNING => 'exclamation',
        self::ERROR => 'times'
    );

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
     * @return void
     */
    public function setStoreInSession($storeInSession)
    {
        $this->storeInSession = (bool)$storeInSession;
    }

    /**
     * Gets the message severity class name
     *
     * @return string The message severity class name
     */
    public function getClass()
    {
        return 'alert-' . $this->classes[$this->severity];
    }

    /**
     * Gets the message severity icon name
     *
     * @return string The message severity icon name
     */
    public function getIconName()
    {
        return $this->icons[$this->severity];
    }

    /**
     * Gets the message rendered as clean and secure markup
     *
     * @return string
     */
    public function getMessageAsMarkup()
    {
        $messageTitle = $this->getTitle();
        $markup = [];
        $markup[] = '<div class="alert ' . htmlspecialchars($this->getClass()) . '">';
        $markup[] = '    <div class="media">';
        $markup[] = '        <div class="media-left">';
        $markup[] = '            <span class="fa-stack fa-lg">';
        $markup[] = '                <i class="fa fa-circle fa-stack-2x"></i>';
        $markup[] = '                <i class="fa fa-' . htmlspecialchars($this->getIconName()) . ' fa-stack-1x"></i>';
        $markup[] = '            </span>';
        $markup[] = '        </div>';
        $markup[] = '        <div class="media-body">';
        if (!empty($messageTitle)) {
            $markup[] = '            <h4 class="alert-title">' . htmlspecialchars($messageTitle) . '</h4>';
        }
        $markup[] = '            <p class="alert-message">' . htmlspecialchars($this->getMessage()) . '</p>';
        $markup[] = '        </div>';
        $markup[] = '    </div>';
        $markup[] = '</div>';
        return implode('', $markup);
    }
}
