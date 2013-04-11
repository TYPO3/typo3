<?php
namespace TYPO3\CMS\Core\Messaging;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A class representing flash messages.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class FlashMessage extends \TYPO3\CMS\Core\Messaging\AbstractMessage {

	/**
	 * defines whether the message should be stored in the session (to survive redirects) or only for one request (default)
	 *
	 * @var boolean
	 */
	protected $storeInSession = FALSE;

	/**
	 * @var string The message severity class names
	 */
	protected $classes = array(
		self::NOTICE => 'notice',
		self::INFO => 'information',
		self::OK => 'ok',
		self::WARNING => 'warning',
		self::ERROR => 'error'
	);

	/**
	 * Constructor for a flash message
	 *
	 * @param string $message The message.
	 * @param string $title Optional message title.
	 * @param integer $severity Optional severity, must be either of one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
	 * @param boolean $storeInSession Optional, defines whether the message should be stored in the session or only for one request (default)
	 * @return void
	 */
	public function __construct($message, $title = '', $severity = self::OK, $storeInSession = FALSE) {
		$this->setMessage($message);
		$this->setTitle($title);
		$this->setSeverity($severity);
		$this->setStoreInSession($storeInSession);
	}

	/**
	 * Gets the message's storeInSession flag.
	 *
	 * @return boolean TRUE if message should be stored in the session, otherwise FALSE.
	 */
	public function isSessionMessage() {
		return $this->storeInSession;
	}

	/**
	 * Sets the message's storeInSession flag
	 *
	 * @param boolean The persistence flag
	 * @return void
	 */
	public function setStoreInSession($storeInSession) {
		$this->storeInSession = (bool) $storeInSession;
	}

	/**
	 * Gets the message severity class name
	 *
	 * @return string The message severity class name
	 */
	public function getClass() {
		return 'message-' . $this->classes[$this->severity];
	}

	/**
	 * Renders the flash message.
	 *
	 * @return string The flash message as HTML.
	 */
	public function render() {
		$title = '';
		if (!empty($this->title)) {
			$title = '<div class="message-header">' . $this->title . '</div>';
		}
		$message = '<div class="typo3-message ' . $this->getClass() . '">' . $title . '<div class="message-body">' . $this->message . '</div>' . '</div>';
		return $message;
	}

}


?>