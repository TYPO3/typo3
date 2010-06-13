<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 Kasper Skaarhoj (kasper@typo3.com)
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
 * Contains an example DBAL handler class
 *
 * $Id: class.tx_dbal_handler_rawmysql.php 25889 2009-10-27 10:09:11Z xperseguers $
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   86: class tx_dbal_handler_rawmysql extends tx_dbal_sqlengine
 *   99:     function init($config,&$pObj)
 *  123:     function exec_INSERTquery($table,$fields_values)
 *  135:     function exec_UPDATEquery($table,$where,$fields_values)
 *  146:     function exec_DELETEquery($table,$where)
 *  161:     function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit)
 *  173:     function sql_error()
 *  182:     function sql_insert_id()
 *  191:     function sql_affected_rows()
 *  201:     function sql_query($query)
 *  213:     function quoteStr($str)
 *
 *              SECTION: SQL admin functions
 *  237:     function admin_get_tables()
 *  254:     function admin_get_fields($tableName)
 *  272:     function admin_get_keys($tableName)
 *  290:     function admin_query($query)
 *
 *
 *  308: class tx_dbal_handler_rawmysql_sqlObj extends tx_dbal_sqlengine_resultobj
 *  317:     function sql_num_rows()
 *  326:     function sql_fetch_assoc()
 *  335:     function sql_fetch_row()
 *  345:     function sql_data_seek($pointer)
 *
 * TOTAL FUNCTIONS: 18
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */










/**
 * Example DBAL userdefined handler class
 * It simply makes pass-through of MySQL
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_dbal
 */
class tx_dbal_handler_rawmysql extends tx_dbal_sqlengine {

	var $config = array();
	var $link;
	var $pObj;	// Set from DBAL class.

	/**
	 * Initialize.
	 * For MySQL we will have to connect to the database and select the database.
	 *
	 * @param	array		Configuration array from handler
	 * @param	object		Parent object.
	 * @return	boolean		True if connection and database selection worked out well.
	 */
	function init($config,&$pObj)	{
		$this->config = $config['config'];
		$this->pObj = $pObj;
		$this->link = mysql_pconnect(
							$this->config['host'],
							$this->config['username'],
							$this->config['password']
						);

			// Select database as well:
		if (mysql_select_db($this->config['database'], $this->link))	{
			$output = TRUE;
		}

		return $output;
	}

	/**
	 * Execute INSERT query
	 *
	 * @param	string		Table name
	 * @param	array		Field=>Value array
	 * @return	boolean		True on success
	 */
	function exec_INSERTquery($table,$fields_values)	{
		return mysql_query($GLOBALS['TYPO3_DB']->INSERTquery($table,$fields_values), $this->link);
	}

	/**
	 * Execute UPDATE query
	 *
	 * @param	string		Table name
	 * @param	string		WHERE clause
	 * @param	array		Field=>Value array
	 * @return	boolean		True on success
	 */
	function exec_UPDATEquery($table,$where,$fields_values)	{
		return mysql_query($GLOBALS['TYPO3_DB']->UPDATEquery($table,$where,$fields_values), $this->link);
	}

	/**
	 * Execute DELETE query
	 *
	 * @param	string		Table name
	 * @param	string		WHERE clause
	 * @return	boolean		True on success
	 */
	function exec_DELETEquery($table,$where)	{
		return mysql_query($GLOBALS['TYPO3_DB']->DELETEquery($table,$where), $this->link);
	}

