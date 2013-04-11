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
 * 'installToolPassword', 'change'
 * );
 * then puts the generated form token in a hidden field in the template
 * </pre>
 *
 * The three parameters $formName, $action and $formInstanceName can be
 * arbitrary strings, but they should make the form token as specific as
 * possible. For different forms (e.g. the password change and editing a the
 * configuration), those values should be different.
 *
 * When processing the data that has been submitted by the form, you can check
 * that the form token is valid like this:
 *
 * <pre>
 * if ($dataHasBeenSubmitted && $this->formProtection()->validateToken(
 * $_POST['formToken'],
 * 'installToolPassword',
 * 'change'
 * ) {
 * processes the data
 * } else {
 * no need to do anything here as the install tool form protection will
 * create an error message for an invalid token
 * }
 * </pre>
 */
/**
 * Install Tool form protection
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class InstallToolFormProtection extends AbstractFormProtection {

	/**
	 * an instance of the install tool used for displaying messages
	 *
	 * @var \TYPO3\CMS\Install\Installer
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
	 * @param \TYPO3\CMS\Install\Installer $installTool the current instance of the install tool
	 * @return void
	 */
	public function injectInstallTool(\TYPO3\CMS\Install\Installer $installTool) {
		$this->installTool = $installTool;
	}

	/**
	 * Creates or displayes an error message telling the user that the submitted
	 * form token is invalid.
	 *
	 * @return void
	 */
	protected function createValidationErrorMessage() {
		$this->installTool->addErrorMessage('Validating the security token of this form has failed. ' . 'Please reload the form and submit it again.');
	}

	/**
	 * Retrieves or generates the session token.
	 *
	 * @return void
	 */
	protected function retrieveSessionToken() {
		if (isset($_SESSION['installToolFormToken']) && !empty($_SESSION['installToolFormToken'])) {
			$this->sessionToken = $_SESSION['installToolFormToken'];
		} else {
			$this->sessionToken = $this->generateSessionToken();
			$this->persistSessionToken();
		}
	}

	/**
	 * Saves the tokens so that they can be used by a later incarnation of this
	 * class.
	 *
	 * @return void
	 */
	public function persistSessionToken() {
		$_SESSION['installToolFormToken'] = $this->sessionToken;
	}

}


?>