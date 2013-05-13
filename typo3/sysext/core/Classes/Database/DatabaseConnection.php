<?php
namespace TYPO3\CMS\Core\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains the class "DatabaseConnection" containing functions for building SQL queries
 * and mysqli wrappers, thus providing a foundational API to all database
 * interaction.
 * This class is instantiated globally as $TYPO3_DB in TYPO3 scripts.
 *
 * TYPO3 "database wrapper" class (new in 3.6.0)
 * This class contains
 * - abstraction functions for executing INSERT/UPDATE/DELETE/SELECT queries ("Query execution"; These are REQUIRED for all future connectivity to the database, thus ensuring DBAL compliance!)
 * - functions for building SQL queries (INSERT/UPDATE/DELETE/SELECT) ("Query building"); These are transitional functions for building SQL queries in a more automated way. Use these to build queries instead of doing it manually in your code!
 * - mysqli wrapper functions; These are transitional functions. By a simple search/replace you should be able to substitute all mysql*() calls with $GLOBALS['TYPO3_DB']->sql*() and your application will work out of the box. YOU CANNOT (legally) use any mysqli functions not found as wrapper functions in this class!
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
 * Eg. $GLOBALS['TYPO3_DB']->sql_fetch_assoc()
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DatabaseConnection {

	/**
	 * The AND constraint in where clause
	 *
	 * @var string
	 */
	const AND_Constraint = 'AND';

	/**
	 * The OR constraint in where clause
	 *
	 * @var string
	 */
	const OR_Constraint = 'OR';

	// Set "TRUE" or "1" if you want database errors outputted. Set to "2" if you also want successful database actions outputted.
	/**
	 * @todo Define visibility
	 */
	public $debugOutput = FALSE;

	// Internally: Set to last built query (not necessarily executed...)
	/**
	 * @todo Define visibility
	 */
	public $debug_lastBuiltQuery = '';

	// Set "TRUE" if you want the last built query to be stored in $debug_lastBuiltQuery independent of $this->debugOutput
	/**
	 * @todo Define visibility
	 */
	public $store_lastBuiltQuery = FALSE;

	// Set this to 1 to get queries explained (devIPmask must match). Set the value to 2 to the same but disregarding the devIPmask.
	// There is an alternative option to enable explain output in the admin panel under "TypoScript", which will produce much nicer output, but only works in FE.
	/**
	 * @todo Define visibility
	 */
	public $explainOutput = 0;

	/**
	 * @var string Database host to connect to
	 */
	protected $databaseHost = '';

	/**
	 * @var integer Database port to connect to
	 */
	protected $databasePort = 3306;

	/**
	 * @var string Database name to connect to
	 */
	protected $databaseName = '';

	/**
	 * @var string Database user to connect with
	 */
	protected $databaseUsername = '';

	/**
	 * @var string Database password to connect with
	 */
	protected $databaseUserPassword = '';

	/**
	 * @var boolean TRUE if database connection should be persistent
	 * @see http://php.net/manual/de/mysqli.persistconns.php
	 */
	protected $persistentDatabaseConnection = FALSE;

	/**
	 * @var boolean TRUE if connection between client and sql server is compressed
	 */
	protected $connectionCompression = FALSE;

	/**
	 * @var array List of commands executed after connection was established
	 */
	protected $initializeCommandsAfterConnect = array();

	/**
	 * @var boolean TRUE if database connection is established
	 */
	protected $isConnected = FALSE;

	/**
	 * @var \mysqli $link Default database link object
	 */
	protected $link = NULL;

	// Default character set, applies unless character set or collation are explicitly set
	/**
	 * @todo Define visibility
	 */
	public $default_charset = 'utf8';

	/**
	 * @var array<PostProcessQueryHookInterface>
	 */
	protected $preProcessHookObjects = array();

	/**
	 * @var array<PreProcessQueryHookInterface>
	 */
	protected $postProcessHookObjects = array();

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
	 *
	 * @param string $table Table name
	 * @param array $fields_values Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($this->INSERTquery($table, $fields_values, $no_quote_fields));
		if ($this->debugOutput) {
			$this->debug('exec_INSERTquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_INSERTquery_postProcessAction($table, $fields_values, $no_quote_fields, $this);
		}
		return $res;
	}

	/**
	 * Creates and executes an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @param array $rows Table rows. Each row should be an array with field values mapping to $fields
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($this->INSERTmultipleRows($table, $fields, $rows, $no_quote_fields));
		if ($this->debugOutput) {
			$this->debug('exec_INSERTmultipleRows');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_INSERTmultipleRows_postProcessAction($table, $fields, $rows, $no_quote_fields, $this);
		}
		return $res;
	}

	/**
	 * Creates and executes an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Using this function specifically allow us to handle BLOB and CLOB fields depending on DB
	 *
	 * @param string $table Database tablename
	 * @param string $where WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param array $fields_values Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($this->UPDATEquery($table, $where, $fields_values, $no_quote_fields));
		if ($this->debugOutput) {
			$this->debug('exec_UPDATEquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_UPDATEquery_postProcessAction($table, $where, $fields_values, $no_quote_fields, $this);
		}
		return $res;
	}

	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 *
	 * @param string $table Database tablename
	 * @param string $where WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_DELETEquery($table, $where) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($this->DELETEquery($table, $where));
		if ($this->debugOutput) {
			$this->debug('exec_DELETEquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_DELETEquery_postProcessAction($table, $where, $this);
		}
		return $res;
	}

	/**
	 * Creates and executes a SELECT SQL-statement
	 * Using this function specifically allow us to handle the LIMIT feature independently of DB.
	 *
	 * @param string $select_fields List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param string $from_table Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param string $where_clause Additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param string $groupBy Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy Optional ORDER BY field(s), if none, supply blank string.
	 * @param string $limit Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
		$res = $this->link->query($query);
		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}
		if ($this->explainOutput) {
			$this->explain($query, $from_table, $res->num_rows);
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_SELECTquery_postProcessAction($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', $this);
		}
		return $res;
	}

	/**
	 * Creates and executes a SELECT query, selecting fields ($select) from two/three tables joined
	 * Use $mm_table together with $local_table or $foreign_table to select over two tables. Or use all three tables to select the full MM-relation.
	 * The JOIN is done with [$local_table].uid <--> [$mm_table].uid_local  / [$mm_table].uid_foreign <--> [$foreign_table].uid
	 * The function is very useful for selecting MM-relations between tables adhering to the MM-format used by TCE (TYPO3 Core Engine). See the section on $GLOBALS['TCA'] in Inside TYPO3 for more details.
	 *
	 * @param string $select Field list for SELECT
	 * @param string $local_table Tablename, local table
	 * @param string $mm_table Tablename, relation table
	 * @param string $foreign_table Tablename, foreign table
	 * @param string $whereClause Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT! You have to prepend 'AND ' to this parameter yourself!
	 * @param string $groupBy Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy Optional ORDER BY field(s), if none, supply blank string.
	 * @param string $limit Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 * @see exec_SELECTquery()
	 */
	public function exec_SELECT_mm_query($select, $local_table, $mm_table, $foreign_table, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '') {
		if ($foreign_table == $local_table) {
			$foreign_table_as = $foreign_table . uniqid('_join');
		}
		$mmWhere = $local_table ? $local_table . '.uid=' . $mm_table . '.uid_local' : '';
		$mmWhere .= ($local_table and $foreign_table) ? ' AND ' : '';
		$tables = ($local_table ? $local_table . ',' : '') . $mm_table;
		if ($foreign_table) {
			$mmWhere .= ($foreign_table_as ? $foreign_table_as : $foreign_table) . '.uid=' . $mm_table . '.uid_foreign';
			$tables .= ',' . $foreign_table . ($foreign_table_as ? ' AS ' . $foreign_table_as : '');
		}
		return $this->exec_SELECTquery($select, $tables, $mmWhere . ' ' . $whereClause, $groupBy, $orderBy, $limit);
	}

	/**
	 * Executes a select based on input query parts array
	 *
	 * @param array $queryParts Query parts array
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 * @see exec_SELECTquery()
	 */
	public function exec_SELECT_queryArray($queryParts) {
		return $this->exec_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT']);
	}

	/**
	 * Creates and executes a SELECT SQL-statement AND traverse result set and returns array with records in.
	 *
	 * @param string $select_fields See exec_SELECTquery()
	 * @param string $from_table See exec_SELECTquery()
	 * @param string $where_clause See exec_SELECTquery()
	 * @param string $groupBy See exec_SELECTquery()
	 * @param string $orderBy See exec_SELECTquery()
	 * @param string $limit See exec_SELECTquery()
	 * @param string $uidIndexField If set, the result array will carry this field names value as index. Requires that field to be selected of course!
	 * @return array|NULL Array of rows, or NULL in case of SQL error
	 */
	public function exec_SELECTgetRows($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '') {
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

				}
				array_pop($output);
			}
			$this->sql_free_result($res);
		} else {
			$output = NULL;
		}
		return $output;
	}

	/**
	 * Creates and executes a SELECT SQL-statement AND gets a result set and returns an array with a single record in.
	 * LIMIT is automatically set to 1 and can not be overridden.
	 *
	 * @param string $select_fields List of fields to select from the table.
	 * @param string $from_table Table(s) from which to select.
	 * @param string $where_clause Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param string $groupBy Optional GROUP BY field(s), if none, supply blank string.
	 * @param string $orderBy Optional ORDER BY field(s), if none, supply blank string.
	 * @param boolean $numIndex If set, the result will be fetched with sql_fetch_row, otherwise sql_fetch_assoc will be used.
	 * @return array Single row or NULL if it fails.
	 */
	public function exec_SELECTgetSingleRow($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $numIndex = FALSE) {
		$res = $this->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, '1');
		if ($this->debugOutput) {
			$this->debug('exec_SELECTquery');
		}
		$output = NULL;
		if ($res !== FALSE) {
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
	 * @param string $field Name of the field to use in the COUNT() expression (e.g. '*')
	 * @param string $table Name of the table to count rows for
	 * @param string $where (optional) WHERE statement of the query
	 * @return mixed Number of rows counter (integer) or FALSE if something went wrong (boolean)
	 */
	public function exec_SELECTcountRows($field, $table, $where = '') {
		$count = FALSE;
		$resultSet = $this->exec_SELECTquery('COUNT(' . $field . ')', $table, $where);
		if ($resultSet !== FALSE) {
			list($count) = $this->sql_fetch_row($resultSet);
			$count = intval($count);
			$this->sql_free_result($resultSet);
		}
		return $count;
	}

	/**
	 * Truncates a table.
	 *
	 * @param string $table Database tablename
	 * @return mixed Result from handler
	 */
	public function exec_TRUNCATEquery($table) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($this->TRUNCATEquery($table));
		if ($this->debugOutput) {
			$this->debug('exec_TRUNCATEquery');
		}
		foreach ($this->postProcessHookObjects as $hookObject) {
			/** @var $hookObject PostProcessQueryHookInterface */
			$hookObject->exec_TRUNCATEquery_postProcessAction($table, $this);
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
	 *
	 * @param string $table See exec_INSERTquery()
	 * @param array $fields_values See exec_INSERTquery()
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return string Full SQL query for INSERT (unless $fields_values does not contain any elements in which case it will be FALSE)
	 */
	public function INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (is_array($fields_values) && count($fields_values)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				$hookObject->INSERTquery_preProcessAction($table, $fields_values, $no_quote_fields, $this);
			}
			// Quote and escape values
			$fields_values = $this->fullQuoteArray($fields_values, $table, $no_quote_fields);
			// Build query
			$query = 'INSERT INTO ' . $table . ' (' . implode(',', array_keys($fields_values)) . ') VALUES ' . '(' . implode(',', $fields_values) . ')';
			// Return query
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		}
	}

	/**
	 * Creates an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param string $table Table name
	 * @param array $fields Field names
	 * @param array $rows Table rows. Each row should be an array with field values mapping to $fields
	 * @param boolean $no_quote_fields See fullQuoteArray()
	 * @return string Full SQL query for INSERT (unless $rows does not contain any elements in which case it will be FALSE)
	 */
	public function INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (count($rows)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->INSERTmultipleRows_preProcessAction($table, $fields, $rows, $no_quote_fields, $this);
			}
			// Build query
			$query = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES ';
			$rowSQL = array();
			foreach ($rows as $row) {
				// Quote and escape values
				$row = $this->fullQuoteArray($row, $table, $no_quote_fields);
				$rowSQL[] = '(' . implode(', ', $row) . ')';
			}
			$query .= implode(', ', $rowSQL);
			// Return query
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		}
	}

	/**
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 *
	 *
	 * @param string $table See exec_UPDATEquery()
	 * @param string $where See exec_UPDATEquery()
	 * @param array $fields_values See exec_UPDATEquery()
	 * @param boolean $no_quote_fields
	 * @throws \InvalidArgumentException
	 * @return string Full SQL query for UPDATE
	 */
	public function UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this
		// function (contrary to values in the arrays which may be insecure).
		if (is_string($where)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->UPDATEquery_preProcessAction($table, $where, $fields_values, $no_quote_fields, $this);
			}
			$fields = array();
			if (is_array($fields_values) && count($fields_values)) {
				// Quote and escape values
				$nArr = $this->fullQuoteArray($fields_values, $table, $no_quote_fields, TRUE);
				foreach ($nArr as $k => $v) {
					$fields[] = $k . '=' . $v;
				}
			}
			// Build query
			$query = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) . (strlen($where) > 0 ? ' WHERE ' . $where : '');
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		} else {
			throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for UPDATE query was not a string in $this->UPDATEquery() !', 1270853880);
		}
	}

	/**
	 * Creates a DELETE SQL-statement for $table where $where-clause
	 *
	 * @param string $table See exec_DELETEquery()
	 * @param string $where See exec_DELETEquery()
	 * @return string Full SQL query for DELETE
	 * @throws \InvalidArgumentException
	 */
	public function DELETEquery($table, $where) {
		if (is_string($where)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				/** @var $hookObject PreProcessQueryHookInterface */
				$hookObject->DELETEquery_preProcessAction($table, $where, $this);
			}
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			$query = 'DELETE FROM ' . $table . (strlen($where) > 0 ? ' WHERE ' . $where : '');
			if ($this->debugOutput || $this->store_lastBuiltQuery) {
				$this->debug_lastBuiltQuery = $query;
			}
			return $query;
		} else {
			throw new \InvalidArgumentException('TYPO3 Fatal Error: "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !', 1270853881);
		}
	}

	/**
	 * Creates a SELECT SQL-statement
	 *
	 * @param string $select_fields See exec_SELECTquery()
	 * @param string $from_table See exec_SELECTquery()
	 * @param string $where_clause See exec_SELECTquery()
	 * @param string $groupBy See exec_SELECTquery()
	 * @param string $orderBy See exec_SELECTquery()
	 * @param string $limit See exec_SELECTquery()
	 * @return string Full SQL query for SELECT
	 */
	public function SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
		foreach ($this->preProcessHookObjects as $hookObject) {
			/** @var $hookObject PreProcessQueryHookInterface */
			$hookObject->SELECTquery_preProcessAction($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit, $this);
		}
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
		// Build basic query
		$query = 'SELECT ' . $select_fields . ' FROM ' . $from_table . (strlen($where_clause) > 0 ? ' WHERE ' . $where_clause : '');
		// Group by
		$query .= strlen($groupBy) > 0 ? ' GROUP BY ' . $groupBy : '';
		// Order by
		$query .= strlen($orderBy) > 0 ? ' ORDER BY ' . $orderBy : '';
		// Group by
		$query .= strlen($limit) > 0 ? ' LIMIT ' . $limit : '';
		// Return query
		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}
		return $query;
	}

	/**
	 * Creates a SELECT SQL-statement to be used as subquery within another query.
	 * BEWARE: This method should not be overriden within DBAL to prevent quoting from happening.
	 *
	 * @param string $select_fields List of fields to select from the table.
	 * @param string $from_table Table from which to select.
	 * @param string $where_clause Conditional WHERE statement
	 * @return string Full SQL query for SELECT
	 */
	public function SELECTsubquery($select_fields, $from_table, $where_clause) {
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
		// Build basic query:
		$query = 'SELECT ' . $select_fields . ' FROM ' . $from_table . (strlen($where_clause) > 0 ? ' WHERE ' . $where_clause : '');
		// Return query
		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}
		return $query;
	}

	/**
	 * Creates a TRUNCATE TABLE SQL-statement
	 *
	 * @param string $table See exec_TRUNCATEquery()
	 * @return string Full SQL query for TRUNCATE TABLE
	 */
	public function TRUNCATEquery($table) {
		foreach ($this->preProcessHookObjects as $hookObject) {
			/** @var $hookObject PreProcessQueryHookInterface */
			$hookObject->TRUNCATEquery_preProcessAction($table, $this);
		}
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
	 * @param string $field Field name
	 * @param string $value Value to find in list
	 * @param string $table Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @throws \InvalidArgumentException
	 * @return string WHERE clause for a query
	 */
	public function listQuery($field, $value, $table) {
		$value = (string) $value;
		if (strpos(',', $value) !== FALSE) {
			throw new \InvalidArgumentException('$value must not contain a comma (,) in $this->listQuery() !', 1294585862);
		}
		$pattern = $this->quoteStr($value, $table);
		$where = 'FIND_IN_SET(\'' . $pattern . '\',' . $field . ')';
		return $where;
	}

	/**
	 * Returns a WHERE clause which will make an AND or OR search for the words in the $searchWords array in any of the fields in array $fields.
	 *
	 * @param array $searchWords Array of search words
	 * @param array $fields Array of fields
	 * @param string $table Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @param string $constraint How multiple search words have to match ('AND' or 'OR')
	 * @return string WHERE clause for search
	 */
	public function searchQuery($searchWords, $fields, $table, $constraint = self::AND_Constraint) {
		switch ($constraint) {
			case self::OR_Constraint:
				$constraint = 'OR';
				break;
			default:
				$constraint = 'AND';
				break;
		}

		$queryParts = array();
		foreach ($searchWords as $sw) {
			$like = ' LIKE \'%' . $this->quoteStr($sw, $table) . '%\'';
			$queryParts[] = $table . '.' . implode(($like . ' OR ' . $table . '.'), $fields) . $like;
		}
		$query = '(' . implode(') ' . $constraint . ' (', $queryParts) . ')';

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
	 * @param string $select_fields See exec_SELECTquery()
	 * @param string $from_table See exec_SELECTquery()
	 * @param string $where_clause See exec_SELECTquery()
	 * @param string $groupBy See exec_SELECTquery()
	 * @param string $orderBy See exec_SELECTquery()
	 * @param string $limit See exec_SELECTquery()
	 * @param array $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_AUTOTYPE.
	 * @return \TYPO3\CMS\Core\Database\PreparedStatement Prepared statement
	 */
	public function prepare_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '', array $input_parameters = array()) {
		$query = $this->SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);
		/** @var $preparedStatement \TYPO3\CMS\Core\Database\PreparedStatement */
		$preparedStatement = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\PreparedStatement', $query, $from_table, array());
		// Bind values to parameters
		foreach ($input_parameters as $key => $value) {
			$preparedStatement->bindValue($key, $value, \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_AUTOTYPE);
		}
		// Return prepared statement
		return $preparedStatement;
	}

	/**
	 * Creates a SELECT prepared SQL statement based on input query parts array
	 *
	 * @param array $queryParts Query parts array
	 * @param array $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. All values are treated as \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_AUTOTYPE.
	 * @return \TYPO3\CMS\Core\Database\PreparedStatement Prepared statement
	 */
	public function prepare_SELECTqueryArray(array $queryParts, array $input_parameters = array()) {
		return $this->prepare_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT'], $input_parameters);
	}

	/**
	 * Executes a prepared query.
	 * This method may only be called by \TYPO3\CMS\Core\Database\PreparedStatement
	 *
	 * @param string $query The query to execute
	 * @param array $queryComponents The components of the query to execute
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function exec_PREPAREDquery($query, array $queryComponents) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($query);
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
	 *
	 * @param string $str Input string
	 * @param string $table Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @param boolean $allowNull Whether to allow NULL values
	 * @return string Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function fullQuoteStr($str, $table, $allowNull = FALSE) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		if ($allowNull && $str === NULL) {
			return 'NULL';
		}

		return '\'' . $this->link->real_escape_string($str) . '\'';
	}

	/**
	 * Will fullquote all values in the one-dimensional array so they are ready to "implode" for an sql query.
	 *
	 * @param array $arr Array with values (either associative or non-associative array)
	 * @param string $table Table name for which to quote
	 * @param boolean|array $noQuote List/array of keys NOT to quote (eg. SQL functions) - ONLY for associative arrays
	 * @param boolean $allowNull Whether to allow NULL values
	 * @return array The input array with the values quoted
	 * @see cleanIntArray()
	 */
	public function fullQuoteArray($arr, $table, $noQuote = FALSE, $allowNull = FALSE) {
		if (is_string($noQuote)) {
			$noQuote = explode(',', $noQuote);
		} elseif (!is_array($noQuote)) {
			$noQuote = FALSE;
		}
		foreach ($arr as $k => $v) {
			if ($noQuote === FALSE || !in_array($k, $noQuote)) {
				$arr[$k] = $this->fullQuoteStr($v, $table, $allowNull);
			}
		}
		return $arr;
	}

	/**
	 * Substitution for PHP function "addslashes()"
	 * Use this function instead of the PHP addslashes() function when you build queries - this will prepare your code for DBAL.
	 * NOTICE: You must wrap the output of this function in SINGLE QUOTES to be DBAL compatible. Unless you have to apply the single quotes yourself you should rather use ->fullQuoteStr()!
	 *
	 * @param string $str Input string
	 * @param string $table Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return string Output string; Quotes (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function quoteStr($str, $table) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		return $this->link->real_escape_string($str);
	}

	/**
	 * Escaping values for SQL LIKE statements.
	 *
	 * @param string $str Input string
	 * @param string $table Table name for which to escape string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return string Output string; % and _ will be escaped with \ (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function escapeStrForLike($str, $table) {
		return addcslashes($str, '_%');
	}

	/**
	 * Will convert all values in the one-dimensional array to integers.
	 * Useful when you want to make sure an array contains only integers before imploding them in a select-list.
	 *
	 * @param array $arr Array with values
	 * @return array The input array with all values passed through intval()
	 * @see cleanIntList()
	 */
	public function cleanIntArray($arr) {
		foreach ($arr as $k => $v) {
			$arr[$k] = intval($arr[$k]);
		}
		return $arr;
	}

	/**
	 * Will force all entries in the input comma list to integers
	 * Useful when you want to make sure a commalist of supposed integers really contain only integers; You want to know that when you don't trust content that could go into an SQL statement.
	 *
	 * @param string $list List of comma-separated values which should be integers
	 * @return string The input list but with every value passed through intval()
	 * @see cleanIntArray()
	 */
	public function cleanIntList($list) {
		return implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $list));
	}

	/**
	 * Removes the prefix "ORDER BY" from the input string.
	 * This function is used when you call the exec_SELECTquery() function and want to pass the ORDER BY parameter by can't guarantee that "ORDER BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 *
	 * @param string $str eg. "ORDER BY title, uid
	 * @return string eg. "title, uid
	 * @see exec_SELECTquery(), stripGroupBy()
	 */
	public function stripOrderBy($str) {
		return preg_replace('/^(?:ORDER[[:space:]]*BY[[:space:]]*)+/i', '', trim($str));
	}

	/**
	 * Removes the prefix "GROUP BY" from the input string.
	 * This function is used when you call the SELECTquery() function and want to pass the GROUP BY parameter by can't guarantee that "GROUP BY" is not prefixed.
	 * Generally; This function provides a work-around to the situation where you cannot pass only the fields by which to order the result.
	 *
	 * @param string $str eg. "GROUP BY title, uid
	 * @return string eg. "title, uid
	 * @see exec_SELECTquery(), stripOrderBy()
	 */
	public function stripGroupBy($str) {
		return preg_replace('/^(?:GROUP[[:space:]]*BY[[:space:]]*)+/i', '', trim($str));
	}

	/**
	 * Takes the last part of a query, eg. "... uid=123 GROUP BY title ORDER BY title LIMIT 5,2" and splits each part into a table (WHERE, GROUPBY, ORDERBY, LIMIT)
	 * Work-around function for use where you know some userdefined end to an SQL clause is supplied and you need to separate these factors.
	 *
	 * @param string $str Input string
	 * @return array
	 */
	public function splitGroupOrderLimit($str) {
		// Prepending a space to make sure "[[:space:]]+" will find a space there
		// for the first element.
		$str = ' ' . $str;
		// Init output array:
		$wgolParts = array(
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);
		// Find LIMIT
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+LIMIT[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['LIMIT'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Find ORDER BY
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+ORDER[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['ORDERBY'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Find GROUP BY
		$reg = array();
		if (preg_match('/^(.*)[[:space:]]+GROUP[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i', $str, $reg)) {
			$wgolParts['GROUPBY'] = trim($reg[2]);
			$str = $reg[1];
		}
		// Rest is assumed to be "WHERE" clause
		$wgolParts['WHERE'] = $str;
		return $wgolParts;
	}

	/**
	 * Returns the date and time formats compatible with the given database table.
	 *
	 * @param string $table Table name for which to return an empty date. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how date and time should be formatted).
	 * @return array
	 */
	public function getDateTimeFormats($table) {
		return array(
			'date' => array(
				'empty' => '0000-00-00',
				'format' => 'Y-m-d'
			),
			'datetime' => array(
				'empty' => '0000-00-00 00:00:00',
				'format' => 'Y-m-d H:i:s'
			)
		);
	}

	/**************************************
	 *
	 * MySQL(i) wrapper functions
	 * (For use in your applications)
	 *
	 **************************************/
	/**
	 * Executes query
	 * MySQLi query() wrapper function
	 * Beware: Use of this method should be avoided as it is experimentally supported by DBAL. You should consider
	 * using exec_SELECTquery() and similar methods instead.
	 *
	 * @param string $query Query to execute
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function sql_query($query) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($query);
		if ($this->debugOutput) {
			$this->debug('sql_query', $query);
		}
		return $res;
	}

	/**
	 * Returns the error status on the last query() execution
	 *
	 * @return string MySQLi error string.
	 */
	public function sql_error() {
		return $this->link->error;
	}

	/**
	 * Returns the error number on the last query() execution
	 *
	 * @return integer MySQLi error number
	 */
	public function sql_errno() {
		return $this->link->errno;
	}

	/**
	 * Returns the number of selected rows.
	 *
	 * @param boolean|\mysqli_result|object $res MySQLi result object / DBAL object
	 * @return integer Number of resulting rows
	 */
	public function sql_num_rows($res) {
		if ($this->debug_check_recordset($res)) {
			return $res->num_rows;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * MySQLi fetch_assoc() wrapper function
	 *
	 * @param boolean|\mysqli_result|object $res MySQLi result object / DBAL object
	 * @return array|boolean Associative array of result row.
	 */
	public function sql_fetch_assoc($res) {
		if ($this->debug_check_recordset($res)) {
			$result = $res->fetch_assoc();
			if ($result === NULL) {
				// Needed for compatibility
				$result = FALSE;
			}
			return $result;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains the values in numerical indices.
	 * MySQLi fetch_row() wrapper function
	 *
	 * @param boolean|\mysqli_result|object $res MySQLi result object / DBAL object
	 * @return array|boolean Array with result rows.
	 */
	public function sql_fetch_row($res) {
		if ($this->debug_check_recordset($res)) {
			$result = $res->fetch_row();
			if ($result === NULL) {
				// Needed for compatibility
				$result = FALSE;
			}
			return $result;
		} else {
			return FALSE;
		}
	}

	/**
	 * Free result memory
	 * free_result() wrapper function
	 *
	 * @param boolean|\mysqli_result|object $res MySQLi result object / DBAL object
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function sql_free_result($res) {
		if ($this->debug_check_recordset($res)) {
			return $res->free();
		} else {
			return FALSE;
		}
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return integer The uid of the last inserted record.
	 */
	public function sql_insert_id() {
		return $this->link->insert_id;
	}

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
	 *
	 * @return integer Number of rows affected by last query
	 */
	public function sql_affected_rows() {
		return $this->link->affected_rows;
	}

	/**
	 * Move internal result pointer
	 *
	 * @param boolean|\mysqli_result|object $res MySQLi result object / DBAL object
	 * @param integer $seek Seek result number.
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function sql_data_seek($res, $seek) {
		if ($this->debug_check_recordset($res)) {
			return $res->data_seek($seek);
		} else {
			return FALSE;
		}
	}

	/**
	 * Get the type of the specified field in a result
	 * mysql_field_type() wrapper function
	 *
	 * @param boolean|\mysqli_result|object $res MySQLi result object / DBAL object
	 * @param integer $pointer Field index.
	 * @return string Returns the name of the specified field index, or FALSE on error
	 */
	public function sql_field_type($res, $pointer) {
		// mysql_field_type compatibility map
		// taken from: http://www.php.net/manual/en/mysqli-result.fetch-field-direct.php#89117
		// Constant numbers see http://php.net/manual/en/mysqli.constants.php
		$mysql_data_type_hash = array(
			1=>'tinyint',
			2=>'smallint',
			3=>'int',
			4=>'float',
			5=>'double',
			7=>'timestamp',
			8=>'bigint',
			9=>'mediumint',
			10=>'date',
			11=>'time',
			12=>'datetime',
			13=>'year',
			16=>'bit',
			//252 is currently mapped to all text and blob types (MySQL 5.0.51a)
			253=>'varchar',
			254=>'char',
			246=>'decimal'
		);
		if ($this->debug_check_recordset($res)) {
			$metaInfo = $res->fetch_field_direct($pointer);
			if ($metaInfo === FALSE) {
				return FALSE;
			}
			return $mysql_data_type_hash[$metaInfo->type];
		} else {
			return FALSE;
		}
	}

	/**
	 * Open a (persistent) connection to a MySQL server
	 *
	 * @param string $host Deprecated since 6.1, will be removed in two versions. Database host IP/domain[:port]
	 * @param string $username Deprecated since 6.1, will be removed in two versions. Username to connect with.
	 * @param string $password Deprecated since 6.1, will be removed in two versions. Password to connect with.
	 * @return boolean|void
	 * @throws \RuntimeException
	 */
	public function sql_pconnect($host = NULL, $username = NULL, $password = NULL) {
		if ($this->isConnected) {
			return $this->link;
		}

		if (!extension_loaded('mysqli')) {
			throw new \RuntimeException(
				'Database Error: PHP mysqli extension not loaded. This is a must have for TYPO3 CMS!',
				1271492607
			);
		}

		if ($host || $username || $password) {
			$this->handleDeprecatedConnectArguments($host, $username, $password);
		}

		$host = $this->persistentDatabaseConnection
			? 'p:' . $this->databaseHost
			: $this->databaseHost;

		$this->link = mysqli_init();
		$connected = $this->link->real_connect(
			$this->databaseHost,
			$this->databaseUsername,
			$this->databaseUserPassword,
			NULL,
			$this->databasePort,
			NULL,
			$this->connectionCompression ? MYSQLI_CLIENT_COMPRESS : 0
		);

		if ($connected) {
			$this->isConnected = TRUE;
			foreach ($this->initializeCommandsAfterConnect as $command) {
				if ($this->link->query($command) === FALSE) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
						'Could not initialize DB connection with query "' . $command . '": ' . $this->sql_error(),
						'Core',
						\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR
					);
				}
			}
			$this->setSqlMode();
		} else {
			// @TODO: This should raise an exception. Would be useful especially to work during installation.
			$error_msg = $this->link->connect_error;
			$this->link = NULL;
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				'Could not connect to MySQL server ' . $host . ' with user ' . $username . ': ' . $error_msg,
				'Core',
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_FATAL
			);
		}
		return $this->link;
	}

	/**
	 * Fixes the SQL mode by unsetting NO_BACKSLASH_ESCAPES if found.
	 *
	 * @return void
	 */
	protected function setSqlMode() {
		$resource = $this->sql_query('SELECT @@SESSION.sql_mode;');
		if ($resource) {
			$result = $this->sql_fetch_row($resource);
			if (isset($result[0]) && $result[0] && strpos($result[0], 'NO_BACKSLASH_ESCAPES') !== FALSE) {
				$modes = array_diff(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $result[0]), array('NO_BACKSLASH_ESCAPES'));
				$query = 'SET sql_mode=\'' . $this->link->real_escape_string(implode(',', $modes)) . '\';';
				$this->sql_query($query);
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
					'NO_BACKSLASH_ESCAPES could not be removed from SQL mode: ' . $this->sql_error(),
					'Core',
					\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR
				);
			}
		}
	}

	/**
	 * Select a SQL database
	 *
	 * @param string $TYPO3_db Deprecated since 6.1, will be removed in two versions. Database to connect to.
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function sql_select_db($TYPO3_db = NULL) {
		if (!$this->isConnected) {
			$this->connectDB();
		}

		if ($TYPO3_db) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
				'DatabaseConnection->sql_select_db() should be called without arguments.' .
					' Use the setDatabaseName() before. Will be removed two versions after 6.1.'
			);
		} else {
			$TYPO3_db = $this->databaseName;
		}

		$ret = $this->link->select_db($TYPO3_db);
		if (!$ret) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				'Could not select MySQL database ' . $TYPO3_db . ': ' . $this->sql_error(),
				'Core',
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_FATAL
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
	 *
	 * @return array Each entry represents a database name
	 */
	public function admin_get_dbs() {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$dbArr = array();
		$db_list = $this->link->query("SHOW DATABASES");
		while ($row = $db_list->fetch_object()) {
			try {
				$this->setDatabaseName($row->Database);
				if ($this->sql_select_db()) {
					$dbArr[] = $row->Database;
				}
			} catch (\RuntimeException $exception) {
				// The exception happens if we cannot connect to the database
				// (usually due to missing permissions). This is ok here.
				// We catch the exception, skip the database and continue.
			}
		}
		return $dbArr;
	}

	/**
	 * Returns the list of tables from the default database, TYPO3_db (quering the DBMS)
	 * In a DBAL this method should 1) look up all tables from the DBMS  of
	 * the _DEFAULT handler and then 2) add all tables *configured* to be managed by other handlers
	 *
	 * @return array Array with tablenames as key and arrays with status information as value
	 */
	public function admin_get_tables() {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$whichTables = array();
		$tables_result = $this->link->query('SHOW TABLE STATUS FROM `' . TYPO3_db . '`');
		if ($tables_result !== FALSE) {
			while ($theTable = $tables_result->fetch_assoc()) {
				$whichTables[$theTable['Name']] = $theTable;
			}
			$tables_result->free();
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
	 * @param string $tableName Table name
	 * @return array Field information in an associative array with fieldname => field row
	 */
	public function admin_get_fields($tableName) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$output = array();
		$columns_res = $this->link->query('SHOW COLUMNS FROM `' . $tableName . '`');
		while ($fieldRow = $columns_res->fetch_assoc()) {
			$output[$fieldRow['Field']] = $fieldRow;
		}
		$columns_res->free();
		return $output;
	}

	/**
	 * Returns information about each index key in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 *
	 * @param string $tableName Table name
	 * @return array Key information in a numeric array
	 */
	public function admin_get_keys($tableName) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$output = array();
		$keyRes = $this->link->query('SHOW KEYS FROM `' . $tableName . '`');
		while ($keyRow = $keyRes->fetch_assoc()) {
			$output[] = $keyRow;
		}
		$keyRes->free();
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
	 * @return array Array with Charset as key and an array of "Charset", "Description", "Default collation", "Maxlen" as values
	 */
	public function admin_get_charsets() {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$output = array();
		$columns_res = $this->link->query('SHOW CHARACTER SET');
		if ($columns_res !== FALSE) {
			while ($row = $columns_res->fetch_assoc()) {
				$output[$row['Charset']] = $row;
			}
			$columns_res->free();
		}
		return $output;
	}

	/**
	 * mysqli() wrapper function, used by the Install Tool and EM for all queries regarding management of the database!
	 *
	 * @param string $query Query to execute
	 * @return boolean|\mysqli_result|object MySQLi result object / DBAL object
	 */
	public function admin_query($query) {
		if (!$this->isConnected) {
			$this->connectDB();
		}
		$res = $this->link->query($query);
		if ($this->debugOutput) {
			$this->debug('admin_query', $query);
		}
		return $res;
	}

	/******************************
	 *
	 * Connect handling
	 *
	 ******************************/

	/**
	 * Set database host
	 *
	 * @param string $host
	 */
	public function setDatabaseHost($host = 'localhost') {
		$this->disconnectIfConnected();
		$this->databaseHost = $host;
	}

	/**
	 * Set database port
	 *
	 * @param integer $port
	 */
	public function setDatabasePort($port = 3306) {
		$this->disconnectIfConnected();
		$this->databasePort = (int)$port;
	}

	/**
	 * Set database name
	 *
	 * @param string $name
	 */
	public function setDatabaseName($name) {
		$this->disconnectIfConnected();
		$this->databaseName = $name;
	}

	/**
	 * Set database username
	 *
	 * @param string $username
	 */
	public function setDatabaseUsername($username) {
		$this->disconnectIfConnected();
		$this->databaseUsername = $username;
	}

	/**
	 * Set database password
	 *
	 * @param string $password
	 */
	public function setDatabasePassword($password) {
		$this->disconnectIfConnected();
		$this->databaseUserPassword = $password;
	}

	/**
	 * Set persistent database connection
	 *
	 * @param boolean $persistentDatabaseConnection
	 * @see http://php.net/manual/de/mysqli.persistconns.php
	 */
	public function setPersistentDatabaseConnection($persistentDatabaseConnection) {
		$this->disconnectIfConnected();
		$this->persistentDatabaseConnection = (bool)$persistentDatabaseConnection;
	}

	/**
	 * Set connection compression. Might be an advantage, if SQL server is not on localhost
	 *
	 * @param bool $connectionCompression TRUE if connection should be compressed
	 */
	public function setConnectionCompression($connectionCompression) {
		$this->disconnectIfConnected();
		$this->connectionCompression = (bool)$connectionCompression;
	}

	/**
	 * Set commands to be fired after connection was established
	 *
	 * @param array $commands List of SQL commands to be executed after connect
	 */
	public function setInitializeCommandsAfterConnect(array $commands) {
		$this->disconnectIfConnected();
		$this->initializeCommandsAfterConnect = $commands;
	}

	/**
	 * Connects to database for TYPO3 sites:
	 *
	 * @param string $host Deprecated since 6.1, will be removed in two versions Database. host IP/domain[:port]
	 * @param string $username Deprecated since 6.1, will be removed in two versions. Username to connect with
	 * @param string $password Deprecated since 6.1, will be removed in two versions. Password to connect with
	 * @param string $db Deprecated since 6.1, will be removed in two versions. Database name to connect to
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @internal param string $user Username to connect with.
	 * @return void
	 */
	public function connectDB($host = NULL, $username = NULL, $password = NULL, $db = NULL) {
		// Early return if connected already
		if ($this->isConnected) {
			return;
		}

		if (!$this->databaseName && !$db) {
			throw new \RuntimeException(
				'TYPO3 Fatal Error: No database selected!',
				1270853882
			);
		}

		if ($host || $username || $password || $db) {
			$this->handleDeprecatedConnectArguments($host, $username, $password, $db);
		}

		if ($this->sql_pconnect()) {
			if (!$this->sql_select_db()) {
				throw new \RuntimeException(
					'TYPO3 Fatal Error: Cannot connect to the current database, "' . $this->databaseName . '"!',
					1270853883
				);
			}
		} else {
			throw new \RuntimeException(
				'TYPO3 Fatal Error: The current username, password or host was not accepted when the connection to the database was attempted to be established!',
				1270853884
			);
		}

		// Prepare user defined objects (if any) for hooks which extend query methods
		$this->preProcessHookObjects = array();
		$this->postProcessHookObjects = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors'] as $classRef) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (!(
					$hookObject instanceof \TYPO3\CMS\Core\Database\PreProcessQueryHookInterface
					|| $hookObject instanceof \TYPO3\CMS\Core\Database\PostProcessQueryHookInterface
				)) {
					throw new \UnexpectedValueException(
						'$hookObject must either implement interface TYPO3\\CMS\\Core\\Database\\PreProcessQueryHookInterface or interface TYPO3\\CMS\\Core\\Database\\PostProcessQueryHookInterface',
						1299158548
					);
				}
				if ($hookObject instanceof \TYPO3\CMS\Core\Database\PreProcessQueryHookInterface) {
					$this->preProcessHookObjects[] = $hookObject;
				}
				if ($hookObject instanceof \TYPO3\CMS\Core\Database\PostProcessQueryHookInterface) {
					$this->postProcessHookObjects[] = $hookObject;
				}
			}
		}
	}

	/**
	 * Checks if database is connected
	 *
	 * @return boolean
	 */
	public function isConnected() {
		return $this->isConnected;
	}

	/**
	 * Disconnect from database if connected
	 *
	 * @return void
	 */
	protected function disconnectIfConnected() {
		if ($this->isConnected()) {
			$this->link->close();
			$this->isConnected = FALSE;
		}
	}

	/**
	 * Returns current database handle
	 *
	 * @return \mysqli|NULL
	 */
	public function getDatabaseHandle() {
		return $this->link;
	}

	/**
	 * Set current database handle, usually \mysqli
	 *
	 * @param \mysqli $handle
	 */
	public function setDatabaseHandle($handle) {
		$this->link = $handle;
	}

	/**
	 * Handle deprecated arguments for sql_pconnect() and connectDB()
	 *
	 * @param string|null $host Database host[:port]
	 * @param string|null $username Database user name
	 * @param string|null $password User password
	 * @param string|null $db Database
	 */
	protected function handleDeprecatedConnectArguments($host = NULL, $username = NULL, $password = NULL, $db = NULL) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
			'DatabaseConnection->sql_pconnect() and DatabaseConnection->connectDB() should be ' .
			'called without arguments. Use the setters instead.'
		);
		if ($host) {
			if (strpos($host, ':') > 0) {
				list($databaseHost, $databasePort) = explode(':', $host);
				$this->setDatabaseHost($databaseHost);
				$this->setDatabasePort($databasePort);
			} else {
				$this->setDatabaseHost($host);
			}
		}
		if ($username) {
			$this->setDatabaseUsername($username);
		}
		if ($password) {
			$this->setDatabasePassword($password);
		}
		if ($db) {
			$this->setDatabaseName($db);
		}
	}

	/******************************
	 *
	 * Debugging
	 *
	 ******************************/
	/**
	 * Debug function: Outputs error if any
	 *
	 * @param string $func Function calling debug()
	 * @param string $query Last query if not last built query
	 * @return void
	 * @todo Define visibility
	 */
	public function debug($func, $query = '') {
		$error = $this->sql_error();
		if ($error || (int) $this->debugOutput === 2) {
			\TYPO3\CMS\Core\Utility\DebugUtility::debug(
				array(
					'caller' => 'TYPO3\\CMS\\Core\\Database\\DatabaseConnection::' . $func,
					'ERROR' => $error,
					'lastBuiltQuery' => $query ? $query : $this->debug_lastBuiltQuery,
					'debug_backtrace' => \TYPO3\CMS\Core\Utility\DebugUtility::debugTrail()
				),
				$func,
				is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'], 'debug'))
					? ''
					: 'DB Error'
			);
		}
	}

	/**
	 * Checks if record set is valid and writes debugging information into devLog if not.
	 *
	 * @param boolean|\mysqli_result|object MySQLi result object / DBAL object
	 * @return boolean TRUE if the  record set is valid, FALSE otherwise
	 * @todo Define visibility
	 */
	public function debug_check_recordset($res) {
		if ($res !== FALSE) {
			return TRUE;
		}
		$msg = 'Invalid database result detected';
		$trace = debug_backtrace();
		array_shift($trace);
		$cnt = count($trace);
		for ($i = 0; $i < $cnt; $i++) {
			// Complete objects are too large for the log
			if (isset($trace['object'])) {
				unset($trace['object']);
			}
		}
		$msg .= ': function TYPO3\\CMS\\Core\\Database\\DatabaseConnection->' . $trace[0]['function'] . ' called from file ' . substr($trace[0]['file'], (strlen(PATH_site) + 2)) . ' in line ' . $trace[0]['line'];
		\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
			$msg . '. Use a devLog extension to get more details.',
			'Core/t3lib_db',
			\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR
		);
		// Send to devLog if enabled
		if (TYPO3_DLOG) {
			$debugLogData = array(
				'SQL Error' => $this->sql_error(),
				'Backtrace' => $trace
			);
			if ($this->debug_lastBuiltQuery) {
				$debugLogData = array('SQL Query' => $this->debug_lastBuiltQuery) + $debugLogData;
			}
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog($msg . '.', 'Core/t3lib_db', 3, $debugLogData);
		}
		return FALSE;
	}

	/**
	 * Explain select queries
	 * If $this->explainOutput is set, SELECT queries will be explained here. Only queries with more than one possible result row will be displayed.
	 * The output is either printed as raw HTML output or embedded into the TS admin panel (checkbox must be enabled!)
	 *
	 * TODO: Feature is not DBAL-compliant
	 *
	 * @param string $query SQL query
	 * @param string $from_table Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param integer $row_count Number of resulting rows
	 * @return boolean TRUE if explain was run, FALSE otherwise
	 */
	protected function explain($query, $from_table, $row_count) {
		$debugAllowedForIp = \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(
			\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
		);
		if (
			(int)$this->explainOutput == 1
			|| ((int)$this->explainOutput == 2 && $debugAllowedForIp)
		) {
			// Raw HTML output
			$explainMode = 1;
		} elseif ((int) $this->explainOutput == 3 && is_object($GLOBALS['TT'])) {
			// Embed the output into the TS admin panel
			$explainMode = 2;
		} else {
			return FALSE;
		}
		$error = $this->sql_error();
		$trail = \TYPO3\CMS\Core\Utility\DebugUtility::debugTrail();
		$explain_tables = array();
		$explain_output = array();
		$res = $this->sql_query('EXPLAIN ' . $query, $this->link);
		if (is_a($res, '\\mysqli_result')) {
			while ($tempRow = $this->sql_fetch_assoc($res)) {
				$explain_output[] = $tempRow;
				$explain_tables[] = $tempRow['table'];
			}
			$this->sql_free_result($res);
		}
		$indices_output = array();
		// Notice: Rows are skipped if there is only one result, or if no conditions are set
		if (
			$explain_output[0]['rows'] > 1
			|| \TYPO3\CMS\Core\Utility\GeneralUtility::inList('ALL', $explain_output[0]['type'])
		) {
			// Only enable output if it's really useful
			$debug = TRUE;
			foreach ($explain_tables as $table) {
				$tableRes = $this->sql_query('SHOW TABLE STATUS LIKE \'' . $table . '\'');
				$isTable = $this->sql_num_rows($tableRes);
				if ($isTable) {
					$res = $this->sql_query('SHOW INDEX FROM ' . $table, $this->link);
					if (is_a($res, '\\mysqli_result')) {
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
					\TYPO3\CMS\Core\Utility\DebugUtility::debug($data, 'Tables: ' . $from_table, 'DB SQL EXPLAIN');
				} elseif ($explainMode == 2) {
					$GLOBALS['TT']->setTSselectQuery($data);
				}
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Serialize destructs current connection
	 *
	 * @return array All protected properties that should be saved
	 */
	public function __sleep() {
		$this->disconnectIfConnected();
		return array(
			'debugOutput',
			'explainOutput',
			'databaseHost',
			'databasePort',
			'databaseName',
			'databaseUsername',
			'databaseUserPassword',
			'persistentDatabaseConnection',
			'connectionCompression',
			'initializeCommandsAfterConnect',
			'default_charset',
		);
	}
}
?>
