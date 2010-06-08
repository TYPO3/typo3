<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010 Christian Kuhn <lolli@schwarzbu.ch>
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
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A helper class for handling PDO databases
 * Backport of FLOW3 class PdoHelper, last synced version: 3528
 *
 * @author Karsten Dambekalns <karsten@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 * @version $Id$
 * @scope prototype
 */
class t3lib_PdoHelper {

	/**
	 * @var PDO
	 */
	protected $databaseHandle;

	/**
	 * @var string
	 */
	protected $pdoDriver;

	/**
	 * Construct the helper instance and set up PDO connection.
	 *
	 * @param string $dataSourceName
	 * @param string $user
	 * @param string $password
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($dataSourceName, $user, $password) {
		$splitdsn = explode(':', $dataSourceName, 2);
		$this->pdoDriver = $splitdsn[0];

		$this->databaseHandle = t3lib_div::makeInstance('PDO', $dataSourceName, $user, $password);
		$this->databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if ($this->pdoDriver === 'mysql') {
			$this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI_QUOTES\';');
		}
	}

	/**
	 * Pumps the SQL into the database. Use for DDL only.
	 *
	 * Important: key definitions with length specifiers (needed for MySQL) must
	 * be given as "field"(xyz) - no space between double quote and parenthesis -
	 * so they can be removed automatically.
	 *
	 * @param string $pathAndFilename
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function importSql($pathAndFilename) {
		$sql = file($pathAndFilename, FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);

			// Remove MySQL style key length delimiters (yuck!) if we are not setting up a MySQL db
		if ($this->pdoDriver !== 'mysql') {
			$sql = preg_replace('/"\([0-9]+\)/', '"', $sql);
		}

		$statement = '';
		foreach ($sql as $line) {
			$statement .= ' ' . trim($line);
			if (substr($statement, -1) === ';') {
				$this->databaseHandle->query($statement);
				$statement = '';
			}
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_pdohelper.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_pdohelper.php']);
}

?>
