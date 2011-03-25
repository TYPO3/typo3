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
 */
abstract class t3lib_formprotection_Abstract {
	/**
	 * the maximum number of tokens that can exist at the same time
	 *
	 * @var integer
	 */
	protected $maximumNumberOfTokens = 0;

	/**
	 * Valid tokens sorted from oldest to newest.
	 *
	 * [tokenId] => array(formName, formInstanceName)
	 *
	 * @var array<array>
	 */
	protected $tokens = array();

	/**
	 * Tokens that have been added during this request.
	 *
	 * @var array<array>
	 */
	protected $addedTokens = array();

	/**
	 * Token ids of tokens that have been dropped during this request.
	 *
	 * @var array
	 */
	protected $droppedTokenIds = array();

	/**
	 * Constructor. Makes sure existing tokens are read and available for
	 * checking.
	 */
	public function __construct() {
		$this->tokens = $this->retrieveTokens();
	}

	/**
	 * Frees as much memory as possible.
	 */
	public function __destruct() {
		$this->tokens = array();
	}

	/**
	 * Deletes all existing tokens and persists the (empty) token table.
	 *
	 * This function is intended to be called when a user logs on or off.
	 *
	 * @return void
	 */
	public function clean() {
		$this->tokens = array();
		$this->persistTokens();
	}

	/**
	 * Generates and stores a token for a form.
	 *
	 * Calling this function two times with the same parameters will create
	 * two valid, different tokens.
	 *
	 * Generating more tokens than $maximumNumberOfEntries will cause the oldest
	 * tokens to get dropped.
	 *
	 * Note: This function does not persist the tokens.
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

		do {
			$tokenId = bin2hex(t3lib_div::generateRandomBytes(16));
		} while (isset($this->tokens[$tokenId]));

		$this->tokens[$tokenId] = array(
			'formName' => $formName,
			'action' => $action,
			'formInstanceName' => $formInstanceName,
		);
		$this->addedTokens[$tokenId] = $this->tokens[$tokenId];
		$this->preventOverflow();

		return $tokenId;
	}

	/**
	 * Checks whether the token $tokenId is valid in the form $formName with
	 * $formInstanceName.
	 *
	 * A token is valid if $tokenId, $formName and $formInstanceName match and
	 * the token has not been used yet.
	 *
	 * Calling this function will mark the token $tokenId as invalud (if it
	 * exists).
	 *
	 * So calling this function with the same parameters two times will return
	 * FALSE the second time.
	 *
	 * @param string $tokenId
	 *		a form token to check, may also be empty or utterly misformed
	 * @param string $formName
	 *		the name of the form to check, for example "tt_content",
	 *		may also be empty or utterly misformed
	 * @param string $action
	 *		the action of the form to check, for example "edit",
	 *		may also be empty or utterly misformed
	 * @param string $formInstanceName
	 *		the instance name of the form to check, for example "42" or "foo"
	 *		or "31,42", may also be empty or utterly misformed
	 *
	 * @return boolean
	 *		 TRUE if $tokenId, $formName, $action and $formInstanceName match
	 *		 and the token has not been used yet, FALSE otherwise
	 */
	public function validateToken(
		$tokenId, $formName, $action = '', $formInstanceName = ''
	) {
		if (isset($this->tokens[$tokenId])) {
			$token = $this->tokens[$tokenId];
			$isValid = ($token['formName'] == $formName)
					   && ($token['action'] == $action)
					   && ($token['formInstanceName'] == $formInstanceName);
			$this->dropToken($tokenId);
		} else {
			$isValid = FALSE;
		}

		if (!$isValid) {
			$this->createValidationErrorMessage();
		}

		return $isValid;
	}

	/**
	 * Creates or displayes an error message telling the user that the submitted
	 * form token is invalid.
	 *
	 * This function may also be empty if the validation error should be handled
	 * silently.
	 *
	 * @return void
	 */
	abstract protected function createValidationErrorMessage();

	/**
	 * Retrieves all saved tokens.
	 *
	 * @return array<arrray>
	 *		 the saved tokens, will be empty if no tokens have been saved
	 */
	abstract protected function retrieveTokens();

	/**
	 * Saves the tokens so that they can be used by a later incarnation of this
	 * class.
	 *
	 * @return void
	 */
	abstract public function persistTokens();

	/**
	 * Drops the token with the ID $tokenId.
	 *
	 * If there is no token with that ID, this function is a no-op.
	 *
	 * Note: This function does not persist the tokens.
	 *
	 * @param string $tokenId
	 *		the 32-character ID of an existing token, must not be empty
	 *
	 * @return void
	 */
	protected function dropToken($tokenId) {
		if (isset($this->tokens[$tokenId])) {
			unset($this->tokens[$tokenId]);
			$this->droppedTokenIds[] = $tokenId;
		}
	}

	/**
	 * Persisting of tokens is only required, if tokens are
	 * deleted or added during this request.
	 *
	 * @return boolean
	 */
	protected function isPersistingRequired() {
		return !empty($this->droppedTokenIds) || !empty($this->addedTokens);
	}

	/**
	 * Reset the arrays of added or deleted tokens.
	 *
	 * @return void
	 */
	protected function resetPersistingRequiredStatus() {
		$this->droppedTokenIds = array();
		$this->addedTokens = array();
	}

	/**
	 * Checks whether the number of current tokens still is at most
	 * $this->maximumNumberOfTokens.
	 *
	 * If there are more tokens, the oldest tokens are removed until the number
	 * of tokens is low enough.
	 *
	 * Note: This function does not persist the tokens.
	 *
	 * @return void
	 */
	protected function preventOverflow() {
		if (empty($this->tokens)) {
			return;
		}

		while (count($this->tokens) > $this->maximumNumberOfTokens) {
			reset($this->tokens);
			$this->dropToken(key($this->tokens));
		}
	}
}

?>
