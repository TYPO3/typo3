<?php
namespace TYPO3\CMS\Core\Database;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 */
class PdoHelper {

	/**
	 * Pumps the SQL into the database. Use for DDL only.
	 *
	 * Important: key definitions with length specifiers (needed for MySQL) must
	 * be given as "field"(xyz) - no space between double quote and parenthesis -
	 * so they can be removed automatically.
	 *
	 * @param PDO $databaseHandle
	 * @param string $pdoDriver
	 * @param string $pathAndFilename
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function importSql(\PDO $databaseHandle, $pdoDriver, $pathAndFilename) {
		$sql = file($pathAndFilename, FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);
		// Remove MySQL style key length delimiters (yuck!) if we are not setting up a MySQL db
		if (substr($pdoDriver, 0, 5) !== 'mysql') {
			$sql = preg_replace('/"\\([0-9]+\\)/', '"', $sql);
		}
		$statement = '';
		foreach ($sql as $line) {
			$statement .= ' ' . trim($line);
			if (substr($statement, -1) === ';') {
				$databaseHandle->exec($statement);
				$statement = '';
			}
		}
	}

}


?>