<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2004-2010 Karsten Dambekalns <karsten@typo3.org>
*  (c) 2009-2010 Xavier Perseguers <typo3@perseguers.ch>
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
 * $Id: class.ux_t3lib_db.php 29977 2010-02-13 13:18:32Z xperseguers $
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @author	Karsten Dambekalns <k.dambekalns@fishfarm.de>
 * @author	Xavier Perseguers <typo3@perseguers.ch>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  123: class ux_t3lib_DB extends t3lib_DB
 *  169:     function ux_t3lib_DB()
 *  184:     function initInternalVariables()
 *
 *              SECTION: Query Building (Overriding parent methods)
 *  217:     function exec_INSERTquery($table,$fields_values)
 *  275:     function exec_UPDATEquery($table,$where,$fields_values)
 *  334:     function exec_DELETEquery($table,$where)
 *  387:     function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy = '',$orderBy = '',$limit = '')
 *
 *              SECTION: Creates an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
 *  533:     function SELECTquery($select_fields,$from_table,$where_clause,$groupBy = '',$orderBy = '',$limit = '')
 *  556:     function quoteSelectFields(&$select_fields)
 *  573:     function quoteFromTables(&$from_table)
 *  595:     function quoteWhereClause(&$where_clause)
 *  620:     function quoteGroupBy(&$groupBy)
 *  637:     function quoteOrderBy(&$orderBy)
 *
 *              SECTION: Various helper functions
 *  663:     function quoteStr($str, $table)
 *
 *              SECTION: SQL wrapper functions (Overriding parent methods)
 *  707:     function sql_error()
 *  734:     function sql_num_rows(&$res)
 *  760:     function sql_fetch_assoc(&$res)
 *  808:     function sql_fetch_row(&$res)
 *  842:     function sql_free_result(&$res)
 *  868:     function sql_insert_id()
 *  893:     function sql_affected_rows()
 *  919:     function sql_data_seek(&$res,$seek)
 *  946:     function sql_field_type(&$res,$pointer)
 *
 *              SECTION: Legacy functions, bound to _DEFAULT handler. (Overriding parent methods)
 *  987:     function sql($db,$query)
 *  999:     function sql_query($query)
 * 1035:     function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password)
 * 1055:     function sql_select_db($TYPO3_db)
 *
 *              SECTION: SQL admin functions
 * 1086:     function admin_get_tables()
 * 1149:     function admin_get_fields($tableName)
 * 1210:     function admin_get_keys($tableName)
 * 1270:     function admin_query($query)
 *
 *              SECTION: Handler management
 * 1333:     function handler_getFromTableList($tableList)
 * 1379:     function handler_init($handlerKey)
 *
 *              SECTION: Table/Field mapping
 * 1488:     function map_needMapping($tableList,$fieldMappingOnly = FALSE)
 * 1524:     function map_assocArray($input,$tables,$rev = FALSE)
 * 1573:     function map_remapSELECTQueryParts(&$select_fields,&$from_table,&$where_clause,&$groupBy,&$orderBy)
 * 1615:     function map_sqlParts(&$sqlPartArray, $defaultTable)
 * 1650:     function map_genericQueryParsed(&$parsedQuery)
 * 1717:     function map_fieldNamesInArray($table,&$fieldArray)
 *
 *              SECTION: Debugging
 * 1758:     function debugHandler($function,$execTime,$inData)
 * 1823:     function debug_log($query,$ms,$data,$join,$errorFlag)
 * 1849:     function debug_explain($query)
 *
 * TOTAL FUNCTIONS: 41
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * TYPO3 database abstraction layer
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @author	Karsten Dambekalns <k.dambekalns@fishfarm.de>
 * @package TYPO3
 * @subpackage tx_dbal
 */
class ux_t3lib_DB extends t3lib_DB {

		// Internal, static:
	var $printErrors = FALSE;	// Enable output of SQL errors after query executions. Set through TYPO3_CONF_VARS, see init()
	var $debug = FALSE;			// Enable debug mode. Set through TYPO3_CONF_VARS, see init()
	var $conf = array();		// Configuration array, copied from TYPO3_CONF_VARS in constructor.

	var $mapping = array();		// See manual.
	var $table2handlerKeys = array();	// See manual.
	var $handlerCfg = array(	// See manual.
	    '_DEFAULT' => array(
				'type' => 'native',
				'config' => array(
				    'username' => '',	// Set by default (overridden)
				    'password' => '',	// Set by default (overridden)
				    'host' => '',	// Set by default (overridden)
				    'database' => '',	// Set by default (overridden)
				    'driver' => '',	// ONLY "adodb" type; eg. "mysql"
				    'sequenceStart' => 1,	// ONLY "adodb", first number in sequences/serials/...
				    'useNameQuote' => 0	// ONLY "adodb", whether to use NameQuote() method from ADOdb to quote names
				)
	    ),
	);


		// Internal, dynamic:
	var $handlerInstance = array();				// Contains instance of the handler objects as they are created. Exception is the native mySQL calls which are registered as an array with keys "handlerType" = "native" and "link" pointing to the link resource for the connection.
	var $lastHandlerKey = '';					// Storage of the handler key of last ( SELECT) query - used for subsequent fetch-row calls etc.
	var $lastQuery = '';						// Storage of last SELECT query
	var $lastParsedAndMappedQueryArray = array();	// Query array, the last one parsed

	var $resourceIdToTableNameMap = array();	// Mapping of resource ids to table names.

		// Internal, caching:
	var $cache_handlerKeyFromTableList = array();			// Caching handlerKeys for table lists
	var $cache_mappingFromTableList = array();			// Caching mapping information for table lists
	var $cache_autoIncFields = array(); // parsed SQL from standard DB dump file
	var $cache_fieldType = array(); // field types for tables/fields
	var $cache_primaryKeys = array(); // primary keys

	/**
	 * SQL parser
	 *
	 * @var tx_dbal_sqlengine
	 */
	var $SQLparser;

	/**
	 * Installer
	 *
	 * @var t3lib_install
	 */
	var $Installer;


	/**
	 * Constructor.
	 * Creates SQL parser object and imports configuration from $TYPO3_CONF_VARS['EXTCONF']['dbal']
	 */
	public function __construct() {
			// Set SQL parser object for internal use:
		$this->SQLparser = t3lib_div::makeInstance('tx_dbal_sqlengine');
		$this->Installer = t3lib_div::makeInstance('t3lib_install');

			// Set internal variables with configuration:
		$this->conf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal'];
		$this->initInternalVariables();
	}

	/**
	 * Setting internal variables from $this->conf.
	 *
	 * @return	void
	 */
	protected function initInternalVariables() {
			// Set outside configuration:
		if (isset($this->conf['mapping'])) {
			$this->mapping = $this->conf['mapping'];
		}
		if (isset($this->conf['table2handlerKeys'])) {
			$this->table2handlerKeys = $this->conf['table2handlerKeys'];
		}
		if (isset($this->conf['handlerCfg'])) {
			$this->handlerCfg = $this->conf['handlerCfg'];
		}

		$this->cacheFieldInfo();
			// Debugging settings:
		$this->printErrors = $this->conf['debugOptions']['printErrors'] ? TRUE : FALSE;
		$this->debug = $this->conf['debugOptions']['enabled'] ? TRUE : FALSE;
	}

	/**
	 * Clears the cached field information file.
	 * 
	 * @return void
	 */
	public function clearCachedFieldInfo() {
		if (file_exists(PATH_typo3conf . 'temp_fieldInfo.php')) {
			unlink(PATH_typo3conf . 'temp_fieldInfo.php');	
		}
	}

