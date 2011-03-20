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
 * Class t3lib_formProtection_Testing.
 *
 * This is a testing subclass of the abstract t3lib_formprotection_Abstract
 * class.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_formProtection_Testing extends t3lib_formprotection_Abstract {
	/**
	 * Creates or displayes an error message telling the user that the submitted
	 * form token is invalid.
	 *
	 * @return void
	 */
	protected function createValidationErrorMessage() {}

	/**
	 * Retrieves all saved tokens.
	 *
	 * @return array the saved tokens as a two-dimensional array, will be empty
	 *               if no tokens have been saved
	 */
	protected function retrieveSessionToken() {
		$this->sessionToken = $this->generateSessionToken();
	}

	/**
	 * Saves the tokens so that they can be used by a later incarnation of this
	 * class.
	 *
	 * @return void
	 */
	public function persistSessionToken() {}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/formprotection/class.t3lib_formprotection_testing.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/formprotection/class.t3lib_formprotection_testing.php']);
}
?>