	/**
	 * Execute SELECT query
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values with addslashes() first
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	object		Result object
	 */
	function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit)	{
		$res = t3lib_div::makeInstance('tx_dbal_handler_rawmysql_sqlObj');		// Create result object
		$this->pObj->lastQuery = $GLOBALS['TYPO3_DB']->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
		$res->result = mysql(TYPO3_db, $this->pObj->lastQuery, $this->link);	// Execute query
		return $res;
	}

	/**
	 * mysql_error() wrapper
	 *
	 * @return	string		mysql_error()
	 */
	function sql_error()	{
		return mysql_error();
	}

	/**
	 * mysql_insert_id() wrapper
	 *
	 * @return	integer		mysql_insert_id();
	 */
	function sql_insert_id()	{
		return mysql_insert_id();
	}

	/**
	 * mysql_affected_rows() wrapper
	 *
	 * @return	integer		mysql_affected_rows()
	 */
	function sql_affected_rows()	{
		return mysql_affected_rows();
	}

	/**
	 * mysql_query() wrapper
	 *
	 * @param	string		Query string
	 * @return	object		Result object
	 */
	function sql_query($query)	{
		$res = t3lib_div::makeInstance('tx_dbal_handler_rawmysql_sqlObj');
		$res->result = mysql_query($query, $this->link);
		return $res;
	}

	/**
	 * Escape quotes in strings
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function quoteStr($str)	{
		return addslashes($str);
	}









	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/

	/**
	 * Returns the list of tables from the database, quering MySQL for it.
	 *
	 * @return	array		Tables in an array (tablename is in both key and value)
	 * @todo	Should return table details in value! see t3lib_db::admin_get_tables()
	 */
	function admin_get_tables()	{
		$whichTables = array();
		$tables_result = mysql_list_tables($this->config['database'], $this->link);
		if (!mysql_error())	{
			while ($theTable = mysql_fetch_assoc($tables_result)) {
				$whichTables[current($theTable)] = current($theTable);
			}
		}
		return $whichTables;
	}

	/**
	 * Returns information about each field in the $table, quering MySQL for it.
	 *
	 * @param	string		Table name
	 * @return	array		Field information in an associative array with fieldname => field row
	 */
	function admin_get_fields($tableName)	{
		$output = array();

		if ($columns_res = @mysql_query('SHOW columns FROM '.$tableName, $this->link))	{
			while($fieldRow = mysql_fetch_assoc($columns_res))	{
				$output[$fieldRow["Field"]] = $fieldRow;
			}
		}

		return $output;
	}

	/**
	 * Returns information about each index key in the $table, quering MySQL for it.
	 *
	 * @param	string		Table name
	 * @return	array		Key information in a numeric array
	 */
	function admin_get_keys($tableName)	{
		$output = array();

		if ($keyRes = @mysql_query('SHOW keys FROM '.$tableName, $this->link))	{
			while($keyRow = mysql_fetch_assoc($keyRes))	{
				$output[] = $keyRow;
			}
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
		return $this->sql_query($query);
	}
}







/**
 * Result object for this MySQL userdefined handler
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_dbal
 */
class tx_dbal_handler_rawmysql_sqlObj extends tx_dbal_sqlengine_resultobj {

	var $result = '';			// Not array here, but resource pointer.

	/**
	 * mysql_num_rows() Wrapper
	 *
	 * @return	integer		mysql_num_rows()
	 */
	function sql_num_rows()	{
		return mysql_num_rows($this->result);
	}

	/**
	 * mysql_fetch_assoc() Wrapper
	 *
	 * @return	array		mysql_fetch_assoc()
	 */
	function sql_fetch_assoc()	{
		return mysql_fetch_assoc($this->result);
	}

	/**
	 * mysql_fetch_row()	wrapper
	 *
	 * @return	array		mysql_fetch_row()
	 */
	function sql_fetch_row()	{
		return mysql_fetch_row($this->result);
	}

	/**
	 * mysql_data_seek() wrapper
	 *
	 * @param	integer		Pointer to go to.
	 * @return	boolean		mysql_data_seek()
	 */
	function sql_data_seek($pointer)	{
		return mysql_data_seek($this->result,$pointer);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/handlers/class.tx_dbal_handler_rawmysql.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/handlers/class.tx_dbal_handler_rawmysql.php']);
}
?>
