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
	 * Constructor for this Status Exception
	 *
	 * @param string $statusHeader HTTP Status header line
	 * @param string $message Error Message
	 * @param int $code Exception Code
	 */
	public function __construct($statusHeader, $message, $code = 0) {
		header($statusHeader);
		parent::__construct($message, $code);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/t3lib_error_http_statusexception'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/error/t3lib_error_http_statusexception']);
}

?>