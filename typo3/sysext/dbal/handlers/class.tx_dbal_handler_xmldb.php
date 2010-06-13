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
 * $Id: class.tx_dbal_handler_xmldb.php 25889 2009-10-27 10:09:11Z xperseguers $
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   74: class tx_dbal_handler_xmldb extends tx_dbal_sqlengine
 *   91:     function init($config, &$pObj)
 *  128:     function readDataSource($table)
 *  157:     function saveDataSource($table)
 *  184:     function xmlDB_writeStructure()
 *  193:     function xmlDB_readStructure()
 *
 *              SECTION: SQL admin functions
 *  217:     function admin_get_tables()
 *  242:     function admin_get_fields($tableName)
 *  276:     function admin_get_keys($tableName)
 *  314:     function admin_query($query)
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */










/**
 * Example DBAL handler class
 * Stores data in XML, not a database.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_dbal
 */
class tx_dbal_handler_xmldb extends tx_dbal_sqlengine {

	var $config = array();
	var $pObj;	// Set from DBAL class.

	// Database Storage directory:
	var $DBdir = '';
	var $DBstructure = array(
		'tables' => array()
	);

	/**
	 * Initialize handler
	 *
	 * @param	array		Configuration from DBAL
	 * @param	object		Parent object
	 * @return	void
	 */
	function init($config, $pObj) {
		$this->config = $config['config'];

		$dbStorage = t3lib_div::getFileAbsFileName($this->config['DBstorageDir']);
		if ($dbStorage && @is_dir($dbStorage) && ($dbStorage{strlen($dbStorage)-1} == '/')) {
			$this->DBdir = $dbStorage;

				// Read structure file:
			if (@is_file($this->DBdir.'_STRUCTURE.xml'))	{
				$this->xmlDB_readStructure();
				if (is_array($this->DBstructure))	{
					return TRUE;
				} else {
					$this->errorStatus = 'The database structure array could not be loaded correctly. "_STRUCTURE.xml" may be corrupt';
				}
			} else {
				$this->xmlDB_writeStructure();
				if (@is_file($this->DBdir.'_STRUCTURE.xml'))	{
					return TRUE;
				} else {
					$this->errorStatus = 'The database structure file could not be created in dir "'.$dbStorage.'"';
				}
			}


		} else $this->errorStatus = 'The database storage dir "'.$dbStorage.'" did not exist!';

debug($this->errorStatus,'XMLDB connect ERROR:');
		return FALSE;
	}

	/**
	 * Setting table data (overriding function)
	 *
	 * @param	string		Table name
	 * @return	void
	 */
	function readDataSource($table)	{

		if (!$this->DBdir)	{
			$this->errorStatus = 'XMLdatabase not connected';
			return FALSE;
		}

			// Reading table:
		if (is_array($this->DBstructure['tables'][$table]))	{
			if (!isset($this->data[$table]))	{	// Checking if it has already been read
				$newTableFile = 'TABLE_'.$table.'.xml';
				if (@is_file($this->DBdir.$newTableFile))	{
					$this->data[$table] = t3lib_div::xml2array(t3lib_div::getUrl($this->DBdir.$newTableFile));
					if (!is_array($this->data[$table]))		$this->data[$table] = array();
					return TRUE;
				} else {
					$this->data[$table] = array();
					$this->errorStatus = 'Tablefile for "'.$table.'" not found';
				}
			}
		} else $this->errorStatus = 'Table "'.$table.'" not found';
	}

	/**
	 * Saving data source
	 *
	 * @param	string		Table name
	 * @return	boolean		True on success
	 */
	function saveDataSource($table)	{

		if (!$this->DBdir)	{
			$this->errorStatus = 'XMLdatabase not connected';
			return FALSE;
		}

			// Writing table:
		if (is_array($this->DBstructure['tables'][$table]))	{
			$newTableFile = 'TABLE_'.$table.'.xml';
			if (t3lib_div::getFileAbsFileName($this->DBdir.$newTableFile) && @is_file($this->DBdir.$newTableFile))	{

				$storeInCharset = $GLOBALS['LANG']->charSet;
				$xmlValue = t3lib_div::array2xml($this->data[$table],'',0,'T3xmlDB',0,array('useIndexTagForNum'=>'rec'));
				$content = '<?xml version="1.0" encoding="'.$storeInCharset.'" standalone="yes" ?>'.chr(10).$xmlValue;
				t3lib_div::writeFile($this->DBdir.$newTableFile,$content);

				return TRUE;
			} else $this->errorStatus = 'Tablefile for "'.$table.'" not found';
		} else $this->errorStatus = 'Table "'.$table.'" not found';
	}

