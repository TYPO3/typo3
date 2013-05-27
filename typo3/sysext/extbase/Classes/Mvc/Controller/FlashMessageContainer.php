<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class FlashMessageContainer implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Add another flash message.
	 * Severity can be specified and must be one of
	 * t3lib_FlashMessage::NOTICE,
	 * t3lib_FlashMessage::INFO,
	 * t3lib_FlashMessage::OK,
	 * t3lib_FlashMessage::WARNING,
	 * t3lib_FlashMessage::ERROR
	 *
	 * @param string $message
	 * @param string $title optional message title
	 * @param integer $severity optional severity code. One of the t3lib_FlashMessage constants
	 * @throws \InvalidArgumentException
	 * @return void
	 * @api
	 */
	public function add($message, $title = '', $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
		if (!is_string($message)) {
			throw new \InvalidArgumentException('The flash message must be string, ' . gettype($message) . ' given.', 1243258395);
		}
		/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, $title, $severity, TRUE);
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
	}

	/**
	 * Get all flash messages currently available.
	 *
	 * @return array<t3lib_FlashMessage> An array of flash messages
	 * @api
	 * @see t3lib_FlashMessage
	 */
	public function getAllMessages() {
		return \TYPO3\CMS\Core\Messaging\FlashMessageQueue::getAllMessages();
	}

	/**
	 * Reset all flash messages.
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::getAllMessagesAndFlush();
	}

	/**
	 * Get all flash messages currently available. And removes them from the session.
	 *
	 * @return array<t3lib_FlashMessage> An array of flash messages
	 * @see t3lib_FlashMessage
	 * @api
	 */
	public function getAllMessagesAndFlush() {
		return \TYPO3\CMS\Core\Messaging\FlashMessageQueue::getAllMessagesAndFlush();
	}
}

?>