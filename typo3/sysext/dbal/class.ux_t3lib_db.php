<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Contains a database abstraction layer class for TYPO3
 *
 * $Id$
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  114: class ux_t3lib_DB extends t3lib_DB 
 *  158:     function ux_t3lib_DB()	
 *
 *              SECTION: Query Building (Overriding parent methods)
 *  196:     function exec_INSERTquery($table,$fields_values)	
 *  253:     function exec_UPDATEquery($table,$where,$fields_values)	
 *  311:     function exec_DELETEquery($table,$where)	
 *  363:     function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')	
 *
 *              SECTION: Various helper functions
 *  487:     function quoteStr($str, $table='')
 *
 *              SECTION: SQL wrapper functions (Overriding parent methods)
 *  531:     function sql_error()	
 *  558:     function sql_num_rows(&$res)	
 *  584:     function sql_fetch_assoc(&$res)	
 *  632:     function sql_fetch_row(&$res)	
 *  666:     function sql_free_result(&$res)	
 *  691:     function sql_insert_id()	
 *  715:     function sql_affected_rows()	
 *  741:     function sql_data_seek(&$res,$seek)	
 *  768:     function sql_field_type(&$res,$pointer)	
 *
 *              SECTION: Legacy functions, bound to _DEFAULT handler. (Overriding parent methods)
 *  809:     function sql($db,$query)	
 *  821:     function sql_query($query)	
 *  857:     function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password)	
 *  877:     function sql_select_db($TYPO3_db)	
 *
 *              SECTION: SQL admin functions
 *  908:     function admin_get_tables()	
 *  960:     function admin_get_fields($tableName)	
 * 1017:     function admin_get_keys($tableName)	
 * 1077:     function admin_query($query)	
 *
 *              SECTION: Handler management
 * 1136:     function handler_getFromTableList($tableList)	
 * 1182:     function handler_init($handlerKey)	
 *
 *              SECTION: Table/Field mapping
 * 1291:     function map_needMapping($tableList,$fieldMappingOnly=FALSE)	
 * 1327:     function map_assocArray($input,$tables,$rev=FALSE)	
 * 1376:     function map_remapSELECTQueryParts(&$select_fields,&$from_table,&$where_clause,&$groupBy,&$orderBy)	
 * 1418:     function map_sqlParts(&$sqlPartArray, $defaultTable)	
 * 1451:     function map_genericQueryParsed(&$parsedQuery)	
 * 1493:     function map_fieldNamesInArray($table,&$fieldArray)	
 *
 *              SECTION: Debugging
 * 1532:     function debugHandler($function,$execTime,$inData)	
 * 1598:     function debug_log($query,$ms,$data,$join,$errorFlag)	
 * 1624:     function debug_explain($query)	
 *
 * TOTAL FUNCTIONS: 34
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 









require_once(PATH_t3lib.'class.t3lib_sqlengine.php');

/**
 * TYPO3 database abstraction layer
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_dbal
 */
class ux_t3lib_DB extends t3lib_DB {

		// Internal, static:
	var $printErrors = FALSE;			// Enable output of SQL errors after query executions. Set through TYPO3_CONF_VARS, see init()
	var $debug = FALSE;					// Enable debug mode. Set through TYPO3_CONF_VARS, see init()
	var $conf = array();				// Configuration array, copied from TYPO3_CONF_VARS in constructor.

	var $mapping = array();				// See manual.
	var $table2handlerKeys = array();	// See manual.	
	var $handlerCfg = array (			// See manual.
		'_DEFAULT' => array (
			'type' => 'native',
			'config' => array(
				'username' => '',		// Set by default (overridden)
				'password' => '',		// Set by default (overridden)
				'host' => '',			// Set by default (overridden)
				'database' => '',		// Set by default (overridden)
				'driver' => '',			// ONLY "adodb" / "pear" type; eg. "mysql"
			)
		),
	);


		// Internal, dynamic:
	var $handlerInstance = array();				// Contains instance of the handler objects as they are created. Exception is the native mySQL calls which are registered as an array with keys "handlerType" = "native" and "link" pointing to the link resource for the connection.
	var $lastHandlerKey = '';					// Storage of the handler key of last ( SELECT) query - used for subsequent fetch-row calls etc.
	var $lastQuery = '';						// Storage of last SELECT query
	var $lastParsedAndMappedQueryArray=array();	// Query array, the last one parsed

	var $resourceIdToTableNameMap = array();	// Mapping of resource ids to table names.

		// Internal, caching:
	var $cache_handlerKeyFromTableList = array();			// Caching handlerKeys for table lists
	var $cache_mappingFromTableList = array();			// Caching mapping information for table lists


	
	
	
	
	/**
	 * Constructor. 
	 * Creates SQL parser object and imports configuration from $TYPO3_CONF_VARS['EXTCONF']['dbal']
	 *
	 * @return	void
	 */
	function ux_t3lib_DB()	{

			// Set SQL parser object for internal use:
		$this->SQLparser = t3lib_div::makeInstance('t3lib_sqlengine');

			// Set internal variables with configuration:
		$this->conf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal'];
		$this->initInternalVariables();
	}

	/**
	 * Setting internal variables from $this->conf
	 *
	 * @return	void
	 */
	function initInternalVariables()	{

			// Set outside configuration:
		if (isset($this->conf['mapping']))				$this->mapping = $this->conf['mapping'];
		if (isset($this->conf['table2handlerKeys']))	$this->table2handlerKeys = $this->conf['table2handlerKeys'];
		if (isset($this->conf['handlerCfg']))			$this->handlerCfg = $this->conf['handlerCfg'];

			// Debugging settings:
		$this->printErrors = $this->conf['debugOptions']['printErrors'] ? TRUE : FALSE;
		$this->debug = $this->conf['debugOptions']['enabled'] ? TRUE : FALSE;
	}








	/************************************
	 * 
	 * Query Building (Overriding parent methods)
	 * These functions are extending counterparts in the parent class.
	 * 
	 **************************************/

