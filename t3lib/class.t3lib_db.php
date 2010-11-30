<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Contains the class "t3lib_db" containing functions for building SQL queries
 * and mysql wrappers, thus providing a foundational API to all database
 * interaction.
 * This class is instantiated globally as $TYPO3_DB in TYPO3 scripts.
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  138: class t3lib_DB
 *
 *			  SECTION: Query execution
 *  175:	 function exec_INSERTquery($table,$fields_values,$no_quote_fields=FALSE)
 *  192:	 function exec_UPDATEquery($table,$where,$fields_values,$no_quote_fields=FALSE)
 *  206:	 function exec_DELETEquery($table,$where)
 *  225:	 function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')
 *  250:	 function exec_SELECT_mm_query($select,$local_table,$mm_table,$foreign_table,$whereClause='',$groupBy='',$orderBy='',$limit='')
 *  278:	 function exec_SELECT_queryArray($queryParts)
 *  301:	 function exec_SELECTgetRows($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='',$uidIndexField='')
 *
 *			  SECTION: Query building
 *  346:	 function INSERTquery($table,$fields_values,$no_quote_fields=FALSE)
 *  381:	 function UPDATEquery($table,$where,$fields_values,$no_quote_fields=FALSE)
 *  422:	 function DELETEquery($table,$where)
 *  451:	 function SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')
 *  492:	 function listQuery($field, $value, $table)
 *  506:	 function searchQuery($searchWords,$fields,$table)
 *
 *			  SECTION: Various helper functions
 *  552:	 function fullQuoteStr($str, $table)
 *  569:	 function fullQuoteArray($arr, $table, $noQuote=FALSE)
 *  596:	 function quoteStr($str, $table)
 *  612:	 function escapeStrForLike($str, $table)
 *  625:	 function cleanIntArray($arr)
 *  641:	 function cleanIntList($list)
 *  655:	 function stripOrderBy($str)
 *  669:	 function stripGroupBy($str)
 *  681:	 function splitGroupOrderLimit($str)
 *
 *			  SECTION: MySQL wrapper functions
 *  749:	 function sql($db,$query)
 *  763:	 function sql_query($query)
 *  776:	 function sql_error()
 *  788:	 function sql_num_rows($res)
 *  800:	 function sql_fetch_assoc($res)
 *  813:	 function sql_fetch_row($res)
 *  825:	 function sql_free_result($res)
 *  836:	 function sql_insert_id()
 *  847:	 function sql_affected_rows()
 *  860:	 function sql_data_seek($res,$seek)
 *  873:	 function sql_field_type($res,$pointer)
 *  887:	 function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password)
 *  915:	 function sql_select_db($TYPO3_db)
 *
 *			  SECTION: SQL admin functions
 *  947:	 function admin_get_dbs()
 *  965:	 function admin_get_tables()
 *  984:	 function admin_get_fields($tableName)
 * 1002:	 function admin_get_keys($tableName)
 * 1020:	 function admin_query($query)
 *
 *			  SECTION: Connecting service
 * 1048:	 function connectDB()
 *
 *			  SECTION: Debugging
 * 1086:	 function debug($func)
 *
 * TOTAL FUNCTIONS: 42
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * TYPO3 "database wrapper" class (new in 3.6.0)
 * This class contains
 * - abstraction functions for executing INSERT/UPDATE/DELETE/SELECT queries ("Query execution"; These are REQUIRED for all future connectivity to the database, thus ensuring DBAL compliance!)
 * - functions for building SQL queries (INSERT/UPDATE/DELETE/SELECT) ("Query building"); These are transitional functions for building SQL queries in a more automated way. Use these to build queries instead of doing it manually in your code!
 * - mysql() wrapper functions; These are transitional functions. By a simple search/replace you should be able to substitute all mysql*() calls with $GLOBALS['TYPO3_DB']->sql*() and your application will work out of the box. YOU CANNOT (legally) use any mysql functions not found as wrapper functions in this class!
 * See the Project Coding Guidelines (doc_core_cgl) for more instructions on best-practise
 *
 * This class is not in itself a complete database abstraction layer but can be extended to be a DBAL (by extensions, see "dbal" for example)
 * ALL connectivity to the database in TYPO3 must be done through this class!
 * The points of this class are:
 * - To direct all database calls through this class so it becomes possible to implement DBAL with extensions.
 * - To keep it very easy to use for developers used to MySQL in PHP - and preserve as much performance as possible when TYPO3 is used with MySQL directly...
 * - To create an interface for DBAL implemented by extensions; (Eg. making possible escaping characters, clob/blob handling, reserved words handling)
 * - Benchmarking the DB bottleneck queries will become much easier; Will make it easier to find optimization possibilities.
 *
 * USE:
 * In all TYPO3 scripts the global variable $TYPO3_DB is an instance of this class. Use that.
 * Eg.		 $GLOBALS['TYPO3_DB']->sql_fetch_assoc()
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_DB {


		// Debug:
	var $debugOutput = FALSE; // Set "TRUE" if you want database errors outputted.
	var $debug_lastBuiltQuery = ''; // Internally: Set to last built query (not necessarily executed...)
	var $store_lastBuiltQuery = FALSE; // Set "TRUE" if you want the last built query to be stored in $debug_lastBuiltQuery independent of $this->debugOutput
	var $explainOutput = 0; // Set this to 1 to get queries explained (devIPmask must match). Set the value to 2 to the same but disregarding the devIPmask. There is an alternative option to enable explain output in the admin panel under "TypoScript", which will produce much nicer output, but only works in FE.

		// Default link identifier:
	var $link = FALSE;

		// Default character set, applies unless character set or collation are explicitely set
	var $default_charset = 'utf8';


	/************************************
	 *
	 * Query execution
	 *
	 * These functions are the RECOMMENDED DBAL functions for use in your applications
	 * Using these functions will allow the DBAL to use alternative ways of accessing data (contrary to if a query is returned!)
	 * They compile a query AND execute it immediately and then return the result
	 * This principle heightens our ability to create various forms of DBAL of the functions.
	 * Generally: We want to return a result pointer/object, never queries.
	 * Also, having the table name together with the actual query execution allows us to direct the request to other databases.
	 *
	 **************************************/

	/**
	 * Creates and executes an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * Using this function specifically allows us to handle BLOB and CLOB fields depending on DB
	 * Usage count/core: 47
	 *
	 * @param	string		Table name
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param	string/array		See fullQuoteArray()
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {
		$res = mysql_query($this->INSERTquery($table, $fields_values, $no_quote_fields), $this->link);
		if ($this->debugOutput) {
			$this->debug('exec_INSERTquery');
		}
		return $res;
	}

	/**
	 * Creates and executes an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param	string		Table name
	 * @param	array		Field names
	 * @param	array		Table rows. Each row should be an array with field values mapping to $fields
	 * @param	string/array		See fullQuoteArray()
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		$res = mysql_query($this->INSERTmultipleRows($table, $fields, $rows, $no_quote_fields), $this->link);
		if ($this->debugOutput) {
			$this->debug('exec_INSERTmultipleRows');
		}
		return $res;
	}

	/**
	 * Creates and executes an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Using this function specifically allow us to handle BLOB and CLOB fields depending on DB
	 * Usage count/core: 50
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param	string/array		See fullQuoteArray()
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
		$res = mysql_query($this->UPDATEquery($table, $where, $fields_values, $no_quote_fields), $this->link);
		if ($this->debugOutput) {
			$this->debug('exec_UPDATEquery');
		}
		return $res;
	}

	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 40
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_DELETEquery($table, $where) {
		$res = mysql_query($this->DELETEquery($table, $where), $this->link);
		if ($this->debugOutput) {
			$this->debug('exec_DELETEquery');
		}
		return $res;
	}

	/**
	 * Creates and executes a SELECT SQL-statement
	 * Using this function specifically allow us to handle the LIMIT feature independently of DB.
	 * Usage count/core: 340
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
		$query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
		$res = mysql_query($query, $this->link);

		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}
		if ($this->explainOutput) {
			$this->explain($query, $from_table, $this->sql_num_rows($res));
		}

		return $res;
	}

	/**
	 * Creates and executes a SELECT query, selecting fields ($select) from two/three tables joined
	 * Use $mm_table together with $local_table or $foreign_table to select over two tables. Or use all three tables to select the full MM-relation.
	 * The JOIN is done with [$local_table].uid <--> [$mm_table].uid_local  / [$mm_table].uid_foreign <--> [$foreign_table].uid
	 * The function is very useful for selecting MM-relations between tables adhering to the MM-format used by TCE (TYPO3 Core Engine). See the section on $TCA in Inside TYPO3 for more details.
	 *
	 * Usage: 12 (spec. ext. sys_action, sys_messages, sys_todos)
	 *
	 * @param	string		Field list for SELECT
	 * @param	string		Tablename, local table
	 * @param	string		Tablename, relation table
	 * @param	string		Tablename, foreign table
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT! You have to prepend 'AND ' to this parameter yourself!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	pointer		MySQL result pointer / DBAL object
	 * @see exec_SELECTquery()
	 */
	function exec_SELECT_mm_query($select, $local_table, $mm_table, $foreign_table, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '') {
		if ($foreign_table == $local_table) {
			$foreign_table_as = $foreign_table . uniqid('_join');
		}

		$mmWhere = $local_table ? $local_table . '.uid=' . $mm_table . '.uid_local' : '';
		$mmWhere .= ($local_table AND $foreign_table) ? ' AND ' : '';

		$tables = ($local_table ? $local_table . ',' : '') . $mm_table;

		if ($foreign_table) {
			$mmWhere .= ($foreign_table_as ? $foreign_table_as : $foreign_table) . '.uid=' . $mm_table . '.uid_foreign';
			$tables .= ',' . $foreign_table . ($foreign_table_as ? ' AS ' . $foreign_table_as : '');
		}

		return $this->exec_SELECTquery(
			$select,
			$tables,
				// whereClauseMightContainGroupOrderBy
				$mmWhere . ' ' . $whereClause,
			$groupBy,
			$orderBy,
			$limit
		);
	}

	/**
	 * Executes a select based on input query parts array
	 *
	 * Usage: 9
	 *
	 * @param	array		Query parts array
	 * @return	pointer		MySQL select result pointer / DBAL object
	 * @see exec_SELECTquery()
	 */
	function exec_SELECT_queryArray($queryParts) {
		return $this->exec_SELECTquery(
			$queryParts['SELECT'],
			$queryParts['FROM'],
			$queryParts['WHERE'],
			$queryParts['GROUPBY'],
			$queryParts['ORDERBY'],
			$queryParts['LIMIT']
		);
	}

	/**
	 * Creates and executes a SELECT SQL-statement AND traverse result set and returns array with records in.
	 *
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		If set, the result array will carry this field names value as index. Requires that field to be selected of course!
	 * @return	array		Array of rows.
	 */
	function exec_SELECTgetRows($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '') {
		$res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}

		if (!$this->sql_error()) {
			$output = array();

			if ($uidIndexField) {
				while ($tempRow = $this->sql_fetch_assoc($res)) {
					$output[$tempRow[$uidIndexField]] = $tempRow;
				}
			} else {
				while ($output[] = $this->sql_fetch_assoc($res)) {
					;
				}
				array_pop($output);
			}
			$this->sql_free_result($res);
		}
		return $output;
	}

	/**
	 * Creates and executes a SELECT SQL-statement AND gets a result set and returns an array with a single record in.
	 * LIMIT is automatically set to 1 and can not be overridden.
	 *
	 * @param string $select_fields: List of fields to select from the table.
	 * @param string $from_table: Table(s) from which to select.
	 * @param string $where_clause: Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param string $groupBy: Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy: Optional ORDER BY field(s), if none, supply blank string.
	 * @param boolean $numIndex: If set, the result will be fetched with sql_fetch_row, otherwise sql_fetch_assoc will be used.
	 * @return array Single row or NULL if it fails.
	 */
	public function exec_SELECTgetSingleRow($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $numIndex = FALSE) {
		$res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, '1');
		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}

		$output = NULL;
		if ($res) {
			if ($numIndex) {
				$output = $this->sql_fetch_row($res);
			} else {
				$output = $this->sql_fetch_assoc($res);
			}
			$this->sql_free_result($res);
		}
		return $output;
	}

	/**
	 * Counts the number of rows in a table.
	 *
	 * @param	string		$field: Name of the field to use in the COUNT() expression (e.g. '*')
	 * @param	string		$table: Name of the table to count rows for
	 * @param	string		$where: (optional) WHERE statement of the query
	 * @return	mixed		Number of rows counter (integer) or false if something went wrong (boolean)
	 */
	public function exec_SELECTcountRows($field, $table, $where = '') {
		$count = FALSE;
		$resultSet = $this->exec_SELECTquery('COUNT(' . $field . ')', $table, $where);
		if ($resultSet !== FALSE) {
			list($count) = $this->sql_fetch_row($resultSet);
			$this->sql_free_result($resultSet);
		}
		return $count;
	}

	/**
	 * Truncates a table.
	 *
	 * @param	string		Database tablename
	 * @return	mixed		Result from handler
	 */
	public function exec_TRUNCATEquery($table) {
		$res = mysql_query($this->TRUNCATEquery($table), $this->link);
		if ($this->debugOutput) {
			$this->debug('exec_TRUNCATEquery');
		}
		return $res;
	}


	/**************************************
	 *
	 * Query building
	 *
	 **************************************/

	/**
	 * Creates an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * Usage count/core: 4
	 *
	 * @param	string		See exec_INSERTquery()
	 * @param	array		See exec_INSERTquery()
	 * @param	string/array		See fullQuoteArray()
	 * @return	string		Full SQL query for INSERT (unless $fields_values does not contain any elements in which case it will be false)
	 */
	function INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {

			// Table and fieldnames should be "SQL-injection-safe" when supplied to this
			// function (contrary to values in the arrays which may be insecure).
		if (is_array($fields_values) && count($fields_values)) {

				// quote and escape values
			$fields_values = $this->fullQuoteArray($fields_values, $table, $no_quote_fields);

				// Build query:
			$query = 'INSERT INTO ' . $table .
					' (' . implode(',', array_keys($fields_values)) . ') VALUES ' .
					'(' . implode(',', $fields_values) . ')';

				// Return query:
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		}
	}

	/**
	 * Creates an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param	string		Table name
	 * @param	array		Field names
	 * @param	array		Table rows. Each row should be an array with field values mapping to $fields
	 * @param	string/array		See fullQuoteArray()
	 * @return	string		Full SQL query for INSERT (unless $rows does not contain any elements in which case it will be false)
	 */
	public function INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this
			// function (contrary to values in the arrays which may be insecure).
		if (count($rows)) {
				// Build query:
			$query = 'INSERT INTO ' . $table .
					' (' . implode(', ', $fields) . ') VALUES ';

			$rowSQL = array();
			foreach ($rows as $row) {
					// quote and escape values
				$row = $this->fullQuoteArray($row, $table, $no_quote_fields);
				$rowSQL[] = '(' . implode(', ', $row) . ')';
			}

			$query .= implode(', ', $rowSQL);

				// Return query:
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}

			return $query;
		}
	}

	/**
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Usage count/core: 6
	 *
	 * @param	string		See exec_UPDATEquery()
	 * @param	string		See exec_UPDATEquery()
	 * @param	array		See exec_UPDATEquery()
	 * @param	array		See fullQuoteArray()
	 * @return	string		Full SQL query for UPDATE
	 */
	function UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this
			// function (contrary to values in the arrays which may be insecure).
		if (is_string($where)) {
			$fields = array();
			if (is_array($fields_values) && count($fields_values)) {

					// quote and escape values
				$nArr = $this->fullQuoteArray($fields_values, $table, $no_quote_fields);

				foreach ($nArr as $k => $v) {
					$fields[] = $k . '=' . $v;
				}
			}

				// Build query:
			$query = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) .
					(strlen($where) > 0 ? ' WHERE ' . $where : '');

			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		} else {
			throw new InvalidArgumentException(
				'TYPO3 Fatal Error: "Where" clause argument for UPDATE query was not a string in $this->UPDATEquery() !',
				1270853880
			);
		}
	}

	/**
	 * Creates a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 3
	 *
	 * @param	string		See exec_DELETEquery()
	 * @param	string		See exec_DELETEquery()
	 * @return	string		Full SQL query for DELETE
	 */
	function DELETEquery($table, $where) {
		if (is_string($where)) {

				// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			$query = 'DELETE FROM ' . $table .
					(strlen($where) > 0 ? ' WHERE ' . $where : '');

			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		} else {
			throw new InvalidArgumentException(
				'TYPO3 Fatal Error: "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !',
				1270853881
			);
		}
	}

	/**
	 * Creates a SELECT SQL-statement
	 * Usage count/core: 11
	 *
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @param	string		See exec_SELECTquery()
	 * @return	string		Full SQL query for SELECT
	 */
	function SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {

			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			// Build basic query:
		$query = 'SELECT ' . $select_fields . ' FROM ' . $from_table .
				(strlen($where_clause) > 0 ? ' WHERE ' . $where_clause : '');

			// Group by:
		$query .= (strlen($groupBy) > 0 ? ' GROUP BY ' . $groupBy : '');

			// Order by:
		$query .= (strlen($orderBy) > 0 ? ' ORDER BY ' . $orderBy : '');

			// Group by:
		$query .= (strlen($limit) > 0 ? ' LIMIT ' . $limit : '');

			// Return query:
		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}
		return $query;
	}

	/**
	 * Creates a SELECT SQL-statement to be used as subquery within another query.
	 * BEWARE: This method should not be overriden within DBAL to prevent quoting from happening.
	 *
	 * @param	string		$select_fields: List of fields to select from the table.
	 * @param	string		$from_table: Table from which to select.
	 * @param	string		$where_clause: Conditional WHERE statement
	 * @return	string		Full SQL query for SELECT
	 */
	public function SELECTsubquery($select_fields, $from_table, $where_clause) {
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			// Build basic query:
		$query = 'SELECT ' . $select_fields . ' FROM ' . $from_table .
				(strlen($where_clause) > 0 ? ' WHERE ' . $where_clause : '');

			// Return query:
		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}

		return $query;
	}

	/**
	 * Creates a TRUNCATE TABLE SQL-statement
	 *
	 * @param	string		See exec_TRUNCATEquery()
	 * @return	string		Full SQL query for TRUNCATE TABLE
	 */
	public function TRUNCATEquery($table) {
			// Table should be "SQL-injection-safe" when supplied to this function
			// Build basic query:
		$query = 'TRUNCATE TABLE ' . $table;

			// Return query:
		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}

		return $query;
	}

	/**
	 * Returns a WHERE clause that can find a value ($value) in a list field ($field)
	 * For instance a record in the database might contain a list of numbers,
	 * "34,234,5" (with no spaces between). This query would be able to select that
	 * record based on the value "34", "234" or "5" regardless of their position in
	 * the list (left, middle or right).
	 * The value must not contain a comma (,)
	 * Is nice to look up list-relations to records or files in TYPO3 database tables.
	 *
	 * @param	string		Field name
	 * @param	string		Value to find in list
	 * @param	string		Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @return	string		WHERE clause for a query
	 */
	public function listQuery($field, $value, $table) {
		$value = (string) $value;
		if (strpos(',', $value) !== FALSE) {
			throw new InvalidArgumentException('$value must not contain a comma (,) in $this->listQuery() !');
		}
		$pattern = $this->quoteStr($value, $table);
		$where = 'FIND_IN_SET(\'' . $pattern . '\',' . $field . ')';
		return $where;
	}

	/**
	 * Returns a WHERE clause which will make an AND search for the words in the $searchWords array in any of the fields in array $fields.
	 *
	 * @param	array		Array of search words
	 * @param	array		Array of fields
	 * @param	string		Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @return	string		WHERE clause for search
	 */
	function searchQuery($searchWords, $fields, $table) {
		$queryParts = array();

		foreach ($searchWords as $sw) {
			$like = ' LIKE \'%' . $this->quoteStr($sw, $table) . '%\'';
			$queryParts[] = $table . '.' . implode($like . ' OR ' . $table . '.', $fields) . $like;
		}
		$query = '(' . implode(') AND (', $queryParts) . ')';
		return $query;
	}


	/**************************************
	 *
	 * Prepared Query Support
	 *
	 **************************************/

	/**
	 * Creates a SELECT prepared SQL statement.
	 *
	 * @param string See exec_SELECTquery()
	 * @param string See exec_SELECTquery()
	 * @param string See exec_SELECTquery()
	 * @param string See exec_SELECTquery()
	 * @param string See exec_SELECTquery()
	 * @param string See exec_SELECTquery()
	 * @param array $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as t3lib_db_PreparedStatement::PARAM_AUTOTYPE.
	 * @return t3lib_db_PreparedStatement Prepared statement
	 */
	public function prepare_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', array $input_parameters = array()) {
		$query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
		$preparedStatement = t3lib_div::makeInstance('t3lib_db_PreparedStatement', $query, $from_table, array());
		/* @var $preparedStatement t3lib_db_PreparedStatement */

			// Bind values to parameters
		foreach ($input_parameters as $key => $value) {
			$preparedStatement->bindValue($key, $value, t3lib_db_PreparedStatement::PARAM_AUTOTYPE);
		}

			// Return prepared statement
		return $preparedStatement;
	}

	/**
	 * Creates a SELECT prepared SQL statement based on input query parts array
	 *
	 * @param array Query parts array
	 * @param array $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as t3lib_db_PreparedStatement::PARAM_AUTOTYPE.
	 * @return t3lib_db_PreparedStatement Prepared statement
	 */
	public function prepare_SELECTqueryArray(array $queryParts, array $input_parameters = array()) {
		return $this->prepare_SELECTquery(
			$queryParts['SELECT'],
			$queryParts['FROM'],
			$queryParts['WHERE'],
			$queryParts['GROUPBY'],
			$queryParts['ORDERBY'],
			$queryParts['LIMIT'],
			$input_parameters
		);
	}

	/**
	 * Executes a prepared query.
	 * This method may only be called by t3lib_db_PreparedStatement.
	 *
	 * @param string $query The query to execute
	 * @param array $queryComponents The components of the query to execute
	 * @return pointer MySQL result pointer / DBAL object
	 * @access private
	 */
	public function exec_PREPAREDquery($query, array $queryComponents) {
		$res = mysql_query($query, $this->link);
		if ($this->debugOutput) {
			$this->debug('stmt_execute', $query);
		}
		return $res;
	}


	/**************************************
	 *
	 * Various helper functions
	 *
	 * Functions recommended to be used for
	 * - escaping values,
	 * - cleaning lists of values,
	 * - stripping of excess ORDER BY/GROUP BY keywords
	 *
	 **************************************/

	/**
	 * Escaping and quoting values for SQL statements.
	 * Usage count/core: 100
	 *
	 * @param	string		Input string
	 * @param	string		Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return	string		Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	function fullQuoteStr($str, $table) {
		return '\'' . mysql_real_escape_string($str, $this->link) . '\'';
	}

	/**
	 * Will fullquote all values in the one-dimensional array so they are ready to "implode" for an sql query.
	 *
	 * @param	array		Array with values (either associative or non-associative array)
	 * @param	string		Table name for which to quote
	 * @param	string/array		List/array of keys NOT to quote (eg. SQL functions) - ONLY for associative arrays
	 * @return	array		The input array with the values quoted
	 * @see cleanIntArray()
	 */
	function fullQuoteArray($arr, $table, $noQuote = FALSE) {
		if (is_string($noQuote)) {
			$noQuote = explode(',', $noQuote);
			// sanity check
		} elseif (!is_array($noQuote)) {
			$noQuote = FALSE;
		}

		foreach ($arr as $k => $v) {
			if ($noQuote === FALSE || !in_array($k, $noQuote)) {
				$arr[$k] = $this->fullQuoteStr($v, $table);
			}
		}
		return $arr;
	}

	/**
	 * Substitution for PHP function "addslashes()"
	 * Use this function instead of the PHP addslashes() function when you build queries - this will prepare your code for DBAL.
	 * NOTICE: You must wrap the output of this function in SINGLE QUOTES to be DBAL compatible. Unless you have to apply the single quotes yourself you should rather use ->fullQuoteStr()!
	 *
	 * Usage count/core: 20
	 *
	 * @param	string		Input string
	 * @param	string		Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return	string		Output string; Quotes (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	function quoteStr($str, $table) {
		return mysql_real_escape_string($str, $this->link);
	}

	/**
	 * Escaping values for SQL LIKE statements.
	 *
	 * @param	string		Input string
	 * @param	string		Table name for which to escape string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return	string		Output string; % and _ will be escaped with \ (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	function escapeStrForLike($str, $table) {
		return addcslashes($str, '_%');
	}

	/**
	 * Will convert all values in the one-dimensional array to integers.
	 * Useful when you want to make sure an array contains only integers before imploding them in a select-list.
	 * Usage count/core: 7
	 *
	 * @param	array		Array with values
	 * @return	array		The input array with all values passed through intval()
	 * @see cleanIntList()
	 */
	function cleanIntArray($arr) {
		foreach ($arr as $k => $v) {
			$arr[$k] = intval($arr[$k]);
		}
		return $arr;
	}

	/**
	 * Will force all entries in the input comma list to integers
	 * Useful when you want to make sure a commalist of supposed integers really contain only integers; You want to know that when you don't trust content that could go into an SQL statement.
	 * Usage count/core: 6
	 *
	 * @param	string		List of comma-separated values which should be integers
	 * @return	string		The input list but with every value passed through intval()
	 * @see cleanIntArray()
	 */
	function cleanIntList($list) {
		return implode(',', t3lib_div::intExplode(',', $list));
	}

	/**
	 * Removes the prefix "ORDER BY" from the input string.
	 * This function is used when you call the exec_SELECTquery() function and want to pass the ORDER BY parameter by can't guarantee that "ORDER BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 * Usage count/core: 11
	 *
	 * @param	string		eg. "ORDER BY title, uid"
	 * @return	string		eg. "title, uid"
	 * @see exec_SELECTquery(), stripGroupBy()
	 */
	function stripOrderBy($str) {
		return preg_replace('/^ORDER[[:space:]]+BY[[:space:]]+/i', '', trim($str));
	}

	/**
	 * Removes the prefix "GROUP BY" from the input string.
	 * This function is used when you call the SELECTquery() function and want to pass the GROUP BY parameter by can't guarantee that "GROUP BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 * Usage count/core: 1
	 *
	 * @param	string		eg. "GROUP BY title, uid"
	 * @return	string		eg. "title, uid"
	 * @see exec_SELECTquery(), stripOrderBy()
	 */
	function stripGroupBy($str) {
		return preg_replace('/^GROUP[[:space:]]+BY[[:space:]]+/i', '', trim($str));
	}

	/**
	 * Takes the last part of a query, eg. "... uid=123 GROUP BY title ORDER BY title LIMIT 5,2" and splits each part into a table (WHERE, GROUPBY, ORDERBY, LIMIT)
	 * Work-around function for use where you know some userdefined end to an SQL clause is supplied and you need to separate these factors.
	 * Usage count/core: 13
	 *
	 * @param	string		Input string
	 * @return	array
	 */
	function splitGroupOrderLimit($str) {
			// Prepending a space to make sure "[[:space:]]+" will find a space there
			// for the first element.
		$str = ' ' . $str;
			// Init output array:
		$wgolParts = array(
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => '',
		);

			// Find LIMIT:
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+LIMIT[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['LIMIT'] = trim($reg[2]);
			$str = $reg[1];
		}

			// Find ORDER BY:
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+ORDER[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['ORDERBY'] = trim($reg[2]);
			$str = $reg[1];
		}

			// Find GROUP BY:
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+GROUP[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['GROUPBY'] = trim($reg[2]);
			$str = $reg[1];
		}

			// Rest is assumed to be "WHERE" clause:
		$wgolParts['WHERE'] = $str;

		return $wgolParts;
	}


	/**************************************
	 *
	 * MySQL wrapper functions
	 * (For use in your applications)
	 *
	 **************************************/

	/**
	 * Executes query
	 * mysql() wrapper function
	 * Usage count/core: 0
	 *
	 * @param	string		Database name
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer / DBAL object
	 * @deprecated since TYPO3 3.6, will be removed in TYPO3 4.6
	 * @see sql_query()
	 */
	function sql($db, $query) {
		t3lib_div::logDeprecatedFunction();

		$res = mysql_query($query, $this->link);
		if ($this->debugOutput) {
			$this->debug('sql', $query);
		}
		return $res;
	}

	/**
	 * Executes query
	 * mysql_query() wrapper function
	 * Beware: Use of this method should be avoided as it is experimentally supported by DBAL. You should consider
	 *         using exec_SELECTquery() and similar methods instead.
	 * Usage count/core: 1
	 *
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer / DBAL object
	 */
	function sql_query($query) {
		$res = mysql_query($query, $this->link);
		if ($this->debugOutput) {
			$this->debug('sql_query', $query);
		}
		return $res;
	}

	/**
	 * Returns the error status on the last sql() execution
	 * mysql_error() wrapper function
	 * Usage count/core: 32
	 *
	 * @return	string		MySQL error string.
	 */
	function sql_error() {
		return mysql_error($this->link);
	}

	/**
	 * Returns the error number on the last sql() execution
	 * mysql_errno() wrapper function
	 *
	 * @return	int		MySQL error number.
	 */
	function sql_errno() {
		return mysql_errno($this->link);
	}

	/**
	 * Returns the number of selected rows.
	 * mysql_num_rows() wrapper function
	 * Usage count/core: 85
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @return	integer		Number of resulting rows
	 */
	function sql_num_rows($res) {
		if ($this->debug_check_recordset($res)) {
			return mysql_num_rows($res);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * mysql_fetch_assoc() wrapper function
	 * Usage count/core: 307
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @return	array		Associative array of result row.
	 */
	function sql_fetch_assoc($res) {
		if ($this->debug_check_recordset($res)) {
			return mysql_fetch_assoc($res);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains the values in numerical indices.
	 * mysql_fetch_row() wrapper function
	 * Usage count/core: 56
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @return	array		Array with result rows.
	 */
	function sql_fetch_row($res) {
		if ($this->debug_check_recordset($res)) {
			return mysql_fetch_row($res);
		} else {
			return FALSE;
		}
	}

	/**
	 * Free result memory
	 * mysql_free_result() wrapper function
	 * Usage count/core: 3
	 *
	 * @param	pointer		MySQL result pointer to free / DBAL object
	 * @return	boolean		Returns TRUE on success or FALSE on failure.
	 */
	function sql_free_result($res) {
		if ($this->debug_check_recordset($res)) {
			return mysql_free_result($res);
		} else {
			return FALSE;
		}
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 * mysql_insert_id() wrapper function
	 * Usage count/core: 13
	 *
	 * @return	integer		The uid of the last inserted record.
	 */
	function sql_insert_id() {
		return mysql_insert_id($this->link);
	}

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
	 * mysql_affected_rows() wrapper function
	 * Usage count/core: 1
	 *
	 * @return	integer		Number of rows affected by last query
	 */
	function sql_affected_rows() {
		return mysql_affected_rows($this->link);
	}

	/**
	 * Move internal result pointer
	 * mysql_data_seek() wrapper function
	 * Usage count/core: 3
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @param	integer		Seek result number.
	 * @return	boolean		Returns TRUE on success or FALSE on failure.
	 */
	function sql_data_seek($res, $seek) {
		if ($this->debug_check_recordset($res)) {
			return mysql_data_seek($res, $seek);
		} else {
			return FALSE;
		}
	}

	/**
	 * Get the type of the specified field in a result
	 * mysql_field_type() wrapper function
	 * Usage count/core: 2
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @param	integer		Field index.
	 * @return	string		Returns the name of the specified field index
	 */
	function sql_field_type($res, $pointer) {
		if ($this->debug_check_recordset($res)) {
			return mysql_field_type($res, $pointer);
		} else {
			return FALSE;
		}
	}

	/**
	 * Open a (persistent) connection to a MySQL server
	 * mysql_pconnect() wrapper function
	 * Usage count/core: 12
	 *
	 * @param	string		Database host IP/domain
	 * @param	string		Username to connect with.
	 * @param	string		Password to connect with.
	 * @return	pointer		Returns a positive MySQL persistent link identifier on success, or FALSE on error.
	 */
	function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password) {
			// mysql_error() is tied to an established connection
			// if the connection fails we need a different method to get the error message
		@ini_set('track_errors', 1);
		@ini_set('html_errors', 0);

			// check if MySQL extension is loaded
		if (!extension_loaded('mysql')) {
			$message = 'Database Error: It seems that MySQL support for PHP is not installed!';
			throw new RuntimeException($message, 1271492606);
		}

			// Check for client compression
		$isLocalhost = ($TYPO3_db_host == 'localhost' || $TYPO3_db_host == '127.0.0.1');
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['no_pconnect']) {
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['dbClientCompress'] && !$isLocalhost) {
					// We use PHP's default value for 4th parameter (new_link), which is false.
					// See PHP sources, for example: file php-5.2.5/ext/mysql/php_mysql.c,
					// function php_mysql_do_connect(), near line 525
				$this->link = @mysql_connect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password, FALSE, MYSQL_CLIENT_COMPRESS);
			} else {
				$this->link = @mysql_connect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password);
			}
		} else {
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['dbClientCompress'] && !$isLocalhost) {
					// See comment about 4th parameter in block above
				$this->link = @mysql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password, MYSQL_CLIENT_COMPRESS);
			} else {
				$this->link = @mysql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password);
			}
		}

		$error_msg = $php_errormsg;
		@ini_restore('track_errors');
		@ini_restore('html_errors');

		if (!$this->link) {
			t3lib_div::sysLog('Could not connect to MySQL server ' . $TYPO3_db_host .
					' with user ' . $TYPO3_db_username . ': ' . $error_msg,
				'Core',
				4
			);
		} else {
			$setDBinit = t3lib_div::trimExplode(LF, str_replace("' . LF . '", LF, $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']), TRUE);
			foreach ($setDBinit as $v) {
				if (mysql_query($v, $this->link) === FALSE) {
					t3lib_div::sysLog('Could not initialize DB connection with query "' . $v .
							'": ' . mysql_error($this->link),
						'Core',
						3
					);
				}
			}
		}

		return $this->link;
	}

	/**
	 * Select a MySQL database
	 * mysql_select_db() wrapper function
	 * Usage count/core: 8
	 *
	 * @param	string		Database to connect to.
	 * @return	boolean		Returns TRUE on success or FALSE on failure.
	 */
	function sql_select_db($TYPO3_db) {
		$ret = @mysql_select_db($TYPO3_db, $this->link);
		if (!$ret) {
			t3lib_div::sysLog('Could not select MySQL database ' . $TYPO3_db . ': ' .
					mysql_error(),
				'Core',
				4
			);
		}
		return $ret;
	}


	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/

	/**
	 * Listing databases from current MySQL connection. NOTICE: It WILL try to select those databases and thus break selection of current database.
	 * This is only used as a service function in the (1-2-3 process) of the Install Tool.
	 * In any case a lookup should be done in the _DEFAULT handler DBMS then.
	 * Use in Install Tool only!
	 * Usage count/core: 1
	 *
	 * @return	array		Each entry represents a database name
	 */
	function admin_get_dbs() {
		$dbArr = array();
		$db_list = mysql_list_dbs($this->link);
		while ($row = mysql_fetch_object($db_list)) {
			if ($this->sql_select_db($row->Database)) {
				$dbArr[] = $row->Database;
			}
		}
		return $dbArr;
	}

	/**
	 * Returns the list of tables from the default database, TYPO3_db (quering the DBMS)
	 * In a DBAL this method should 1) look up all tables from the DBMS  of
	 * the _DEFAULT handler and then 2) add all tables *configured* to be managed by other handlers
	 * Usage count/core: 2
	 *
	 * @return	array		Array with tablenames as key and arrays with status information as value
	 */
	function admin_get_tables() {
		$whichTables = array();

		$tables_result = mysql_query('SHOW TABLE STATUS FROM `' . TYPO3_db . '`', $this->link);
		if (!mysql_error()) {
			while ($theTable = mysql_fetch_assoc($tables_result)) {
				$whichTables[$theTable['Name']] = $theTable;
			}

			$this->sql_free_result($tables_result);
		}

		return $whichTables;
	}

	/**
	 * Returns information about each field in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 * This function is important not only for the Install Tool but probably for
	 * DBALs as well since they might need to look up table specific information
	 * in order to construct correct queries. In such cases this information should
	 * probably be cached for quick delivery.
	 *
	 * @param	string		Table name
	 * @return	array		Field information in an associative array with fieldname => field row
	 */
	function admin_get_fields($tableName) {
		$output = array();

		$columns_res = mysql_query('SHOW COLUMNS FROM `' . $tableName . '`', $this->link);
		while ($fieldRow = mysql_fetch_assoc($columns_res)) {
			$output[$fieldRow['Field']] = $fieldRow;
		}

		$this->sql_free_result($columns_res);

		return $output;
	}

	/**
	 * Returns information about each index key in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 *
	 * @param	string		Table name
	 * @return	array		Key information in a numeric array
	 */
	function admin_get_keys($tableName) {
		$output = array();

		$keyRes = mysql_query('SHOW KEYS FROM `' . $tableName . '`', $this->link);
		while ($keyRow = mysql_fetch_assoc($keyRes)) {
			$output[] = $keyRow;
		}

		$this->sql_free_result($keyRes);

		return $output;
	}

	/**
	 * Returns information about the character sets supported by the current DBM
	 * This function is important not only for the Install Tool but probably for
	 * DBALs as well since they might need to look up table specific information
	 * in order to construct correct queries. In such cases this information should
	 * probably be cached for quick delivery.
	 *
	 * This is used by the Install Tool to convert tables tables with non-UTF8 charsets
	 * Use in Install Tool only!
	 *
	 * @return	array		Array with Charset as key and an array of "Charset", "Description", "Default collation", "Maxlen" as values
	 */
	function admin_get_charsets() {
		$output = array();

		$columns_res = mysql_query('SHOW CHARACTER SET', $this->link);
		if ($columns_res) {
			while (($row = mysql_fetch_assoc($columns_res))) {
				$output[$row['Charset']] = $row;
			}

			$this->sql_free_result($columns_res);
		}

		return $output;
	}

	/**
	 * mysql() wrapper function, used by the Install Tool and EM for all queries regarding management of the database!
	 * Usage count/core: 10
	 *
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer
	 */
	function admin_query($query) {
		$res = mysql_query($query, $this->link);
		if ($this->debugOutput) {
			$this->debug('admin_query', $query);
		}
		return $res;
	}


	/******************************
	 *
	 * Connecting service
	 *
	 ******************************/

	/**
	 * Connects to database for TYPO3 sites:
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param string $db
	 * @return	void
	 */
	function connectDB($host = TYPO3_db_host, $user = TYPO3_db_username, $password = TYPO3_db_password, $db = TYPO3_db) {
		if ($this->sql_pconnect($host, $user, $password)) {
			if (!$db) {
				throw new RuntimeException(
					'TYPO3 Fatal Error: No database selected!',
					1270853882
				);
			} elseif (!$this->sql_select_db($db)) {
				throw new RuntimeException(
					'TYPO3 Fatal Error: Cannot connect to the current database, "' . $db . '"!',
					1270853883
				);
			}
		} else {
			throw new RuntimeException(
				'TYPO3 Fatal Error: The current username, password or host was not accepted when the connection to the database was attempted to be established!',
				1270853884
			);
		}
	}

	/**
	 * Checks if database is connected
	 *
	 * @return boolean
	 */
	public function isConnected() {
		return is_resource($this->link);
	}


	/******************************
	 *
	 * Debugging
	 *
	 ******************************/

	/**
	 * Debug function: Outputs error if any
	 *
	 * @param	string		Function calling debug()
	 * @param	string		Last query if not last built query
	 * @return	void
	 */
	function debug($func, $query = '') {

		$error = $this->sql_error();
		if ($error || $this->debugOutput == 2) {
			debug(
				array(
					'caller' => 't3lib_DB::' . $func,
					'ERROR' => $error,
					'lastBuiltQuery' => ($query ? $query : $this->debug_lastBuiltQuery),
					'debug_backtrace' => t3lib_utility_Debug::debugTrail(),
				),
				$func,
					is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'], 'debug')) ? '' : 'DB Error'
			);
		}
	}

	/**
	 * Checks if recordset is valid and writes debugging inormation into devLog if not.
	 *
	 * @param	resource	$res	Recordset
	 * @return	boolean	<code>false</code> if recordset is not valid
	 */
	function debug_check_recordset($res) {
		if (!$res) {
			$trace = FALSE;
			$msg = 'Invalid database result resource detected';
			$trace = debug_backtrace();
			array_shift($trace);
			$cnt = count($trace);
			for ($i = 0; $i < $cnt; $i++) {
					// complete objects are too large for the log
				if (isset($trace['object'])) {
					unset($trace['object']);
				}
			}
			$msg .= ': function t3lib_DB->' . $trace[0]['function'] . ' called from file ' .
					substr($trace[0]['file'], strlen(PATH_site) + 2) . ' in line ' .
					$trace[0]['line'];
			t3lib_div::sysLog($msg . '. Use a devLog extension to get more details.', 'Core/t3lib_db', 3);
				// Send to devLog if enabled
			if (TYPO3_DLOG) {
				$debugLogData = array(
					'SQL Error' => $this->sql_error(),
					'Backtrace' => $trace,
				);
				if ($this->debug_lastBuiltQuery) {
					$debugLogData = array('SQL Query' => $this->debug_lastBuiltQuery) + $debugLogData;
				}
				t3lib_div::devLog($msg . '.', 'Core/t3lib_db', 3, $debugLogData);
			}

			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Explain select queries
	 * If $this->explainOutput is set, SELECT queries will be explained here. Only queries with more than one possible result row will be displayed.
	 * The output is either printed as raw HTML output or embedded into the TS admin panel (checkbox must be enabled!)
	 *
	 * TODO: Feature is not DBAL-compliant
	 *
	 * @param	string		SQL query
	 * @param	string		Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	integer		Number of resulting rows
	 * @return	boolean		True if explain was run, false otherwise
	 */
	protected function explain($query, $from_table, $row_count) {

		if ((int) $this->explainOutput == 1 || ((int) $this->explainOutput == 2 &&
				t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']))
		) {
				// raw HTML output
			$explainMode = 1;
		} elseif ((int) $this->explainOutput == 3 && is_object($GLOBALS['TT'])) {
				// embed the output into the TS admin panel
			$explainMode = 2;
		} else {
			return FALSE;
		}

		$error = $this->sql_error();
		$trail = t3lib_utility_Debug::debugTrail();

		$explain_tables = array();
		$explain_output = array();
		$res = $this->sql_query('EXPLAIN ' . $query, $this->link);
		if (is_resource($res)) {
			while ($tempRow = $this->sql_fetch_assoc($res)) {
				$explain_output[] = $tempRow;
				$explain_tables[] = $tempRow['table'];
			}
			$this->sql_free_result($res);
		}

		$indices_output = array();
			// Notice: Rows are skipped if there is only one result, or if no conditions are set
		if ($explain_output[0]['rows'] > 1 || t3lib_div::inList('ALL', $explain_output[0]['type'])) {
				// only enable output if it's really useful
			$debug = TRUE;

			foreach ($explain_tables as $table) {
				$tableRes = $this->sql_query('SHOW TABLE STATUS LIKE \'' . $table . '\'');
				$isTable = $this->sql_num_rows($tableRes);
				if ($isTable) {
					$res = $this->sql_query('SHOW INDEX FROM ' . $table, $this->link);
					if (is_resource($res)) {
						while ($tempRow = $this->sql_fetch_assoc($res)) {
							$indices_output[] = $tempRow;
						}
						$this->sql_free_result($res);
					}
				}
				$this->sql_free_result($tableRes);
			}
		} else {
			$debug = FALSE;
		}

		if ($debug) {
			if ($explainMode) {
				$data = array();
				$data['query'] = $query;
				$data['trail'] = $trail;
				$data['row_count'] = $row_count;

				if ($error) {
					$data['error'] = $error;
				}
				if (count($explain_output)) {
					$data['explain'] = $explain_output;
				}
				if (count($indices_output)) {
					$data['indices'] = $indices_output;
				}

				if ($explainMode == 1) {
					t3lib_utility_Debug::debug($data, 'Tables: ' . $from_table, 'DB SQL EXPLAIN');
				} elseif ($explainMode == 2) {
					$GLOBALS['TT']->setTSselectQuery($data);
				}
			}
			return TRUE;
		}

		return FALSE;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_db.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_db.php']);
}

?>