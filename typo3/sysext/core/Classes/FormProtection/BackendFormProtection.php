<?php
namespace TYPO3\CMS\Core\FormProtection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Klee <typo3-coding@oliverklee.de>
 * (c) 2010-2013 Helmut Hummel <helmut.hummel@typo3.org>
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
 * $formToken = TYPO3\CMS\Core\FormProtection\BackendFormProtectionFactory::get()
 * ->generateToken(
 * 'BE user setup', 'edit'
 * );
 * $this->content .= '<input type="hidden" name="formToken" value="' .
 * $formToken . '" />';
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
 * $formToken = \TYPO3\CMS\Core\FormProtection\BackendFormProtectionFactory::get()
 * ->getFormProtection()->generateToken(
 * 'tt_content', 'edit', $uid
 * );
 * </pre>
 *
 *
 * When processing the data that has been submitted by the form, you can check
 * that the form token is valid like this:
 *
 * <pre>
 * if ($dataHasBeenSubmitted && TYPO3\CMS\Core\FormProtection\BackendFormProtectionFactory::get()
 * ->validateToken(
 * \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('formToken'),
 * 'BE user setup', 'edit
 * )
 * ) {
 * processes the data
 * } else {
 * no need to do anything here as the BE form protection will create a
 * flash message for an invalid token
 * }
 * </pre>
 */
/**
 * Backend form protection
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class BackendFormProtection extends \TYPO3\CMS\Core\FormProtection\AbstractFormProtection {

	/**
	 * Keeps the instance of the user which existed during creation
	 * of the object.
	 *
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $backendUser;

	/**
	 * Instance of the registry, which is used to permanently persist
	 * the session token so that it can be restored during re-login.
	 *
	 * @var \TYPO3\CMS\Core\Registry
	 */
	protected $registry;

	/**
	 * Only allow construction if we have a backend session
	 */
	public function __construct() {
		if (!$this->isAuthorizedBackendSession()) {
			throw new \TYPO3\CMS\Core\Error\Exception('A back-end form protection may only be instantiated if there' . ' is an active back-end session.', 1285067843);
		}
		$this->backendUser = $GLOBALS['BE_USER'];
		parent::__construct();
	}

	/**
	 * Creates or displays an error message telling the user that the submitted
	 * form token is invalid.
	 *
	 * @return void
	 */
	protected function createValidationErrorMessage() {
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:error.formProtection.tokenInvalid'),
			'',
			\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
			!(isset($GLOBALS['TYPO3_AJAX']) && $GLOBALS['TYPO3_AJAX'] === TRUE)
		);
		/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
		$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessageService'
		);
		/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		$defaultFlashMessageQueue->enqueue($flashMessage);
	}

	/**
	 * Retrieves the saved session token or generates a new one.
	 *
	 * @return array<array>
	 */
	protected function retrieveSessionToken() {
		$this->sessionToken = $this->backendUser->getSessionData('formSessionToken');
		if (empty($this->sessionToken)) {
			$this->sessionToken = $this->generateSessionToken();
			$this->persistSessionToken();
		}
	}

	/**
	 * Saves the tokens so that they can be used by a later incarnation of this
	 * class.
	 *
	 * @access private
	 * @return void
	 */
	public function persistSessionToken() {
		$this->backendUser->setAndSaveSessionData('formSessionToken', $this->sessionToken);
	}

	/**
	 * Sets the session token for the user from the registry
	 * and returns it additionally.
	 *
	 * @access private
	 * @return string
	 */
	public function setSessionTokenFromRegistry() {
		$this->sessionToken = $this->getRegistry()->get('core', 'formSessionToken:' . $this->backendUser->user['uid']);
		if (empty($this->sessionToken)) {
			throw new \UnexpectedValueException('Failed to restore the session token from the registry.', 1301827270);
		}
		return $this->sessionToken;
	}

	/**
	 * Stores the session token in the registry to have it
	 * available during re-login of the user.
	 *
	 * @access private
	 * @return void
	 */
	public function storeSessionTokenInRegistry() {
		$this->getRegistry()->set('core', 'formSessionToken:' . $this->backendUser->user['uid'], $this->sessionToken);
	}

	/**
	 * Removes the session token for the user from the registry.
	 *
	 * @access private
	 * @return string
	 */
	public function removeSessionTokenFromRegistry() {
		return $this->getRegistry()->remove('core', 'formSessionToken:' . $this->backendUser->user['uid']);
	}

	/**
	 * Returns the instance of the registry.
	 *
	 * @return \TYPO3\CMS\Core\Registry
	 */
	protected function getRegistry() {
		if (!$this->registry instanceof \TYPO3\CMS\Core\Registry) {
			$this->registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		}
		return $this->registry;
	}

	/**
	 * Inject the registry. Currently only used in unit tests.
	 *
	 * @access private
	 * @param \TYPO3\CMS\Core\Registry $registry
	 * @return void
	 */
	public function injectRegistry(\TYPO3\CMS\Core\Registry $registry) {
		$this->registry = $registry;
	}

	/**
	 * Checks if a user is logged in and the session is active.
	 *
	 * @return boolean
	 */
	protected function isAuthorizedBackendSession() {
		return isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof \TYPO3\CMS\Core\Authentication\BackendUserAuthentication && isset($GLOBALS['BE_USER']->user['uid']);
	}

}


?>