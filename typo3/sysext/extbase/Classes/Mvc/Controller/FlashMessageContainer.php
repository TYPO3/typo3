<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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
/**
 * This is a container for all Flash Messages. It is of scope session, but as Extbase
 * has no session scope, we need to save it manually.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 *
 * This object is deprecated as Extbase uses the FlashMessageService provided
 * by the core from 6.1 on. Therefore please do not use this object but call
 * (Abstract)Controller->controllerContext->getFlashMessageQueue() instead.
 *
 * For sure you are free to use TYPO3\CMS\Core\Messaging\FlashMessageService
 * and fetch a custom FlashMessageQueue by calling
 * FlashMessageQueue->getMessageQueueByIdentifier('customIdentifier')
 *
 * @deprecated since Extbase 6.1, will be removed 2 versions later
 */
class FlashMessageContainer implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Add another flash message.
	 * Severity can be specified and must be one of
	 * \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE,
	 * \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
	 * \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
	 * \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
	 * \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
	 *
	 * @param string $message
	 * @param string $title optional message title
	 * @param integer $severity optional severity code. One of the \TYPO3\CMS\Core\Messaging\FlashMessage constants
	 * @throws \InvalidArgumentException
	 * @return void
	 * @deprecated since 6.1, will be removed 2 versions later use Mvc\Controller\AbstractController->addFlashMessage instead
	 */
	public function add($message, $title = '', $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		if (!is_string($message)) {
			throw new \InvalidArgumentException(
				'The flash message must be string, ' . gettype($message) . ' given.',
				1243258396
			);
		}
		/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, $title, $severity, TRUE
		);
		$this->controllerContext->getFlashMessageQueue()->enqueue($flashMessage);
	}

	/**
	 * @return array An array of flash messages: array<\TYPO3\CMS\Core\Messaging\FlashMessage>
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function getAllMessages() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->controllerContext->getFlashMessageQueue()->getAllMessages();
	}

	/**
	 * @return void
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function flush() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->controllerContext->getFlashMessageQueue()->getAllMessagesAndFlush();
	}

	/**
	 * @return array An array of flash messages: array<\TYPO3\CMS\Core\Messaging\FlashMessage>
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function getAllMessagesAndFlush() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->controllerContext->getFlashMessageQueue()->getAllMessagesAndFlush();
	}
}
