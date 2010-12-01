<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This is a container for all Flash Messages. It is of scope session, but as Extbase
 * has no session scope, we need to save it manually.
 *
 * @version $Id: FlashMessages.php 1729 2009-11-25 21:37:20Z stucki $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope session
 * @api
 */
class Tx_Extbase_MVC_Controller_FlashMessages implements t3lib_Singleton {

	/**
	 * Add another flash message.
	 * Severity can be specified and must be one of
	 *  t3lib_FlashMessage::NOTICE,
	 *  t3lib_FlashMessage::INFO,
	 *  t3lib_FlashMessage::OK,
	 *  t3lib_FlashMessage::WARNING,
	 *  t3lib_FlashMessage::ERROR
	 *
	 * @param string $message
	 * @param string $title optional message title
	 * @param integer $severity optional severity code. One of the t3lib_FlashMessage constants
	 * @return void
	 * @api
	 */
	public function add($message, $title = '', $severity = t3lib_FlashMessage::OK) {
		if (!is_string($message)) {
			throw new InvalidArgumentException('The flash message must be string, ' . gettype($message) . ' given.', 1243258395);
		}
		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$message,
			$title,
			$severity,
			TRUE
		);
		t3lib_FlashMessageQueue::addMessage($flashMessage);
	}

	/**
	 * Get all flash messages currently available.
	 *
	 * @return array<string> An array of flash messages
	 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0. Use  Use getAllMessages() instead
	 */
	public function getAll() {
		t3lib_div::logDeprecatedFunction();
		$flashMessages = t3lib_FlashMessageQueue::getAllMessages();
		$messages = array();
		foreach ($flashMessages as $flashMessage) {
			$messages[] = $flashMessage->getMessage();
		}
		return $messages;
	}

	/**
	 * Get all flash messages currently available.
	 *
	 * @return array<t3lib_FlashMessage> An array of flash messages
	 * @api
	 * @see t3lib_FlashMessage
	 */
	public function getAllMessages() {
		return t3lib_FlashMessageQueue::getAllMessages();
	}

	/**
	 * Reset all flash messages.
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
		t3lib_FlashMessageQueue::getAllMessagesAndFlush();
	}

	/**
	 * Get all flash messages currently available and delete them afterwards.
	 *
	 * @return array<string>
	 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0. Use getAllMessagesAndFlush() instead
	 */
	public function getAllAndFlush() {
		t3lib_div::logDeprecatedFunction();
		$flashMessages = t3lib_FlashMessageQueue::getAllMessagesAndFlush();
		$messages = array();
		foreach ($flashMessages as $flashMessage) {
			$messages[] = $flashMessage->getMessage();
		}
		return $messages;
	}

	/**
	 * Get all flash messages currently available. And removes them from the session.
	 *
	 * @return array<t3lib_FlashMessage> An array of flash messages
	 * @see t3lib_FlashMessage
	 * @api
	 */
	public function getAllMessagesAndFlush() {
		return t3lib_FlashMessageQueue::getAllMessagesAndFlush();
	}

}

?>