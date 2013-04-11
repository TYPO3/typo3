<?php
namespace TYPO3\CMS\Core\FormProtection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Helmut Hummel <helmut.hummel@typo3.org>
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
 * This class is a dummy implementation of the form protection,
 * which is used when no authentication is used.
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class DisabledFormProtection extends \TYPO3\CMS\Core\FormProtection\AbstractFormProtection {

	/**
	 * Disable parent constructor
	 */
	public function __construct() {

	}

	/**
	 * Disable parent method
	 */
	public function generateToken($formName, $action = '', $formInstanceName = '') {
		return 'dummyToken';
	}

	/**
	 * Disable parent method.
	 * Always return TRUE.
	 *
	 * @return boolean
	 */
	public function validateToken($tokenId, $formName, $action = '', $formInstanceName = '') {
		return TRUE;
	}

	/**
	 * Dummy implementation
	 */
	protected function createValidationErrorMessage() {

	}

	/**
	 * Dummy implementation
	 */
	protected function retrieveSessionToken() {

	}

	/**
	 * Dummy implementation
	 */
	public function persistSessionToken() {

	}

}


?>