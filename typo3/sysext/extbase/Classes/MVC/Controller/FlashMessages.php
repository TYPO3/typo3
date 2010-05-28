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
	 * The array of flash messages
	 * @var array<string>
	 */
	protected $flashMessages = array();

	/**
	 * If FALSE, flash message container still needs to be initialized.
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * The key from which the flash messages should be retrieved.
	 * We have to incorporate the PluginKey and the Extension Key in here, to make
	 * it working when multiple plugins are on the same page.
	 * @var string
	 */
	protected $flashMessageStorageKey = NULL;

	/**
	 * Add another flash message.
	 *
	 * @param string $message
	 * @return void
	 * @api
	 */
	public function add($message) {
		if (!is_string($message)) throw new InvalidArgumentException('The flash message must be string, ' . gettype($message) . ' given.', 1243258395);
		$this->initialize();
		$this->flashMessages[] = $message;
	}

	/**
	 * Get all flash messages currently available.
	 *
	 * @return array<string> An array of flash messages
	 * @api
	 */
	public function getAll() {
		$this->initialize();
		return $this->flashMessages;
	}

	/**
	 * Reset all flash messages.
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
		$this->initialize();
		$this->flashMessages = array();
	}

	/**
	 * Get all flash messages currently available and delete them afterwards.
	 *
	 * @return array<string>
	 * @api
	 */
	public function getAllAndFlush() {
		$this->initialize();
		$flashMessages = $this->flashMessages;
		$this->flashMessages = array();
		return $flashMessages;
	}

	/**
	 * Initialize the flash message
	 */
	protected function initialize() {
		if ($this->initialized) return;

		$frameworkConfiguration = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
		$this->flashMessageStorageKey = 'Tx_Extbase_MVC_Controller_FlashMessages_messages_' . $frameworkConfiguration['extensionName'] . $frameworkConfiguration['pluginName'];

		$flashMessages = $this->loadFlashMessagesFromSession();
		if (is_array($flashMessages)) {
			$this->flashMessages = $flashMessages;
		}

		$this->initialized = TRUE;
	}

	/**
	 * Loads the flash messages from the current user session.
	 */
	protected function loadFlashMessagesFromSession() {
		$flashMessages = NULL;
		if (TYPO3_MODE === 'FE') {
			$flashMessages = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->flashMessageStorageKey);
		} else {
			$flashMessages = $GLOBALS['BE_USER']->uc[$this->flashMessageStorageKey];
			$GLOBALS['BE_USER']->writeUC();
		}
		return $flashMessages;
	}

	/**
	 * Reset the flash messages. Needs to be called at the beginning of a new rendering,
	 * to account when multiple plugins appear on the same page.
	 */
	public function reset() {
		$this->flashMessages = array();
		$this->initialized = FALSE;
		$this->flashMessageStorageKey = NULL;
	}

	/**
	 * Persist the flash messages in the session.
	 */
	public function persist() {
		if (!$this->initialized) {
			return;
		}
		if (TYPO3_MODE === 'FE') {
			$GLOBALS['TSFE']->fe_user->setKey(
				'ses',
				$this->flashMessageStorageKey,
				$this->flashMessages
			);
			$GLOBALS['TSFE']->fe_user->storeSessionData();
		} else {
			$GLOBALS['BE_USER']->uc[$this->flashMessageStorageKey] = $this->flashMessages;
			$GLOBALS['BE_USER']->writeUc();
		}
	}
}

?>