	/**
	 * Writing database structure
	 *
	 * @return	void
	 */
	function xmlDB_writeStructure()	{
		t3lib_div::writeFile($this->DBdir.'_STRUCTURE.xml', t3lib_div::array2xml($this->DBstructure,'',0,'T3xmlDBStructure',0,array('useIndexTagForNum'=>'item')));
	}

	/**
	 * Reading database structure
	 *
	 * @return	void
	 */
	function xmlDB_readStructure()	{
		$this->DBstructure = t3lib_div::xml2array(t3lib_div::getUrl($this->DBdir.'_STRUCTURE.xml'));
	}









	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/

	/**
	 * Returns the list of tables from the database
	 *
	 * @return	array		Tables in an array (tablename is in both key and value)
	 * @todo	Should return table details in value! see t3lib_db::admin_get_tables()
	 */
	function admin_get_tables()	{

		if (!$this->DBdir)	{
			$this->errorStatus = 'XMLdatabase not connected';
			return FALSE;
		}

		$whichTables = array();

			// Traverse tables:
		if (is_array($this->DBstructure['tables']))	{
			foreach($this->DBstructure['tables'] as $tableName => $tableInfo)	{
				$whichTables[$tableName] = $tableName;
			}
		}

		return $whichTables;
	}

	/**
	 * Returns information about each field in the $table
	 *
	 * @param	string		Table name
	 * @return	array		Field information in an associative array with fieldname => field row
	 */
	function admin_get_fields($tableName)	{

		if (!$this->DBdir)	{
			$this->errorStatus = 'XMLdatabase not connected';
			return FALSE;
		}

		$output = array();

			// Traverse fields in table:
		if (is_array($this->DBstructure['tables'][$tableName]) && is_array($this->DBstructure['tables'][$tableName]['FIELDS']))	{
			foreach($this->DBstructure['tables'][$tableName]['FIELDS'] as $fieldName => $fieldInfo)	{
				$output[$fieldName] = array(
					'Field' => $fieldName,
					'Type' => $fieldInfo['definition']['fieldType'].
									($fieldInfo['definition']['value']?'('.$fieldInfo['definition']['value'].')':'').
									(isset($fieldInfo['definition']['featureIndex']['UNSIGNED']) ? ' '.$fieldInfo['definition']['featureIndex']['UNSIGNED']['keyword'] : ''),
					'Null' => isset($fieldInfo['definition']['featureIndex']['NOTNULL']) ? '' : 'Yes',
					'Key' => '',
					'Default' => $fieldInfo['definition']['featureIndex']['DEFAULT']['value'][0],
					'Extra' => isset($fieldInfo['definition']['featureIndex']['AUTO_INCREMENT']) ? 'auto_increment' : '',
				);
			}
		}

		return $output;
	}