	/**
	 * Inserts a record for $table from the array with field/value pairs $fields_values.
	 * 
	 * @param	string		Table name
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @return	mixed		Result from handler, usually TRUE when success and FALSE on failure
	 */
	function exec_INSERTquery($table,$fields_values)	{

			// Do field mapping if needed:
		$ORIG_tableName = $table;
		if ($tableArray = $this->map_needMapping($table))	{
		
				// Field mapping of array:
			$fields_values = $this->map_assocArray($fields_values,$tableArray);

				// Table name:
			if ($this->mapping[$table]['mapTableName'])	{
				$table = $this->mapping[$table]['mapTableName'];
			}
		}
		
			// Getting information about the fields in the table:
			// FUTURE: Needed to find fields which requires speciel handling like eg. BLOBs/CLOBs and auto-incremented fields
			// Should acquire cached information or cache per process in internal memory variable
		#$tableFieldInformation = $this->admin_get_fields($table);

			// Select API:
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch((string)$this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$sqlResult = mysql_query($this->INSERTquery($table,$fields_values), $this->handlerInstance[$this->lastHandlerKey]['link']);
			break;
			case 'adodb':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->Execute($this->INSERTquery($table,$fields_values));
					# FUTURE: $conn->UpdateBlob('blobtable','blobcol',$blobvalue,'id=1'); - for inserting clobs/blobs in oracle?
					# FUTURE: ??? $conn->GenID($seqName = 'adodbseq',$startID=1) - for generating IDs in oracle?
					# USE???: GetInsertSQL(&$rs, $arrFields,$magicq=false)	
			break;
			case 'pear':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->query($this->INSERTquery($table,$fields_values));
				$this->handlerInstance[$this->lastHandlerKey]->TYPO3_DBAL_lastErrorMsg = DB::isError($sqlResult) ? $sqlResult->getMessage() : '';
					# $id = $db->nextID('mySequence');
					# ALSO setting insert_id
			break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_INSERTquery($table,$fields_values);
			break;
		}

			// Print errors:		
		if ($this->printErrors && $this->sql_error())	{ debug(array($this->sql_error()));	}
		
			// Return output:
		return $sqlResult;
	}

	/**
	 * Updates a record from $table
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->quoteStr() yourself!
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @return	mixed		Result from handler, usually TRUE when success and FALSE on failure
	 */
	function exec_UPDATEquery($table,$where,$fields_values)	{

			// Do table/field mapping:
		$ORIG_tableName = $table;
		if ($tableArray = $this->map_needMapping($table))	{
		
				// Field mapping of array:
			$fields_values = $this->map_assocArray($fields_values,$tableArray);
			
				// Where clause table and field mapping:
			$whereParts = $this->SQLparser->parseWhereClause($where);
			$this->map_sqlParts($whereParts,$tableArray[0]['table']);
			$where = $this->SQLparser->compileWhereClause($whereParts);

				// Table name:
			if ($this->mapping[$table]['mapTableName'])	{
				$table = $this->mapping[$table]['mapTableName'];
			}
		}

			// Getting information about the fields in the table:
			// FUTURE: Needed to find fields which requires speciel handling like eg. BLOBs/CLOBs and auto-incremented fields
			// Should acquire cached information or cache per process in internal memory variable
		#$tableFieldInformation = $this->admin_get_fields($table);

			// Select API
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch((string)$this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$sqlResult = mysql_query($this->UPDATEquery($table,$where,$fields_values), $this->handlerInstance[$this->lastHandlerKey]['link']);
			break;
			case 'adodb':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->Execute($this->UPDATEquery($table,$where,$fields_values));
					# FUTURE: $conn->UpdateBlob('blobtable','blobcol',$blobvalue,'id=1'); - for updating clobs/blobs in oracle?
					# USE???: GetUpdateSQL(&$rs, $arrFields, $forceUpdate=false,$magicq=false)
			break;
			case 'pear':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->query($this->UPDATEquery($table,$where,$fields_values));
				$this->handlerInstance[$this->lastHandlerKey]->TYPO3_DBAL_lastErrorMsg = DB::isError($sqlResult) ? $sqlResult->getMessage() : '';
			break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_UPDATEquery($table,$where,$fields_values);
			break;
		}

			// Print errors:		
		if ($this->printErrors && $this->sql_error())	{ debug(array($this->sql_error()));	}
		
			// Return result:
		return $sqlResult;
	}

	/**
	 * Deletes records from table
	 * 
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->quoteStr() yourself!
	 * @return	mixed		Result from handler
	 */
	function exec_DELETEquery($table,$where)	{

			// Do table/field mapping:
		$ORIG_tableName = $table;
		if ($tableArray = $this->map_needMapping($table))	{

				// Where clause:
			$whereParts = $this->SQLparser->parseWhereClause($where);
			$this->map_sqlParts($whereParts,$tableArray[0]['table']);
			$where = $this->SQLparser->compileWhereClause($whereParts);

				// Table name:
			if ($this->mapping[$table]['mapTableName'])	{
				$table = $this->mapping[$table]['mapTableName'];
			}
		}

			// Select API
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch((string)$this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$sqlResult = mysql_query($this->DELETEquery($table,$where), $this->handlerInstance[$this->lastHandlerKey]['link']);
			break;
			case 'adodb':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->Execute($this->DELETEquery($table,$where));
			break;
			case 'pear':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->query($this->DELETEquery($table,$where));
				$this->handlerInstance[$this->lastHandlerKey]->TYPO3_DBAL_lastErrorMsg = DB::isError($sqlResult) ? $sqlResult->getMessage() : '';
			break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_DELETEquery($table,$where);
			break;
		}

			// Print errors:		
		if ($this->printErrors && $this->sql_error())	{ debug(array($this->sql_error()));	}
		
			// Return result:
		return $sqlResult;
	}

