<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
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
 * A class representing a certain status
 *
 * @author		Ingo Renner <ingo@typo3.org>
 * @package		TYPO3
 * @subpackage	reports
 *
 * $Id$
 */
class tx_reports_reports_status_Status {

	const NOTICE  = -2;
	const INFO    = -1;
	const OK      = 0;
	const WARNING = 1;
	const ERROR   = 2;

	protected $title;
	protected $value;
	protected $message;
	protected $severity;

	/**
	 * constructor for class tx_reports_report_status_Status
	 *
	 * @param	string	the status' title
	 * @param	string	the status' value
	 * @param	string	an optional message further describing the status
	 * @param	integer	a severity level, one of
	 */
	public function __construct($title, $value, $message = '', $severity = self::OK) {
		$this->title    = (string) $title;
		$this->value    = (string) $value;
		$this->message  = (string) $message;

		$this->severity = t3lib_div::intInRange(
			$severity,
			self::NOTICE, self::ERROR, self::OK
		);
	}

	/**
	 * gets the status' title
	 *
	 * @return	string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * gets the status' value
	 *
	 * @return	string
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * gets the status' message (if any)
	 *
	 * @return	string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * gets the status' severity
	 *
	 * @return	integer
	 */
	public function getSeverity() {
		return $this->severity;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_status.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_status.php']);
}

?>