	/**
	 * Returns information about each index key in the $table
	 *
	 * @param	string		Table name
	 * @return	array		Key information in a numeric array
	 */
	function admin_get_keys($tableName)	{

		if (!$this->DBdir)	{
			$this->errorStatus = 'XMLdatabase not connected';
			return FALSE;
		}

		$output = array();

			// Traverse fields in table:
		if (is_array($this->DBstructure['tables'][$tableName]) && is_array($this->DBstructure['tables'][$tableName]['KEYS']))	{
			foreach($this->DBstructure['tables'][$tableName]['KEYS'] as $keyName => $keyInfo)	{
				foreach($keyInfo as $seq => $keyField)	{
					$output[] = array(
						'Table' => $tableName,
						'Non_unique' => ($keyName=='PRIMARYKEY' ? 0 : 1),
						'Key_name' => ($keyName=='PRIMARYKEY' ? 'PRIMARY' : $keyName),
						'Seq_in_index' => $seq+1,
						'Column_name' => $keyField,
						'Collation' => 'A',
						'Cardinality' => '',
						'Sub_part' => '',
						'Packed' => '',
						'Comment' => '',
					);
				}
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

		if (!$this->DBdir)	{
			$this->errorStatus = 'XMLdatabase not connected';
			return FALSE;
		}

		$parsedQuery = $this->parseSQL($query);
		$table = $parsedQuery['TABLE'];

		if (is_array($parsedQuery))	{
				// Process query based on type:
			switch($parsedQuery['type'])	{
				case 'CREATETABLE':
					if (!is_array($this->DBstructure['tables'][$table]))	{
						$newTableFile = 'TABLE_'.$table.'.xml';
						if (!@is_file($this->DBdir.$newTableFile))	{

								// Write table file:
							t3lib_div::writeFile($this->DBdir.$newTableFile, '');	// Create file
							if (@is_file($this->DBdir.$newTableFile))	{

									// Set and write structure
								if (!is_array($this->DBstructure['tables']))	$this->DBstructure['tables']=array();
								$this->DBstructure['tables'][(string)$table] = $parsedQuery;	// I have some STRANGE behaviours with this variable - had to do this trick to make it work!

								$this->xmlDB_writeStructure();
								return TRUE;
							} else $this->errorStatus = 'Table file "'.$this->DBdir.$newTableFile.'" could not be created! Cannot create table!';
						} else $this->errorStatus = 'Table file "'.$this->DBdir.$newTableFile.'" already exists! Cannot create table!';
					} else $this->errorStatus = 'Table "'.$table.'" already exists!';
				break;
				case 'ALTERTABLE':
					if (is_array($this->DBstructure['tables'][$table]))	{
						switch($parsedQuery['action'])	{
							case 'ADD':
								if (!is_array($this->DBstructure['tables'][$table]['FIELDS'][$parsedQuery['FIELD']]))	{
									$this->DBstructure['tables'][$table]['FIELDS'][$parsedQuery['FIELD']]['definition'] = $parsedQuery['definition'];	// Adding field in the end of list.
									$this->xmlDB_writeStructure();
									return TRUE;

									// TODO: Should traverse all data an add that field in arrays!
								} else $this->errorStatus = 'Field "'.$parsedQuery['FIELD'].'" already exists!';
							break;
							case 'CHANGE':
								if (is_array($this->DBstructure['tables'][$table]['FIELDS']))	{
									if (is_array($this->DBstructure['tables'][$table]['FIELDS'][$parsedQuery['FIELD']]))	{
										$newFieldInfo = array();
										foreach($this->DBstructure['tables'][$table]['FIELDS'] as $fieldName => $fieldDefinition)	{
											if (!strcmp($fieldName,$parsedQuery['FIELD']))	{

													// New fieldname?
												if ($parsedQuery['newField'])	{
													if (!is_array($this->DBstructure['tables'][$table]['FIELDS'][$parsedQuery['newField']]))	{
														$fieldName = $parsedQuery['newField'];
													} else {
														$this->errorStatus = 'A field in the table was already named "'.$parsedQuery['newField'].'"';
														return FALSE;
													}
												}
													// Set new field definition:
												$fieldDefinition['definition'] = $parsedQuery['definition'];
											}

												// Set the whole thing in new var:
											$newFieldInfo[$fieldName] = $fieldDefinition;
										}
										$this->DBstructure['tables'][$table]['FIELDS'] = $newFieldInfo;
										$this->xmlDB_writeStructure();
										return TRUE;

										// TODO: Should traverse all data an remove that field in arrays!
									} else $this->errorStatus = 'Field "'.$parsedQuery['FIELD'].'" does not exist!';
								} else $this->errorStatus = 'There are not fields in the table - strange!';
							break;
							case 'DROP':
								if (is_array($this->DBstructure['tables'][$table]['FIELDS'][$parsedQuery['FIELD']]))	{
									unset($this->DBstructure['tables'][$table]['FIELDS'][$parsedQuery['FIELD']]);	// Removing it...
									$this->xmlDB_writeStructure();
									return TRUE;

									// TODO: Should traverse all data an remove that field in arrays!
								} else $this->errorStatus = 'Field "'.$parsedQuery['FIELD'].'" does not exist!';
							break;
						}
					} else $this->errorStatus = 'Table "'.$table.'" does not exist!';
				break;
				case 'DROPTABLE':

						// TODO:
					debug($parsedQuery);


				break;
				default:
					$this->errorStatus = 'Query type "'.$parsedQuery['type'].'" was not supported!';
				break;
			}

		} else $this->errorStatus = 'SQL parse error: '.$parsedQuery;

		return FALSE;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/handlers/class.tx_dbal_handler_xmldb.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/handlers/class.tx_dbal_handler_xmldb.php']);
}
?>
