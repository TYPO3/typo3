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
     * Renders the flash message.
     *
     * @return string The flash message as HTML.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function render()
    {
        GeneralUtility::logDeprecatedFunction();
        $title = '';
        if (!empty($this->title)) {
            $title = '<h4 class="alert-title">' . $this->title . '</h4>';
        }
        $message = '
			<div class="alert ' . $this->getClass() . '">
				<div class="media">
					<div class="media-left">
						<span class="fa-stack fa-lg">
							<i class="fa fa-circle fa-stack-2x"></i>
							<i class="fa fa-' . $this->getIconName() . ' fa-stack-1x"></i>
						</span>
					</div>
					<div class="media-body">
						' . $title . '
						<div class="alert-message">' . $this->message . '</div>
					</div>
				</div>
			</div>';
        return $message;
    }
}
