<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2010 Rupert Germann <rupi@gmx.li>
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
 *  A class which collects and renders flash messages.
 *
 * @author	Rupert Germann <rupi@gmx.li>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_FlashMessageQueue {

	static $messages = array();

	/**
	 * Static class, no instances allowed.
	 */
	protected function __construct() {
	}


	/**
	 * Adds a message either to the BE_USER session (if the $message has the storeInSession flag set)
	 * or it adds the message to self::$messages.
	 *
	 * @param	object	 instance of t3lib_FlashMessage, representing a message
	 * @return	 void
	 */
	public static function addMessage(t3lib_FlashMessage $message) {
		if ($message->isSessionMessage()) {
			$queuedFlashMessages = self::getFlashMessagesFromSession();
			$queuedFlashMessages[] = $message;
			self::storeFlashMessagesInSession($queuedFlashMessages);
		} else {
			self::$messages[] = $message;
		}
	}

	/**
	 * Returns all messages from the current PHP session and from the current request.
	 *
	 * @return	 array	 array of t3lib_FlashMessage objects
	 */
	public static function getAllMessages() {
			// get messages from user session
		$queuedFlashMessagesFromSession = self::getFlashMessagesFromSession();
		$queuedFlashMessages = array_merge($queuedFlashMessagesFromSession, self::$messages);

		return $queuedFlashMessages;
	}

	/**
	 * Returns all messages from the current PHP session and from the current request.
	 * After fetching the messages the internal queue and the message queue in the session
	 * will be emptied.
	 *
	 * @return	 array	 array of t3lib_FlashMessage objects
	 */
	public static function getAllMessagesAndFlush() {
		$queuedFlashMessages = self::getAllMessages();

			// reset messages in user session
		self::removeAllFlashMessagesFromSession();
			// reset internal messages
		self::$messages = array();

		return $queuedFlashMessages;
	}

	/**
	 * Stores given flash messages in the session
	 *
	 * @param	array	array of t3lib_FlashMessage
	 * @return	void
	 */
	protected static function storeFlashMessagesInSession(array $flashMessages) {
		self::getUserByContext()->setAndSaveSessionData('core.template.flashMessages', $flashMessages);

	}

	/**
	 * Removes all flash messages from the session
	 *
	 * @return	void
	 */
	protected static function removeAllFlashMessagesFromSession() {
		self::getUserByContext()->setAndSaveSessionData('core.template.flashMessages', NULL);
	}

	/**
	 * Returns current flash messages from the session, making sure to always
	 * return an array.
	 *
	 * @return	array	An array of t3lib_FlashMessage flash messages.
	 */
	protected static function getFlashMessagesFromSession() {
		$flashMessages = self::getUserByContext()->getSessionData('core.template.flashMessages');

		return is_array($flashMessages) ? $flashMessages : array();
	}

	/**
	 * Gets user object by context
	 *
	 * @return object user object
	 */
	protected static function getUserByContext() {
		return TYPO3_MODE === 'BE' ? $GLOBALS['BE_USER'] : $GLOBALS['TSFE']->fe_user;
	}

	/**
	 * Fetches and renders all available flash messages from the queue.
	 *
	 * @return	string	All flash messages in the queue rendered as HTML.
	 */
	public static function renderFlashMessages() {
		$content = '';
		$flashMessages = self::getAllMessagesAndFlush();

		if (count($flashMessages)) {
			foreach ($flashMessages as $flashMessage) {
				$content .= $flashMessage->render();
			}
		}
		return $content;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flashmessagequeue.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flashmessagequeue.php']);
}
?>
