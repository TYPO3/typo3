<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Objects of this kind contain a list of validation errors which occurred during
 * validation.
 *
 * @package Extbase
 * @subpackage extbase
 * @version $ID:$
 * @scope prototype
 */
class Tx_Extbase_Validation_Error {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Unknown validation error';

	/**
	 * @var string The error code
	 */
	protected $code = 1201447005;

	/**
	 * Constructs this error
	 *
	 * @param string $message: An english error message which is used if no other error message can be resolved
	 * @param integer $code: A unique error code
	 */
	public function __construct($message, $code) {
		$this->message = $message;
		$this->code = $code;
	}

	/**
	 * Returns the error message
	 * @return string The error message
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Returns the error code
	 * @return string The error code
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * Converts this error into a string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->message . ' (#' . $this->code . ')';
	}
}

?>