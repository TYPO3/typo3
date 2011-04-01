<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Steffen Gebert <steffen.gebert@typo3.org>
 *  All rights reserved
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
 * HTTP Status Exception
 *
 * @author	Steffen Gebert <steffen.gebert@typo3.org>
 * @package TYPO3
 * @subpackage error
 */
class t3lib_error_http_StatusException extends t3lib_error_Exception {

	/**
	 * @var array HTTP Status Header lines
	 */
	protected $statusHeaders;

	/**
	 * @var string Title of the message
	 */
	protected $title = 'Oops, an error occurred!';

	/**
	 * Constructor for this Status Exception
	 *
	 * @param string|array $statusHeaders HTTP Status header line(s)
	 * @param string $title Title of the error message
	 * @param string $message Error Message
	 * @param int $code Exception Code
	 */
	public function __construct($statusHeaders, $message, $title = '', $code = 0) {
		if (is_array($statusHeaders)) {
			$this->statusHeaders = $statusHeaders;
		} else {
			$this->statusHeaders = array($statusHeaders);
		}
		$this->title = $title ? $title : $this->title;
		parent::__construct($message, $code);
	}

	/**
	 * Setter for the title.
	 *
	 * @param  string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Getter for the title.
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Getter for the Status Header.
	 *
	 * @return string
	 */
	public function getStatusHeaders() {
		return $this->statusHeaders;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/t3lib_error_http_statusexception'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/t3lib_error_http_statusexception']);
}

?>