<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Class t3lib_formprotection_InstallToolFormProtection.
 *
 * This class provides protection against cross-site request forgery (XSRF/CSRF)
 * in the install tool.
 *
 *
 * How to use this in the install tool:
 *
 * For each form in the install tool (or link that changes some data), create a
 * token and insert is as a hidden form element. The name of the form element
 * does not matter; you only need it to get the form token for verifying it.
 *
 * <pre>
 * $formToken = $this->formProtection->generateToken(
 *	'installToolPassword', 'change'
 * );
 * // then puts the generated form token in a hidden field in the template
 * </pre>
 *
 * The three parameters $formName, $action and $formInstanceName can be
 * arbitrary strings, but they should make the form token as specific as
 * possible. For different forms (e.g. the password change and editing a the
 * configuration), those values should be different.
 *
 * At the end of the form, you need to persist the tokens. This makes sure that
 * generated tokens get saved, and also that removed tokens stay removed:
 *
 * <pre>
 * $this->formProtection()->persistTokens();
 * </pre>
 *
 *
 * When processing the data that has been submitted by the form, you can check
 * that the form token is valid like this:
 *
 * <pre>
 * if ($dataHasBeenSubmitted && $this->formProtection()->validateToken(
 *	 (string) $_POST['formToken'],
 *	 'installToolPassword',
 *	 'change'
 * ) {
 *	 // processes the data
 * } else {
 *	 // no need to do anything here as the install tool form protection will
 *	 // create an error message for an invalid token
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
class t3lib_formprotection_InstallToolFormProtection extends t3lib_formProtection_Abstract {
	/**
	 * the maximum number of tokens that can exist at the same time
	 *
	 * @var integer
	 */
	protected $maximumNumberOfTokens = 100;

	/**
	 * an instance of the install tool used for displaying messages
	 *
	 * @var tx_install
	 */
	protected $installTool = NULL;

	/**
	 * Frees as much memory as possible.
	 */
	public function __destruct() {
		$this->installTool = NULL;
		parent::__destruct();
	}

	/**
	 * Injects the current instance of the install tool.
	 *
	 * This instance will be used for displaying messages.
	 *
	 * @param tx_install $installTool the current instance of the install tool
	 *
	 * @return void
	 */
	public function injectInstallTool(tx_install $installTool) {
		$this->installTool = $installTool;
	}

	/**
	 * Creates or displayes an error message telling the user that the submitted
	 * form token is invalid.
	 *
	 * @return void
	 */
	protected function createValidationErrorMessage() {
		$this->installTool->addErrorMessage(
			'Validating the security token of this form has failed. ' .
			'Please reload the form and submit it again.'
		);
	}

	/**
	 * Retrieves all saved tokens.
	 *
	 * @return array<array>
	 *		 the saved tokens, will be empty if no tokens have been saved
	 */
	protected function retrieveTokens() {
		if (isset($_SESSION['installToolFormTokens'])
			&& is_array($_SESSION['installToolFormTokens'])
		) {
			$this->tokens = $_SESSION['installToolFormTokens'];
		} else {
			$this->tokens = array();
		}
	}

	/**
	 * Saves the tokens so that they can be used by a later incarnation of this
	 * class.
	 *
	 * @return void
	 */
	public function persistTokens() {
		$_SESSION['installToolFormTokens'] = $this->tokens;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install_formprotection.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install_formprotection.php']);
}
?>