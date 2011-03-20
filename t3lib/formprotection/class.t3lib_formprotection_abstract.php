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
 * Class t3lib_formprotection_Abstract.
 *
 * This class provides protection against cross-site request forgery (XSRF/CSRF)
 * for forms.
 *
 * For documentation on how to use this class, please see the documentation of
 * the corresponding subclasses, e.g. t3lib_formprotection_BackendFormProtection.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
abstract class t3lib_formprotection_Abstract {
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
	 *		the name of the form, for example a table name like "tt_content",
	 *		or some other identifier like "install_tool_password", must not be
	 *		empty
	 * @param string $action
	 *		the name of the action of the form, for example "new", "delete" or
	 *		"edit", may also be empty
	 * @param string $formInstanceName
	 *		a string used to differentiate two instances of the same form,
	 *		form example a record UID or a comma-separated list of UIDs,
	 *		may also be empty
	 *
	 * @return string the 32-character hex ID of the generated token
	 */
	public function generateToken(
		$formName, $action = '', $formInstanceName = ''
	) {
		if ($formName == '') {
			throw new InvalidArgumentException('$formName must not be empty.', 1294586643);
		}

		$tokenId = t3lib_div::hmac(
			$formName .
			$action .
			$formInstanceName .
			$this->sessionToken
		);

		return $tokenId;
	}

	/**
	 * Checks whether the token $tokenId is valid in the form $formName with
	 * $formInstanceName.
	 *
	 * @param string $tokenId
	 *		a form token to check, may also be empty or utterly malformed
	 * @param string $formName
	 *		the name of the form to check, for example "tt_content",
	 *		may also be empty or utterly malformed
	 * @param string $action
	 *		the action of the form to check, for example "edit",
	 *		may also be empty or utterly malformed
	 * @param string $formInstanceName
	 *		the instance name of the form to check, for example "42" or "foo"
	 *		or "31,42", may also be empty or utterly malformed
	 *
	 * @return boolean
	 *		 TRUE if $tokenId, $formName, $action and $formInstanceName match
	 */
	public function validateToken(
		$tokenId, $formName, $action = '', $formInstanceName = ''
	) {
		$validTokenId = t3lib_div::hmac(
			(string)$formName .
			(string)$action .
			(string)$formInstanceName .
			$this->sessionToken
		);

		if ((string)$tokenId === $validTokenId) {
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
		return bin2hex(t3lib_div::generateRandomBytes(32));
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
	 *		 the saved session token, will be empty if no token has been saved
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