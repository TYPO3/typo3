<?php
namespace TYPO3\CMS\Install\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * A "mock" to suppress database calls on $GLOBALS['TYPO3_DB'].
 * Used in TestSetup install tool action to prevent caching in \TYPO3\CMS\Core\Imaging\GraphicalFunctions
 */
class DatabaseConnectionMock {

	/**
	 * Get single row mock
	 *
	 * @return NULL
	 */
	public function exec_SELECTgetSingleRow() {
		return NULL;
	}

	/**
	 * Insert row mock
	 *
	 * @return boolean TRUE
	 */
	public function exec_INSERTquery() {
		return TRUE;
	}

	/**
	 * Quote string mock
	 *
	 * @param string $string
	 * @return string
	 */
	public function fullQuoteStr($string) {
		return $string;
	}

	/**
	 * Error mock
	 *
	 * @return string Empty string
	 */
	public function sql_error() {
		return '';
	}
}
