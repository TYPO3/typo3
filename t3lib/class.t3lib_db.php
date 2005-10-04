<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Contains the class "t3lib_db" containing functions for building SQL queries and mysql wrappers, thus providing a foundational API to all database interaction.
 * This class is instantiated globally as $TYPO3_DB in TYPO3 scripts.
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  133: class t3lib_DB
 *
 *              SECTION: Query execution
 *  168:     function exec_INSERTquery($table,$fields_values)
 *  184:     function exec_UPDATEquery($table,$where,$fields_values)
 *  198:     function exec_DELETEquery($table,$where)
 *  217:     function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')
 *  242:     function exec_SELECT_mm_query($select,$local_table,$mm_table,$foreign_table,$whereClause='',$groupBy='',$orderBy='',$limit='')
 *  265:     function exec_SELECT_queryArray($queryParts)
 *  288:     function exec_SELECTgetRows($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='',$uidIndexField='')
 *
 *              SECTION: Query building
 *  333:     function INSERTquery($table,$fields_values)
 *  369:     function UPDATEquery($table,$where,$fields_values)
 *  408:     function DELETEquery($table,$where)
 *  437:     function SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')
 *  478:     function listQuery($field, $value, $table)
 *  492:     function searchQuery($searchWords,$fields,$table)
 *
 *              SECTION: Various helper functions
 *  538:     function fullQuoteStr($str, $table)
 *  554:     function quoteStr($str, $table)
 *  567:     function cleanIntArray($arr)
 *  583:     function cleanIntList($list)
 *  597:     function stripOrderBy($str)
 *  611:     function stripGroupBy($str)
 *  623:     function splitGroupOrderLimit($str)
 *
 *              SECTION: MySQL wrapper functions
 *  688:     function sql($db,$query)
 *  702:     function sql_query($query)
 *  715:     function sql_error()
 *  727:     function sql_num_rows($res)
 *  739:     function sql_fetch_assoc($res)
 *  752:     function sql_fetch_row($res)
 *  764:     function sql_free_result($res)
 *  775:     function sql_insert_id()
 *  786:     function sql_affected_rows()
 *  799:     function sql_data_seek($res,$seek)
 *  812:     function sql_field_type($res,$pointer)
 *  826:     function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password)
 *  843:     function sql_select_db($TYPO3_db)
 *
 *              SECTION: SQL admin functions
 *  871:     function admin_get_dbs()
 *  889:     function admin_get_tables()
 *  908:     function admin_get_fields($tableName)
 *  926:     function admin_get_keys($tableName)
 *  944:     function admin_query($query)
 *
 *              SECTION: Debugging
 *  971:     function debug($func)
 *
 * TOTAL FUNCTIONS: 39
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
 * Eg. 		$GLOBALS['TYPO3_DB']->sql_fetch_assoc()
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_DB {


		// Debug:
	var $debugOutput = FALSE;		// Set "TRUE" if you want database errors outputted.
	var $debug_lastBuiltQuery = '';		// Internally: Set to last built query (not necessarily executed...)
	var $store_lastBuiltQuery = FALSE;	// Set "TRUE" if you want the last built query to be stored in $debug_lastBuiltQuery independent of $this->debugOutput

		// Default link identifier:
	var $link;




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
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_INSERTquery($table,$fields_values)	{
		$res = mysql_query($this->INSERTquery($table,$fields_values), $this->link);
		if ($this->debugOutput)	$this->debug('exec_INSERTquery');
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
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_UPDATEquery($table,$where,$fields_values)	{
		$res = mysql_query($this->UPDATEquery($table,$where,$fields_values), $this->link);
		if ($this->debugOutput)	$this->debug('exec_UPDATEquery');
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
	function exec_DELETEquery($table,$where)	{
		$res = mysql_query($this->DELETEquery($table,$where), $this->link);
		if ($this->debugOutput)	$this->debug('exec_DELETEquery');
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
	function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')	{
		$res = mysql_query($this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit), $this->link);
		if ($this->debugOutput)	$this->debug('exec_SELECTquery');
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
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	pointer		MySQL result pointer / DBAL object
	 * @see exec_SELECTquery()
	 */
	function exec_SELECT_mm_query($select,$local_table,$mm_table,$foreign_table,$whereClause='',$groupBy='',$orderBy='',$limit='')	{
		$mmWhere = $local_table ? $local_table.'.uid='.$mm_table.'.uid_local' : '';
		$mmWhere.= ($local_table AND $foreign_table) ? ' AND ' : '';
		$mmWhere.= $foreign_table ? $foreign_table.'.uid='.$mm_table.'.uid_foreign' : '';
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$select,
					($local_table ? $local_table.',' : '').$mm_table.($foreign_table ? ','.$foreign_table : ''),
					$mmWhere.' '.$whereClause,		// whereClauseMightContainGroupOrderBy
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
	function exec_SELECT_queryArray($queryParts)	{
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
	function exec_SELECTgetRows($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='',$uidIndexField='')	{
		$res = $this->exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
		if ($this->debugOutput)	$this->debug('exec_SELECTquery');

		unset($output);
		if (!$this->sql_error())	{
			$output = array();

			if ($uidIndexField)	{
				while($tempRow = $this->sql_fetch_assoc($res))	{
					$output[$tempRow[$uidIndexField]] = $tempRow;
				}
			} else {
				while($output[] = $this->sql_fetch_assoc($res));
				array_pop($output);
			}
		}
		return $output;
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
	 * @return	string		Full SQL query for INSERT (unless $fields_values does not contain any elements in which case it will be false)
	 * @depreciated			use exec_INSERTquery() instead if possible!
	 */
	function INSERTquery($table,$fields_values)	{

			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function (contrary to values in the arrays which may be insecure).
		if (is_array($fields_values) && count($fields_values))	{

				// Add slashes old-school:
			foreach($fields_values as $k => $v)	{
				$fields_values[$k] = $this->fullQuoteStr($fields_values[$k], $table);
			}

				// Build query:
			$query = 'INSERT INTO '.$table.'
				(
					'.implode(',
					',array_keys($fields_values)).'
				) VALUES (
					'.implode(',
					',$fields_values).'
				)';

				// Return query:
			if ($this->debugOutput || $this->store_lastBuiltQuery) $this->debug_lastBuiltQuery = $query;
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
	 * @return	string		Full SQL query for UPDATE (unless $fields_values does not contain any elements in which case it will be false)
	 * @depreciated			use exec_UPDATEquery() instead if possible!
	 */
	function UPDATEquery($table,$where,$fields_values)	{

			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function (contrary to values in the arrays which may be insecure).
		if (is_string($where))	{
			if (is_array($fields_values) && count($fields_values))	{

					// Add slashes old-school:
				$nArr = array();
				foreach($fields_values as $k => $v)	{
					$nArr[] = $k.'='.$this->fullQuoteStr($v, $table);
				}

					// Build query:
				$query = 'UPDATE '.$table.'
					SET
						'.implode(',
						',$nArr).
					(strlen($where)>0 ? '
					WHERE
						'.$where : '');

					// Return query:
				if ($this->debugOutput || $this->store_lastBuiltQuery) $this->debug_lastBuiltQuery = $query;
				return $query;
			}
		} else {
			die('<strong>TYPO3 Fatal Error:</strong> "Where" clause argument for UPDATE query was not a string in $this->UPDATEquery() !');
		}
	}

	/**
	 * Creates a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 3
	 *
	 * @param	string		See exec_DELETEquery()
	 * @param	string		See exec_DELETEquery()
	 * @return	string		Full SQL query for DELETE
	 * @depreciated			use exec_DELETEquery() instead if possible!
	 */
	function DELETEquery($table,$where)	{
		if (is_string($where))	{

				// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			$query = 'DELETE FROM '.$table.
				(strlen($where)>0 ? '
				WHERE
					'.$where : '');

			if ($this->debugOutput || $this->store_lastBuiltQuery) $this->debug_lastBuiltQuery = $query;
			return $query;
		} else {
			die('<strong>TYPO3 Fatal Error:</strong> "Where" clause argument for DELETE query was not a string in $this->DELETEquery() !');
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
	 * @depreciated			use exec_SELECTquery() instead if possible!
	 */
	function SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')	{

			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
			// Build basic query:
		$query = 'SELECT '.$select_fields.'
			FROM '.$from_table.
			(strlen($where_clause)>0 ? '
			WHERE
				'.$where_clause : '');

			// Group by:
		if (strlen($groupBy)>0)	{
			$query.= '
			GROUP BY '.$groupBy;
		}
			// Order by:
		if (strlen($orderBy)>0)	{
			$query.= '
			ORDER BY '.$orderBy;
		}
			// Group by:
		if (strlen($limit)>0)	{
			$query.= '
			LIMIT '.$limit;
		}

			// Return query:
		if ($this->debugOutput || $this->store_lastBuiltQuery) $this->debug_lastBuiltQuery = $query;
		return $query;
	}

	/**
	 * Returns a WHERE clause that can find a value ($value) in a list field ($field)
	 * For instance a record in the database might contain a list of numbers, "34,234,5" (with no spaces between). This query would be able to select that record based on the value "34", "234" or "5" regardless of their positioni in the list (left, middle or right).
	 * Is nice to look up list-relations to records or files in TYPO3 database tables.
	 *
	 * @param	string		Field name
	 * @param	string		Value to find in list
	 * @param	string		Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @return	string		WHERE clause for a query
	 */
	function listQuery($field, $value, $table)	{
		$command = $this->quoteStr($value, $table);
		$where = '('.$field.' LIKE \'%,'.$command.',%\' OR '.$field.' LIKE \''.$command.',%\' OR '.$field.' LIKE \'%,'.$command.'\' OR '.$field.'=\''.$command.'\')';
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
	function searchQuery($searchWords,$fields,$table)	{
		$queryParts = array();

		foreach($searchWords as $sw)	{
			$like=' LIKE \'%'.$this->quoteStr($sw, $table).'%\'';
			$queryParts[] = $table.'.'.implode($like.' OR '.$table.'.',$fields).$like;
		}
		$query = '('.implode(') AND (',$queryParts).')';
		return $query ;
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
	function fullQuoteStr($str, $table)	{
		return '\''.addslashes($str).'\'';
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
	function quoteStr($str, $table)	{
		return addslashes($str);
	}

	/**
	 * Will convert all values in the one-dimentional array to integers.
	 * Useful when you want to make sure an array contains only integers before imploding them in a select-list.
	 * Usage count/core: 7
	 *
	 * @param	array		Array with values
	 * @return	array		The input array with all values passed through intval()
	 * @see cleanIntList()
	 */
	function cleanIntArray($arr)	{
		foreach($arr as $k => $v)	{
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
	function cleanIntList($list)	{
		return implode(',',t3lib_div::intExplode(',',$list));
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
	function stripOrderBy($str)	{
		return preg_replace('/^ORDER[[:space:]]+BY[[:space:]]+/i','',trim($str));
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
	function stripGroupBy($str)	{
		return preg_replace('/^GROUP[[:space:]]+BY[[:space:]]+/i','',trim($str));
	}

	/**
	 * Takes the last part of a query, eg. "... uid=123 GROUP BY title ORDER BY title LIMIT 5,2" and splits each part into a table (WHERE, GROUPBY, ORDERBY, LIMIT)
	 * Work-around function for use where you know some userdefined end to an SQL clause is supplied and you need to separate these factors.
	 * Usage count/core: 13
	 *
	 * @param	string		Input string
	 * @return	array
	 */
	function splitGroupOrderLimit($str)	{
		$str = ' '.$str;	// Prepending a space to make sure "[[:space:]]+" will find a space there for the first element.
			// Init output array:
		$wgolParts = array(
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);

			// Find LIMIT:
		if (preg_match('/^(.*)[[:space:]]+LIMIT[[:space:]]+([[:alnum:][:space:],._]+)$/i',$str,$reg))	{
			$wgolParts['LIMIT'] = trim($reg[2]);
			$str = $reg[1];
		}

			// Find ORDER BY:
		if (preg_match('/^(.*)[[:space:]]+ORDER[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i',$str,$reg))	{
			$wgolParts['ORDERBY'] = trim($reg[2]);
			$str = $reg[1];
		}

			// Find GROUP BY:
		if (preg_match('/^(.*)[[:space:]]+GROUP[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._]+)$/i',$str,$reg))	{
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
	 * DEPRECIATED - use exec_* functions from this class instead!
	 * Usage count/core: 9
	 *
	 * @param	string		Database name
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer / DBAL object
	 */
	function sql($db,$query)	{
		$res = mysql_query($query, $this->link);
		if ($this->debugOutput)	$this->debug('sql');
		return $res;
	}

	/**
	 * Executes query
	 * mysql_query() wrapper function
	 * Usage count/core: 1
	 *
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer / DBAL object
	 */
	function sql_query($query)	{
		$res = mysql_query($query, $this->link);
		if ($this->debugOutput)	$this->debug('sql_query');
		return $res;
	}

	/**
	 * Returns the error status on the last sql() execution
	 * mysql_error() wrapper function
	 * Usage count/core: 32
	 *
	 * @return	string		MySQL error string.
	 */
	function sql_error()	{
		return mysql_error($this->link);
	}

	/**
	 * Returns the number of selected rows.
	 * mysql_num_rows() wrapper function
	 * Usage count/core: 85
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @return	integer		Number of resulting rows.
	 */
	function sql_num_rows($res)	{
		return mysql_num_rows($res);
	}

	/**
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * mysql_fetch_assoc() wrapper function
	 * Usage count/core: 307
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @return	array		Associative array of result row.
	 */
	function sql_fetch_assoc($res)	{
		return mysql_fetch_assoc($res);
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
	function sql_fetch_row($res)	{
		return mysql_fetch_row($res);
	}

	/**
	 * Free result memory
	 * mysql_free_result() wrapper function
	 * Usage count/core: 3
	 *
	 * @param	pointer		MySQL result pointer to free / DBAL object
	 * @return	boolean		Returns TRUE on success or FALSE on failure.
	 */
	function sql_free_result($res)	{
		return mysql_free_result($res);
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 * mysql_insert_id() wrapper function
	 * Usage count/core: 13
	 *
	 * @return	integer		The uid of the last inserted record.
	 */
	function sql_insert_id()	{
		return mysql_insert_id($this->link);
	}

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
	 * mysql_affected_rows() wrapper function
	 * Usage count/core: 1
	 *
	 * @return	integer		Number of rows affected by last query
	 */
	function sql_affected_rows()	{
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
	function sql_data_seek($res,$seek)	{
		return mysql_data_seek($res,$seek);
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
	function sql_field_type($res,$pointer)	{
		return mysql_field_type($res,$pointer);
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
	function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['no_pconnect'])	{
			$this->link = mysql_connect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password);
		} else {
			$this->link = mysql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password);
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
	function sql_select_db($TYPO3_db)	{
		return mysql_select_db($TYPO3_db, $this->link);
	}










	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/

	/**
	 * Listing databases from current MySQL connection. NOTICE: It WILL try to select those databases and thus break selection of current database.
	 * This is only used as a service function in the (1-2-3 process) of the Install Tool. In any case a lookup should be done in the _DEFAULT handler DBMS then.
	 * Use in Install Tool only!
	 * Usage count/core: 1
	 *
	 * @return	array		Each entry represents a database name
	 */
	function admin_get_dbs()	{
		$dbArr = array();
		$db_list = mysql_list_dbs($this->link);
		while ($row = mysql_fetch_object($db_list)) {
			if ($this->sql_select_db($row->Database))	{
				$dbArr[] = $row->Database;
			}
		}
		return $dbArr;
	}

	/**
	 * Returns the list of tables from the default database, TYPO3_db (quering the DBMS)
	 * In a DBAL this method should 1) look up all tables from the DBMS  of the _DEFAULT handler and then 2) add all tables *configured* to be managed by other handlers
	 * Usage count/core: 2
	 *
	 * @return	array		Tables in an array (tablename is in both key and value)
	 */
	function admin_get_tables()	{
		$whichTables = array();
		$tables_result = mysql_list_tables(TYPO3_db, $this->link);
		if (!mysql_error())	{
			while ($theTable = mysql_fetch_assoc($tables_result)) {
				$whichTables[current($theTable)] = current($theTable);
			}
		}
		return $whichTables;
	}

	/**
	 * Returns information about each field in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 * This function is important not only for the Install Tool but probably for DBALs as well since they might need to look up table specific information in order to construct correct queries. In such cases this information should probably be cached for quick delivery.
	 *
	 * @param	string		Table name
	 * @return	array		Field information in an associative array with fieldname => field row
	 */
	function admin_get_fields($tableName)	{
		$output = array();

		$columns_res = mysql_query('SHOW columns FROM '.$tableName, $this->link);
		while($fieldRow = mysql_fetch_assoc($columns_res))	{
			$output[$fieldRow['Field']] = $fieldRow;
		}

		return $output;
	}

	/**
	 * Returns information about each index key in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 *
	 * @param	string		Table name
	 * @return	array		Key information in a numeric array
	 */
	function admin_get_keys($tableName)	{
		$output = array();

		$keyRes = mysql_query('SHOW keys FROM '.$tableName, $this->link);
		while($keyRow = mysql_fetch_assoc($keyRes))	{
			$output[] = $keyRow;
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
	function admin_query($query)	{
		$res = mysql_query($query, $this->link);
		if ($this->debugOutput)	$this->debug('admin_query');
		return $res;
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
	 * @return	void
	 */
	function debug($func)	{

		$error = $this->sql_error();
		if ($error)		{
			echo t3lib_div::view_array(array(
				'caller' => 't3lib_DB::'.$func,
				'ERROR' => $error,
				'lastBuiltQuery' => $this->debug_lastBuiltQuery,
				'debug_backtrace' => t3lib_div::debug_trail()
			));
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_db.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_db.php']);
}
?>
