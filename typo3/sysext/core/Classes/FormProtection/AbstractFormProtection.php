<?php
namespace TYPO3\CMS\Core\FormProtection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Klee <typo3-coding@oliverklee.de>
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
 * for forms.
 *
 * For documentation on how to use this class, please see the documentation of
 * the corresponding subclasses
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
abstract class AbstractFormProtection {

	/**
	 * The session token which is used to be hashed during token generation.
	 *
	 * @var string
	 */
	protected $sessionToken;

	/**
	 * Constructor. Makes sure the session token is read and
	 * available for checking.
	 */
	public function __construct() {
		$this->retrieveSessionToken();
	}

	/**
	 * Frees as much memory as possible.
	 */
	public function __destruct() {
		unset($this->sessionToken);
	}

	/**
	 * Deletes the session token and persists the (empty) token.
	 *
	 * This function is intended to be called when a user logs on or off.
	 *
	 * @return void
	 */
	public function clean() {
		unset($this->sessionToken);
		$this->persistSessionToken();
	}

	/**
	 * Generates a token for a form by hashing the given parameters
	 * with the secret session token.
	 *
	 * Calling this function two times with the same parameters will create
	 * the same valid token during one user session.
	 *
	 * @param string $formName
	 * @param string $action
	 * @param string $formInstanceName
	 * @return string the 32-character hex ID of the generated token
	 */
	public function generateToken($formName, $action = '', $formInstanceName = '') {
		if ($formName == '') {
			throw new \InvalidArgumentException('$formName must not be empty.', 1294586643);
		}
		$tokenId = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($formName . $action . $formInstanceName . $this->sessionToken);
		return $tokenId;
	}

	/**
	 * Checks whether the token $tokenId is valid in the form $formName with
	 * $formInstanceName.
	 *
	 * @param string $tokenId
	 * @param string $formName
	 * @param string $action
	 * @param string $formInstanceName
	 * @return boolean
	 */
	public function validateToken($tokenId, $formName, $action = '', $formInstanceName = '') {
		$validTokenId = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(((string) $formName . (string) $action) . (string) $formInstanceName . $this->sessionToken);
		if ((string) $tokenId === $validTokenId) {
			$isValid = TRUE;
		} else {
			$isValid = FALSE;
		}
		if (!$isValid) {
			$this->createValidationErrorMessage();
		}
		return $isValid;
	}

	/**
	 * Generates the random token which is used in the hash for the form tokens.
	 *
	 * @return string
	 */
	protected function generateSessionToken() {
		return bin2hex(\TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes(32));
	}

	/**
	 * Creates or displays an error message telling the user that the submitted
	 * form token is invalid.
	 *
	 * This function may also be empty if the validation error should be handled
	 * silently.
	 *
	 * @return void
	 */
	abstract protected function createValidationErrorMessage();

	/**
	 * Retrieves the session token.
	 *
	 * @return string
	 */
	abstract protected function retrieveSessionToken();

	/**
	 * Saves the session token so that it can be used by a later incarnation
	 * of this class.
	 *
	 * @access private
	 * @return void
	 */
	abstract public function persistSessionToken();

}


?>