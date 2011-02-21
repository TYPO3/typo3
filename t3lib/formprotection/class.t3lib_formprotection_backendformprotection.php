<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2011 Oliver Klee <typo3-coding@oliverklee.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class t3lib_formprotection_BackendFormProtection.
 *
 * This class provides protection against cross-site request forgery (XSRF/CSRF)
 * for forms in the BE.
 *
 * How to use:
 *
 * For each form in the BE (or link that changes some data), create a token and
 * insert is as a hidden form element. The name of the form element does not
 * matter; you only need it to get the form token for verifying it.
 *
 * <pre>
 * $formToken = t3lib_formprotection_Factory::get(
 *	 t3lib_formprotection_Factory::TYPE_BACK_END
 * )->generateToken(
 *	 'BE user setup', 'edit'
 * );
 * $this->content .= '<input type="hidden" name="formToken" value="' .
 *	 $formToken . '" />';
 * </pre>
 *
 * The three parameters $formName, $action and $formInstanceName can be
 * arbitrary strings, but they should make the form token as specific as
 * possible. For different forms (e.g. BE user setup and editing a tt_content
 * record) or different records (with different UIDs) from the same table,
 * those values should be different.
 *
 * For editing a tt_content record, the call could look like this:
 *
 * <pre>
 * $formToken = t3lib_formprotection_Factory::get(
 *	 t3lib_formprotection_Factory::TYPE_BACK_END
 * )->getFormProtection()->generateToken(
 *	'tt_content', 'edit', $uid
 * );
 * </pre>
 *
 * At the end of the form, you need to persist the tokens. This makes sure that
 * generated tokens get saved, and also that removed tokens stay removed:
 *
 * <pre>
 * t3lib_formprotection_Factory::get(
 *	 t3lib_formprotection_Factory::TYPE_BACK_END
 * )->persistTokens();
 * </pre>
 *
 * In BE lists, it might be necessary to generate hundreds of tokens. So the
 * tokens do not get automatically persisted after creation for performance
 * reasons.
 *
 *
 * When processing the data that has been submitted by the form, you can check
 * that the form token is valid like this:
 *
 * <pre>
 * if ($dataHasBeenSubmitted && t3lib_formprotection_Factory::get(
 *		 t3lib_formprotection_Factory::TYPE_BACK_END
 *	 )->validateToken(
 *		 (string) t3lib_div::_POST('formToken'),
 *		 'BE user setup', 'edit
 *	 )
 * ) {
 *	 // processes the data
 * } else {
 *	 // no need to do anything here as the BE form protection will create a
 *	 // flash message for an invalid token
 * }
 * </pre>
 *
 * Note that validateToken invalidates the token with the token ID. So calling
 * validate with the same parameters two times in a row will always return FALSE
 * for the second call.
 *
 * It is important that the tokens get validated <em>before</em> the tokens are
 * persisted. This makes sure that the tokens that get invalidated by
 * validateToken cannot be used again.
 *
 * $Id$
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_formprotection_BackendFormProtection extends t3lib_formprotection_Abstract {
	/**
	 * the maximum number of tokens that can exist at the same time
	 *
	 * @var integer
	 */
	protected $maximumNumberOfTokens = 20000;

	/**
	 * Keeps the instance of the user which existed during creation
	 * of the object.
	 *
	 * @var t3lib_beUserAuth
	 */
	protected $backendUser;

	/**
	 * Only allow construction if we have a backend session
	 */
	public function __construct() {
		if (!$this->isAuthorizedBackendSession()) {
			throw new t3lib_error_Exception(
				'A back-end form protection may only be instantiated if there' .
				' is an active back-end session.',
				1285067843
			);
		}
		$this->backendUser = $GLOBALS['BE_USER'];
		parent::__construct();
	}

	/**
	 * Overrule the method in the absract class, because we can drop the
	 * whole locking procedure, which is done in persistTokens, if we
	 * simply want to delete all tokens.
	 *
	 * @see t3lib/formprotection/t3lib_formprotection_Abstract::clean()
	 */
	public function clean() {
		$this->tokens = array();
		$this->backendUser->setAndSaveSessionData('formTokens', $this->tokens);
		$this->resetPersistingRequiredStatus();
	}

	/**
	 * Override the abstract class to be able to strip out
	 * the token id from the POST variable.
	 *
	 * @see t3lib/formprotection/t3lib_formprotection_Abstract::validateToken()
	 */
	public function validateToken(
		$token, $formName, $action = '', $formInstanceName = ''
	) {
		list($tokenId, $_) = explode('-', (string)$token);

		return parent::validateToken($tokenId, $formName, $action, $formInstanceName);
	}

	/**
	 * Creates or displayes an error message telling the user that the submitted
	 * form token is invalid.
	 *
	 * @return void
	 */
	protected function createValidationErrorMessage() {
		$message = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$GLOBALS['LANG']->sL(
				'LLL:EXT:lang/locallang_core.xml:error.formProtection.tokenInvalid'
			),
			'',
			t3lib_FlashMessage::ERROR,
			TRUE
		);
		t3lib_FlashMessageQueue::addMessage($message);
	}

	/**
	 * Retrieves all saved tokens.
	 *
	 * @return array<array>
	 *		 the saved tokens as, will be empty if no tokens have been saved
	 */
	protected function retrieveTokens() {
		$tokens = $this->backendUser->getSessionData('formTokens');
		if (!is_array($tokens)) {
			$tokens = array();
		}

		return $tokens;
	}

	/**
	 * It might be that two (or more) scripts are executed at the same time,
	 * which would lead to a race condition, where both (all) scripts retrieve
	 * the same tokens from the session, so the script that is executed
	 * last will overwrite the tokens generated in the first scripts.
	 * So before writing all tokens back to the session we need to get the
	 * current tokens from the session again.
	 *
	 */
	protected function updateTokens() {
		$this->backendUser->user = $this->backendUser->fetchUserSession(TRUE);
		$tokens = $this->retrieveTokens();
		$this->tokens = array_merge($tokens, $this->addedTokens);
		foreach ($this->droppedTokenIds as $tokenId) {
			unset($this->tokens[$tokenId]);
		}
	}

	/**
	 * Saves the tokens so that they can be used by a later incarnation of this
	 * class.
	 *
	 * @return void
	 */
	public function persistTokens() {
		if ($this->isPersistingRequired()) {
			$lockObject = $this->acquireLock();

			$this->updateTokens();
			$this->backendUser->setAndSaveSessionData('formTokens', $this->tokens);
			$this->resetPersistingRequiredStatus();

			$this->releaseLock($lockObject);
		}
	}

	/**
	 * Tries to acquire a lock to not allow a race condition.
	 *
	 * @return t3lib_lock|FALSE The lock object or FALSE
	 */
	protected function acquireLock() {
		$identifier = 'persistTokens' . $this->backendUser->id;
		try {
			/** @var t3lib_lock $lockObject */
			$lockObject = t3lib_div::makeInstance('t3lib_lock', $identifier, 'simple');
			$lockObject->setEnableLogging(FALSE);
			$success = $lockObject->acquire();
		} catch (Exception $e) {
			t3lib_div::sysLog('Locking: Failed to acquire lock: '.$e->getMessage(), 't3lib_formprotection_BackendFormProtection', t3lib_div::SYSLOG_SEVERITY_ERROR);
			$success = FALSE;	// If locking fails, return with false and continue without locking
		}

		return $success ? $lockObject : FALSE;
	}

	/**
	 * Releases the lock if it was acquired before.
	 *
	 * @return boolean
	 */
	protected function releaseLock(&$lockObject) {
		$success = FALSE;
			// If lock object is set and was acquired, release it:
		if (is_object($lockObject) && $lockObject instanceof t3lib_lock && $lockObject->getLockStatus()) {
			$success = $lockObject->release();
			$lockObject = NULL;
		}

		return $success;
	}

	/**
	 * Checks if a user is logged in and the session is active.
	 *
	 * @return boolean
	 */
	protected function isAuthorizedBackendSession() {
		return (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof t3lib_beUserAuth && isset($GLOBALS['BE_USER']->user['uid']));
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/formprotection/class.t3lib_formprotection_backendformprotection.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/formprotection/class.t3lib_formprotection_backendformprotection.php']);
}
?>