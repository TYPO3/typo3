<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Marcus Krause <marcus#exp2010@t3sec.info>
 *  (c) 2010 Steffen Kamper <info@sk-typo3.de>
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
 * class.tx_em_database.php
 *
 * Module: Extension manager - DB access
 *
 * $Id: class.tx_em_database.php 2082 2010-03-21 17:19:42Z steffenk $
 *
 * @author  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */

/**
 * DB access class for extension manager.
 *
 * Contains static methods for DB operations.
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-27
 * @package	 TYPO3
 * @subpackage  EM
 */
final class tx_em_Database {

	const MULTI_LINEBREAKS = "\n\n\n";

	const TABLE_REPOSITORY = 'sys_ter';

	const TABLE_EXTENSION = 'cache_extensions';


	/**
	 * Get the count of extensions in cache_extensions from a repository.
	 *
	 * If $repository parameter is obmitted, sum of all extensions will be
	 * returned.
	 *
	 * @access  public
	 * @param   integer  $repository  (optional) repository uid of extensions to count
	 * @return  integer  sum of extensions in database
	 */
	public function getExtensionCountFromRepository($repository = NULL) {
		if (is_null($repository)) {
			return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'DISTINCT extkey',
				self::TABLE_EXTENSION
			);
		} else {
			return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'DISTINCT extkey',
				self::TABLE_EXTENSION,
				'repository=' . intval($repository)
			);
		}
	}

	/**
	 * Get extension list from cache_extensions
	 *
	 * @param int $repository
	 * @param string $addFields
	 * @param string $andWhere
	 * @param string $order
	 * @param string $limit
	 * @return array
	 */
	public function getExtensionListFromRepository($repository, $addFields = '', $andWhere = '', $order = '', $limit = '') {
		$ret = array();
		$temp = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'count(*) AS count',
			'cache_extensions',
			'repository=' . intval($repository) . $andWhere,
			'extkey'
		);
		$ret['count'] = count($temp);

		$ret['results'] = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'cache_extensions.*, count(*) AS versions, cache_extensions.intversion AS maxintversion' .
			($addFields === '' ? '' : ',' . $addFields),
			'cache_extensions JOIN cache_extensions AS ce ON cache_extensions.extkey = ce.extkey',
			'cache_extensions.lastversion=1 AND cache_extensions.repository=' . intval($repository) . $andWhere,
			'ce.extkey',
			$order,
			$limit
		);
		//debug($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
		return $ret;
	}

	/**
	 * Get versions of extension
	 *
	 * @param int $repository
	 * @param string $extKey
	 * @return array $versions
	 */
	public function getExtensionVersionsFromRepository($repository, $extKey) {
		$versions = array();
		//TODO: implement
		return $versions;
	}

	/**
	 * Function inserts a repository object into database.
	 *
	 * @access  public
	 * @param   tx_em_Repository $repository  repository object
	 * @return  void
	 */
	public function updateRepository(tx_em_Repository $repository) {
		$repositoryData = array(
			'title' => $repository->getTitle(),
			'description' => $repository->getDescription(),
			'wsdl_url' => $repository->getWsdlUrl(),
			'mirror_url' => $repository->getMirrorListUrl(),
			'lastUpdated' => $repository->getLastUpdate(),
			'extCount' => $repository->getExtensionCount(),
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			self::TABLE_REPOSITORY,
			'uid=' . $repository->getId(),
			$repositoryData
		);

	}


	/**
	 * Function inserts a repository object into database.
	 *
	 * @access  public
	 * @param   tx_em_Repository $repository  repository object
	 * @return  integer  UID of the newly inserted repository object
	 */
	public function insertRepository(tx_em_Repository $repository) {
		$repositoryData = array(
			'title' => $repository->getTitle(),
			'description' => $repository->getDescription(),
			'wsdl_url' => $repository->getWsdlUrl(),
			'mirror_url' => $repository->getMirrorListUrl(),
			'lastUpdated' => $repository->getLastUpdate(),
			'extCount' => $repository->getExtensionCount(),
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			self::TABLE_REPOSITORY,
			$repositoryData
		);
		return $GLOBALS['TYPO3_DB']->sql_insert_id();
	}

	/**
	 * Deletes given Repository
	 *
	 * @param  tx_em_Repository $repository  repository object
	 * @return void
	 */
	public function deleteRepository(tx_em_Repository $repository) {
	 	$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			 self::TABLE_REPOSITORY,
			'uid=' . $repository->getId()
		 );
	}

	/**
	 * Updates ExtCount and lastUpdated  in Repository eg after import
	 * @param  int $extCount
	 * @param int $uid
	 * @return void
	 */
	public function updateRepositoryCount($extCount, $uid = 1) {
	 	$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			 self::TABLE_REPOSITORY,
			 'uid=' . intval($uid),
			 array (
			  	'lastUpdated' => time(),
				'extCount' => intval($extCount)
			 ));
	}

	/**
	 * Insert version
	 *
	 * @param  $arrFields
	 * @return void
	 */
	public function insertVersion(array $arrFields) {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(self::TABLE_EXTENSION, $arrFields);
	}

	/**
	 * Update the lastversion field after update
	 *
	 * @param int $repositoryUid
	 * @return void
	 */
	public function insertLastVersion($repositoryUid = 1) {
		$groupedRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'extkey, version, max(intversion) maxintversion',
			'cache_extensions',
			'repository=' . intval($repositoryUid),
			'extkey'
		);
		$extensions = count($groupedRows);

		if ($extensions > 0) {
			// set all to 0
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'cache_extensions',
				'lastversion=1 AND repository=' . intval($repositoryUid),
				array('lastversion' => 0)
			);

				// Find latest version of extensions and set lastversion to 1 for these
			foreach ($groupedRows as $row) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'cache_extensions',
					'extkey="' . $row['extkey'] . '" AND intversion="' . $row['maxintversion'] . '" AND repository=' . intval($repositoryUid),
					array('lastversion' => 1)
				);
			}
		}

		return $extensions;
	}


	/**
	 * Method finds and returns repository fields identified by its UID.
	 *
	 * @access  public
	 * @param   int  $uid  repository UID
	 */
	public function getRepositoryByUID($uid) {
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', self::TABLE_REPOSITORY, 'uid=' . intval($uid));

		return $row;
	}

	/**
	 * Method finds and returns repository identified by its title
	 *
	 * @param  $title
	 * @return
	 */
	public function getRepositoryByTitle($title) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			self::TABLE_REPOSITORY,
			'title=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($title,
			self::TABLE_REPOSITORY)
		);
	}

	/**
	 * Get available repositories
	 *
	 * @param string $where
	 * @return array
	 */
	public function getRepositories($where = NULL) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			self::TABLE_REPOSITORY,
			$where ? $where : ''
		);
	}

	/**
	 * Dump table content
	 * Is DBAL compliant, but the dump format is written as MySQL standard. If the INSERT statements should be imported in a DBMS using other quoting than MySQL they must first be translated. t3lib_sqlengine can parse these queries correctly and translate them somehow.
	 *
	 * @param	string		Table name
	 * @param	array		Field structure
	 * @return	string		SQL Content of dump (INSERT statements)
	 */
	function dumpTableContent($table, $fieldStructure) {

		// Substitution of certain characters (borrowed from phpMySQL):
		$search = array('\\', '\'', "\x00", "\x0a", "\x0d", "\x1a");
		$replace = array('\\\\', '\\\'', '\0', '\n', '\r', '\Z');

		$lines = array();

		// Select all rows from the table:
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, '');

		// Traverse the selected rows and dump each row as a line in the file:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$values = array();
			foreach ($fieldStructure as $field => $structure) {
				$values[] = isset($row[$field]) ? "'" . str_replace($search, $replace, $row[$field]) . "'" : 'NULL';
			}
			$lines[] = 'INSERT INTO ' . $table . ' VALUES (' . implode(', ', $values) . ');';
		}

		// Free DB result:
		$GLOBALS['TYPO3_DB']->sql_free_result($result);

		// Implode lines and return:
		return implode(LF, $lines);
	}

	/**
	 * Gets the table and field structure from database.
	 * Which fields and which tables are determined from the ext_tables.sql file
	 *
	 * @param	string		Array with table.field values
	 * @return	array		Array of tables and fields splitted.
	 */
	function getTableAndFieldStructure($parts) {
		// Instance of install tool
		$instObj = new t3lib_install();
		$dbFields = $instObj->getFieldDefinitions_database(TYPO3_db);

		$outTables = array();
		foreach ($parts as $table) {
			$sub = explode('.', $table);
			if ($sub[0] && isset($dbFields[$sub[0]])) {
				if ($sub[1]) {
					$key = explode('KEY:', $sub[1], 2);
					if (count($key) == 2 && !$key[0]) { // key:
						if (isset($dbFields[$sub[0]]['keys'][$key[1]])) {
							$outTables[$sub[0]]['keys'][$key[1]] = $dbFields[$sub[0]]['keys'][$key[1]];
						}
					} else {
						if (isset($dbFields[$sub[0]]['fields'][$sub[1]])) {
							$outTables[$sub[0]]['fields'][$sub[1]] = $dbFields[$sub[0]]['fields'][$sub[1]];
						}
					}
				} else {
					$outTables[$sub[0]] = $dbFields[$sub[0]];
				}
			}
		}

		return $outTables;
	}


	/**
	 * Makes a dump of the tables/fields definitions for an extension
	 *
	 * @param	array		Array with table => field/key definition arrays in
	 * @return	string		SQL for the table definitions
	 * @see dumpStaticTables()
	 */
	function dumpTableAndFieldStructure($arr) {
		$tables = array();

		if (count($arr)) {

			// Get file header comment:
			$tables[] = self::dumpHeader();

			// Traverse tables, write each table/field definition:
			foreach ($arr as $table => $fieldKeyInfo) {
				$tables[] = self::dumpTableHeader($table, $fieldKeyInfo);
			}
		}

		// Return result:
		return implode(LF . LF . LF, $tables);
	}

	/**
	 * Link to dump of database tables
	 *
	 * @param	array  $tablesArray
	 * @param	string $extKey
	 * @param	array  $additionalLinkParameter
	 * @return	string		HTML
	 */
	function dumpDataTablesLine($tablesArray, $extKey, $additionalLinkParameter = array()) {
		$tables = array();
		$tablesNA = array();
		$allTables = array_keys($GLOBALS['TYPO3_DB']->admin_get_tables());

		foreach ($tablesArray as $tableName) {
			if (in_array($tableName, $allTables)) {
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $tableName);
				$tables[$tableName] = '<tr><td>&nbsp;</td><td>
					<a class="t3-link dumpLink" href="' .
						htmlspecialchars(t3lib_div::linkThisScript(
							array_merge(array(
								'CMD[dumpTables]' => $tableName,
								'CMD[showExt]' => $extKey,
							), $additionalLinkParameter)
						)) .
						'" title="' .
						sprintf($GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_dump_table'),
							$tableName) .
						'">' . $tableName . '</a></td><td>&nbsp;&nbsp;&nbsp;</td><td>' .
						sprintf($GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_number_of_records'),
							$count) . '</td></tr>';
			} else {
				$tablesNA[$tableName] = '<tr><td>&nbsp;</td><td>' . $tableName . '</td><td>&nbsp;</td><td>' .
						$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_table_not_there') . '</td></tr>';
			}
		}
		$label = '<table border="0" cellpadding="0" cellspacing="0">' .
				implode('', array_merge($tables, $tablesNA)) .
				'</table>';
		if (count($tables)) {
			$label = '<a class="t3-link dumpLink" href="' .
					htmlspecialchars(t3lib_div::linkThisScript(
						array_merge(array(
							'CMD[dumpTables]' => implode(',', array_keys($tables)),
							'CMD[showExt]' => $extKey
						), $additionalLinkParameter)
					)) .
					'" title="' . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_dump_all_tables') . '">' .
					$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_download_all_data') . '</a><br /><br />' . $label;
		}
		else {
			$label = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_nothing_to_dump') . '<br /><br />' . $label;
		}
		return $label;
	}

	/**
	 * Dump content for static tables
	 *
	 * @param	string		Comma list of tables from which to dump content
	 * @return	string		Returns the content
	 * @see dumpTableAndFieldStructure()
	 */
	function dumpStaticTables($tableList) {
		$instObj = t3lib_div::makeInstance('t3lib_install');
		$dbFields = $instObj->getFieldDefinitions_database(TYPO3_db);

		$out = '';
		$parts = t3lib_div::trimExplode(',', $tableList, TRUE);

		// Traverse the table list and dump each:
		foreach ($parts as $table) {
			if (is_array($dbFields[$table]['fields'])) {
				$header = self::dumpHeader();
				$tableHeader = self::dumpTableHeader($table, $dbFields[$table], TRUE);
				$insertStatements = self::dumpTableContent($table, $dbFields[$table]['fields']);
				$out .= $header . self::MULTI_LINEBREAKS .
						$tableHeader . self::MULTI_LINEBREAKS .
						$insertStatements . self::MULTI_LINEBREAKS;
			} else {
				throw new RuntimeException(
					'TYPO3 Fatal Error: ' . $GLOBALS['LANG']->getLL('dumpStaticTables_table_not_found'),
					1270853983
				);
			}
		}
		unset($instObj);
		return $out;
	}

	/**
	 * Header comments of the SQL dump file
	 *
	 * @return	string		Table header
	 */
	function dumpHeader() {
		return trim('
# TYPO3 Extension Manager dump 1.1
#
# Host: ' . TYPO3_db_host . '    Database: ' . TYPO3_db . '
#--------------------------------------------------------
');
	}

	/**
	 * Dump CREATE TABLE definition
	 *
	 * @param	string		Table name
	 * @param	array		Field and key information (as provided from Install Tool class!)
	 * @param	boolean		If true, add "DROP TABLE IF EXISTS"
	 * @return	string		Table definition SQL
	 */
	function dumpTableHeader($table, $fieldKeyInfo, $dropTableIfExists = 0) {
		$lines = array();
		$dump = '';

		// Create field definitions
		if (is_array($fieldKeyInfo['fields'])) {
			foreach ($fieldKeyInfo['fields'] as $fieldN => $data) {
				$lines[] = '  ' . $fieldN . ' ' . $data;
			}
		}

		// Create index key definitions
		if (is_array($fieldKeyInfo['keys'])) {
			foreach ($fieldKeyInfo['keys'] as $fieldN => $data) {
				$lines[] = '  ' . $data;
			}
		}

		// Compile final output:
		if (count($lines)) {
			$dump = trim('
#
# Table structure for table "' . $table . '"
#
' . ($dropTableIfExists ? 'DROP TABLE IF EXISTS ' . $table . ';
' : '') . 'CREATE TABLE ' . $table . ' (
' . implode(',' . LF, $lines) . '
);');
		}

		return $dump;
	}

}

?>