	/**
	 * Selects records from Data Source
	 * 
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->quoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	mixed		Result from handler. Typically object from DBAL layers.
	 */
	function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy='',$orderBy='',$limit='')	{

		if ($this->debug)	$pt = t3lib_div::milliseconds();

			// Map table / field names if needed:
		$ORIG_from_table = $from_table;	// Saving table names in $ORIG_from_table since $from_table is transformed beneath:
		if ($tableArray = $this->map_needMapping($ORIG_from_table))	{
			$this->map_remapSELECTQueryParts($select_fields,$from_table,$where_clause,$groupBy,$orderBy);	// Variables passed by reference!
		}

			// Get handler key and select API:
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_from_table);
		$hType = (string)$this->handlerCfg[$this->lastHandlerKey]['type'];
		switch($hType)	{
			case 'native':
				$this->lastQuery = $this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
				$sqlResult = mysql_query($this->lastQuery, $this->handlerInstance[$this->lastHandlerKey]['link']);
				$this->resourceIdToTableNameMap[(string)$sqlResult] = $ORIG_from_table;
			break;
			case 'adodb':
				if ($limit)	{
					$splitLimit = t3lib_div::intExplode(',',$limit);		// Splitting the limit values:
					if ($splitLimit[1])	{	// If there are two parameters, do mapping differently than otherwise:
						$numrows = $splitLimit[1];
						$offset = $splitLimit[0];
					} else {
						$numrows = $splitLimit[0];
						$offset = 0;
					}
					
					$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->SelectLimit(
											$this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy),
											$numrows,
											$offset
										);
					$this->lastQuery = $sqlResult->sql;
				} else {
					$this->lastQuery = $this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
					$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->Execute($this->lastQuery);
				}
				$sqlResult->TYPO3_DBAL_handlerType = 'adodb';	// Setting handler type in result object (for later recognition!)
				$sqlResult->TYPO3_DBAL_tableList = $ORIG_from_table;
			break;
			case 'pear':
				if ($limit)	{
					$splitLimit = t3lib_div::intExplode(',',$limit);		// Splitting the limit values:
					if ($splitLimit[1])	{	// If there are two parameters, do mapping differently than otherwise:
						$numrows = $splitLimit[1];
						$offset = $splitLimit[0];
					} else {
						$numrows = $splitLimit[0];
						$offset = 0;
					}
					
					$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->limitQuery(
											$this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy),
											$offset,
											$numrows											
										);
					$this->lastQuery = $sqlResult->dbh->last_query;
				} else {
					$this->lastQuery = $this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
					$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->query($this->lastQuery);
				}
				$sqlResult->TYPO3_DBAL_handlerType = 'pear';	// Setting handler type in result object (for later recognition!)
				$sqlResult->TYPO3_DBAL_tableList = $ORIG_from_table;
				$this->handlerInstance[$this->lastHandlerKey]->TYPO3_DBAL_lastErrorMsg = DB::isError($sqlResult) ? $sqlResult->getMessage() : '';
			break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
				if (is_object($sqlResult))	{
					$sqlResult->TYPO3_DBAL_handlerType = 'userdefined';	// Setting handler type in result object (for later recognition!)
					$sqlResult->TYPO3_DBAL_tableList = $ORIG_from_table;
				}
			break;
		}

			// Print errors:
		if ($this->printErrors && $this->sql_error())	{ debug(array($this->sql_error()));	}

			# DEBUG:
		if ($this->debug)	{
			$this->debugHandler(
				'exec_SELECTquery',
				t3lib_div::milliseconds()-$pt,
				array(
					'handlerType' => $hType,
					'args' => array($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit),
					'ORIG_from_table' => $ORIG_from_table
				)
			);
		}

			// Return result handler.		
		return $sqlResult;
	}















	
	
	/**************************************
	 *
	 * Various helper functions
	 *
	 **************************************/

	/**
	 * Perform context sensitive escaping of strings. This may be "addslashes()" or whatever method fits the current database driver.
	 * 
	 * @param	string		Input string
	 * @param	string		Table name for which to quote string. Just enter which table(s) that goes into the query. Important for detection of DBMS handler of the query!
	 * @return	string		Output string; Quotes (" / ') and \ will be backslashed according to DBMS used.
	 */
	function quoteStr($str, $table)	{
		$this->lastHandlerKey = $this->handler_getFromTableList($table);
		switch((string)$this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$str = addslashes($str);
			break;
			case 'adodb':
				$str = $this->handlerInstance[$this->lastHandlerKey]->qstr($str);
			break;
			case 'pear':
				$str = $this->handlerInstance[$this->lastHandlerKey]->quoteString($str);
			break;
			case 'userdefined':
				$str = $this->handlerInstance[$this->lastHandlerKey]->quoteStr($str);
			break;
			default:
				die('No handler found!!!');
			break;
		}

		return $str;
	}
	
	
	
	
	
	
	
	
	
	
	/**************************************
	 *
	 * SQL wrapper functions (Overriding parent methods)
	 * (For use in your applications)
	 *
	 **************************************/

	/**
	 * Returns the error status on the most recent sql() execution (based on $this->lastHandlerKey)
	 * 
	 * @return	string		Handler error strings
	 */
	function sql_error()	{

		switch($this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$output = mysql_error($this->handlerInstance[$this->lastHandlerKey]['link']);
			break;
			case 'adodb':
				$output = $this->handlerInstance[$this->lastHandlerKey]->ErrorMsg();
			break;
			case 'pear':
				// PEAR returns an ERROR object if something is wrong. For other APIs the error is something found in the handler object. 
				// Solution is to set a forced error message in the handler object each time we get a result-object in the execution methods...
				$output = $this->handlerInstance[$this->lastHandlerKey]->TYPO3_DBAL_lastErrorMsg;
			break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->sql_error();
			break;
		}
		return $output;
	}

	/**
	 * Returns the number of selected rows.
	 * 
	 * @param	pointer		Result pointer / DBAL object
	 * @return	integer		Number of resulting rows.
	 */
	function sql_num_rows(&$res)	{

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType : 'native';
		switch($handlerType)	{
			case 'native':
				$output = mysql_num_rows($res);
			break;
			case 'adodb':
				$output = $res->RecordCount();
			break;
			case 'pear':
				$output = $res->numRows();
			break;
			case 'userdefined':
				$output = $res->sql_num_rows();
			break;
		}
		return $output;
	}

	/**
	 * Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * 
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @return	array		Associative array of result row.
	 */
	function sql_fetch_assoc(&$res)	{

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType :  'native';
		switch($handlerType)	{
			case 'native':
				$output = mysql_fetch_assoc($res);
				$tableList = $this->resourceIdToTableNameMap[(string)$res];	// Reading list of tables from SELECT query:
			break;
			case 'adodb':
				$output = $res->FetchRow();
				$tableList = $res->TYPO3_DBAL_tableList;	// Reading list of tables from SELECT query:

					// Removing all numeric/integer keys.
					// Just a temporary workaround till I know how to make ADOdb return this by default... Most likely this will not work with other databases than MySQL
				if (is_array($output))	{
					foreach($output as $key => $value)	{
						if (is_integer($key))	unset($output[$key]);
					}
				}
			break;
			case 'pear':
				$output = $res->fetchRow(DB_FETCHMODE_ASSOC);
				$tableList = $res->TYPO3_DBAL_tableList;	// Reading list of tables from SELECT query:
			break;
			case 'userdefined':
				$output = $res->sql_fetch_assoc();
				$tableList = $res->TYPO3_DBAL_tableList;	// Reading list of tables from SELECT query:
			break;
		}
		
			// Table/Fieldname mapping:
		if (is_array($output))	{
			if ($tables = $this->map_needMapping($tableList,TRUE))	{
				$output = $this->map_assocArray($output,$tables,1);
			}
		}

			// Return result:
		return $output;
	}

	/**
	 * Returns an array that corresponds to the fetched row, or FALSE if there are no more rows.
	 * The array contains the values in numerical indices.
	 * 
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @return	array		Array with result rows.
	 */
	function sql_fetch_row(&$res)	{

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType :  'native';
		switch($handlerType)	{
			case 'native':
				$output = mysql_fetch_row($res);
			break;
			case 'adodb':
				$output = $res->FetchRow();
				
					// Removing all assoc. keys. 
					// Just a temporary workaround till I know how to make ADOdb return this by default... Most likely this will not work with other databases than MySQL
				if (is_array($output))	{
					foreach($output as $key => $value)	{
						if (!is_integer($key))	unset($output[$key]);
					}
				}
			break;
			case 'pear':
				$output = $res->fetchRow(DB_FETCHMODE_ORDERED);
			break;
			case 'userdefined':
				$output = $res->sql_fetch_row();
			break;
		}
		return $output;
	}

	/**
	 * Free result memory / unset result object
	 * 
	 * @param	pointer		MySQL result pointer to free / DBAL object
	 * @return	boolean		Returns TRUE on success or FALSE on failure.
	 */
	function sql_free_result(&$res)	{

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType :  'native';
		switch($handlerType)	{
			case 'native':
				$output = mysql_free_result($res);
			break;
			case 'adodb':
				# ?
			break;
			case 'pear':
				$res->free();
			break;
			case 'userdefined':
				unset($res);
			break;
		}
		return $output;
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 * 
	 * @return	integer		The uid of the last inserted record.
	 */
	function sql_insert_id()	{

		switch($this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$output = mysql_insert_id($this->handlerInstance[$this->lastHandlerKey]['link']);
			break;
			case 'adodb':
				$output = $this->handlerInstance[$this->lastHandlerKey]->Insert_ID();
			break;
			case 'pear':
				$output = mysql_insert_id();	// WILL NOT BE THE FINAL SOLUTION HERE!!!
			break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->sql_insert_id();
			break;
		}
		return $output;
	}

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE query
	 * 
	 * @return	integer		Number of rows affected by last query
	 */
	function sql_affected_rows()	{

		switch($this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$output = mysql_affected_rows();
			break;
			case 'adodb':
				$output = $this->handlerInstance[$this->lastHandlerKey]->Affected_Rows();
			break;
			case 'pear':
				$output = $this->handlerInstance[$this->lastHandlerKey]->affectedRows();
			break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->sql_affected_rows();
			break;
		}
		return $output;
	}

	/**
	 * Move internal result pointer
	 * 
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @param	integer		Seek result number.
	 * @return	boolean		Returns TRUE on success or FALSE on failure.
	 */
	function sql_data_seek(&$res,$seek)	{

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType :  'native';
		switch($handlerType)	{
			case 'native':
				$output = mysql_data_seek($res,$seek);
			break;
			case 'adodb':
				$output = $res->Move($seek);
			break;
			case 'pear':
				# ??? - no idea!
			break;
			case 'userdefined':
				$output = $res->sql_data_seek($seek);
			break;
		}
		return $output;
	}

	/**
	 * Get the type of the specified field in a result
	 * 
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object
	 * @param	integer		Field index.
	 * @return	string		Returns the name of the specified field index
	 */
	function sql_field_type(&$res,$pointer)	{

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType :  'native';
		switch($handlerType)	{
			case 'native':
				$output = mysql_field_type($res,$pointer);
			break;
			case 'adodb':
			break;
			case 'pear':
			break;
			case 'userdefined':
				$output = $res->sql_field_type($pointer);
			break;
		}
		return $output;
	}
	







	/**********
	 *
	 * Legacy functions, bound to _DEFAULT handler. (Overriding parent methods)
	 * Depreciated.
	 *
	 **********/
	
	/**
	 * Executes query (on DEFAULT handler!)
	 * DEPRECIATED - use exec_* functions from this class instead!
	 * 
	 * @param	string		Database name
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer
	 * @depreciated
	 */
	function sql($db,$query)	{
		return $this->sql_query($query);
	}

	/**
	 * Executes query (on DEFAULT handler!)
	 * DEPRECIATED - use exec_* functions from this class instead!
	 * 
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer / DBAL object
	 * @depreciated
	 */
	function sql_query($query)	{
		
		switch($this->handlerCfg['_DEFAULT']['type'])	{
			case 'native':
				$sqlResult = mysql_query($query, $this->handlerInstance['_DEFAULT']['link']);
			break;
			case 'adodb':
				$sqlResult = $this->handlerInstance['_DEFAULT']->Execute($query);
			break;
			case 'pear':
				$sqlResult = $this->handlerInstance['_DEFAULT']->query($query);
				$this->handlerInstance['_DEFAULT']->TYPO3_DBAL_lastErrorMsg = DB::isError($sqlResult) ? $sqlResult->getMessage() : '';
			break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance['_DEFAULT']->sql_query($query);
			break;
		}

			// Print errors:		
		if ($this->printErrors && $this->sql_error())	{ debug(array($this->sql_error()));	}
		
		return $sqlResult;
	}

	/**
	 * Opening the _DEFAULT connection handler to the database.
	 * This is typically done by the scripts "init.php" in the backend or "index_ts.php" in the frontend (tslib_fe->connectToMySQL())
	 * You wouldn't need to use this at any time - let TYPO3 core handle this.
	 * 
	 * @param	string		Database host IP/domain
	 * @param	string		Username to connect with.
	 * @param	string		Password to connect with.
	 * @return	mixed		Returns handler connection value
	 * @depreciated
	 * @see handler_init()
	 */
	function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password)	{

			// Overriding the _DEFAULT handler configuration of username, password, localhost and database name:
		$this->handlerCfg['_DEFAULT']['config']['username'] = $TYPO3_db_username;
		$this->handlerCfg['_DEFAULT']['config']['password'] = $TYPO3_db_password;
		$this->handlerCfg['_DEFAULT']['config']['host'] = $TYPO3_db_host;
		$this->handlerCfg['_DEFAULT']['config']['database'] = TYPO3_db;

			// Initializing and output value:
		$sqlResult = $this->handler_init('_DEFAULT');
		return $sqlResult;
	}

	/**
	 * Select database for _DEFAULT handler.
	 * 
	 * @param	string		Database to connect to.
	 * @return	boolean		Always returns true; function is obsolete, database selection is made in handler_init() function!
	 * @depreciated
	 */
	function sql_select_db($TYPO3_db)	{
		return TRUE;
	}










	




	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/

	/**
	 * Returns the list of tables from the system (quering the DBMSs)
	 * It looks up all tables from the DBMS of the _DEFAULT handler and then add all tables *configured* to be managed by other handlers
	 * 
	 * @return	array		Tables in an array (tablename is in both key and value)
	 */
	function admin_get_tables()	{
		$whichTables = array();
		
			// Getting real list of tables:
		switch($this->handlerCfg['_DEFAULT']['type'])	{
			case 'native':
				$tables_result = mysql_list_tables(TYPO3_db, $this->handlerInstance['_DEFAULT']['link']); 
				if (!$this->sql_error())	{
					while ($theTable = $this->sql_fetch_assoc($tables_result)) {
						$whichTables[current($theTable)] = current($theTable);
					}
				}
			break;
			case 'adodb':
			break;
			case 'pear':
			break;
			case 'userdefined':
				$whichTables = $this->handlerInstance['_DEFAULT']->admin_get_tables();
			break;
		}
		
			// Check mapping:
		if (is_array($this->mapping))	{
		
				// Mapping table names in reverse, first getting list of real table names:
			$tMap = array();
			foreach($this->mapping as $tN => $tMapInfo)	{
				if (isset($tMapInfo['mapTableName']))	$tMap[$tMapInfo['mapTableName']]=$tN;
			}
			
				// Do mapping:
			$newList=array();
			foreach($whichTables as $tN)	{
				if (isset($tMap[$tN]))	$tN = $tMap[$tN];
				$newList[$tN] = $tN;
			}

			$whichTables = $newList;
		}
		
			// Adding tables configured to reside in other DBMS (handler by other handlers than the default):
		if (is_array($this->table2handlerKeys))	{
			foreach($this->table2handlerKeys as $key => $handlerKey)	{
				$whichTables[$key] = $key;
			}
		}

		return $whichTables;
	}

	/**
	 * Returns information about each field in the $table (quering the DBMS)
	 * In a DBAL this should look up the right handler for the table and return compatible information
	 * This function is important not only for the Install Tool but probably for DBALs as well since they might need to look up table specific information in order to construct correct queries. In such cases this information should probably be cached for quick delivery
	 * 
	 * @param	string		Table name
	 * @return	array		Field information in an associative array with fieldname => field row
	 */
	function admin_get_fields($tableName)	{
		$output = array();

			// Do field mapping if needed:
		$ORIG_tableName = $tableName;
		if ($tableArray = $this->map_needMapping($tableName))	{

				// Table name:
			if ($this->mapping[$tableName]['mapTableName'])	{
				$tableName = $this->mapping[$tableName]['mapTableName'];
			}
		}

			// Find columns
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch((string)$this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$columns_res = mysql_query('SHOW columns FROM '.$tableName, $this->handlerInstance[$this->lastHandlerKey]['link']);
				while($fieldRow = mysql_fetch_assoc($columns_res))	{
					$output[$fieldRow["Field"]] = $fieldRow;
				}
			break;
			case 'adodb':
			break;
			case 'pear':
			break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->admin_get_fields($tableName);
			break;
		}
		
			// mapping should be done:
		if (is_array($tableArray) && is_array($this->mapping[$ORIG_tableName]['mapFieldNames']))	{
			$revFields = array_flip($this->mapping[$ORIG_tableName]['mapFieldNames']);

			$newOutput = array();
			foreach($output as $fN => $fInfo)	{
				if (isset($revFields[$fN]))	{
					$fN = $revFields[$fN];
					$fInfo['Field'] = $fN;
				}
				$newOutput[$fN] = $fInfo;
			}
			$output = $newOutput;
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

			// Do field mapping if needed:
		$ORIG_tableName = $tableName;
		if ($tableArray = $this->map_needMapping($tableName))	{

				// Table name:
			if ($this->mapping[$tableName]['mapTableName'])	{
				$tableName = $this->mapping[$tableName]['mapTableName'];
			}
		}

			// Find columns
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch((string)$this->handlerCfg[$this->lastHandlerKey]['type'])	{
			case 'native':
				$keyRes = mysql_query("SHOW keys FROM ".$tableName, $this->handlerInstance[$this->lastHandlerKey]['link']);
				while($keyRow = mysql_fetch_assoc($keyRes))	{
					$output[] = $keyRow;
				}
			break;
			case 'adodb':
			break;
			case 'pear':
			break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->admin_get_keys($tableName);
			break;
		}

			// mapping should be done:
		if (is_array($tableArray) && is_array($this->mapping[$ORIG_tableName]['mapFieldNames']))	{
			$revFields = array_flip($this->mapping[$ORIG_tableName]['mapFieldNames']);

			$newOutput = array();
			foreach($output as $kN => $kInfo)	{
					// Table:
				$kInfo['Table'] = $ORIG_tableName;
					
					// Column
				if (isset($revFields[$kInfo['Column_name']]))	{
					$kInfo['Column_name'] = $revFields[$kInfo['Column_name']];
				}
				
					// Write it back:
				$newOutput[$kN] = $kInfo;
			}
			$output = $newOutput;
		}

		return $output;
	}		

	/**
	 * mysql() wrapper function, used by the Install Tool and EM for all queries regarding management of the database!
	 * 
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer
	 */
	function admin_query($query)	{
		$parsedQuery = $this->SQLparser->parseSQL($query);
		$ORIG_table = $parsedQuery['TABLE'];
		
		if (is_array($parsedQuery))	{
				
				// Process query based on type:
			switch($parsedQuery['type'])	{
				case 'CREATETABLE':
				case 'ALTERTABLE':
				case 'DROPTABLE':
					$this->map_genericQueryParsed($parsedQuery);
				break;
				default:
					die('ERROR: Invalid Query type for ->admin_query() function!: "'.htmlspecialchars($query).'"');
				break;
			}

				// Setting query array (for other applications to access if needed)			
			$this->lastParsedAndMappedQueryArray = $parsedQuery;

				// Compiling query:
			$compiledQuery =  $this->SQLparser->compileSQL($this->lastParsedAndMappedQueryArray);
			
				// Execute query (based on handler derived from the TABLE name which we actually know for once!)
			$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_table);
			switch((string)$this->handlerCfg[$this->lastHandlerKey]['type'])	{
				case 'native':
					return mysql_query($compiledQuery, $this->link);
				break;
				case 'adodb':
				break;
				case 'pear':
				break;
				case 'userdefined':
					return $this->handlerInstance[$this->lastHandlerKey]->admin_query($compiledQuery);
				break;
			}
		} else die('ERROR: Query could not be parsed: "'.htmlspecialchars($parsedQuery).'". Query: "'.htmlspecialchars($query).'"');
	}







	


	/************************************
	 * 
	 * Handler management
	 * 
	 **************************************/

	/**
	 * Return the handler key pointing to an appropriate database handler as found in $this->handlerCfg array
	 * Notice: TWO or more tables in the table list MUST use the SAME handler key - otherwise a fatal error is thrown! (Logically, no database can possibly join two tables from separate sources!)
	 * 
	 * @param	string		Table list, eg. "pages" or "pages, tt_content" or "pages AS A, tt_content AS B"
	 * @return	string		Handler key (see $this->handlerCfg array) for table
	 */
	function handler_getFromTableList($tableList)	{

		$key = $tableList;
		if (!isset($this->cache_handlerKeyFromTableList[$key]))	{

				// Get tables separated:
			$_tableList = $tableList;
			$tableArray = $this->SQLparser->parseFromTables($_tableList);

				// If success, traverse the tables:
			if (is_array($tableArray) && count($tableArray))	{
				foreach($tableArray as $vArray)	{

						// Find handler key, select "_DEFAULT" if none is specifically configured:
					$handlerKey = $this->table2handlerKeys[$vArray['table']] ? $this->table2handlerKeys[$vArray['table']] : '_DEFAULT';

						// In case of separate handler keys for joined tables:
					if ($outputHandlerKey && $handlerKey != $outputHandlerKey)	{
						die('DBAL fatal error: Tables in this list "'.$tableList.'" didn\'t use the same DB handler!');
					}

					$outputHandlerKey = $handlerKey;
				}

					// Check initialized state; if handler is NOT initialized (connected) then we will connect it!
				if (!isset($this->handlerInstance[$outputHandlerKey]))	{
					$this->handler_init($outputHandlerKey);
				}

					// Return handler key:
				$this->cache_handlerKeyFromTableList[$key] = $outputHandlerKey;
			} else {
				die('DBAL fatal error: No tables found: "'.$tableList.'"');
			}
		}

		return $this->cache_handlerKeyFromTableList[$key];
	}

	/**
	 * Initialize handler (connecting to database)
	 * 
	 * @param	string		Handler key
	 * @return	boolean		If connection went well, return true
	 * @see handler_getFromTableList()
	 */
	function handler_init($handlerKey)	{

			// Find handler configuration:
		$cfgArray = $this->handlerCfg[$handlerKey];
		$handlerType = (string)$cfgArray['type'];
		$output = FALSE;
		
		if (is_array($cfgArray))	{
			switch($handlerType)	{
				case 'native':
					$link = mysql_pconnect(
									$cfgArray['config']['host'], 
									$cfgArray['config']['username'], 
									$cfgArray['config']['password']
								);
								
						// Set handler instance:
					$this->handlerInstance[$handlerKey] = array(
						'handlerType' => 'native',
						'link' => $link
					);
						
						// If link succeeded:
					if ($link)	{

							// For default, set ->link (see t3lib_DB)
						if ($handlerKey == '_DEFAULT') {
							$this->link = $link;
						}

							// Select database as well:
						if (mysql_select_db($cfgArray['config']['database'], $link))	{
							$output = TRUE;
						}
					}
				break;
				case 'adodb':
					require_once(t3lib_extMgm::extPath('dbal').'adodb/adodb.inc.php'); 
					$this->handlerInstance[$handlerKey] = &ADONewConnection($cfgArray['config']['driver']); 
					
					$output = $this->handlerInstance[$handlerKey]->PConnect(
									$cfgArray['config']['host'], 
									$cfgArray['config']['username'], 
									$cfgArray['config']['password'], 
									$cfgArray['config']['database']
								);
				break;
				case 'pear':
					require_once(t3lib_extMgm::extPath('dbal').'DB-1.6.0RC6/DB.php'); 
					
					$dsn = $cfgArray['config']['driver'].'://'.
							$cfgArray['config']['username'].':'.
							$cfgArray['config']['password'].'@'.
							$cfgArray['config']['host'].'/'.
							$cfgArray['config']['database'];
							
					$this->handlerInstance[$handlerKey] = DB::connect($dsn);
					$output = !DB::isError($this->handlerInstance[$handlerKey]);
				break;
				case 'userdefined':
						// Find class file:
					$fileName = t3lib_div::getFileAbsFileName($cfgArray['config']['classFile']);
					if (@is_file($fileName))	{
						require_once($fileName);
					} else die('DBAL error: "'.$fileName.'" was not a file to include.');

						// Initialize:
					$this->handlerInstance[$handlerKey] = t3lib_div::makeInstance($cfgArray['config']['class']);
					$this->handlerInstance[$handlerKey]->init($cfgArray,$this);
					
					if (is_object($this->handlerInstance[$handlerKey]))		{
						$output = TRUE;
					}
				break;
				default:
					die('ERROR: Invalid handler type: "'.$cfgArray['type'].'"');
				break;
			}
				
			return $output;
		} else die('ERROR: No handler for key "'.$handlerKey.'"');
	}














	/************************************
	 * 
	 * Table/Field mapping
	 * 
	 **************************************/

	/**
	 * Checks if mapping is needed for a table(list)
	 * 
	 * @param	string		List of tables in query
	 * @param	boolean		If true, it will check only if FIELDs are configured and ignore the mapped table name if any.
	 * @return	mixed		Returns an array of table names (parsed version of input table) if mapping is needed, otherwise just false.
	 */
	function map_needMapping($tableList,$fieldMappingOnly=FALSE)	{

		$key = $tableList.'|'.$fieldMappingOnly;
		if (!isset($this->cache_mappingFromTableList[$key]))	{
			$this->cache_mappingFromTableList[$key] = FALSE;	// Default:
			
			$tables = $this->SQLparser->parseFromTables($tableList);
			if (is_array($tables))	{
				foreach($tables as $tableCfg)	{
					if ($fieldMappingOnly)	{
						if (is_array($this->mapping[$tableCfg['table']]['mapFieldNames']))	{
							$this->cache_mappingFromTableList[$key] = $tables;
						}
					} else {
						if (is_array($this->mapping[$tableCfg['table']]))	{
							$this->cache_mappingFromTableList[$key] = $tables;
						}
					}
				}
			}
		}
		
		return $this->cache_mappingFromTableList[$key];
	}

	/**
	 * Takes an associated array with field => value pairs and remaps the field names if configured for this table in $this->mapping array.
	 * Be careful not to map a field name to another existing fields name (although you can use this to swap fieldnames of course...:-)
	 * Observe mapping problems with join-results (more than one table): Joined queries should always prefix the table name to avoid problems with this.
	 * Observe that alias fields are not mapped of course (should not be a problem though)
	 * 
	 * @param	array		Input array, associative keys
	 * @param	array		Array of tables from the query. Normally just one table; many tables in case of a join. NOTICE: for multiple tables (with joins) there MIGHT occur trouble with fields of the same name in the two tables: This function traverses the mapping information for BOTH tables and applies mapping without checking from which table the field really came!
	 * @param	boolean		If true, reverse direction. Default direction is to map an array going INTO the database (thus mapping TYPO3 fieldnames to PHYSICAL field names!)
	 * @return	array		Output array, with mapped associative keys.
	 */
	function map_assocArray($input,$tables,$rev=FALSE)	{

			// Traverse tables from query (hopefully only one table):
		foreach($tables as $tableCfg)	{
			if (is_array($this->mapping[$tableCfg['table']]['mapFieldNames']))	{
			
					// Get the map (reversed if needed):
				if ($rev)	{
					$theMap = array_flip($this->mapping[$tableCfg['table']]['mapFieldNames']);
				} else {
					$theMap = $this->mapping[$tableCfg['table']]['mapFieldNames'];
				}

					// Traverse selected record, map fieldnames:
				$output = array();
				foreach($input as $fN => $value)	{
				
						// Set the field name, change it if found in mapping array:
					if ($theMap[$fN])	{
						$newKey = $theMap[$fN];
					} else {
						$newKey = $fN;
					}
					
						// Set value to fieldname:
					$output[$newKey] = $value;
				}
				
					// When done, override the $input array with the result:
				$input = $output;
			}
		}

			// Return input array (which might have been altered in the mean time)
		return $input;
	}

	/**
	 * Remaps table/field names in a SELECT query's parts
	 * Notice: All arguments are passed by reference!
	 * 
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		Table(s) from which to select. This is what comes right after "FROM ...". Require value.
	 * @param	string		Where clause. This is what comes right after "WHERE ...". Can be blank.
	 * @param	string		Group by field(s)
	 * @param	string		Order by field(s)
	 * @return	void		
	 * @see exec_SELECTquery()
	 */
	function map_remapSELECTQueryParts(&$select_fields,&$from_table,&$where_clause,&$groupBy,&$orderBy)	{
		
			// Tables:
		$tables = $this->SQLparser->parseFromTables($from_table);
		$defaultTable = $tables[0]['table'];
		foreach($tables as $k => $v)	{
			if ($this->mapping[$v['table']]['mapTableName'])	{
				$tables[$k]['table'] = $this->mapping[$v['table']]['mapTableName'];
			}
		}	
		$from_table = $this->SQLparser->compileFromTables($tables);

			// Where clause:
		$whereParts = $this->SQLparser->parseWhereClause($where_clause);
		$this->map_sqlParts($whereParts,$defaultTable);
		$where_clause = $this->SQLparser->compileWhereClause($whereParts);

			// Select fields:
		$expFields = $this->SQLparser->parseFieldList($select_fields);
		$this->map_sqlParts($expFields,$defaultTable);
		$select_fields = $this->SQLparser->compileFieldList($expFields);

			// Group By fields
		$expFields = $this->SQLparser->parseFieldList($groupBy);
		$this->map_sqlParts($expFields,$defaultTable);
		$groupBy = $this->SQLparser->compileFieldList($expFields);

			// Order By fields
		$expFields = $this->SQLparser->parseFieldList($orderBy);
		$this->map_sqlParts($expFields,$defaultTable);
		$orderBy = $this->SQLparser->compileFieldList($expFields);
	}

	/**
	 * Generic mapping of table/field names arrays (as parsed by t3lib_sqlengine)
	 * 
	 * @param	array		Array with parsed SQL parts; Takes both fields, tables, where-parts, group and order-by. Passed by reference.
	 * @param	string		Default table name to assume if no table is found in $sqlPartArray
	 * @return	void		
	 * @access private
	 * @see map_remapSELECTQueryParts()
	 */
	function map_sqlParts(&$sqlPartArray, $defaultTable)	{

			// Traverse sql Part array:
		if (is_array($sqlPartArray))	{
			foreach($sqlPartArray as $k => $v)	{

					// Look for sublevel (WHERE parts only)
				if (is_array($sqlPartArray[$k]['sub']))	{
					$this->map_sqlParts($sqlPartArray[$k]['sub'], $defaultTable);	// Call recursively!
				} else {
						// For the field, look for table mapping (generic):
					$t = $sqlPartArray[$k]['table'] ? $sqlPartArray[$k]['table'] : $defaultTable;
					
						// Mapping field name, if set:
					if (is_array($this->mapping[$t]['mapFieldNames']) && $this->mapping[$t]['mapFieldNames'][$sqlPartArray[$k]['field']])	{
						$sqlPartArray[$k]['field'] = $this->mapping[$t]['mapFieldNames'][$sqlPartArray[$k]['field']];
					}
					
						// Map table?
					if ($sqlPartArray[$k]['table'] && $this->mapping[$sqlPartArray[$k]['table']]['mapTableName'])	{
						$sqlPartArray[$k]['table'] = $this->mapping[$sqlPartArray[$k]['table']]['mapTableName'];
					}
				}
			}
		}
	}

	/**
	 * Will do table/field mapping on a general t3lib_sqlengine-compliant SQL query
	 * (May still not support all query types...)
	 * 
	 * @param	array		Parsed QUERY as from t3lib_sqlengine::parseSQL(). NOTICE: Passed by reference!
	 * @return	void		
	 * @see t3lib_sqlengine::parseSQL()
	 */
	function map_genericQueryParsed(&$parsedQuery)	{
	
			// Getting table - same for all:
		$table = $parsedQuery['TABLE'];
		if ($table)	{
				// Do field mapping if needed:
			if ($tableArray = $this->map_needMapping($table))	{
	
					// Table name:
				if ($this->mapping[$table]['mapTableName'])	{
					$parsedQuery['TABLE'] = $this->mapping[$table]['mapTableName'];
				}

					// Based on type, do additional changes:			
				switch($parsedQuery['type'])	{
					case 'ALTERTABLE':

							// Changing field name:
						$newFieldName = $this->mapping[$table]['mapFieldNames'][$parsedQuery['FIELD']];
						if ($newFieldName)	{
							if ($parsedQuery['FIELD'] == $parsedQuery['newField'])	{
								$parsedQuery['FIELD'] = $parsedQuery['newField'] = $newFieldName;
							} else $parsedQuery['FIELD'] = $newFieldName;
						}

							// Changing key field names:
						if (is_array($parsedQuery['fields']))	{
							$this->map_fieldNamesInArray($table,$parsedQuery['fields']);
						}
					break;
					case 'CREATETABLE':
							// Remapping fields:
						if (is_array($parsedQuery['FIELDS']))	{
							$newFieldsArray = array();
							foreach($parsedQuery['FIELDS'] as $fN => $fInfo)	{
								if ($this->mapping[$table]['mapFieldNames'][$fN])	{
									$fN = $this->mapping[$table]['mapFieldNames'][$fN];
								}
								$newFieldsArray[$fN] = $fInfo;
							}
							$parsedQuery['FIELDS'] = $newFieldsArray;
						}
						
							// Remapping keys:
						if (is_array($parsedQuery['KEYS']))	{
							foreach($parsedQuery['KEYS'] as $kN => $kInfo)	{
								$this->map_fieldNamesInArray($table,$parsedQuery['KEYS'][$kN]);
							}
						}
					break;
					
					
					/// ... and here support for all other query types should be!
					
					
				}	
			}
		} else die('ERROR, mapping: No table found in parsed Query array...');
	}

	/**
	 * Re-mapping field names in array
	 * 
	 * @param	string		(TYPO3) Table name for fields.
	 * @param	array		Array of fieldnames to remap. Notice: Passed by reference!
	 * @return	void		
	 */
	function map_fieldNamesInArray($table,&$fieldArray)	{
		if (is_array($this->mapping[$table]['mapFieldNames']))	{
			foreach($fieldArray as $k => $v)	{
				if ($this->mapping[$table]['mapFieldNames'][$v])	{
					$fieldArray[$k] = $this->mapping[$table]['mapFieldNames'][$v];
				}
			}
		}
	}














	


	/**************************************
	 *
	 * Debugging
	 *
	 **************************************/
	
	/**
	 * Debug handler for query execution
	 * 
	 * @param	string		Function name from which this function is called.
	 * @param	string		Execution time in ms of the query
	 * @param	array		In-data of various kinds.
	 * @return	void		
	 * @access private
	 */
	function debugHandler($function,$execTime,$inData)	{

		switch($function)	{
			case 'exec_SELECTquery':

					// Initialize:
				$data = array();
				$errorFlag = 0;
				$joinTable = '';

					// Check error:
				if ($this->sql_error())	{
					$data['sqlError'] = $this->sql_error();
					$errorFlag|=1;
				}

					// Get explain data:
				if ($this->conf['debugOptions']['EXPLAIN'] && t3lib_div::inList('pear,adodb,native',$inData['handlerType']))	{
					$data['EXPLAIN'] = $this->debug_explain($this->lastQuery);
				}
				
					// Check parsing of Query:
				if ($this->conf['debugOptions']['parseQuery'])	{
					$parseResults = array();
					$parseResults['SELECT'] = $this->SQLparser->debug_parseSQLpart('SELECT',$inData['args'][0]);
					$parseResults['FROM'] = $this->SQLparser->debug_parseSQLpart('FROM',$inData['args'][1]);
					$parseResults['WHERE'] = $this->SQLparser->debug_parseSQLpart('WHERE',$inData['args'][2]);
					$parseResults['GROUPBY'] = $this->SQLparser->debug_parseSQLpart('SELECT',$inData['args'][3]);	// Using select field list syntax
					$parseResults['ORDERBY'] = $this->SQLparser->debug_parseSQLpart('SELECT',$inData['args'][4]);	// Using select field list syntax
					
					foreach($parseResults as $k => $v)	{
						if (!strlen($parseResults[$k]))	unset($parseResults[$k]);
					}
					if (count($parseResults))	{
						$data['parseError'] = $parseResults;
						$errorFlag|=2;
					}
				}

					// Checking joinTables:
				if ($this->conf['debugOptions']['joinTables'])	{
					if (count(explode(',', $inData['ORIG_from_table']))>1)		{
						$joinTable = $inData['args'][1];
					}
				}
				
					// Logging it:					
				$this->debug_log($this->lastQuery,$execTime,$data,$joinTable,$errorFlag);
			break;			
			case 'exec_INSERTquery':
				// Filter out tx_dbal_debuglog table!!!
			break;
		}
	}

	/**
	 * Insert row in the log table
	 * 
	 * @param	string		The current query
	 * @param	integer		Execution time of query in milliseconds
	 * @param	array		Data to be stored serialized.
	 * @param	string		Join string if there IS a join.
	 * @param	integer		Error status.
	 * @return	void		
	 */
	function debug_log($query,$ms,$data,$join,$errorFlag)	{

		$script = substr(PATH_thisScript,strlen(PATH_site));
		
		if (substr($script,-strlen('ext/dbal/mod1/index.php'))!='ext/dbal/mod1/index.php')	{
			$insertArray = array (
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'beuser_id' => intval($GLOBALS['BE_USER']->user['uid']),
				'script' => $script,
				'exec_time' => $ms,
				'table_join' => $join,
				'serdata' => serialize($data),
				'query' => $query,
				'errorFlag' => $errorFlag
			);
			$this->exec_INSERTquery('tx_dbal_debuglog', $insertArray);
		}
	}

	/**
	 * Perform EXPLAIN query on DEFAULT handler!
	 * 
	 * @param	string		SELECT Query
	 * @return	array		The Explain result rows in an array
	 * @todo	Not supporting other than the default handler? And what about DBMS of other kinds than MySQl - support for EXPLAIN?
	 */
	function debug_explain($query)	{
		$res = $this->sql_query('EXPLAIN '.$query);
		
		$output = array();
		while($row = $this->sql_fetch_assoc($res))	{
			$output[] = $row;
		}
		return $output;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_t3lib_db.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_t3lib_db.php']);
}
?>
