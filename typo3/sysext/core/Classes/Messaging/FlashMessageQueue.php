<?php
namespace TYPO3\CMS\Core\Messaging;

/**
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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

/**
 * A class which collects and renders flash messages.
 *
 * @author Rupert Germann <rupi@gmx.li>
 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
 */
class FlashMessageQueue extends \SplQueue {

	/**
	 * A unique identifier for this queue
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * @param string $identifier The unique identifier for this queue
	 */
	public function __construct($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Adds a message either to the BE_USER session (if the $message has the storeInSession flag set)
	 * or it enqueues the message.
	 *
	 * @param FlashMessage $message Instance of \TYPO3\CMS\Core\Messaging\FlashMessage, representing a message
	 * @throws \TYPO3\CMS\Core\Exception
	 * @return void
	 */
	public function enqueue($message) {
		if (!($message instanceof FlashMessage)) {
			throw new \TYPO3\CMS\Core\Exception(
				'FlashMessageQueue::enqueue() expects an object of type \TYPO3\CMS\Core\Messaging\FlashMessage but got type "' . (is_object($message) ? get_class($message) : gettype($message)) . '"',
				1376833554
			);
		}
		if ($message->isSessionMessage()) {
			$this->addFlashMessageToSession($message);
		} else {
			parent::enqueue($message);
		}
	}

	/**
	 * @param FlashMessage $message
	 * @return void
	 */
	public function addMessage(FlashMessage $message) {
		$this->enqueue($message);
	}

	/**
	 * @return void
	 */
	public function dequeue() {
		// deliberately empty
	}

	/**
	 * Adds the given flash message to the array of
	 * flash messages that will be stored in the session.
	 *
	 * @param FlashMessage $message
	 * @return void
	 */
	protected function addFlashMessageToSession(FlashMessage $message) {
		$queuedFlashMessages = $this->getFlashMessagesFromSession();
		$queuedFlashMessages[] = $message;
		$this->storeFlashMessagesInSession($queuedFlashMessages);
	}

	/**
	 * Returns all messages from the current PHP session and from the current request.
	 *
	 * @return FlashMessage[]
	 */
	public function getAllMessages() {
		// Get messages from user session
		$queuedFlashMessagesFromSession = $this->getFlashMessagesFromSession();
		$queuedFlashMessages = array_merge($queuedFlashMessagesFromSession, $this->toArray());
		return $queuedFlashMessages;
	}

	/**
	 * Returns all messages from the current PHP session and from the current request.
	 * After fetching the messages the internal queue and the message queue in the session
	 * will be emptied.
	 *
	 * @return FlashMessage[]
	 */
	public function getAllMessagesAndFlush() {
		$queuedFlashMessages = $this->getAllMessages();
		// Reset messages in user session
		$this->removeAllFlashMessagesFromSession();
		// Reset internal messages
		$this->clear();
		return $queuedFlashMessages;
	}

	/**
	 * Stores given flash messages in the session
	 *
	 * @param FlashMessage[] $flashMessages
	 * @return void
	 */
	protected function storeFlashMessagesInSession(array $flashMessages) {
		$this->getUserByContext()->setAndSaveSessionData($this->identifier, $flashMessages);
	}

	/**
	 * Removes all flash messages from the session
	 *
	 * @return void
	 */
	protected function removeAllFlashMessagesFromSession() {
		$this->getUserByContext()->setAndSaveSessionData($this->identifier, NULL);
	}

	/**
	 * Returns current flash messages from the session, making sure to always
	 * return an array.
	 *
	 * @return FlashMessage[]
	 */
	protected function getFlashMessagesFromSession() {
		$flashMessages = $this->getUserByContext()->getSessionData($this->identifier);
		return is_array($flashMessages) ? $flashMessages : array();
	}

	/**
	 * Gets user object by context
	 *
	 * @return AbstractUserAuthentication
	 */
	protected function getUserByContext() {
		return TYPO3_MODE === 'BE' ? $GLOBALS['BE_USER'] : $GLOBALS['TSFE']->fe_user;
	}

	/**
	 * Fetches and renders all available flash messages from the queue.
	 *
	 * @return string All flash messages in the queue rendered as HTML.
	 */
	public function renderFlashMessages() {
		$content = '';
		$flashMessages = $this->getAllMessagesAndFlush();
		if (!empty($flashMessages)) {
			foreach ($flashMessages as $flashMessage) {
				$content .= $flashMessage->render();
			}
		}
		return $content;
	}

	/**
	 * Returns all items of the queue as array
	 *
	 * @return FlashMessage[]
	 */
	public function toArray() {
		$array = array();
		$this->rewind();
		while ($this->valid()) {
			$array[] = $this->current();
			$this->next();
		}
		return $array;
	}

	/**
	 * Removes all items from the queue
	 *
	 * @return void
	 */
	public function clear() {
		$this->rewind();
		while (!$this->isEmpty()) {
			parent::dequeue();
		}
	}
}