	/**
	 * Caches the field information.
	 * 
	 * @return void
	 */
	public function cacheFieldInfo() {
		$extSQL = '';
		$parsedExtSQL = array();

			// try to fetch cached file first
			// file is removed when admin_query() is called
		if (file_exists(PATH_typo3conf . 'temp_fieldInfo.php')) {
			$fdata = unserialize(t3lib_div::getUrl(PATH_typo3conf . 'temp_fieldInfo.php'));
			$this->cache_autoIncFields = $fdata['incFields'];
			$this->cache_fieldType = $fdata['fieldTypes'];
			$this->cache_primaryKeys = $fdata['primaryKeys'];
		} else {
				// handle stddb.sql, parse and analyze
			$extSQL = t3lib_div::getUrl(PATH_site . 't3lib/stddb/tables.sql');
			$parsedExtSQL = $this->Installer->getFieldDefinitions_fileContent($extSQL);
			$this->analyzeFields($parsedExtSQL);

				// loop over all installed extensions
			foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $ext => $v) {
				if (!is_array($v) || !isset($v['ext_tables.sql'])) {
					continue;
				}

					// fetch db dump (if any) and parse it, then analyze
				$extSQL = t3lib_div::getUrl($v['ext_tables.sql']);
				$parsedExtSQL = $this->Installer->getFieldDefinitions_fileContent($extSQL);
				$this->analyzeFields($parsedExtSQL);
			}

			$cachedFieldInfo = array('incFields' => $this->cache_autoIncFields, 'fieldTypes' => $this->cache_fieldType, 'primaryKeys' => $this->cache_primaryKeys);
			$cachedFieldInfo = serialize($this->mapCachedFieldInfo($cachedFieldInfo));

				// write serialized content to file
			t3lib_div::writeFile(PATH_typo3conf . 'temp_fieldInfo.php', $cachedFieldInfo);

			if (strcmp(t3lib_div::getUrl(PATH_typo3conf . 'temp_fieldInfo.php'), $cachedFieldInfo)) {
				die('typo3temp/temp_incfields.php was NOT updated properly (written content didn\'t match file content) - maybe write access problem?');
			}
		}
	}

	/**
	 * Analyzes fields and adds the extracted information to the field type, auto increment and primary key info caches.
	 *
	 * @param array $parsedExtSQL The output produced by t3lib_install::getFieldDefinitions_fileContent()
	 * @return void
	 * @see t3lib_install::getFieldDefinitions_fileContent()
	 */
	protected function analyzeFields($parsedExtSQL) {
		foreach ($parsedExtSQL as $table => $tdef) {
			if (is_array($tdef['fields'])) {
				foreach ($tdef['fields'] as $field => $fdef) {
					$fdef = $this->SQLparser->parseFieldDef($fdef);
					$this->cache_fieldType[$table][$field]['type'] = $fdef['fieldType'];
					$this->cache_fieldType[$table][$field]['metaType'] = $this->MySQLMetaType($fdef['fieldType']);
					$this->cache_fieldType[$table][$field]['notnull'] = (isset($fdef['featureIndex']['NOTNULL']) && !$this->SQLparser->checkEmptyDefaultValue($fdef['featureIndex'])) ? 1 : 0;
					if (isset($fdef['featureIndex']['DEFAULT'])) {
						$default = $fdef['featureIndex']['DEFAULT']['value'][0];
						if (isset($fdef['featureIndex']['DEFAULT']['value'][1])) {
							$default = $fdef['featureIndex']['DEFAULT']['value'][1] . $default . $fdef['featureIndex']['DEFAULT']['value'][1];
						}
						$this->cache_fieldType[$table][$field]['default'] = $default;
					}
					if (isset($fdef['featureIndex']['AUTO_INCREMENT'])) {
						$this->cache_autoIncFields[$table] = $field;
					}
					if (isset($tdef['keys']['PRIMARY'])) {
						$this->cache_primaryKeys[$table] = substr($tdef['keys']['PRIMARY'], 13, -1);
					}
				}
			}
		}
	}

	/**
	* This function builds all definitions for mapped tables and fields
	* @see cacheFieldInfo()
	*/
	protected function mapCachedFieldInfo($fieldInfo) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping'] as $mappedTable => $mappedConf) {
				if (array_key_exists($mappedTable, $fieldInfo['incFields'])) {
					$mappedTableAlias = $mappedConf['mapTableName'];
					if (isset($mappedConf['mapFieldNames'][$fieldInfo['incFields'][$mappedTable]])) {
						$fieldInfo['incFields'][$mappedTableAlias] = $mappedConf['mapFieldNames'][$fieldInfo['incFields'][$mappedTable]];
					} else {
						$fieldInfo['incFields'][$mappedTableAlias] = $fieldInfo['incFields'][$mappedTable];	
					}
				}

				if (array_key_exists($mappedTable, $fieldInfo['fieldTypes'])) {
					foreach ($fieldInfo['fieldTypes'][$mappedTable] as $field => $fieldConf) {
						$tempMappedFieldConf[$mappedConf['mapFieldNames'][$field]] = $fieldConf;
					}

					$fieldInfo['fieldTypes'][$mappedConf['mapTableName']] = $tempMappedFieldConf;
				}

				if (array_key_exists($mappedTable, $fieldInfo['primaryKeys'])) {
					$mappedTableAlias = $mappedConf['mapTableName'];
					if (isset($mappedConf['mapFieldNames'][$fieldInfo['primaryKeys'][$mappedTable]])) {
						$fieldInfo['primaryKeys'][$mappedTableAlias] = $mappedConf['mapFieldNames'][$fieldInfo['primaryKeys'][$mappedTable]];
					} else {
						$fieldInfo['primaryKeys'][$mappedTableAlias] = $fieldInfo['primaryKeys'][$mappedTable];	
					}
				}
			}
		}

		return $fieldInfo;
	}


	/************************************
	*
	* Query Building (Overriding parent methods)
	* These functions are extending counterparts in the parent class.
	*
	**************************************/

	/* From the ADOdb documentation, this is what we do (_Execute for SELECT, _query for the other actions)

	Execute() is the default way to run queries. You can use the low-level functions _Execute() and _query() to reduce query overhead.
	Both these functions share the same parameters as Execute().

	If you do not have any bind parameters or your database supports binding (without emulation), then you can call _Execute() directly.
	Calling this function bypasses bind emulation. Debugging is still supported in _Execute().

	If you do not require debugging facilities nor emulated binding, and do not require a recordset to be returned, then you can call _query.
	This is great for inserts, updates and deletes. Calling this function bypasses emulated binding, debugging, and recordset handling. Either
	the resultid, TRUE or FALSE are returned by _query().
	*/

	/**
	 * Inserts a record for $table from the array with field/value pairs $fields_values.
	 *
	 * @param	string		Table name
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param mixed    List/array of keys NOT to quote (eg. SQL functions)
	 * @return	mixed		Result from handler, usually TRUE when success and FALSE on failure
	 */
	public function exec_INSERTquery($table, $fields_values, $no_quote_fields = '') {

		if ($this->debug) {
			$pt = t3lib_div::milliseconds();
		}

			// Do field mapping if needed:
		$ORIG_tableName = $table;
		if ($tableArray = $this->map_needMapping($table)) {

				// Field mapping of array:
			$fields_values = $this->map_assocArray($fields_values, $tableArray);

				// Table name:
			if ($this->mapping[$table]['mapTableName']) {
				$table = $this->mapping[$table]['mapTableName'];
			}
		}
			// Select API:
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$this->lastQuery = $this->INSERTquery($table,$fields_values,$no_quote_fields);
				if (is_string($this->lastQuery)) {
					$sqlResult = mysql_query($this->lastQuery, $this->handlerInstance[$this->lastHandlerKey]['link']);
				} else {
					$sqlResult = mysql_query($this->lastQuery[0], $this->handlerInstance[$this->lastHandlerKey]['link']);
					foreach ($this->lastQuery[1] as $field => $content) {
						mysql_query('UPDATE ' . $this->quoteFromTables($table) . ' SET ' . $this->quoteFromTables($field) . '=' . $this->fullQuoteStr($content, $table) . ' WHERE ' . $this->quoteWhereClause($where), $this->handlerInstance[$this->lastHandlerKey]['link']);
					}
				}
				break;
			case 'adodb':
					// auto generate ID for auto_increment fields if not present (static import needs this!)
					// should we check the table name here (static_*)?
				if (isset($this->cache_autoIncFields[$table])) {
					if (isset($fields_values[$this->cache_autoIncFields[$table]])) {
						$new_id = $fields_values[$this->cache_autoIncFields[$table]];
						if ($table != 'tx_dbal_debuglog') {
							$this->handlerInstance[$this->lastHandlerKey]->last_insert_id = $new_id;
						}
					} else {
						$new_id = $this->handlerInstance[$this->lastHandlerKey]->GenID($table.'_'.$this->cache_autoIncFields[$table], $this->handlerInstance[$this->lastHandlerKey]->sequenceStart);
						$fields_values[$this->cache_autoIncFields[$table]] = $new_id;
						if ($table != 'tx_dbal_debuglog') {
							$this->handlerInstance[$this->lastHandlerKey]->last_insert_id = $new_id;
						}
					}
				}

				$this->lastQuery = $this->INSERTquery($table,$fields_values,$no_quote_fields);
				if (is_string($this->lastQuery)) {
					$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->_query($this->lastQuery,FALSE);
				} else {
					$this->handlerInstance[$this->lastHandlerKey]->StartTrans();
					if (strlen($this->lastQuery[0])) {
						$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->_query($this->lastQuery[0],FALSE);
					}
					if (is_array($this->lastQuery[1])) {
						foreach ($this->lastQuery[1] as $field => $content) {
							if (empty($content)) continue;

							if (isset($this->cache_autoIncFields[$table]) && isset($new_id)) {
								$this->handlerInstance[$this->lastHandlerKey]->UpdateBlob($this->quoteFromTables($table),$field,$content,$this->quoteWhereClause($this->cache_autoIncFields[$table].'='.$new_id));
							} elseif (isset($this->cache_primaryKeys[$table])) {
								$where = '';
								$pks = explode(',', $this->cache_primaryKeys[$table]);
								foreach ($pks as $pk) {
									if (isset($fields_values[$pk]))
									$where .= $pk.'='.$this->fullQuoteStr($fields_values[$pk], $table).' AND ';
								}
								$where = $this->quoteWhereClause($where.'1=1');
								$this->handlerInstance[$this->lastHandlerKey]->UpdateBlob($this->quoteFromTables($table),$field,$content,$where);
							} else {
								$this->handlerInstance[$this->lastHandlerKey]->CompleteTrans(FALSE);
								die('Could not update BLOB >>>> no WHERE clause found!'); // should never ever happen
							}
						}
					}
					if (is_array($this->lastQuery[2])) {
						foreach ($this->lastQuery[2] as $field => $content) {
							if (empty($content)) continue;

							if (isset($this->cache_autoIncFields[$table]) && isset($new_id)) {
								$this->handlerInstance[$this->lastHandlerKey]->UpdateClob($this->quoteFromTables($table),$field,$content,$this->quoteWhereClause($this->cache_autoIncFields[$table].'='.$new_id));
							} elseif (isset($this->cache_primaryKeys[$table])) {
								$where = '';
								$pks = explode(',', $this->cache_primaryKeys[$table]);
								foreach ($pks as $pk) {
									if (isset($fields_values[$pk]))
									$where .= $pk.'='.$this->fullQuoteStr($fields_values[$pk], $table).' AND ';
								}
								$where = $this->quoteWhereClause($where.'1=1');
								$this->handlerInstance[$this->lastHandlerKey]->UpdateClob($this->quoteFromTables($table),$field,$content,$where);
							} else {
								$this->handlerInstance[$this->lastHandlerKey]->CompleteTrans(FALSE);
								die('Could not update CLOB >>>> no WHERE clause found!'); // should never ever happen
							}
						}
					}
					$this->handlerInstance[$this->lastHandlerKey]->CompleteTrans();
				}
				break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_INSERTquery($table,$fields_values,$no_quote_fields);
				break;
		}

		if ($this->printErrors && $this->sql_error()) {
			debug(array($this->lastQuery, $this->sql_error()));
		}

		if ($this->debug) {
			$this->debugHandler(
				'exec_INSERTquery',
				t3lib_div::milliseconds()-$pt,
				array(
					'handlerType' => $hType,
					'args' => array($table,$fields_values),
					'ORIG_tablename' => $ORIG_tableName
				)
			);
		}
			// Return output:
		return $sqlResult;
	}

	/**
	 * Creates and executes an INSERT SQL-statement for $table with multiple rows.
	 * This method uses exec_INSERTquery() and is just a syntax wrapper to it.
	 *
	 * @param	string		Table name
	 * @param	array		Field names
	 * @param	array		Table rows. Each row should be an array with field values mapping to $fields
	 * @param	string/array		See fullQuoteArray()
	 * @return	mixed		Result from last handler, usually TRUE when success and FALSE on failure
	 */
	public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		if ((string)$this->handlerCfg[$this->lastHandlerKey]['type'] === 'native') {
			return parent::exec_INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
		}

		foreach ($rows as $row) {
			$fields_values = array();
			foreach ($fields as $key => $value) {
				$fields_values[$value] = $row[$key];
			}
			$res = $this->exec_INSERTquery($table, $fields_values, $no_quote_fields);
		}

		return $res;
	}

	/**
	 * Updates a record from $table
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param mixed    List/array of keys NOT to quote (eg. SQL functions)
	 * @return	mixed		Result from handler, usually TRUE when success and FALSE on failure
	 */
	public function exec_UPDATEquery($table,$where,$fields_values,$no_quote_fields = '') {
		if ($this->debug) {
			$pt = t3lib_div::milliseconds();
		}

			// Do table/field mapping:
		$ORIG_tableName = $table;
		if ($tableArray = $this->map_needMapping($table)) {

				// Field mapping of array:
			$fields_values = $this->map_assocArray($fields_values,$tableArray);

				// Where clause table and field mapping:
			$whereParts = $this->SQLparser->parseWhereClause($where);
			$this->map_sqlParts($whereParts,$tableArray[0]['table']);
			$where = $this->SQLparser->compileWhereClause($whereParts, FALSE);

				// Table name:
			if ($this->mapping[$table]['mapTableName']) {
				$table = $this->mapping[$table]['mapTableName'];
			}
		}

			// Select API
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$this->lastQuery = $this->UPDATEquery($table,$where,$fields_values,$no_quote_fields);
				if (is_string($this->lastQuery)) {
					$sqlResult = mysql_query($this->lastQuery, $this->handlerInstance[$this->lastHandlerKey]['link']);
				}
				else {
					$sqlResult = mysql_query($this->lastQuery[0], $this->handlerInstance[$this->lastHandlerKey]['link']);
					foreach ($this->lastQuery[1] as $field => $content) {
						mysql_query('UPDATE '.$this->quoteFromTables($table).' SET '.$this->quoteFromTables($field).'='.$this->fullQuoteStr($content,$table).' WHERE '.$this->quoteWhereClause($where), $this->handlerInstance[$this->lastHandlerKey]['link']);
					}
				}
			break;
			case 'adodb':
				$this->lastQuery = $this->UPDATEquery($table,$where,$fields_values,$no_quote_fields);
				if (is_string($this->lastQuery)) {
					$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->_query($this->lastQuery,FALSE);
				} else {
					$this->handlerInstance[$this->lastHandlerKey]->StartTrans();
					if (strlen($this->lastQuery[0])) {
						$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->_query($this->lastQuery[0],FALSE);
					}
					if (is_array($this->lastQuery[1])) {
						foreach ($this->lastQuery[1] as $field => $content) {
							$this->handlerInstance[$this->lastHandlerKey]->UpdateBlob($this->quoteFromTables($table),$field,$content,$this->quoteWhereClause($where));
						}
					}
					if (is_array($this->lastQuery[2])) {
						foreach ($this->lastQuery[2] as $field => $content) {
							$this->handlerInstance[$this->lastHandlerKey]->UpdateClob($this->quoteFromTables($table),$field,$content,$this->quoteWhereClause($where));
						}
					}
					$this->handlerInstance[$this->lastHandlerKey]->CompleteTrans();
				}
				break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_UPDATEquery($table,$where,$fields_values,$no_quote_fields);
				break;
		}

		if ($this->printErrors && $this->sql_error()) {
			debug(array($this->lastQuery, $this->sql_error()));
		}

		if ($this->debug) {
			$this->debugHandler(
				'exec_UPDATEquery',
				t3lib_div::milliseconds()-$pt,
				array(
					'handlerType' => $hType,
					'args' => array($table,$where, $fields_values),
					'ORIG_from_table' => $ORIG_tableName
				)
			);
		}

			// Return result:
		return $sqlResult;
	}

	/**
	 * Deletes records from table
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @return	mixed		Result from handler
	 */
	public function exec_DELETEquery($table, $where) {
		if ($this->debug) {
			$pt = t3lib_div::milliseconds();
		}

			// Do table/field mapping:
		$ORIG_tableName = $table;
		if ($tableArray = $this->map_needMapping($table)) {

				// Where clause:
			$whereParts = $this->SQLparser->parseWhereClause($where);
			$this->map_sqlParts($whereParts,$tableArray[0]['table']);
			$where = $this->SQLparser->compileWhereClause($whereParts, FALSE);

				// Table name:
			if ($this->mapping[$table]['mapTableName']) {
				$table = $this->mapping[$table]['mapTableName'];
			}
		}

			// Select API
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$this->lastQuery = $this->DELETEquery($table,$where);
				$sqlResult = mysql_query($this->lastQuery, $this->handlerInstance[$this->lastHandlerKey]['link']);
				break;
			case 'adodb':
				$this->lastQuery = $this->DELETEquery($table,$where);
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->_query($this->lastQuery,FALSE);
				break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_DELETEquery($table,$where);
				break;
		}

		if ($this->printErrors && $this->sql_error()) {
			debug(array($this->lastQuery, $this->sql_error()));
		}

		if ($this->debug) {
			$this->debugHandler(
				'exec_DELETEquery',
				t3lib_div::milliseconds()-$pt,
				array(
					'handlerType' => $hType,
					'args' => array($table,$where),
					'ORIG_from_table' => $ORIG_tableName
				)
			);
		}

			// Return result:
		return $sqlResult;
	}

	/**
	 * Selects records from Data Source
	 *
	 * @param	string $select_fields List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string $from_table Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string $where_clause Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQquoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string $groupBy Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string $orderBy Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string $limit Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	mixed		Result from handler. Typically object from DBAL layers.
	 */
	public function exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
		if ($this->debug) {
			$pt = t3lib_div::milliseconds();
		}

			// Map table / field names if needed:
		$ORIG_tableName = $from_table;	// Saving table names in $ORIG_from_table since $from_table is transformed beneath:
		if ($tableArray = $this->map_needMapping($ORIG_tableName)) {
			$this->map_remapSELECTQueryParts($select_fields,$from_table,$where_clause,$groupBy,$orderBy);	// Variables passed by reference!
		}

			// Get handler key and select API:
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		$hType = (string)$this->handlerCfg[$this->lastHandlerKey]['type'];
		switch ($hType) {
			case 'native':
				$this->lastQuery = $this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
				$sqlResult = mysql_query($this->lastQuery, $this->handlerInstance[$this->lastHandlerKey]['link']);
				$this->resourceIdToTableNameMap[(string)$sqlResult] = $ORIG_tableName;
				break;
			case 'adodb':
				if ($limit != '') {
					$splitLimit = t3lib_div::intExplode(',', $limit);		// Splitting the limit values:
					if ($splitLimit[1]) {	// If there are two parameters, do mapping differently than otherwise:
						$numrows = $splitLimit[1];
						$offset = $splitLimit[0];
					} else {
						$numrows = $splitLimit[0];
						$offset = 0;
					}

					$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->SelectLimit($this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy), $numrows, $offset);
					$this->lastQuery = $sqlResult->sql;
				} else {
					$this->lastQuery = $this->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy);
					$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->_Execute($this->lastQuery);
				}

				$sqlResult->TYPO3_DBAL_handlerType = 'adodb';	// Setting handler type in result object (for later recognition!)
				$sqlResult->TYPO3_DBAL_tableList = $ORIG_tableName;
				break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
				if (is_object($sqlResult)) {
					$sqlResult->TYPO3_DBAL_handlerType = 'userdefined';	// Setting handler type in result object (for later recognition!)
					$sqlResult->TYPO3_DBAL_tableList = $ORIG_tableName;
				}
				break;
		}

		if ($this->printErrors && $this->sql_error()) {
			debug(array($this->lastQuery, $this->sql_error()));
		}

		if ($this->debug) {
			$this->debugHandler(
				'exec_SELECTquery',
				t3lib_div::milliseconds()-$pt,
				array(
					'handlerType' => $hType,
					'args' => array($from_table,$select_fields,$where_clause,$groupBy,$orderBy,$limit),
					'ORIG_from_table' => $ORIG_tableName
				)
			);
		}

			// Return result handler.
		return $sqlResult;
	}

	/**
	 * Truncates a table.
	 * 
	 * @param	string		Database tablename
	 * @return	mixed		Result from handler
	 */
	public function exec_TRUNCATEquery($table) {
		if ($this->debug) {
			$pt = t3lib_div::milliseconds();
		}

			// Do table/field mapping:
		$ORIG_tableName = $table;
		if ($tableArray = $this->map_needMapping($table)) {
				// Table name:
			if ($this->mapping[$table]['mapTableName']) {
				$table = $this->mapping[$table]['mapTableName'];
			}
		}

			// Select API
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$this->lastQuery = $this->TRUNCATEquery($table);
				$sqlResult = mysql_query($this->lastQuery, $this->handlerInstance[$this->lastHandlerKey]['link']);
				break;
			case 'adodb':
				$this->lastQuery = $this->TRUNCATEquery($table);
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->_query($this->lastQuery, FALSE);
				break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance[$this->lastHandlerKey]->exec_TRUNCATEquery($table,$where);
				break;
		}

		if ($this->printErrors && $this->sql_error()) {
			debug(array($this->lastQuery, $this->sql_error()));
		}

		if ($this->debug) {
			$this->debugHandler(
				'exec_TRUNCATEquery',
				t3lib_div::milliseconds() - $pt,
				array(
					'handlerType' => $hType,
					'args' => array($table),
					'ORIG_from_table' => $ORIG_tableName
				)
			);
		}

			// Return result:
		return $sqlResult;
	}

	/**
	 * Executes a query.
	 * EXPERIMENTAL since TYPO3 4.4.
	 * 
	 * @param array $queryParts SQL parsed by method parseSQL() of t3lib_sqlparser
	 * @return pointer Result pointer / DBAL object
	 * @see ux_t3lib_db::sql_query()
	 */
	protected function exec_query(array $queryParts) {
		switch ($queryParts['type']) {
			case 'SELECT':
				$selectFields = $this->SQLparser->compileFieldList($queryParts['SELECT']);
				$fromTables = $this->SQLparser->compileFromTables($queryParts['FROM']);
				$whereClause = isset($queryParts['WHERE']) ? $this->SQLparser->compileWhereClause($queryParts['WHERE']) : '1=1';
				$groupBy = isset($queryParts['GROUPBY']) ? $this->SQLparser->compileWhereClause($queryParts['GROUPBY']) : '';
				$orderBy = isset($queryParts['GROUPBY']) ? $this->SQLparser->compileWhereClause($queryParts['ORDERBY']) : '';
				$limit = isset($queryParts['LIMIT']) ? $this->SQLparser->compileWhereClause($queryParts['LIMIT']) : '';
				return $this->exec_SELECTquery($selectFields, $fromTables, $whereClause, $groupBy, $orderBy, $limit);

			case 'UPDATE':
				$table = $queryParts['TABLE'];
				$fields = array();
				foreach ($components['FIELDS'] as $fN => $fV) {
					$fields[$fN] = $fV[0];
				}
				$whereClause = isset($queryParts['WHERE']) ? $this->SQLparser->compileWhereClause($queryParts['WHERE']) : '1=1';
				return $this->exec_UPDATEquery($table, $whereClause, $fields);

			case 'INSERT':
				$table = $queryParts['TABLE'];
				$values = array();
				if (isset($queryParts['VALUES_ONLY']) && is_array($queryParts['VALUES_ONLY'])) {
					$fields = $GLOBALS['TYPO3_DB']->cache_fieldType[$table];
					$fc = 0;
					foreach ($fields as $fn => $fd) {
						$values[$fn] = $queryParts['VALUES_ONLY'][$fc++][0];
					}
				} else {
					foreach ($queryParts['FIELDS'] as $fN => $fV) {
						$values[$fN] = $fV[0];
					}
				}
				return $this->exec_INSERTquery($table, $values);
				
			case 'DELETE':
				$table = $queryParts['TABLE'];
				$whereClause = isset($queryParts['WHERE']) ? $this->SQLparser->compileWhereClause($queryParts['WHERE']) : '1=1';
				return $this->exec_DELETEquery($table, $whereClause);

			case 'TRUNCATETABLE':
				$table = $queryParts['TABLE'];
				return $this->exec_TRUNCATEquery($table);
		}
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
	 * @param mixed		See exec_INSERTquery()
	 * @return	mixed		Full SQL query for INSERT as string or array (unless $fields_values does not contain any elements in which case it will be FALSE). If BLOB fields will be affected and one is not running the native type, an array will be returned, where 0 => plain SQL, 1 => fieldname/value pairs of BLOB fields
	 */
	public function INSERTquery($table, $fields_values, $no_quote_fields = '') {
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function (contrary to values in the arrays which may be insecure).
		if (is_array($fields_values) && count($fields_values)) {

			if (is_string($no_quote_fields)) {
				$no_quote_fields = explode(',', $no_quote_fields);
			} elseif (!is_array($no_quote_fields)) {
				$no_quote_fields = array();
			}

			$blobfields = array();
			$nArr = array();
			foreach ($fields_values as $k => $v) {
				if (!$this->runningNative() && $this->sql_field_metatype($table, $k) == 'B') {
						// we skip the field in the regular INSERT statement, it is only in blobfields
					$blobfields[$this->quoteFieldNames($k)] = $v;
				} elseif (!$this->runningNative() && $this->sql_field_metatype($table, $k) == 'XL') {
						// we skip the field in the regular INSERT statement, it is only in clobfields
					$clobfields[$this->quoteFieldNames($k)] = $v;
				} else {
						// Add slashes old-school:
						// cast numerical values
					$mt = $this->sql_field_metatype($table, $k);
					if ($mt{0} == 'I') {
						$v = (int)$v;
					} else if ($mt{0} == 'F') {
						$v = (double)$v;
					}

					$nArr[$this->quoteFieldNames($k)] = (!in_array($k,$no_quote_fields)) ? $this->fullQuoteStr($v, $table) : $v;
				}
			}

			if (count($blobfields) || count($clobfields)) {
				if (count($nArr)) {
					$query[0] = 'INSERT INTO ' . $this->quoteFromTables($table) . '
					(
						' . implode(',
						', array_keys($nArr)) . '
					) VALUES (
						' . implode(',
						', $nArr) . '
					)';
				}
				if (count($blobfields)) $query[1] = $blobfields;
				if (count($clobfields)) $query[2] = $clobfields;
				if ($this->debugOutput || $this->store_lastBuiltQuery) $this->debug_lastBuiltQuery = $query[0];
			} else {
				$query = 'INSERT INTO '.$this->quoteFromTables($table).'
				(
					' . implode(',
					', array_keys($nArr)) . '
				) VALUES (
					' . implode(',
					', $nArr) . '
				)';

				if ($this->debugOutput || $this->store_lastBuiltQuery) $this->debug_lastBuiltQuery = $query;
			}

			return $query;
		}
	}

	/**
	 * Creates an INSERT SQL-statement for $table with multiple rows.
	 * This method will create multiple INSERT queries concatenated with ';'
	 *
	 * @param	string		Table name
	 * @param	array		Field names
	 * @param	array		Table rows. Each row should be an array with field values mapping to $fields
	 * @param	string/array		See fullQuoteArray()
	 * @return	array		Full SQL query for INSERT as array of strings (unless $fields_values does not contain any elements in which case it will be FALSE). If BLOB fields will be affected and one is not running the native type, an array will be returned for each row, where 0 => plain SQL, 1 => fieldname/value pairs of BLOB fields.
	 */
	public function INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		if ((string)$this->handlerCfg[$this->lastHandlerKey]['type'] === 'native') {
			return parent::INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
		}

		$result = array();

		foreach ($rows as $row) {
			$fields_values = array();
			foreach ($fields as $key => $value) {
				$fields_values[$value] = $row[$key];
			}
			$rowQuery = $this->INSERTquery($table, $fields_values, $no_quote_fields);
			if (is_array($rowQuery)) {
				$result[] = $rowQuery;
			} else {
				$result[][0] = $rowQuery;
			}
		}

		return $result;
	}

	/**
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Usage count/core: 6
	 *
	 * @param	string		See exec_UPDATEquery()
	 * @param	string		See exec_UPDATEquery()
	 * @param	array		See exec_UPDATEquery()
	 * @param	mixed		See exec_UPDATEquery()
	 * @return	mixed		Full SQL query for UPDATE as string or array (unless $fields_values does not contain any elements in which case it will be FALSE). If BLOB fields will be affected and one is not running the native type, an array will be returned, where 0 => plain SQL, 1 => fieldname/value pairs of BLOB fields
	 */
	public function UPDATEquery($table, $where, $fields_values, $no_quote_fields = '') {
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this function (contrary to values in the arrays which may be insecure).
		if (is_string($where)) {
			$fields = array();
			$blobfields = array();
			$clobfields = array();
			if (is_array($fields_values) && count($fields_values)) {

				if (is_string($no_quote_fields)) {
					$no_quote_fields = explode(',', $no_quote_fields);
				} elseif (!is_array($no_quote_fields)) {
					$no_quote_fields = array();
				}

				$nArr = array();
				foreach ($fields_values as $k => $v) {
					if (!$this->runningNative() && $this->sql_field_metatype($table, $k) == 'B') {
							// we skip the field in the regular UPDATE statement, it is only in blobfields
						$blobfields[$this->quoteFieldNames($k)] = $v;
					} elseif (!$this->runningNative() && $this->sql_field_metatype($table, $k) == 'XL') {
								// we skip the field in the regular UPDATE statement, it is only in clobfields
							$clobfields[$this->quoteFieldNames($k)] = $v;
					} else {
							// Add slashes old-school:
							// cast numeric values
						$mt = $this->sql_field_metatype($table, $k);
						if ($mt{0} == 'I') {
							$v = (int)$v;
						} else if ($mt{0} == 'F') {
							$v = (double)$v;
						}
						$nArr[] = $this->quoteFieldNames($k) . '=' . ((!in_array($k, $no_quote_fields)) ? $this->fullQuoteStr($v, $table) : $v);
					}
				}
			}

			if (count($blobfields) || count($clobfields)) {
				if (count($nArr)) {
					$query[0] = 'UPDATE ' . $this->quoteFromTables($table) . '
						SET
							' . implode(',
							', $nArr) .
							(strlen($where) > 0 ? '
						WHERE
							' . $this->quoteWhereClause($where) : '');
				}
				if (count($blobfields)) {
					$query[1] = $blobfields;
				}
				if (count($clobfields)) {
					$query[2] = $clobfields;
				}
				if ($this->debugOutput || $this->store_lastBuiltQuery) {
					$this->debug_lastBuiltQuery = $query[0];
				}
			} else {
				$query = 'UPDATE ' . $this->quoteFromTables($table) . '
					SET
						' . implode(',
						', $nArr) .
						(strlen($where) > 0 ? '
					WHERE
						' . $this->quoteWhereClause($where) : '');

				if ($this->debugOutput || $this->store_lastBuiltQuery) {
					$this->debug_lastBuiltQuery = $query;
				}
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
	public function DELETEquery($table, $where) {
		if (is_string($where)) {
			$table = $this->quoteFromTables($table);
			$where = $this->quoteWhereClause($where);

			$query = parent::DELETEquery($table, $where);

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
	 */
	public function SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy = '', $limit = '') {
		$this->lastHandlerKey = $this->handler_getFromTableList($from_table);
		$hType = (string)$this->handlerCfg[$this->lastHandlerKey]['type'];
		if ($hType === 'adodb' && $this->runningADOdbDriver('postgres')) {
				// Possibly rewrite the LIMIT to be PostgreSQL-compatible
			$splitLimit = t3lib_div::intExplode(',', $limit);		// Splitting the limit values:
			if ($splitLimit[1]) {	// If there are two parameters, do mapping differently than otherwise:
				$numrows = $splitLimit[1];
				$offset = $splitLimit[0];
				$limit = $numrows . ' OFFSET ' . $offset;
			}
		}
		
		$select_fields = $this->quoteFieldNames($select_fields);
		$from_table = $this->quoteFromTables($from_table);
		$where_clause = $this->quoteWhereClause($where_clause);
		$groupBy = $this->quoteGroupBy($groupBy);
		$orderBy = $this->quoteOrderBy($orderBy);

			// Call parent method to build actual query
		$query = parent::SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);

		if ($this->debugOutput || $this->store_lastBuiltQuery) $this->debug_lastBuiltQuery = $query;

		return $query;
	}

	/**
	 * Creates a TRUNCATE TABLE SQL-statement
	 * 
	 * @param	string		See exec_TRUNCATEquery()
	 * @return	string		Full SQL query for TRUNCATE TABLE
	 */
	public function TRUNCATEquery($table) {
		$table = $this->quoteFromTables($table);

			// Call parent method to build actual query
		$query = parent::TRUNCATEquery($table);

		if ($this->debugOutput || $this->store_lastBuiltQuery) {
			$this->debug_lastBuiltQuery = $query;
		}

		return $query;
	}


	/**************************************
	*
	* Functions for quoting table/field names
	*
	**************************************/

	/**
	 * Quotes components of a SELECT subquery.
	 * 
	 * @param array $components	Array of SQL query components
	 * @return array
	 */
	protected function quoteSELECTsubquery(array $components) {
		$components['SELECT'] = $this->_quoteFieldNames($components['SELECT']);
		$components['FROM'] = $this->_quoteFromTables($components['FROM']);
		$components['WHERE'] = $this->_quoteWhereClause($components['WHERE']);
		return $components;
	}

	/**
	 * Quotes field (and table) names with the quote character suitable for the DB being used
	 * Use quoteFieldNames instead!
	 *
	 * @param	string		List of fields to be selected from DB
	 * @return	string		Quoted list of fields to be selected from DB
	 * @deprecated since TYPO3 4.0
	 */
	public function quoteSelectFields($select_fields) {
		$this->quoteFieldNames($select_fields);
	}

	/**
	 * Quotes field (and table) names with the quote character suitable for the DB being used
	 *
	 * @param	string		List of fields to be used in query to DB
	 * @return	string		Quoted list of fields to be in query to DB
	 */
	public function quoteFieldNames($select_fields) {
		if ($select_fields == '') return '';
		if ($this->runningNative()) return $select_fields;

		$select_fields = $this->SQLparser->parseFieldList($select_fields);
		if ($this->SQLparser->parse_error) {
			die($this->SQLparser->parse_error . ' in ' . __FILE__ . ' : ' . __LINE__);
		}
		$select_fields = $this->_quoteFieldNames($select_fields);

		return $this->SQLparser->compileFieldList($select_fields);
	}

	/**
	 * Quotes field (and table) names in a SQL SELECT clause acccording to DB rules
	 *
	 * @param array $select_fields The parsed fields to quote
	 * @return array
	 * @see quoteFieldNames()
	 */
	protected function _quoteFieldNames(array $select_fields) {
		foreach ($select_fields as $k => $v) {
			if ($select_fields[$k]['field'] != '' && $select_fields[$k]['field'] != '*' && !is_numeric($select_fields[$k]['field'])) {
				$select_fields[$k]['field'] = $this->quoteName($select_fields[$k]['field']);
			}
			if ($select_fields[$k]['table'] != '' && !is_numeric($select_fields[$k]['table'])) {
				$select_fields[$k]['table'] = $this->quoteName($select_fields[$k]['table']);
			}
			if ($select_fields[$k]['as'] != '') {
				$select_fields[$k]['as'] = $this->quoteName($select_fields[$k]['as']);
			}
			if (isset($select_fields[$k]['func_content.']) && $select_fields[$k]['func_content.'][0]['func_content'] != '*'){
				$select_fields[$k]['func_content.'][0]['func_content'] = $this->quoteFieldNames($select_fields[$k]['func_content.'][0]['func_content']);
				$select_fields[$k]['func_content'] = $this->quoteFieldNames($select_fields[$k]['func_content']);
			}
			if (isset($select_fields[$k]['flow-control'])) {
					// Quoting flow-control statements
				if ($select_fields[$k]['flow-control']['type'] === 'CASE') {
					if (isset($select_fields[$k]['flow-control']['case_field'])) {
						$select_fields[$k]['flow-control']['case_field'] = $this->quoteFieldNames($select_fields[$k]['flow-control']['case_field']);
					}
					foreach ($select_fields[$k]['flow-control']['when'] as $key => $when) {
						$select_fields[$k]['flow-control']['when'][$key]['when_value'] = $this->_quoteWhereClause($when['when_value']);
					} 
				}
			}
		}

		return $select_fields;
	}

	/**
	 * Quotes table names with the quote character suitable for the DB being used
	 *
	 * @param	string		List of tables to be selected from DB
	 * @return	string		Quoted list of tables to be selected from DB
	 */
	public function quoteFromTables($from_table) {
		if ($from_table == '') return '';
		if ($this->runningNative()) return $from_table;

		$from_table = $this->SQLparser->parseFromTables($from_table);
		$from_table = $this->_quoteFromTables($from_table);
		return $this->SQLparser->compileFromTables($from_table);
	}

	/**
	 * Quotes table names in a SQL FROM clause acccording to DB rules
	 *
	 * @param array $from_table The parsed FROM clause to quote
	 * @return array
	 * @see quoteFromTables()
	 */
	protected function _quoteFromTables(array $from_table) {
		foreach ($from_table as $k => $v) {
			$from_table[$k]['table'] = $this->quoteName($from_table[$k]['table']);
			if ($from_table[$k]['as'] != '') {
				$from_table[$k]['as'] = $this->quoteName($from_table[$k]['as']);
			}
			if (is_array($v['JOIN'])) {
				foreach ($v['JOIN'] as $joinCnt => $join) {
					$from_table[$k]['JOIN'][$joinCnt]['withTable'] = $this->quoteName($join['withTable']);
					$from_table[$k]['JOIN'][$joinCnt]['as'] = ($join['as']) ? $this->quoteName($join['as']) : '';
					foreach ($from_table[$k]['JOIN'][$joinCnt]['ON'] as &$condition) {
						$condition['left']['table'] = ($condition['left']['table']) ? $this->quoteName($condition['left']['table']) : '';
						$condition['left']['field'] = $this->quoteName($condition['left']['field']);
						$condition['right']['table'] = ($condition['right']['table']) ? $this->quoteName($condition['right']['table']) : '';
						$condition['right']['field'] = $this->quoteName($condition['right']['field']);
					}
				}
			}
		}

		return $from_table;
	}

	/**
	 * Quotes the field (and table) names within a where clause with the quote character suitable for the DB being used
	 *
	 * @param	string		A where clause that can e parsed by parseWhereClause
	 * @return	string		Usable where clause with quoted field/table names
	 */
	public function quoteWhereClause($where_clause) {
		if ($where_clause === '' || $this->runningNative()) return $where_clause;

		$where_clause = $this->SQLparser->parseWhereClause($where_clause);
		if (is_array($where_clause)) {
			$where_clause = $this->_quoteWhereClause($where_clause);
			$where_clause = $this->SQLparser->compileWhereClause($where_clause);
		} else {
			die('Could not parse where clause in ' . __FILE__ . ' : ' . __LINE__);
		}

		return $where_clause;
	}

	/**
	 * Quotes field names in a SQL WHERE clause acccording to DB rules
	 *
	 * @param	array		$where_clause The parsed WHERE clause to quote
	 * @return	array
	 * @see quoteWhereClause()
	 */
	protected function _quoteWhereClause(array $where_clause) {
		foreach ($where_clause as $k => $v) {
				// Look for sublevel:
			if (is_array($where_clause[$k]['sub'])) {
				$where_clause[$k]['sub'] = $this->_quoteWhereClause($where_clause[$k]['sub']);
			} elseif (isset($v['func'])) {
				switch ($where_clause[$k]['func']['type']) {
					case 'EXISTS':
						$where_clause[$k]['func']['subquery'] = $this->quoteSELECTsubquery($v['func']['subquery']);
						break;
					case 'IFNULL':
					case 'LOCATE':
						if ($where_clause[$k]['func']['table'] != '') {
							$where_clause[$k]['func']['table'] = $this->quoteName($v['func']['table']);
						}
						if ($where_clause[$k]['func']['field'] != '') {
							$where_clause[$k]['func']['field'] = $this->quoteName($v['func']['field']);
						}
					break;
				}
			} else {
				if ($where_clause[$k]['table'] != '') {
					$where_clause[$k]['table'] = $this->quoteName($where_clause[$k]['table']);
				}
				if (!is_numeric($where_clause[$k]['field'])) {
					$where_clause[$k]['field'] = $this->quoteName($where_clause[$k]['field']);
				}
				if (isset($where_clause[$k]['calc_table'])) {
					if ($where_clause[$k]['calc_table'] != '') {
						$where_clause[$k]['calc_table'] = $this->quoteName($where_clause[$k]['calc_table']);
					}
					if ($where_clause[$k]['calc_field'] != '') {
						$where_clause[$k]['calc_field'] = $this->quoteName($where_clause[$k]['calc_field']);
					}
				}
			}
			if ($where_clause[$k]['comparator']) {
				if (isset($v['value']['operator'])) {
					foreach ($where_clause[$k]['value']['args'] as $argK => $fieldDef) {
						$where_clause[$k]['value']['args'][$argK]['table'] = $this->quoteName($fieldDef['table']);
						$where_clause[$k]['value']['args'][$argK]['field'] = $this->quoteName($fieldDef['field']);
					}
				} else {
						// Detecting value type; list or plain:
					if (t3lib_div::inList('NOTIN,IN', strtoupper(str_replace(array(' ',"\n", "\r", "\t"), '', $where_clause[$k]['comparator'])))) {
						if (isset($v['subquery'])) {
							$where_clause[$k]['subquery'] = $this->quoteSELECTsubquery($v['subquery']);
						}
					} else {
						if ((!isset($where_clause[$k]['value'][1]) || $where_clause[$k]['value'][1] == '') && is_string($where_clause[$k]['value'][0]) && strstr($where_clause[$k]['value'][0], '.')) {
							$where_clause[$k]['value'][0] = $this->quoteFieldNames($where_clause[$k]['value'][0]);
						}
					}
				}
			}
		}

		return $where_clause;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$groupBy: ...
	 * @return	[type]		...
	 */
	protected function quoteGroupBy($groupBy) {
		if ($groupBy === '') return '';
		if ($this->runningNative()) return $groupBy;

		$groupBy = $this->SQLparser->parseFieldList($groupBy);
		foreach ($groupBy as $k => $v) {
			$groupBy[$k]['field'] = $this->quoteName($groupBy[$k]['field']);
			if ($groupBy[$k]['table'] != '') {
				$groupBy[$k]['table'] = $this->quoteName($groupBy[$k]['table']);
			}
		}
		return $this->SQLparser->compileFieldList($groupBy);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$orderBy: ...
	 * @return	[type]		...
	 */
	protected function quoteOrderBy($orderBy) {
		if ($orderBy === '') return '';
		if ($this->runningNative()) return $orderBy;

		$orderBy = $this->SQLparser->parseFieldList($orderBy);
		foreach ($orderBy as $k => $v) {
			$orderBy[$k]['field'] = $this->quoteName($orderBy[$k]['field']);
			if ($orderBy[$k]['table'] != '') {
				$orderBy[$k]['table'] = $this->quoteName($orderBy[$k]['table']);
			}
		}
		return $this->SQLparser->compileFieldList($orderBy);
	}



	/**************************************
	*
	* Various helper functions
	*
	**************************************/

	/**
	 * Escaping and quoting values for SQL statements.
	 *
	 * @param	string		Input string
	 * @param	string		Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return	string		Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function fullQuoteStr($str, $table) {
		return '\'' . $this->quoteStr($str, $table) . '\'';
	}

	/**
	 * Substitution for PHP function "addslashes()"
	 * NOTICE: You must wrap the output of this function in SINGLE QUOTES to be DBAL compatible. Unless you have to apply the single quotes yourself you should rather use ->fullQuoteStr()!
	 *
	 * @param	string		Input string
	 * @param	string		Table name for which to quote string. Just enter the table that the field-value is selected from (and any DBAL will look up which handler to use and then how to quote the string!).
	 * @return	string		Output string; Quotes (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @see quoteStr()
	 */
	public function quoteStr($str, $table) {
		$this->lastHandlerKey = $this->handler_getFromTableList($table);
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				if ($this->handlerInstance[$this->lastHandlerKey]['link']) {
					$str = mysql_real_escape_string($str, $this->handlerInstance[$this->lastHandlerKey]['link']);
				} else {
						// link may be null when unit testing DBAL
					$str = str_replace('\'', '\\\'', $str);
				}
				break;
			case 'adodb':
				$str = substr($this->handlerInstance[$this->lastHandlerKey]->qstr($str), 1, -1);
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

	/**
	 * Quotes an object name (table name, field, ...)
	 *
	 * @param	string		Object's name
	 * @param	string		Handler key
	 * @param	boolean		If method NameQuote() is not used, whether to use backticks instead of driver-specific quotes
	 * @return	string		Properly-quoted object's name
	 */
	public function quoteName($name, $handlerKey = NULL, $useBackticks = FALSE) {
		$handlerKey = $handlerKey ? $handlerKey : $this->lastHandlerKey;
		$useNameQuote = isset($this->handlerCfg[$handlerKey]['config']['useNameQuote']) ? $this->handlerCfg[$handlerKey]['config']['useNameQuote'] : FALSE;
		if ($useNameQuote) {
			return $this->handlerInstance[$handlerKey]->DataDictionary->NameQuote($name);
		} else {
			$quote = $useBackticks ? '`' : $this->handlerInstance[$handlerKey]->nameQuote;
			return $quote . $name . $quote;
		}
	}

	/**
	 * Return MetaType for native field type (ADOdb only!)
	 *
	 * @param	string		native type as reported by admin_get_fields()
	 * @param	string		Table name for which query type string. Important for detection of DBMS handler of the query!
	 * @return	string		Meta type (currenly ADOdb syntax only, http://phplens.com/lens/adodb/docs-adodb.htm#metatype)
	 */
	public function MetaType($type, $table, $max_length = -1) {
		$this->lastHandlerKey = $this->handler_getFromTableList($table);
		$str = '';
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$str = $type;
				break;
			case 'adodb':
				if (in_array($table, $this->cache_fieldType)) {
					$rs = $this->handlerInstance[$this->lastHandlerKey]->SelectLimit('SELECT * FROM ' . $this->quoteFromTables($table), 1);
					$str = $rs->MetaType($type, $max_length);
				}
				break;
			case 'userdefined':
				$str = $this->handlerInstance[$this->lastHandlerKey]->MetaType($str,$table,$max_length);
				break;
			default:
				die('No handler found!!!');
				break;
		}

		return $str;
	}


	/**
	 * Return MetaType for native MySQL field type
	 *
	 * @param	string		native type as reported as in mysqldump files
	 * @return	string		Meta type (currenly ADOdb syntax only, http://phplens.com/lens/adodb/docs-adodb.htm#metatype)
	 */
	public function MySQLMetaType($t) {

		switch (strtoupper($t)) {
			case 'STRING':
			case 'CHAR':
			case 'VARCHAR':
			case 'TINYBLOB':
			case 'TINYTEXT':
			case 'ENUM':
			case 'SET': return 'C';

			case 'TEXT':
			case 'LONGTEXT':
			case 'MEDIUMTEXT': return 'XL';

			case 'IMAGE':
			case 'LONGBLOB':
			case 'BLOB':
			case 'MEDIUMBLOB': return 'B';

			case 'YEAR':
			case 'DATE': return 'D';

			case 'TIME':
			case 'DATETIME':
			case 'TIMESTAMP': return 'T';

			case 'FLOAT':
			case 'DOUBLE': return 'F';

			case 'INT':
			case 'INTEGER':
			case 'TINYINT':
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'BIGINT': return 'I8'; // we always return I8 to be on the safe side. Under some circumstances the fields are to small otherwise...

			default: return 'N';
		}
	}

	/**
	 * Return actual MySQL type for meta field type
	 *
	 * @param	string		Meta type (currenly ADOdb syntax only, http://phplens.com/lens/adodb/docs-adodb.htm#metatype)
	 * @return	string		native type as reported as in mysqldump files, uppercase
	 */
	public function MySQLActualType($meta) {
		switch (strtoupper($meta)) {
			case 'C': return 'VARCHAR';
			case 'XL':
			case 'X': return 'LONGTEXT';

			case 'C2': return 'VARCHAR';
			case 'X2': return 'LONGTEXT';

			case 'B': return 'LONGBLOB';

			case 'D': return 'DATE';
			case 'T': return 'DATETIME';
			case 'L': return 'TINYINT';

			case 'I':
			case 'I1':
			case 'I2':
			case 'I4':
			case 'I8': return 'BIGINT'; // we only have I8 in DBAL, see MySQLMetaType()

			case 'F': return 'DOUBLE';
			case 'N': return 'NUMERIC';

			default: return $meta;
		}
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
	public function sql_error() {
		switch ($this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$output = mysql_error($this->handlerInstance[$this->lastHandlerKey]['link']);
				break;
			case 'adodb':
				$output = $this->handlerInstance[$this->lastHandlerKey]->ErrorMsg();
				break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->sql_error();
				break;
		}
		return $output;
	}

	/**
	 * Returns the error number on the most recent sql() execution (based on $this->lastHandlerKey)
	 *
	 * @return	int		Handler error number
	 */
	public function sql_errno() {
		switch ($this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$output = mysql_errno($this->handlerInstance[$this->lastHandlerKey]['link']);
				break;
			case 'adodb':
				$output = $this->handlerInstance[$this->lastHandlerKey]->ErrorNo();
				break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->sql_errno();
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
	public function sql_num_rows(&$res) {
		if ($res === FALSE) return 0;

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType : 'native';
		switch ($handlerType) {
			case 'native':
				$output = mysql_num_rows($res);
				break;
			case 'adodb':
				$output = method_exists($res, 'RecordCount') ? $res->RecordCount() : 0;
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
	public function sql_fetch_assoc(&$res) {
		$output = array();

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType : (is_resource($res) ? 'native' : FALSE);
		switch ($handlerType) {
			case 'native':
				$output = mysql_fetch_assoc($res);
				$tableList = $this->resourceIdToTableNameMap[(string)$res];	// Reading list of tables from SELECT query:
				break;
			case 'adodb':
					// Check if method exists for the current $res object.
					// If a table exists in TCA but not in the db, a error
					// occured because $res is not a valid object.
				if (method_exists($res, 'FetchRow')) {
					$output = $res->FetchRow();
					$tableList = $res->TYPO3_DBAL_tableList;	// Reading list of tables from SELECT query:

						// Removing all numeric/integer keys.
						// A workaround because in ADOdb we would need to know what we want before executing the query...
						// MSSQL does not support ADODB_FETCH_BOTH and always returns an assoc. array instead. So
						// we don't need to remove anything.
					if (is_array($output)) {
						if ($this->runningADOdbDriver('mssql')) {
								// MSSQL does not know such thing as an empty string. So it returns one space instead, which we must fix.
							foreach ($output as $key => $value) {
								if ($value === ' ') {
									$output[$key] = '';
								}
							}
						} else {
							foreach ($output as $key => $value) {
								if (is_integer($key)) {
									unset($output[$key]);
								}
							}
						}
					}
				}
				break;
			case 'userdefined':
				$output = $res->sql_fetch_assoc();
				$tableList = $res->TYPO3_DBAL_tableList;	// Reading list of tables from SELECT query:
				break;
		}

			// Table/Fieldname mapping:
		if (is_array($output)) {
			if ($tables = $this->map_needMapping($tableList,TRUE)) {
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
	public function sql_fetch_row(&$res) {
		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType : 'native';
		switch ($handlerType) {
			case 'native':
				$output = mysql_fetch_row($res);
				break;
			case 'adodb':
					// Check if method exists for the current $res object.
					// If a table exists in TCA but not in the db, a error
					// occured because $res is not a valid object.
				if (method_exists($res, 'FetchRow')) {
					$output = $res->FetchRow();

						// Removing all assoc. keys.
						// A workaround because in ADOdb we would need to know what we want before executing the query...
						// MSSQL does not support ADODB_FETCH_BOTH and always returns an assoc. array instead. So
						// we need to convert resultset.
					if (is_array($output)) {
						$keyIndex = 0;
						foreach ($output as $key => $value) {
							unset($output[$key]);
							if (is_integer($key) || $this->runningADOdbDriver('mssql')) {
								$output[$keyIndex] = $value;
								if ($value === ' ') {
										// MSSQL does not know such thing as an empty string. So it returns one space instead, which we must fix.
									$output[$keyIndex] = '';
								}
								$keyIndex++;
							}
						}
					}
				}
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
	public function sql_free_result(&$res) {
		if ($res === FALSE) return FALSE;

		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType : 'native';
		switch ($handlerType) {
			case 'native':
				$output = mysql_free_result($res);
				break;
			case 'adodb':
				if (method_exists($res, 'Close')) {
					$res->Close();
					unset($res);
					$output = TRUE;
				} else {
					$output = FALSE;
				}
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
	public function sql_insert_id() {
		switch ($this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$output = mysql_insert_id($this->handlerInstance[$this->lastHandlerKey]['link']);
				break;
			case 'adodb':
				$output = $this->handlerInstance[$this->lastHandlerKey]->last_insert_id;
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
	public function sql_affected_rows() {
		switch ($this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$output = mysql_affected_rows();
				break;
			case 'adodb':
				$output = $this->handlerInstance[$this->lastHandlerKey]->Affected_Rows();
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
	public function sql_data_seek(&$res, $seek) {
		$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType : 'native';
		switch ($handlerType) {
			case 'native':
				$output = mysql_data_seek($res,$seek);
				break;
			case 'adodb':
				$output = $res->Move($seek);
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
	 * If the first parameter is a string, it is used as table name for the lookup.
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object / table name
	 * @param	integer		Field index. In case of ADOdb a string (field name!) FIXME
	 * @return	string		Returns the type of the specified field index
	 */
	public function sql_field_metatype($table, $field) {
			// If $table and/or $field are mapped, use the original names instead
		foreach ($this->mapping as $tableName => $tableMapInfo) {
			if (isset($tableMapInfo['mapTableName']) && $tableMapInfo['mapTableName'] === $table) {
					// Table name is mapped => use original name
				$table = $tableName;
			}

			if (isset($tableMapInfo['mapFieldNames'])) {
				foreach ($tableMapInfo['mapFieldNames'] as $fieldName => $fieldMapInfo) {
					if ($fieldMapInfo === $field) {
							// Field name is mapped => use original name
						$field = $fieldName;
					}
				}
			}
		}

		return $this->cache_fieldType[$table][$field]['metaType'];
	}

	/**
	 * Get the type of the specified field in a result
	 *
	 * If the first parameter is a string, it is used as table name for the lookup.
	 *
	 * @param	pointer		MySQL result pointer (of SELECT query) / DBAL object / table name
	 * @param	integer		Field index. In case of ADOdb a string (field name!) FIXME
	 * @return	string		Returns the type of the specified field index
	 */
	public function sql_field_type(&$res,$pointer) {
		if ($res === null) {
			debug(array('no res in sql_field_type!'));
			return 'text';
		}
		elseif (is_string($res)){
			if ($res === 'tx_dbal_debuglog') return 'text';
			$handlerType = 'adodb';
		}
		else {
			$handlerType = is_object($res) ? $res->TYPO3_DBAL_handlerType :  'native';
		}

		switch ($handlerType) {
			case 'native':
				$output = mysql_field_type($res,$pointer);
				break;
			case 'adodb':
				if (is_string($pointer)){
					$output = $this->cache_fieldType[$res][$pointer]['type'];
				}

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
	* Deprecated or still experimental.
	*
	**********/

	/**
	 * Executes query (on DEFAULT handler!)
	 * DEPRECATED - use exec_* functions from this class instead!
	 *
	 * @param	string		Database name
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer
	 * @deprecated since TYPO3 4.1
	 */
	public function sql($db,$query) {
		return $this->sql_query($query);
	}

	/**
	 * Executes a query
	 * EXPERIMENTAL - This method will make its best to handle the query correctly
	 * but if it cannot, it will simply pass the query to DEFAULT handler.
	 *
	 * You should use exec_* function from this class instead!
	 * If you don't, anything that does not use the _DEFAULT handler will probably break!
	 * 
	 * This method was deprecated in TYPO3 4.1 but is considered experimental since TYPO3 4.4
	 * as it tries to handle the query correctly anyway.
	 *
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer / DBAL object
	 */
	public function sql_query($query) {
			// This method is heavily used by Extbase, try to handle it with DBAL-native methods
		$queryParts = $this->SQLparser->parseSQL($query);
		if (is_array($queryParts) && t3lib_div::inList('SELECT,UPDATE,INSERT,DELETE', $queryParts['type'])) {
			return $this->exec_query($queryParts);
		}

		switch ($this->handlerCfg['_DEFAULT']['type']) {
			case 'native':
				$sqlResult = mysql_query($query, $this->handlerInstance['_DEFAULT']['link']);
				break;
			case 'adodb':
				$sqlResult = $this->handlerInstance['_DEFAULT']->Execute($query);
				$sqlResult->TYPO3_DBAL_handlerType = 'adodb';
				break;
			case 'userdefined':
				$sqlResult = $this->handlerInstance['_DEFAULT']->sql_query($query);
				$sqlResult->TYPO3_DBAL_handlerType = 'userdefined';
				break;
		}

		if ($this->printErrors && $this->sql_error()) {
			debug(array($this->lastQuery, $this->sql_error()));
		}

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
	 * @deprecated since TYPO3 4.1
	 * @see handler_init()
	 */
	public function sql_pconnect($TYPO3_db_host, $TYPO3_db_username, $TYPO3_db_password) {
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
	 * @return	boolean		Always returns TRUE; function is obsolete, database selection is made in handler_init() function!
	 * @deprecated since TYPO3 4.1
	 */
	public function sql_select_db($TYPO3_db) {
		return TRUE;
	}















	/**************************************
	*
	* SQL admin functions
	* (For use in the Install Tool and Extension Manager)
	*
	**************************************/

	/**
	 * Listing databases from current MySQL connection. NOTICE: It WILL try to select those databases and thus break selection of current database.
	 * Use in Install Tool only!
	 * Usage count/core: 1
	 *
	 * @return	array		Each entry represents a database name
	 */
	public function admin_get_dbs() {
		$dbArr = array();
		switch ($this->handlerCfg['_DEFAULT']['type']) {
			case 'native':
				$db_list = mysql_list_dbs($this->link);
				while ($row = mysql_fetch_object($db_list)) {
					if ($this->sql_select_db($row->Database)) {
						$dbArr[] = $row->Database;
					}
				}
				break;
			case 'adodb':
					// check needed for install tool - otherwise it will just die because the call to
					// MetaDatabases is done on a stdClass instance
				if (method_exists($this->handlerInstance['_DEFAULT'],'MetaDatabases')) {
					$sqlDBs = $this->handlerInstance['_DEFAULT']->MetaDatabases();
					if (is_array($sqlDBs)) {
						foreach ($sqlDBs as $k => $theDB) {
							$dbArr[] = $theDB;
						}
					}
				}
				break;
			case 'userdefined':
				$dbArr = $this->handlerInstance['_DEFAULT']->admin_get_tables();
				break;
		}

		return $dbArr;
	}

	/**
	 * Returns the list of tables from the system (quering the DBMSs)
	 * It looks up all tables from the DBMS of the _DEFAULT handler and then add all tables *configured* to be managed by other handlers
	 *
	 * When fetching the tables, it skips tables whose names begin with BIN$, as this is taken as a table coming from the "Recycle Bin" on Oracle.
	 *
	 * @return	array		Tables in an array (tablename is in both key and value)
	 * @todo	Should the check for Oracle Recycle Bin stuff be moved elsewhere?
	 * @todo	Should return table details in value! see t3lib_db::admin_get_tables()
	 */
	public function admin_get_tables() {
		$whichTables = array();

			// Getting real list of tables:
		switch ($this->handlerCfg['_DEFAULT']['type']) {
			case 'native':
				$tables_result = mysql_query('SHOW TABLE STATUS FROM `' . TYPO3_db . '`', $this->handlerInstance['_DEFAULT']['link']);
				if (!$this->sql_error()) {
					while ($theTable = $this->sql_fetch_assoc($tables_result)) {
						$whichTables[current($theTable)] = current($theTable);
					}
				}
				break;
			case 'adodb':
					// check needed for install tool - otherwise it will just die because the call to
					// MetaTables is done on a stdClass instance
				if (method_exists($this->handlerInstance['_DEFAULT'], 'MetaTables')) {
					$sqlTables = $this->handlerInstance['_DEFAULT']->MetaTables('TABLES');
					while (list($k, $theTable) = each($sqlTables)) {
						if (preg_match('/BIN\$/', $theTable)) continue; // skip tables from the Oracle 10 Recycle Bin
						$whichTables[$theTable] = $theTable;
					}
				}
				break;
			case 'userdefined':
				$whichTables = $this->handlerInstance['_DEFAULT']->admin_get_tables();
				break;
		}

			// Check mapping:
		if (is_array($this->mapping) && count($this->mapping)) {

				// Mapping table names in reverse, first getting list of real table names:
			$tMap = array();
			foreach ($this->mapping as $tN => $tMapInfo) {
				if (isset($tMapInfo['mapTableName']))	$tMap[$tMapInfo['mapTableName']]=$tN;
			}

				// Do mapping:
			$newList=array();
			foreach ($whichTables as $tN) {
				if (isset($tMap[$tN]))	$tN = $tMap[$tN];
				$newList[$tN] = $tN;
			}

			$whichTables = $newList;
		}

			// Adding tables configured to reside in other DBMS (handler by other handlers than the default):
		if (is_array($this->table2handlerKeys)) {
			foreach ($this->table2handlerKeys as $key => $handlerKey) {
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
	public function admin_get_fields($tableName) {
		$output = array();

			// Do field mapping if needed:
		$ORIG_tableName = $tableName;
		if ($tableArray = $this->map_needMapping($tableName)) {

				// Table name:
			if ($this->mapping[$tableName]['mapTableName']) {
				$tableName = $this->mapping[$tableName]['mapTableName'];
			}
		}

			// Find columns
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$columns_res = mysql_query('SHOW columns FROM '.$tableName, $this->handlerInstance[$this->lastHandlerKey]['link']);
				while($fieldRow = mysql_fetch_assoc($columns_res)) {
					$output[$fieldRow['Field']] = $fieldRow;
				}
				break;
			case 'adodb':
				$fieldRows = $this->handlerInstance[$this->lastHandlerKey]->MetaColumns($tableName, FALSE);
				if (is_array($fieldRows)) {
					foreach ($fieldRows as $k => $fieldRow) {
						settype($fieldRow, 'array');
						$fieldRow['Field'] = $fieldRow['name'];
						$ntype = $this->MySQLActualType($this->MetaType($fieldRow['type'],$tableName));
						$ntype .= (($fieldRow['max_length'] != -1) ? (($ntype == 'INT') ? '(11)' :'('.$fieldRow['max_length'].')') : '');
						$fieldRow['Type'] = strtolower($ntype);
						$fieldRow['Null'] = '';
						$fieldRow['Key'] = '';
						$fieldRow['Default'] = $fieldRow['default_value'];
						$fieldRow['Extra'] = '';
						$output[$fieldRow['name']] = $fieldRow;
					}
				}
				break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->admin_get_fields($tableName);
				break;
		}

			// mapping should be done:
		if (is_array($tableArray) && is_array($this->mapping[$ORIG_tableName]['mapFieldNames'])) {
			$revFields = array_flip($this->mapping[$ORIG_tableName]['mapFieldNames']);

			$newOutput = array();
			foreach ($output as $fN => $fInfo) {
				if (isset($revFields[$fN])) {
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
	public function admin_get_keys($tableName) {
		$output = array();

			// Do field mapping if needed:
		$ORIG_tableName = $tableName;
		if ($tableArray = $this->map_needMapping($tableName)) {

				// Table name:
			if ($this->mapping[$tableName]['mapTableName']) {
				$tableName = $this->mapping[$tableName]['mapTableName'];
			}
		}

			// Find columns
		$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_tableName);
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$keyRes = mysql_query('SHOW keys FROM '.$tableName, $this->handlerInstance[$this->lastHandlerKey]['link']);
				while($keyRow = mysql_fetch_assoc($keyRes)) {
					$output[] = $keyRow;
				}
				break;
			case 'adodb':
				$keyRows = $this->handlerInstance[$this->lastHandlerKey]->MetaIndexes($tableName);
				if ($keyRows !== FALSE) {
					while (list($k, $theKey) = each($keyRows)) {
						$theKey['Table'] = $tableName;
						$theKey['Non_unique'] = (int) !$theKey['unique'];
						$theKey['Key_name'] = str_replace($tableName.'_','',$k);
	
							// the following are probably not needed anyway...
						$theKey['Collation'] = '';
						$theKey['Cardinality'] = '';
						$theKey['Sub_part'] = '';
						$theKey['Packed'] = '';
						$theKey['Null'] = '';
						$theKey['Index_type'] = '';
						$theKey['Comment'] = '';
	
							// now map multiple fields into multiple rows (we mimic MySQL, remember...)
						$keycols = $theKey['columns'];
						while (list($c, $theCol) = each($keycols)) {
							$theKey['Seq_in_index'] = $c+1;
							$theKey['Column_name'] = $theCol;
							$output[] = $theKey;
						}
					}
				}
				$priKeyRow = $this->handlerInstance[$this->lastHandlerKey]->MetaPrimaryKeys($tableName);
				$theKey = array();
				$theKey['Table'] = $tableName;
				$theKey['Non_unique'] = 0;
				$theKey['Key_name'] = 'PRIMARY';
	
					// the following are probably not needed anyway...
				$theKey['Collation'] = '';
				$theKey['Cardinality'] = '';
				$theKey['Sub_part'] = '';
				$theKey['Packed'] = '';
				$theKey['Null'] = '';
				$theKey['Index_type'] = '';
				$theKey['Comment'] = '';
	
					// now map multiple fields into multiple rows (we mimic MySQL, remember...)
				if ($priKeyRow !== FALSE) {
					while (list($c, $theCol) = each($priKeyRow)) {
						$theKey['Seq_in_index'] = $c+1;
						$theKey['Column_name'] = $theCol;
						$output[] = $theKey;
					}
				}
				break;
			case 'userdefined':
				$output = $this->handlerInstance[$this->lastHandlerKey]->admin_get_keys($tableName);
				break;
		}

			// mapping should be done:
		if (is_array($tableArray) && is_array($this->mapping[$ORIG_tableName]['mapFieldNames'])) {
			$revFields = array_flip($this->mapping[$ORIG_tableName]['mapFieldNames']);

			$newOutput = array();
			foreach ($output as $kN => $kInfo) {
					// Table:
				$kInfo['Table'] = $ORIG_tableName;

					// Column
				if (isset($revFields[$kInfo['Column_name']])) {
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
	 * mysql() wrapper function, used by the Install Tool.
	 *
	 * @return	array
	 */
	public function admin_get_charsets() {
		return array();
	}

	/**
	 * mysql() wrapper function, used by the Install Tool and EM for all queries regarding management of the database!
	 *
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer
	 */
	public function admin_query($query) {
		$parsedQuery = $this->SQLparser->parseSQL($query);
		$ORIG_table = $parsedQuery['TABLE'];

		if (is_array($parsedQuery)) {

				// Process query based on type:
			switch ($parsedQuery['type']) {
				case 'CREATETABLE':
				case 'ALTERTABLE':
				case 'DROPTABLE':
					if (file_exists(PATH_typo3conf.'temp_fieldInfo.php')) unlink(PATH_typo3conf.'temp_fieldInfo.php');
					$this->map_genericQueryParsed($parsedQuery);
					break;
				case 'INSERT':
				case 'TRUNCATETABLE':
					$this->map_genericQueryParsed($parsedQuery);
					break;
				case 'CREATEDATABASE':
					die('Creating a database with DBAL is not supported. Did you really read the manual?');
					break;
				default:
					die('ERROR: Invalid Query type ('.$parsedQuery['type'].') for ->admin_query() function!: "'.htmlspecialchars($query).'"');
					break;
			}

				// Setting query array (for other applications to access if needed)
			$this->lastParsedAndMappedQueryArray = $parsedQuery;

				// Execute query (based on handler derived from the TABLE name which we actually know for once!)
			$this->lastHandlerKey = $this->handler_getFromTableList($ORIG_table);
			switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
				case 'native':
						// Compiling query:
					$compiledQuery =  $this->SQLparser->compileSQL($this->lastParsedAndMappedQueryArray);

					if ($this->lastParsedAndMappedQueryArray['type']=='INSERT') {
						return mysql_query($compiledQuery, $this->link);
					}
					return mysql_query($compiledQuery[0], $this->link);
					break;
				case 'adodb':
						// Compiling query:
					$compiledQuery =  $this->SQLparser->compileSQL($this->lastParsedAndMappedQueryArray);
					switch ($this->lastParsedAndMappedQueryArray['type']) {
						case 'INSERT':
							return $this->exec_INSERTquery($this->lastParsedAndMappedQueryArray['TABLE'], $compiledQuery);
						case 'TRUNCATETABLE':
							return $this->exec_TRUNCATEquery($this->lastParsedAndMappedQueryArray['TABLE']);
					}
					return $this->handlerInstance[$this->lastHandlerKey]->DataDictionary->ExecuteSQLArray($compiledQuery);
					break;
				case 'userdefined':
						// Compiling query:
					$compiledQuery =  $this->SQLparser->compileSQL($this->lastParsedAndMappedQueryArray);

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
	public function handler_getFromTableList($tableList) {

		$key = $tableList;

		if (!isset($this->cache_handlerKeyFromTableList[$key])) {

				// Get tables separated:
			$_tableList = $tableList;
			$tableArray = $this->SQLparser->parseFromTables($_tableList);

				// If success, traverse the tables:
			if (is_array($tableArray) && count($tableArray)) {
				$outputHandlerKey = '';

				foreach ($tableArray as $vArray) {
						// Find handler key, select "_DEFAULT" if none is specifically configured:
					$handlerKey = $this->table2handlerKeys[$vArray['table']] ? $this->table2handlerKeys[$vArray['table']] : '_DEFAULT';

						// In case of separate handler keys for joined tables:
					if ($outputHandlerKey && $handlerKey != $outputHandlerKey) {
						die('DBAL fatal error: Tables in this list "'.$tableList.'" didn\'t use the same DB handler!');
					}

					$outputHandlerKey = $handlerKey;
				}

					// Check initialized state; if handler is NOT initialized (connected) then we will connect it!
				if (!isset($this->handlerInstance[$outputHandlerKey])) {
					$this->handler_init($outputHandlerKey);
				}

					// Return handler key:
				$this->cache_handlerKeyFromTableList[$key] = $outputHandlerKey;
			} else {
				die('DBAL fatal error: No handler found in handler_getFromTableList() for: "'.$tableList.'" ('.$tableArray.')');
			}
		}

		return $this->cache_handlerKeyFromTableList[$key];
	}

	/**
	 * Initialize handler (connecting to database)
	 *
	 * @param	string		Handler key
	 * @return	boolean		If connection went well, return TRUE
	 * @see handler_getFromTableList()
	 */
	public function handler_init($handlerKey) {

			// Find handler configuration:
		$cfgArray = $this->handlerCfg[$handlerKey];
		$handlerType = (string)$cfgArray['type'];
		$output = FALSE;

		if (is_array($cfgArray)) {
			if (!$cfgArray['config']['database']) {
					// Configuration is incomplete
				return;
			}
			switch ($handlerType) {
				case 'native':
					if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['no_pconnect']) {
						$link = mysql_connect($cfgArray['config']['host'].(isset($cfgArray['config']['port']) ? ':'.$cfgArray['config']['port'] : ''), $cfgArray['config']['username'], $cfgArray['config']['password'], TRUE);
					} else {
						$link = mysql_pconnect($cfgArray['config']['host'].(isset($cfgArray['config']['port']) ? ':'.$cfgArray['config']['port'] : ''), $cfgArray['config']['username'], $cfgArray['config']['password']);
					}

						// Set handler instance:
					$this->handlerInstance[$handlerKey] = array('handlerType' => 'native', 'link' => $link);

						// If link succeeded:
					if ($link) {
							// For default, set ->link (see t3lib_DB)
						if ($handlerKey == '_DEFAULT') {
							$this->link = $link;
						}

							// Select database as well:
						if (mysql_select_db($cfgArray['config']['database'], $link)) {
							$output = TRUE;
						}
						$setDBinit = t3lib_div::trimExplode(chr(10), $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'], 1);
						foreach ($setDBinit as $v) {
							if (mysql_query($v, $link) === FALSE) {
								t3lib_div::sysLog('Could not initialize DB connection with query "'.$v.'".','Core',3);
							}
						}
					} else {
						t3lib_div::sysLog('Could not connect to MySQL server '.$cfgArray['config']['host'].' with user '.$cfgArray['config']['username'].'.','Core',4);
					}
					break;
				case 'adodb':
					$output = TRUE;
					require_once(t3lib_extMgm::extPath('adodb').'adodb/adodb.inc.php');
					if (!defined('ADODB_FORCE_NULLS')) define('ADODB_FORCE_NULLS', 1);
					$GLOBALS['ADODB_FORCE_TYPE'] = ADODB_FORCE_VALUE;
					$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_BOTH;

					$this->handlerInstance[$handlerKey] = &ADONewConnection($cfgArray['config']['driver']);

						// Set driver-specific options
					if (isset($cfgArray['config']['driverOptions'])) {
						foreach ($cfgArray['config']['driverOptions'] as $optionName => $optionValue) {
							$optionSetterName = 'set' . ucfirst($optionName);
							if (method_exists($this->handlerInstance[$handlerKey], $optionSetterName)) {
								$this->handlerInstance[$handlerKey]->$optionSetterName($optionValue);
							} else {
								$this->handlerInstance[$handlerKey]->$optionName = $optionValue;
							}
						}
					}

					if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['no_pconnect']) {
						$this->handlerInstance[$handlerKey]->Connect($cfgArray['config']['host'].(isset($cfgArray['config']['port']) ? ':'.$cfgArray['config']['port'] : ''),$cfgArray['config']['username'],$cfgArray['config']['password'],$cfgArray['config']['database']);
					} else {
						$this->handlerInstance[$handlerKey]->PConnect($cfgArray['config']['host'].(isset($cfgArray['config']['port']) ? ':'.$cfgArray['config']['port'] : ''),$cfgArray['config']['username'],$cfgArray['config']['password'],$cfgArray['config']['database']);
					}
					if (!$this->handlerInstance[$handlerKey]->isConnected()) {
						$dsn = $cfgArray['config']['driver'].'://'.$cfgArray['config']['username'].
							(strlen($cfgArray['config']['password']) ? ':XXXX@' : '').
							$cfgArray['config']['host'].(isset($cfgArray['config']['port']) ? ':'.$cfgArray['config']['port'] : '').'/'.$cfgArray['config']['database'].
							($GLOBALS['TYPO3_CONF_VARS']['SYS']['no_pconnect'] ? '' : '?persistent=1');
						t3lib_div::sysLog('Could not connect to DB server using ADOdb on '.$cfgArray['config']['host'].' with user '.$cfgArray['config']['username'].'.','Core',4);
						error_log('DBAL error: Connection to '.$dsn.' failed. Maybe PHP doesn\'t support the database?');
						$output = FALSE;
					} else {
						$this->handlerInstance[$handlerKey]->DataDictionary  = NewDataDictionary($this->handlerInstance[$handlerKey]);
						$this->handlerInstance[$handlerKey]->last_insert_id = 0;
						if (isset($cfgArray['config']['sequenceStart'])) {
							$this->handlerInstance[$handlerKey]->sequenceStart = $cfgArray['config']['sequenceStart'];
						} else {
							$this->handlerInstance[$handlerKey]->sequenceStart = 1;
						}
					}
					break;
				case 'userdefined':
						// Find class file:
					$fileName = t3lib_div::getFileAbsFileName($cfgArray['config']['classFile']);
					if (@is_file($fileName)) {
						require_once($fileName);
					} else die('DBAL error: "'.$fileName.'" was not a file to include.');

						// Initialize:
					$this->handlerInstance[$handlerKey] = t3lib_div::makeInstance($cfgArray['config']['class']);
					$this->handlerInstance[$handlerKey]->init($cfgArray,$this);

					if (is_object($this->handlerInstance[$handlerKey])) {
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


	/**
	 * Checks if database is connected.
	 *
	 * @return boolean
	 */
	public function isConnected() {
		$result = FALSE;
		switch ((string)$this->handlerCfg[$this->lastHandlerKey]['type']) {
			case 'native':
				$result = is_resource($this->link);
				break;
			case 'adodb':
			case 'userdefined':
				$result = is_object($this->handlerInstance[$this->lastHandlerKey]) && $this->handlerInstance[$this->lastHandlerKey]->isConnected();
				break;
		}
		return $result;
	}


	/**
	 * Checks whether the DBAL is currently inside an operation running on the "native" DB handler (i.e. MySQL)
	 *
	 * @return boolean	True if running on "native" DB handler (i.e. MySQL)
	 */
	public function runningNative() {
		return ((string)$this->handlerCfg[$this->lastHandlerKey]['type']==='native');
	}


	/**
	 * Checks whether the ADOdb handler is running with a driver that contains the argument
	 *
	 * @param string	$driver	Driver name, matched with strstr().
	 * @return boolean	True if running with the given driver
	 */
	public function runningADOdbDriver($driver) {
		return strstr($this->handlerCfg[$this->lastHandlerKey]['config']['driver'], $driver);
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
	 * @param	boolean		If TRUE, it will check only if FIELDs are configured and ignore the mapped table name if any.
	 * @return	mixed		Returns an array of table names (parsed version of input table) if mapping is needed, otherwise just FALSE.
	 */
	protected function map_needMapping($tableList, $fieldMappingOnly = FALSE) {
		$key = $tableList.'|'.$fieldMappingOnly;
		if (!isset($this->cache_mappingFromTableList[$key])) {
			$this->cache_mappingFromTableList[$key] = FALSE;	// Default:

			$tables = $this->SQLparser->parseFromTables($tableList);
			if (is_array($tables)) {
				foreach ($tables as $tableCfg) {
					if ($fieldMappingOnly) {
						if (is_array($this->mapping[$tableCfg['table']]['mapFieldNames'])) {
							$this->cache_mappingFromTableList[$key] = $tables;
						} elseif (is_array($tableCfg['JOIN'])) {
							foreach ($tableCfg['JOIN'] as $join) {
								if (is_array($this->mapping[$join['withTable']]['mapFieldNames'])) {
									$this->cache_mappingFromTableList[$key] = $tables;
									break;
								}
							}
 						}
					} else {
						if (is_array($this->mapping[$tableCfg['table']])) {
							$this->cache_mappingFromTableList[$key] = $tables;
						} elseif (is_array($tableCfg['JOIN'])) {
							foreach ($tableCfg['JOIN'] as $join) {
								if (is_array($this->mapping[$join['withTable']])) {
									$this->cache_mappingFromTableList[$key] = $tables;
									break;
								}
							}
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
	 * @param	boolean		If TRUE, reverse direction. Default direction is to map an array going INTO the database (thus mapping TYPO3 fieldnames to PHYSICAL field names!)
	 * @return	array		Output array, with mapped associative keys.
	 */
	protected function map_assocArray($input, $tables, $rev = FALSE) {
			// Traverse tables from query (hopefully only one table):
		foreach ($tables as $tableCfg) {
			$tableKey = $this->getMappingKey($tableCfg['table']);
			if (is_array($this->mapping[$tableKey]['mapFieldNames'])) {

					// Get the map (reversed if needed):
				if ($rev) {
					$theMap = array_flip($this->mapping[$tableKey]['mapFieldNames']);
				} else {
					$theMap = $this->mapping[$tableKey]['mapFieldNames'];
				}

					// Traverse selected record, map fieldnames:
				$output = array();
				foreach ($input as $fN => $value) {

						// Set the field name, change it if found in mapping array:
					if ($theMap[$fN]) {
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
	protected function map_remapSELECTQueryParts(&$select_fields, &$from_table, &$where_clause, &$groupBy, &$orderBy) {
			// Backup current mapping as it may be altered if aliases on mapped tables are found
		$backupMapping = $this->mapping;

			// Tables:
		$tables = $this->SQLparser->parseFromTables($from_table);
		$defaultTable = $tables[0]['table'];
			// Prepare mapping for aliased tables. This will copy the definition of the original table name.
			// The alias is prefixed with a database-incompatible character to prevent naming clash with real table name
			// Further access to $this->mapping should be made through $this->getMappingKey() method
		foreach ($tables as $k => $v) {
			if ($v['as'] && is_array($this->mapping[$v['table']]['mapFieldNames'])) {
				$mappingKey = $this->getFreeMappingKey($v['as']);
				$this->mapping[$mappingKey]['mapFieldNames'] =& $this->mapping[$v['table']]['mapFieldNames'];
			}
			if (is_array($v['JOIN'])) {
				foreach ($v['JOIN'] as $joinCnt => $join) {
					if ($join['as'] && is_array($this->mapping[$join['withTable']]['mapFieldNames'])) {
						$mappingKey = $this->getFreeMappingKey($join['as']);
						$this->mapping[$mappingKey]['mapFieldNames'] =& $this->mapping[$join['withTable']]['mapFieldNames'];
					}
				}
			}
		}
		foreach ($tables as $k => $v) {
			$tableKey = $this->getMappingKey($v['table']);
			if ($this->mapping[$tableKey]['mapTableName']) {
				$tables[$k]['table'] = $this->mapping[$tableKey]['mapTableName'];
			}
				// Mapping JOINS
			if (is_array($v['JOIN'])) {
				foreach($v['JOIN'] as $joinCnt => $join) {
						// Mapping withTable of the JOIN
					$withTableKey = $this->getMappingKey($join['withTable']);
					if ($this->mapping[$withTableKey]['mapTableName']) {
						$tables[$k]['JOIN'][$joinCnt]['withTable'] = $this->mapping[$withTableKey]['mapTableName'];					
					}
					$onPartsArray = array();
						// Mapping ON parts of the JOIN
					if (is_array($tables[$k]['JOIN'][$joinCnt]['ON'])) {
						foreach ($tables[$k]['JOIN'][$joinCnt]['ON'] as &$condition) {
								// Left side of the comparator
							$leftTableKey = $this->getMappingKey($condition['left']['table']);
							if (isset($this->mapping[$leftTableKey]['mapFieldNames'][$condition['left']['field']])) {
								$condition['left']['field'] = $this->mapping[$leftTableKey]['mapFieldNames'][$condition['left']['field']];
							}
							if (isset($this->mapping[$leftTableKey]['mapTableName'])) {
								$condition['left']['table'] = $this->mapping[$leftTableKey]['mapTableName'];
							}
								// Right side of the comparator
							$rightTableKey = $this->getMappingKey($condition['right']['table']);
							if (isset($this->mapping[$rightTableKey]['mapFieldNames'][$condition['right']['field']])) {
								$condition['right']['field'] = $this->mapping[$rightTableKey]['mapFieldNames'][$condition['right']['field']];
							}
							if (isset($this->mapping[$rightTableKey]['mapTableName'])) {
								$condition['right']['table'] = $this->mapping[$rightTableKey]['mapTableName'];
							}
						}
					}
				}
			}
		}
		$from_table = $this->SQLparser->compileFromTables($tables);

			// Where clause:
		$whereParts = $this->SQLparser->parseWhereClause($where_clause);
		$this->map_sqlParts($whereParts,$defaultTable);
		$where_clause = $this->SQLparser->compileWhereClause($whereParts, FALSE);

			// Select fields:
		$expFields = $this->SQLparser->parseFieldList($select_fields);
		$this->map_sqlParts($expFields,$defaultTable);
		$select_fields = $this->SQLparser->compileFieldList($expFields, FALSE, FALSE);

			// Group By fields
		$expFields = $this->SQLparser->parseFieldList($groupBy);
		$this->map_sqlParts($expFields,$defaultTable);
		$groupBy = $this->SQLparser->compileFieldList($expFields);

			// Order By fields
		$expFields = $this->SQLparser->parseFieldList($orderBy);
		$this->map_sqlParts($expFields,$defaultTable);
		$orderBy = $this->SQLparser->compileFieldList($expFields);

			// Restore the original mapping
		$this->mapping = $backupMapping;
	}

	/**
	 * Returns the key to be used when retrieving information from $this->mapping. This ensures
	 * that mapping from aliased tables is properly retrieved.
	 *
	 * @param string $tableName
	 * @return string
	 */
	protected function getMappingKey($tableName) {
			// Search deepest alias mapping
		while (isset($this->mapping['*' . $tableName])) {
			$tableName = '*' . $tableName;
		}
		return $tableName;
	}

	/**
	 * Returns a free key to be used to store mapping information in $this->mapping.
	 *
	 * @param string $tableName
	 * @return string
	 */
	protected function getFreeMappingKey($tableName) {
		while (isset($this->mapping[$tableName])) {
			$tableName = '*' . $tableName;
		}
		return $tableName;
	}

	/**
	 * Generic mapping of table/field names arrays (as parsed by tx_dbal_sqlengine)
	 *
	 * @param	array		Array with parsed SQL parts; Takes both fields, tables, where-parts, group and order-by. Passed by reference.
	 * @param	string		Default table name to assume if no table is found in $sqlPartArray
	 * @return	void
	 * @access private
	 * @see map_remapSELECTQueryParts()
	 */
	protected function map_sqlParts(&$sqlPartArray, $defaultTable) {
		$defaultTableKey = $this->getMappingKey($defaultTable);
			// Traverse sql Part array:
		if (is_array($sqlPartArray)) {
			foreach ($sqlPartArray as $k => $v) {

				if (isset($sqlPartArray[$k]['type'])) {
					switch ($sqlPartArray[$k]['type']) {
						case 'flow-control':
							$temp = array($sqlPartArray[$k]['flow-control']);
							$this->map_sqlParts($temp, $defaultTable);	// Call recursively!
							$sqlPartArray[$k]['flow-control'] = $temp[0];
							break;
						case 'CASE':
							if (isset($sqlPartArray[$k]['case_field'])) {
								$fieldArray = explode('.', $sqlPartArray[$k]['case_field']);
								if (count($fieldArray) == 1 && is_array($this->mapping[$defaultTableKey]['mapFieldNames']) && isset($this->mapping[$defaultTableKey]['mapFieldNames'][$fieldArray[0]])) {
									$sqlPartArray[$k]['case_field'] = $this->mapping[$defaultTableKey]['mapFieldNames'][$fieldArray[0]];
								}
								elseif (count($fieldArray) == 2) {
										// Map the external table
									$table = $fieldArray[0];
									$tableKey = $this->getMappingKey($table);
									if (isset($this->mapping[$tableKey]['mapTableName'])) {
										$table = $this->mapping[$tableKey]['mapTableName'];
									}
										// Map the field itself
									$field = $fieldArray[1];
									if (is_array($this->mapping[$tableKey]['mapFieldNames']) && isset($this->mapping[$tableKey]['mapFieldNames'][$fieldArray[1]])) {
										$field = $this->mapping[$tableKey]['mapFieldNames'][$fieldArray[1]];
									}
									$sqlPartArray[$k]['case_field'] = $table . '.' . $field;
								}
							}
							foreach ($sqlPartArray[$k]['when'] as $key => $when) {
								$this->map_sqlParts($sqlPartArray[$k]['when'][$key]['when_value'], $defaultTable);
							}
							break;
					}
				}

					// Look for sublevel (WHERE parts only)
				if (is_array($sqlPartArray[$k]['sub'])) {
					$this->map_sqlParts($sqlPartArray[$k]['sub'], $defaultTable);	// Call recursively!
				} elseif (isset($sqlPartArray[$k]['func'])) {
					switch ($sqlPartArray[$k]['func']['type']) {
						case 'EXISTS':
							$this->map_subquery($sqlPartArray[$k]['func']['subquery']);
							break;
						case 'IFNULL':
						case 'LOCATE':
								// For the field, look for table mapping (generic):
							$t = $sqlPartArray[$k]['func']['table'] ? $sqlPartArray[$k]['func']['table'] : $defaultTable;
							$t = $this->getMappingKey($t);
							if (is_array($this->mapping[$t]['mapFieldNames']) && $this->mapping[$t]['mapFieldNames'][$sqlPartArray[$k]['func']['field']]) {
								$sqlPartArray[$k]['func']['field'] = $this->mapping[$t]['mapFieldNames'][$sqlPartArray[$k]['func']['field']];
							}
							if ($this->mapping[$t]['mapTableName']) {
								$sqlPartArray[$k]['func']['table'] = $this->mapping[$t]['mapTableName'];
							}
							break;
					}
				} else {
						// For the field, look for table mapping (generic):
					$t = $sqlPartArray[$k]['table'] ? $sqlPartArray[$k]['table'] : $defaultTable;
					$t = $this->getMappingKey($t);

						// Mapping field name, if set:
					if (is_array($this->mapping[$t]['mapFieldNames']) && isset($this->mapping[$t]['mapFieldNames'][$sqlPartArray[$k]['field']])) {
						$sqlPartArray[$k]['field'] = $this->mapping[$t]['mapFieldNames'][$sqlPartArray[$k]['field']];
					}

						// Mapping field name in SQL-functions like MIN(), MAX() or SUM()
					if ($this->mapping[$t]['mapFieldNames']) {
						$fieldArray = explode('.', $sqlPartArray[$k]['func_content']);
						if (count($fieldArray) == 1 && is_array($this->mapping[$t]['mapFieldNames']) && isset($this->mapping[$t]['mapFieldNames'][$fieldArray[0]])) {
							$sqlPartArray[$k]['func_content.'][0]['func_content'] = $this->mapping[$t]['mapFieldNames'][$fieldArray[0]];
							$sqlPartArray[$k]['func_content'] = $this->mapping[$t]['mapFieldNames'][$fieldArray[0]];
						}
						elseif (count($fieldArray) == 2) {
								// Map the external table
							$table = $fieldArray[0];
							$tableKey = $this->getMappingKey($table);
							if (isset($this->mapping[$tableKey]['mapTableName'])) {
								$table = $this->mapping[$tableKey]['mapTableName'];
							}
								// Map the field itself
							$field = $fieldArray[1];
							if (is_array($this->mapping[$tableKey]['mapFieldNames']) && isset($this->mapping[$tableKey]['mapFieldNames'][$fieldArray[1]])) {
								$field = $this->mapping[$tableKey]['mapFieldNames'][$fieldArray[1]];
							}
							$sqlPartArray[$k]['func_content.'][0]['func_content'] = $table . '.' . $field;
							$sqlPartArray[$k]['func_content'] = $table . '.' . $field;
						}

							// Mapping flow-control statements
						if (isset($sqlPartArray[$k]['flow-control'])) {							
							if (isset($sqlPartArray[$k]['flow-control']['type'])) {
								$temp = array($sqlPartArray[$k]['flow-control']);
								$this->map_sqlParts($temp, $t);	// Call recursively!
								$sqlPartArray[$k]['flow-control'] = $temp[0];
 							}
						}
					}

						// Do we have a function (e.g., CONCAT)
					if (isset($v['value']['operator'])) {
						foreach ($sqlPartArray[$k]['value']['args'] as $argK => $fieldDef) {
							$tableKey = $this->getMappingKey($fieldDef['table']);
							if (isset($this->mapping[$tableKey]['mapTableName'])) {
								$sqlPartArray[$k]['value']['args'][$argK]['table'] = $this->mapping[$tableKey]['mapTableName'];
							}
							if (is_array($this->mapping[$tableKey]['mapFieldNames']) && isset($this->mapping[$tableKey]['mapFieldNames'][$fieldDef['field']])) {
								$sqlPartArray[$k]['value']['args'][$argK]['field'] = $this->mapping[$tableKey]['mapFieldNames'][$fieldDef['field']];	
							}
						}
					}

						// Do we have a subquery (WHERE parts only)?
					if (isset($sqlPartArray[$k]['subquery'])) {
						$this->map_subquery($sqlPartArray[$k]['subquery']);
					}

						// do we have a field name in the value?
						// this is a very simplistic check, beware
					if (!is_numeric($sqlPartArray[$k]['value'][0]) && !isset($sqlPartArray[$k]['value'][1])) {
						$fieldArray = explode('.', $sqlPartArray[$k]['value'][0]);
						if (count($fieldArray) == 1 && is_array($this->mapping[$t]['mapFieldNames']) && isset($this->mapping[$t]['mapFieldNames'][$fieldArray[0]])) {
							$sqlPartArray[$k]['value'][0] = $this->mapping[$t]['mapFieldNames'][$fieldArray[0]];
						} elseif (count($fieldArray) == 2) {
								// Map the external table
							$table = $fieldArray[0];
							$tableKey = $this->getMappingKey($table);
							if (isset($this->mapping[$tableKey]['mapTableName'])) {
								$table = $this->mapping[$tableKey]['mapTableName'];
							}
								// Map the field itself
							$field = $fieldArray[1];
							if (is_array($this->mapping[$tableKey]['mapFieldNames']) && isset($this->mapping[$tableKey]['mapFieldNames'][$fieldArray[1]])) {
								$field = $this->mapping[$tableKey]['mapFieldNames'][$fieldArray[1]];
							}
							$sqlPartArray[$k]['value'][0] = $table . '.' . $field;
						}
					}

						// Map table?
					$tableKey = $this->getMappingKey($sqlPartArray[$k]['table']);
					if ($sqlPartArray[$k]['table'] && $this->mapping[$tableKey]['mapTableName']) {
						$sqlPartArray[$k]['table'] = $this->mapping[$tableKey]['mapTableName'];
					}
				}
			}
		}
	}

	/**
	 * Maps table and field names in a subquery.
	 *
	 * @param array $parsedQuery
	 * @return void
	 */
	protected function map_subquery(&$parsedQuery) {
			// Backup current mapping as it may be altered
		$backupMapping = $this->mapping;

		foreach ($parsedQuery['FROM'] as $k => $v) {
			$mappingKey = $v['table'];
			if ($v['as'] && is_array($this->mapping[$v['table']]['mapFieldNames'])) {
				$mappingKey = $this->getFreeMappingKey($v['as']);
			} else {
					// Should ensure that no alias is defined in the external query
					// which would correspond to a real table name in the subquery
				if ($this->getMappingKey($v['table']) !== $v['table']) {
					$mappingKey = $this->getFreeMappingKey($v['table']);
						// This is the only case when 'mapTableName' should be copied
					$this->mapping[$mappingKey]['mapTableName'] =& $this->mapping[$v['table']]['mapTableName'];
				}
			}
			if ($mapping !== $v['table']) {
				$this->mapping[$mappingKey]['mapFieldNames'] =& $this->mapping[$v['table']]['mapFieldNames'];
			}
		}

			// Perform subquery's remapping
		$defaultTable = $parsedQuery['FROM'][0]['table'];
 		$this->map_sqlParts($parsedQuery['SELECT'], $defaultTable);
 		$this->map_sqlParts($parsedQuery['FROM'], $defaultTable);
 		$this->map_sqlParts($parsedQuery['WHERE'], $defaultTable);

 			// Restore the mapping
 		$this->mapping = $backupMapping;
	}

	/**
	 * Will do table/field mapping on a general tx_dbal_sqlengine-compliant SQL query
	 * (May still not support all query types...)
	 *
	 * @param	array		Parsed QUERY as from tx_dbal_sqlengine::parseSQL(). NOTICE: Passed by reference!
	 * @return	void
	 * @see tx_dbal_sqlengine::parseSQL()
	 */
	protected function map_genericQueryParsed(&$parsedQuery) {

			// Getting table - same for all:
		$table = $parsedQuery['TABLE'];
		if ($table) {
				// Do field mapping if needed:
			if ($tableArray = $this->map_needMapping($table)) {

					// Table name:
				if ($this->mapping[$table]['mapTableName']) {
					$parsedQuery['TABLE'] = $this->mapping[$table]['mapTableName'];
				}

					// Based on type, do additional changes:
				switch ($parsedQuery['type']) {
					case 'ALTERTABLE':

						// Changing field name:
					$newFieldName = $this->mapping[$table]['mapFieldNames'][$parsedQuery['FIELD']];
					if ($newFieldName) {
						if ($parsedQuery['FIELD'] == $parsedQuery['newField']) {
							$parsedQuery['FIELD'] = $parsedQuery['newField'] = $newFieldName;
						} else $parsedQuery['FIELD'] = $newFieldName;
					}

						// Changing key field names:
					if (is_array($parsedQuery['fields'])) {
						$this->map_fieldNamesInArray($table,$parsedQuery['fields']);
					}
					break;
					case 'CREATETABLE':
						// Remapping fields:
					if (is_array($parsedQuery['FIELDS'])) {
						$newFieldsArray = array();
						foreach ($parsedQuery['FIELDS'] as $fN => $fInfo) {
							if ($this->mapping[$table]['mapFieldNames'][$fN]) {
								$fN = $this->mapping[$table]['mapFieldNames'][$fN];
							}
							$newFieldsArray[$fN] = $fInfo;
						}
						$parsedQuery['FIELDS'] = $newFieldsArray;
					}

						// Remapping keys:
					if (is_array($parsedQuery['KEYS'])) {
						foreach ($parsedQuery['KEYS'] as $kN => $kInfo) {
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
	protected function map_fieldNamesInArray($table,&$fieldArray) {
		if (is_array($this->mapping[$table]['mapFieldNames'])) {
			foreach ($fieldArray as $k => $v) {
				if ($this->mapping[$table]['mapFieldNames'][$v]) {
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
	public function debugHandler($function,$execTime,$inData) {
			// we don't want to log our own log/debug SQL
		$script = substr(PATH_thisScript,strlen(PATH_site));

		if (substr($script,-strlen('dbal/mod1/index.php'))!='dbal/mod1/index.php' && !strstr($inData['args'][0], 'tx_dbal_debuglog')) {
			$data = array();
			$errorFlag = 0;
			$joinTable = '';

			if ($this->sql_error()) {
				$data['sqlError'] = $this->sql_error();
				$errorFlag|=1;
			}

				// if lastQuery is empty (for whatever reason) at least log inData.args
			if (empty($this->lastQuery))
				$query = implode(' ',$inData['args']);
			else
				$query = $this->lastQuery;

			if ($this->conf['debugOptions']['backtrace']) {
				$backtrace = debug_backtrace();
				unset($backtrace[0]); // skip this very method :)
				$data['backtrace'] = array_slice($backtrace, 0, $this->conf['debugOptions']['backtrace']);
			}

			switch ($function) {
				case 'exec_INSERTquery':
				case 'exec_UPDATEquery':
				case 'exec_DELETEquery':
					$this->debug_log($query,$execTime,$data,$joinTable,$errorFlag, $script);
					break;

				case 'exec_SELECTquery':
						// Get explain data:
					if ($this->conf['debugOptions']['EXPLAIN'] && t3lib_div::inList('adodb,native',$inData['handlerType'])) {
						$data['EXPLAIN'] = $this->debug_explain($this->lastQuery);
					}

						// Check parsing of Query:
					if ($this->conf['debugOptions']['parseQuery']) {
						$parseResults = array();
						$parseResults['SELECT'] = $this->SQLparser->debug_parseSQLpart('SELECT',$inData['args'][1]);
						$parseResults['FROM'] = $this->SQLparser->debug_parseSQLpart('FROM',$inData['args'][0]);
						$parseResults['WHERE'] = $this->SQLparser->debug_parseSQLpart('WHERE',$inData['args'][2]);
						$parseResults['GROUPBY'] = $this->SQLparser->debug_parseSQLpart('SELECT',$inData['args'][3]);	// Using select field list syntax
						$parseResults['ORDERBY'] = $this->SQLparser->debug_parseSQLpart('SELECT',$inData['args'][4]);	// Using select field list syntax

						foreach ($parseResults as $k => $v) {
							if (!strlen($parseResults[$k]))	unset($parseResults[$k]);
						}
						if (count($parseResults)) {
							$data['parseError'] = $parseResults;
							$errorFlag|=2;
						}
					}

						// Checking joinTables:
					if ($this->conf['debugOptions']['joinTables']) {
						if (count(explode(',', $inData['ORIG_from_table']))>1) {
							$joinTable = $inData['args'][0];
						}
					}

						// Logging it:
					$this->debug_log($query,$execTime,$data,$joinTable,$errorFlag, $script);
					if (!empty($inData['args'][2]))
						$this->debug_WHERE($inData['args'][0], $inData['args'][2], $script);
					break;
			}
		}
	}

	/**
	 * Logs the where clause for debugging purposes.
	 *
	 * @param string $table	Table name(s) the query was targeted at
	 * @param string $where	The WHERE clause to be logged
	 * @param string $script	The script calling the logging
	 * @return void
	 */
	public function debug_WHERE($table, $where, $script = '') {
		$insertArray = array (
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'beuser_id' => intval($GLOBALS['BE_USER']->user['uid']),
			'script' => $script,
			'tablename' => $table,
			'whereclause' => $where
		);

		$this->exec_INSERTquery('tx_dbal_debuglog_where', $insertArray);
	}

	/**
	 * Inserts row in the log table
	 *
	 * @param	string		The current query
	 * @param	integer		Execution time of query in milliseconds
	 * @param	array		Data to be stored serialized.
	 * @param	string		Join string if there IS a join.
	 * @param	integer		Error status.
	 * @param string $script	The script calling the logging
	 * @return	void
	 */
	public function debug_log($query,$ms,$data,$join,$errorFlag, $script='') {
		if (is_array($query)) {
			$queryToLog = $query[0].' --  ';
			if (count($query[1])) {
				$queryToLog .= count($query[1]).' BLOB FIELDS: '.implode(', ',array_keys($query[1]));
			}
			if (count($query[2])) {
				$queryToLog .= count($query[2]).' CLOB FIELDS: '.implode(', ',array_keys($query[2]));
			}
		} else {
			$queryToLog = $query;
		}
		$insertArray = array (
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'beuser_id' => intval($GLOBALS['BE_USER']->user['uid']),
			'script' => $script,
			'exec_time' => $ms,
			'table_join' => $join,
			'serdata' => serialize($data),
			'query' => $queryToLog,
			'errorFlag' => $errorFlag
		);

		$this->exec_INSERTquery('tx_dbal_debuglog', $insertArray);
	}

	/**
	 * Perform EXPLAIN query on DEFAULT handler!
	 *
	 * @param	string		SELECT Query
	 * @return	array		The Explain result rows in an array
	 * @todo	Not supporting other than the default handler? And what about DBMS of other kinds than MySQL - support for EXPLAIN?
	 */
	public function debug_explain($query) {
		$output = array();
		$hType = (string)$this->handlerCfg[$this->lastHandlerKey]['type'];
		switch ($hType) {
			case 'native':
				$res = $this->sql_query('EXPLAIN '.$query);
				while($row = $this->sql_fetch_assoc($res)) {
					$output[] = $row;
				}
				break;
			case 'adodb':
				switch ($this->handlerCfg['_DEFAULT']['config']['driver']) {
					case 'oci8':
						$res = $this->sql_query('EXPLAIN PLAN '.$query);
						$output[] = 'EXPLAIN PLAN data logged to default PLAN_TABLE';
						break;
					default:
						$res = $this->sql_query('EXPLAIN '.$query);
						while($row = $this->sql_fetch_assoc($res)) {
							$output[] = $row;
						}
						break;
				}
			break;
		}

		return $output;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_t3lib_db.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_t3lib_db.php']);
}

?>