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
 * An object representation of a generic error. Subclass this to create
 * more specific errors if necessary.
 *
 * @package Extbase
 * @subpackage Error
 * @version $Id: Error.php 1729 2009-11-25 21:37:20Z stucki $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class Tx_Extbase_Error_Error {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Unknown error';

	/**
	 * @var string The error code
	 */
	protected $code;

	/**
	 * Constructs this error
	 *
	 * @param string $message: An english error message which is used if no other error message can be resolved
	 * @param integer $code: A unique error code
	 * @api
	 */
	public function __construct($message, $code) {
		$this->message = $message;
		$this->code = $code;
	}

	/**
	 * Returns the error message
	 * @return string The error message
	 * @api
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Returns the error code
	 * @return string The error code
	 * @api
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * Converts this error into a string
	 *
	 * @return string
	 * @api
	 */
	public function __toString() {
		return $this->message . ' (#' . $this->code . ')';
	}
}
?>