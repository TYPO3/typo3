<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2004-2006 Karsten Dambekalns <karsten@typo3.org>
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
 * PHP SQL engine
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <k.dambekalns@fishfarm.de>
 */


/**
 * PHP SQL engine / server
 * Some parts are experimental for now.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class ux_t3lib_sqlengine extends t3lib_sqlengine {

	/*************************
	 *
	 * Compiling queries
	 *
	 *************************/

	/**
	 * Compiles an SQL query from components
	 *
	 * @param	array		Array of SQL query components
	 * @return	string		SQL query
	 * @see parseSQL()
	 */
	function compileSQL($components)	{

		switch($components['type'])	{
			case 'SELECT':
				$query = $this->compileSELECT($components);
				break;
			case 'UPDATE':
				$query = $this->compileUPDATE($components);
				break;
			case 'INSERT':
				$query = $this->compileINSERT($components);
				break;
			case 'DELETE':
				$query = $this->compileDELETE($components);
				break;
			case 'EXPLAIN':
				$query = 'EXPLAIN '.$this->compileSELECT($components);
				break;
			case 'DROPTABLE':
				$query = $this->compileDROPTABLE($components);
				break;
			case 'CREATETABLE':
				$query = $this->compileCREATETABLE($components);
				break;
			case 'ALTERTABLE':
				$query = $this->compileALTERTABLE($components);
				break;
		}

		return $query;
	}


	function compileINSERT($components)	{
		switch((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type'])	{
			case 'native':
				parent::compileINSERT($components);
				break;
			case 'adodb':
				if(isset($components['VALUES_ONLY']) && is_array($components['VALUES_ONLY'])) {
					$fields = $GLOBALS['TYPO3_DB']->cache_fieldType[$components['TABLE']];
					$fc = 0;
					foreach($fields as $fn => $fd) {
						$query[$fn] = $components['VALUES_ONLY'][$fc++][0];
					}
				}
				break;
		}

		return $query;
	}

	function compileDROPTABLE($components)	{
		switch((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type'])	{
			case 'native':
				$query = 'DROP TABLE'.($components['ifExists']?' IF EXISTS':'').' '.$components['TABLE'];
				break;
			case 'adodb':
				$query = $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE'])]->DataDictionary->DropTableSQL('`'.$components['TABLE'].'`');
				break;
		}

		return $query;
	}

	/**
	 * Compiles a CREATE TABLE statement from components array
	 *
	 * @param	array		Array of SQL query components
	 * @return	array		array with SQL CREATE TABLE/INDEX command(s)
	 * @see parseCREATETABLE()
	 */
	function compileCREATETABLE($components)	{
			// Execute query (based on handler derived from the TABLE name which we actually know for once!)
		switch((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE'])]['type'])	{
			case 'native':
				$query[] = parent::compileCREATETABLE($components);
				break;
			case 'adodb':
					// Create fields and keys:
				$fieldsKeys = array();
				$indexKeys = array();

				foreach($components['FIELDS'] as $fN => $fCfg)	{
						// the backticks get converted to the correct quote char automatically
					$fieldsKeys[$fN] = '`'.$fN.'` '.$this->compileFieldCfg($fCfg['definition'],$fN);
				}

				if(isset($components['KEYS']) && is_array($components['KEYS'])) {
					foreach($components['KEYS'] as $kN => $kCfg)	{
						if ($kN == 'PRIMARYKEY')	{
							foreach($kCfg as $n => $field)	{
								$fieldsKeys[$field] .= ' PRIMARY';
							}
						} elseif ($kN == 'UNIQUE')	{
							foreach($kCfg as $n => $field)	{
								$indexKeys = array_merge($indexKeys, $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE'])]->DataDictionary->CreateIndexSQL($n, $components['TABLE'], $field, array('UNIQUE')));
							}
						} else {
							$indexKeys = array_merge($indexKeys, $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE'])]->DataDictionary->CreateIndexSQL($components['TABLE'].'_'.$kN, $components['TABLE'], $kCfg));
						}
					}
				}

					// Fetch table/index generation query:
				$query = array_merge($GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->lastHandlerKey]->DataDictionary->CreateTableSQL('`'.$components['TABLE'].'`',implode(','.chr(10), $fieldsKeys)), $indexKeys);
				break;
		}

		return $query;
	}

	function compileALTERTABLE($components)	{
			// Execute query (based on handler derived from the TABLE name which we actually know for once!)
		switch((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type'])	{
			case 'native':
				$query[] = parent::compileALTERTABLE($components);
				break;
			case 'adodb':
				switch(strtoupper(str_replace(array(" ","\n","\r","\t"),'',$components['action'])))	{
					case 'ADD':
						$query = $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->lastHandlerKey]->DataDictionary->AddColumnSQL('`'.$components['TABLE'].'`','`'.$components['FIELD'].'` '.$this->compileFieldCfg($components['definition']));
						break;
					case 'CHANGE':
						$query = $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->lastHandlerKey]->DataDictionary->AlterColumnSQL('`'.$components['TABLE'].'`','`'.$components['FIELD'].'` '.$this->compileFieldCfg($components['definition']));
						break;
					case 'DROP':
					case 'DROPKEY':
						break;
					case 'ADDKEY':
					case 'ADDPRIMARYKEY':
						$query.=' ('.implode(',',$components['fields']).')';
						break;
				}
				break;
		}

		return $query;
	}

	/**
	 * Compile field definition
	 *
	 * @param	array		Field definition parts
	 * @return	string		Field definition string
	 */
	function compileFieldCfg($fieldCfg,$fN='')	{
		switch((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type'])	{
			case 'native':
				$cfg = parent::compileFieldCfg($fieldCfg,$fN);
				break;
			case 'adodb':
					// Set type:
				$cfg = $GLOBALS['TYPO3_DB']->MySQLMetaType($fieldCfg['fieldType']);

					// Add value, if any:
				if (strlen($fieldCfg['value']) && (in_array($cfg, array('C','C2'))))	{
					$cfg.=' '.$fieldCfg['value'];
				} elseif (!isset($fieldCfg['value']) && (in_array($cfg, array('C','C2')))) {
					$cfg .= ' 255'; // add 255 as length for varchar without specified length (e.g. coming from tinytext, tinyblob)
				}

					// Add additional features:
				if (is_array($fieldCfg['featureIndex']))	{

						// MySQL assigns DEFAULT value automatically if NOT NULL, fake this here
					if(isset($fieldCfg['featureIndex']['NOTNULL']) && !isset($fieldCfg['featureIndex']['DEFAULT']) && !isset($fieldCfg['featureIndex']['AUTO_INCREMENT'])) {
						$fieldCfg['featureIndex']['DEFAULT'] = array('keyword' => 'DEFAULT', 'value' => array('','\''));
					}

					foreach($fieldCfg['featureIndex'] as $featureDef)	{
							// unsigned only for mysql, as it is mysql specific
						if($featureDef['keyword'] == 'unsigned' && !strstr($GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['config']['driver'],'mysql')) {
							continue;
						}
							// auto_increment is removed, it is handled by (emulated) sequences
						if($featureDef['keyword'] == 'auto_increment') {
							continue;
						}
							// NOT NULL only if there is no default value, as this conflicts
						if($featureDef['keyword'] == 'NOT NULL') {
							if($this->checkEmptyDefaultValue($fieldCfg['featureIndex'])) continue; // we do not have a default value or it is an empty string
							else $cfg.=' NOTNULL';
						}

						$cfg.=' '.$featureDef['keyword'];

							// Add value if found:
						if (is_array($featureDef['value']))	{
							if(!is_numeric($featureDef['value'][0]) && empty($featureDef['value'][0])) {
								$cfg .= ' "\'\'"';
							} else {
								$cfg.=' '.$featureDef['value'][1].$this->compileAddslashes($featureDef['value'][0]).$featureDef['value'][1];
							}
						}
					}
				}
				$cfg .= ' NOQUOTE';
				break;
		}

			// Return field definition string:
		return $cfg;
	}

	function checkEmptyDefaultValue($featureIndex) {
		if (is_array($featureIndex['DEFAULT']['value']))	{
			if(!is_numeric($featureIndex['DEFAULT']['value'][0]) && empty($featureIndex['DEFAULT']['value'][0])) {
				return true;
			} else {
				return false;
			}
		}
		return true;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_t3lib_sqlengine.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_t3lib_sqlengine.php']);
}
?>
