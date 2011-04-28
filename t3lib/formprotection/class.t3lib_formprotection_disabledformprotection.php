<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Helmut Hummel <helmut.hummel@typo3.org>
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
 * Class t3lib_formprotection_DisabledFormProtection.
 *
 * This class is a dummy implementation of the form protection,
 * which is used when no authentication is used.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class t3lib_formprotection_DisabledFormProtection extends t3lib_formprotection_Abstract {

	/**
	 * Disable parent constructor
	 */
	public function __construct() {
		// Do nothing.
	}

	/**
	 * Disable parent method
	 */
	public function generateToken(
		$formName, $action = '', $formInstanceName = ''
	) {
		return 'dummyToken';
	}

	/**
	 * Disable parent method.
	 * Always return TRUE.
	 *
	 * @return boolean
	 */
	public function validateToken(
		$tokenId, $formName, $action = '', $formInstanceName = ''
	) {
		return TRUE;
	}

	/**
	 * Dummy implementation
	 */
	protected function createValidationErrorMessage() {
		// Do nothing.
	}

	/**
	 * Dummy implementation
	 */
	protected function retrieveTokens() {
		// Do nothing.
	}

	/**
	 * Dummy implementation
	 */
	public function persistTokens() {
		// Do nothing.
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/formprotection/class.t3lib_formprotection_backendformprotection.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/formprotection/class.t3lib_formprotection_backendformprotection.php']);
}
?>