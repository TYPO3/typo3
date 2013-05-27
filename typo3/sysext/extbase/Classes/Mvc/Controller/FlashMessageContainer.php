<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * This is a container for all Flash Messages. It is of scope session, but as Extbase
 * has no session scope, we need to save it manually.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
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
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function add($message, $title = '', $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		if (!is_string($message)) {
			throw new \InvalidArgumentException(
				'The flash message must be string, ' . gettype($message) . ' given.',
				1243258395
			);
		}
		/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, $title, $severity, TRUE
		);
		$this->controllerContext->getFlashMessageQueue()->addMessage($flashMessage);
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

?>