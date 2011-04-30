<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
class t3lib_FlashMessage extends t3lib_message_AbstractMessage {

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
	 *				  t3lib_FlashMessage::WARNING or t3lib_FlashMessage::ERROR. Default is t3lib_FlashMessage::OK.
	 * @param	bool	Optional, defines whether the message should be stored in the session or only for one request (default)
	 * @return	void
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
	 * @return	bool	TRUE if message should be stored in the session, otherwise FALSE.
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
	 * Renders the flash message.
	 *
	 * @return	string	The flash message as HTML.
	 */
	public function render() {
		$classes = array(
			self::NOTICE =>  'notice',
			self::INFO =>    'information',
			self::OK =>      'ok',
			self::WARNING => 'warning',
			self::ERROR =>   'error',
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

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flashmessage.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flashmessage.php']);
}

?>