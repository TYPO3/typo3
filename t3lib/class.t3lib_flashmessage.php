<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
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
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_FlashMessage {

	const NOTICE  = -2;
	const INFO    = -1;
	const OK      = 0;
	const WARNING = 1;
	const ERROR   = 2;

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
	 * @var integer
	 */
	protected $severity = self::OK;

	/**
	 * defines whether the message should be stored in the session (to survive redirects) or only for one request (default)
	 *
	 * @var bool
	 */
	protected $storeInSession = FALSE;

	/**
	 * Constructor for a flash message
	 *
	 * @param	string	The message.
	 * @param	string	Optional message title.
	 * @param	integer	Optional severity, must be either of t3lib_FlashMessage::INFO, t3lib_FlashMessage::OK,
	 *                  t3lib_FlashMessage::WARNING or t3lib_FlashMessage::ERROR. Default is t3lib_FlashMessage::OK.
	 * @param	bool    Optional, defines whether the message should be stored in the session or only for one request (default)
	 * @return	void
	 */
	public function __construct($message, $title = '', $severity = self::OK, $storeInSession = FALSE) {
		$this->setMessage($message);
		$this->setTitle($title);
		$this->setSeverity($severity);
		$this->setStoreInSession($storeInSession);
	}

	/**
	 * Gets the message's title.
	 *
	 * @return	string	The message's title.
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the message's title
	 *
	 * @param	string	The message's title
	 * @return	void
	 */
	public function setTitle($title) {
		$this->title = (string) $title;
	}


	/**
	 * Gets the message's storeInSession flag.
	 *
	 * @return	bool	true if message should be stored in the session, otherwise false.
	 */
	public function isSessionMessage() {
		return $this->storeInSession;
	}

	/**
	 * Sets the message's storeInSession flag
	 *
	 * @param	bool	The persistence flag
	 * @return	void
	 */
	public function setStoreInSession($storeInSession) {
		$this->storeInSession = (bool) $storeInSession;
	}


	/**
	 * Gets the message.
	 *
	 * @return	string	The message.
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Sets the message
	 *
	 * @param	string	The message
	 * @return	void
	 */
	public function setMessage($message) {
		$this->message = (string) $message;
	}

	/**
	 * Gets the message' severity.
	 *
	 * @return	integer	The message' severity, either of t3lib_FlashMessage::INFO, t3lib_FlashMessage::OK, t3lib_FlashMessage::WARNING or t3lib_FlashMessage::ERROR
	 */
	public function getSeverity() {
		return $this->severity;
	}

	/**
	 * Sets the message' severity
	 *
	 * @param	string	The severity, must be either of t3lib_FlashMessage::INFO, t3lib_FlashMessage::OK, t3lib_FlashMessage::WARNING or t3lib_FlashMessage::ERROR. Default is t3lib_FlashMessage::OK.
	 * @return	void
	 */
	public function setSeverity($severity = self::OK) {
		$this->severity = t3lib_div::intInRange(
			$severity,
			self::NOTICE, // minimum
			self::ERROR, // maximum
			self::OK // default if out of bounds
		);
	}

	/**
	 * Renders the flash message.
	 *
	 * @return	string	The flash message as HTML.
	 */
	public function render() {
		$classes = array(
			t3lib_FlashMessage::NOTICE  => 'notice',
			t3lib_FlashMessage::INFO    => 'information',
			t3lib_FlashMessage::OK      => 'ok',
			t3lib_FlashMessage::WARNING => 'warning',
			t3lib_FlashMessage::ERROR   => 'error',
		);

		$title = '';
		if (!empty($this->title)) {
			$title = '<div class="message-header">' . $this->title . '</div>';
		}

		$message = '<div class="typo3-message message-' . $classes[$this->severity] . '">'
			. $title
			. '<div class="message-body">' . $this->message . '</div>'
			. '</div>';

		return $message;
	}


	/**
	 * Creates a string representation of the flash message. Useful for command
	 * line use.
	 *
	 * @return	string	A string representation of the flash message.
	 */
	public function __toString() {
		$severities = array(
			t3lib_FlashMessage::INFO    => 'INFO',
			t3lib_FlashMessage::OK      => 'OK',
			t3lib_FlashMessage::WARNING => 'WARNING',
			t3lib_FlashMessage::ERROR   => 'ERROR',
		);

		$title = '';
		if (!empty($this->title)) {
			$title = ' - ' . $this->title;
		}

		return $severities[$this->severity] . $title . ': ' . $this->message;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flashmessage.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flashmessage.php']);
}

?>