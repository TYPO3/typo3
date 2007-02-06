<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Class to setup values in localconf.php and verify the TYPO3 DB tables/fields
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
 *   83: class t3lib_install
 *  108:     function t3lib_install()
 *
 *              SECTION: Writing to localconf.php
 *  132:     function setValueInLocalconfFile(&$line_array, $variable, $value)
 *  183:     function writeToLocalconf_control($inlines='',$absFullPath='')
 *  253:     function checkForBadString($string)
 *  266:     function slashValueForSingleDashes($value)
 *
 *              SECTION: SQL
 *  291:     function getFieldDefinitions_sqlContent($sqlContent)
 *  359:     function getFieldDefinitions_sqlContent_parseTypes(&$total)
 *  406:     function getFieldDefinitions_database()
 *  450:     function getDatabaseExtra($FDsrc, $FDcomp, $onlyTableList='')
 *  496:     function getUpdateSuggestions($diffArr,$keyList='extra,diff')
 *  589:     function assembleFieldDefinition($row)
 *  611:     function getStatementArray($sqlcode,$removeNonSQL=0,$query_regex='')
 *  649:     function getCreateTables($statements, $insertCountFlag=0)
 *  683:     function getTableInsertStatements($statements, $table)
 *  704:     function performUpdateQueries($arr,$keyArr)
 *  720:     function getListOfTables()
 *  736:     function generateUpdateDatabaseForm_checkboxes($arr,$label,$checked=1,$iconDis=0,$currentValue=array(),$cVfullMsg=0)
 *
 * TOTAL FUNCTIONS: 17
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */








require_once(PATH_t3lib.'class.t3lib_sqlparser.php');

/**
 * Class to setup values in localconf.php and verify the TYPO3 DB tables/fields
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_install {


		// External, Static
	var $updateIdentity = '';					// Set to string which identifies the script using this class.
	var $deletedPrefixKey = 'zzz_deleted_';		// Prefix used for tables/fields when deleted/renamed.
	var $dbUpdateCheckboxPrefix = 'TYPO3_INSTALL[database_update]';	// Prefix for checkbox fields when updating database.
	var $localconf_addLinesOnly = 0;			// If this is set, modifications to localconf.php is done by adding new lines to the array only. If unset, existing values are recognized and changed.
	var $localconf_editPointToken = 'INSTALL SCRIPT EDIT POINT TOKEN - all lines after this points may be changed by the install script!';		// If set and addLinesOnly is disabled, lines will be change only if they are after this token (on a single line!) in the file
	var $allowUpdateLocalConf = 0;		// If true, this class will allow the user to update the localconf.php file. Is set true in the init.php file.
	var $backPath = '../';				// Backpath (used for icons etc.)

	var $multiplySize = 1;				// Multiplier of SQL field size (for char, varchar and text fields)

		// Internal, dynamic:
	var $setLocalconf = 0;				// Used to indicate that a value is change in the line-array of localconf and that it should be written.
	var $messages = array();			// Used to set (error)messages from the executing functions like mail-sending, writing Localconf and such
	var $touchedLine = 0;				// updated with line in localconf.php file that was changed.


	/**
	 * Constructor function
	 *
	 * @return	void
	 */
	function t3lib_install()	{
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['multiplyDBfieldSize']>= 1 && $GLOBALS['TYPO3_CONF_VARS']['SYS']['multiplyDBfieldSize']<=5)	{
			$this->multiplySize = (double)$GLOBALS['TYPO3_CONF_VARS']['SYS']['multiplyDBfieldSize'];
		}
	}



	/**************************************
	 *
	 * Writing to localconf.php
	 *

	 **************************************/

	/**
	 * This functions takes an array with lines from localconf.php, finds a variable and inserts the new value.
	 *
	 * @param	array		$line_array	the localconf.php file exploded into an array by linebreaks. (see writeToLocalconf_control())
	 * @param	string		$variable	The variable name to find and substitute. This string must match the first part of a trimmed line in the line-array. Matching is done backwards so the last appearing line will be substituted.
	 * @param	string		$value		Is the value to be insert for the variable
	 * @return	void
	 * @see writeToLocalconf_control()
	 */
	function setValueInLocalconfFile(&$line_array, $variable, $value)	{
		if (!$this->checkForBadString($value))	return 0;

			// Initialize:
		$found = 0;
		$this->touchedLine = '';
		$commentKey = '## ';
		$inArray = in_array($commentKey.$this->localconf_editPointToken,$line_array);
		$tokenSet = ($this->localconf_editPointToken && !$inArray);		// Flag is set if the token should be set but is not yet...
		$stopAtToken = ($this->localconf_editPointToken && $inArray);
		$comment = ' Modified or inserted by '.$this->updateIdentity.'.';

			// Search for variable name:
		if (!$this->localconf_addLinesOnly && !$tokenSet)	{
			$line_array = array_reverse($line_array);
			foreach($line_array as $k => $v)	{
				$v2 = trim($v);
				if ($stopAtToken && !strcmp($v2,$commentKey.$this->localconf_editPointToken))	break;		// If stopAtToken and token found, break out of the loop..
				if (!strcmp(substr($v2,0,strlen($variable.' ')),$variable.' '))	{
					$mainparts = explode($variable,$v,2);
					if (count($mainparts)==2)	{	// should ALWAYS be....
						$subparts = explode('//',$mainparts[1],2);
						$line_array[$k] = $mainparts[0].$variable." = '".$this->slashValueForSingleDashes($value)."';	".('//'.$comment.str_replace($comment,'',$subparts[1]));
						$this->touchedLine = count($line_array)-$k-1;
						$found = 1;
						break;
					}
				}
			}
			$line_array = array_reverse($line_array);
		}
		if (!$found)	{
			if ($tokenSet)		{
				$line_array[] = $commentKey.$this->localconf_editPointToken;
				$line_array[] = '';
			}
			$line_array[] = $variable." = '".$this->slashValueForSingleDashes($value)."';	// ".$comment;
			$this->touchedLine = -1;
		}
		$this->messages[] = $variable." = '".htmlspecialchars($value)."'";
		$this->setLocalconf = 1;
	}

	/**
	 * Writes or returns lines from localconf.php
	 *
	 * @param	array		Array of lines to write back to localconf.php. Possibly
	 * @param	string		Absolute path of alternative file to use (Notice: this path is not validated in terms of being inside 'TYPO3 space')
	 * @return	mixed		If $inlines is not an array it will return an array with the lines from localconf.php. Otherwise it will return a status string, either "continue" (updated) or "nochange" (not updated)
	 * @see setValueInLocalconfFile()
	 */
	function writeToLocalconf_control($inlines='',$absFullPath='')	{
		$tmpExt = '.TMP.php';
		$writeToLocalconf_dat['file'] = $absFullPath ? $absFullPath : PATH_typo3conf.'localconf.php';
		$writeToLocalconf_dat['tmpfile'] = $writeToLocalconf_dat['file'].$tmpExt;

			// Checking write state of localconf.php:
		if (!$this->allowUpdateLocalConf)	{
			die('->allowUpdateLocalConf flag in the install object is not set and therefore "localconf.php" cannot be altered.');
		}
		if (!@is_writable($writeToLocalconf_dat['file']))	{
			die($writeToLocalconf_dat['file'].' is not writable!');
		}

				// Splitting localconf.php file into lines:
		$lines = explode(chr(10),str_replace(chr(13),'',trim(t3lib_div::getUrl($writeToLocalconf_dat['file']))));
		$writeToLocalconf_dat['endLine'] = array_pop($lines);	// Getting "? >" ending.

			// Checking if "updated" line was set by this tool - if so remove old line.
		$updatedLine = array_pop($lines);
		$writeToLocalconf_dat['updatedText'] = '// Updated by '.$this->updateIdentity.' ';

		if (!strstr($updatedLine, $writeToLocalconf_dat['updatedText']))	{
			array_push($lines,$updatedLine);
		}

		if (is_array($inlines))	{	// Setting a line and write:
				// Setting configuration
			$updatedLine = $writeToLocalconf_dat['updatedText'].date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'].' H:i:s');
			array_push($inlines,$updatedLine);
			array_push($inlines,$writeToLocalconf_dat['endLine']);

			if ($this->setLocalconf)	{
				$success = FALSE;
				if (!t3lib_div::writeFile($writeToLocalconf_dat['tmpfile'],implode(chr(10),$inlines)))	{
					$msg = 'typo3conf/localconf.php'.$tmpExt.' could not be written - maybe a write access problem?';
				}
				elseif (strcmp(t3lib_div::getUrl($writeToLocalconf_dat['tmpfile']), implode(chr(10),$inlines)))	{
					@unlink($writeToLocalconf_dat['tmpfile']);
					$msg = 'typo3conf/localconf.php'.$tmpExt.' was NOT written properly (written content didn\'t match file content) - maybe a disk space problem?';
				}
				elseif (!@copy($writeToLocalconf_dat['tmpfile'],$writeToLocalconf_dat['file']))	{
					$msg = 'typo3conf/localconf.php could not be replaced by typo3conf/localconf.php'.$tmpExt.' - maybe a write access problem?';
				}
				else {
					@unlink($writeToLocalconf_dat['tmpfile']);
					$success = TRUE;
					$msg = 'Configuration written to typo3conf/localconf.php';
				}
				$this->messages[] = $msg;

				if ($success)	{
					return 'continue';
				} else {
					t3lib_div::sysLog($msg, 'Core', 3);
					return 'nochange';
				}
			} else {
				return 'nochange';
			}
		} else {	// Return lines found in localconf.php
			return $lines;
		}
	}

	/**
	 * Checking for linebreaks in the string
	 *
	 * @param	string		String to test
	 * @return	boolean		Returns TRUE if string is OK
	 * @see setValueInLocalconfFile()
	 */
	function checkForBadString($string)	{
		return preg_match('/['.chr(10).chr(13).']/',$string) ? FALSE : TRUE;
	}

	/**
	 * Replaces ' with \' and \ with \\
	 *
	 * @param	string		Input value
	 * @return	string		Output value
	 * @see setValueInLocalconfFile()
	 */
	function slashValueForSingleDashes($value)	{
		$value = str_replace("'.chr(10).'", '###INSTALL_TOOL_LINEBREAK###', $value);
		$value = str_replace("'","\'",str_replace('\\','\\\\',$value));
		$value = str_replace('###INSTALL_TOOL_LINEBREAK###', "'.chr(10).'", $value);

		return $value;
	}










	/*************************************
	 *
	 * SQL
	 *
	 *************************************/

	/**
	 * Reads the field definitions for the input sql-file string
	 *
	 * @param	string		$sqlContent: Should be a string read from an sql-file made with 'mysqldump [database_name] -d'
	 * @return	array		Array with information about table.
	 */
	function getFieldDefinitions_sqlContent($sqlContent)	{
		$lines = t3lib_div::trimExplode(chr(10), $sqlContent,1);
		$isTable = '';
		$total = Array();

		foreach($lines as $value)	{
			if ($value[0]!='#')	{
				if (!$isTable)	{
					$parts = explode(' ',$value);
					if ($parts[0]=='CREATE' && $parts[1]=='TABLE')	{
						$isTable = str_replace( '`', '', $parts[2]);
						if (TYPO3_OS=='WIN') { 	// tablenames are always lowercase on windows!
							$isTable = strtolower($isTable);
						}
					}
				} else {
					if (substr($value,0,1)==')' && substr($value,-1)==';')	{
						$ttype = array();
						preg_match('/(ENGINE|TYPE)=([a-zA-Z]*)/',$value,$ttype);
						$total[$isTable]['extra']['ttype'] = $ttype[2];
						$isTable = '';
					} else {
						$lineV = preg_replace('/,$/','',$value);
						$lineV = str_replace('UNIQUE KEY', 'UNIQUE', $lineV);
						$parts = explode(' ',$lineV,2);

							// Make sure there is no default value when auto_increment is set
						if(stristr($parts[1],'auto_increment'))	{
							$parts[1] = preg_replace('/ default \'0\'/i','',$parts[1]);
						}
							// "default" is always lower-case
						if(strstr($parts[1], ' DEFAULT '))	{
							$parts[1] = str_replace(' DEFAULT ', ' default ', $parts[1]);
						}

							// Change order of "default" and "null" statements
						$parts[1] = preg_replace('/(.*) (default .*) (NOT NULL)/', '$1 $3 $2', $parts[1]);
						$parts[1] = preg_replace('/(.*) (default .*) (NULL)/', '$1 $3 $2', $parts[1]);

							// Remove double blanks
						$parts[1] = preg_replace('/([^ ]+)[ ]+([^ ]+)/', '$1 $2', $parts[1]);

						if ($parts[0]!='PRIMARY' && $parts[0]!='KEY' && $parts[0]!='UNIQUE')	{
							$key = str_replace('`', '', $parts[0]);
							$total[$isTable]['fields'][$key] = $parts[1];
						} else {	// Process keys
							$newParts = explode(' ',$parts[1],2);
							$key = str_replace('`', '', ($parts[0]=='PRIMARY'?$parts[0]:$newParts[0]));
							$lineV = str_replace('`', '', $lineV);
							$total[$isTable]['keys'][$key] = $lineV;
						}
					}
				}
			}
		}

		$this->getFieldDefinitions_sqlContent_parseTypes($total);
		return $total;
	}

	/**
	 * Multiplies varchars/tinytext fields in size according to $this->multiplySize
	 * Useful if you want to use UTF-8 in the database and needs to extend the field sizes in the database so UTF-8 chars are not discarded. For most charsets available as single byte sets, multiplication with 2 should be enough. For chinese, use 3.
	 *
	 * @param	array		Total array (from getFieldDefinitions_sqlContent())
	 * @return	void
	 * @access private
	 * @see getFieldDefinitions_sqlContent()
	 */
	function getFieldDefinitions_sqlContent_parseTypes(&$total)	{

		$mSize = (double)$this->multiplySize;
		if ($mSize > 1)	{

				// Init SQL parser:
			$sqlParser = t3lib_div::makeInstance('t3lib_sqlparser');
			foreach($total as $table => $cfg)	{
				if (is_array($cfg['fields'])) {
					foreach($cfg['fields'] as $fN => $fType)	{
						$orig_fType = $fType;
						$fInfo = $sqlParser->parseFieldDef($fType);

						switch($fInfo['fieldType'])	{
							case 'char':
							case 'varchar':
								$newSize = round($fInfo['value']*$mSize);

								if ($newSize <= 255)	{
									$fInfo['value'] = $newSize;
								} else {
									$fInfo = array(
										'fieldType' => 'text',
										'featureIndex' => array(
											'NOTNULL' => array(
												'keyword' => 'NOT NULL'
											)
										)
									);
										// Change key definition if necessary (must use "prefix" on TEXT columns)
									foreach ($cfg['keys'] as $kN => $kType) {
										$match = array();
										preg_match('/^([^(]*)\(([^)]+)\)(.*)/', $kType, $match);
										$keys = array();
										foreach (t3lib_div::trimExplode(',',$match[2]) as $kfN) {
											if ($fN == $kfN)	{
												$kfN .= '('.$newSize.')';
											}
											$keys[] = $kfN;
										}
										$total[$table]['keys'][$kN] = $match[1].'('.join(',',$keys).')'.$match[3];
									}
								}
							break;
							case 'tinytext':
								$fInfo['fieldType'] = 'text';
							break;
						}

						$total[$table]['fields'][$fN] = $sqlParser->compileFieldCfg($fInfo);
						if ($sqlParser->parse_error)	die($sqlParser->parse_error);
					}
				}
			}
		}
	}

	/**
	 * Reads the field definitions for the current database
	 *
	 * @return	array		Array with information about table.
	 */
	function getFieldDefinitions_database()	{
		$total = array();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
		echo $GLOBALS['TYPO3_DB']->sql_error();

		$tables = $GLOBALS['TYPO3_DB']->admin_get_tables(TYPO3_db);
		foreach($tables as $tableName)	{

				// Fields:
			$fieldInformation = $GLOBALS['TYPO3_DB']->admin_get_fields($tableName);
			foreach($fieldInformation as $fN => $fieldRow)	{
				$total[$tableName]['fields'][$fN] = $this->assembleFieldDefinition($fieldRow);
			}

				// Keys:
			$keyInformation = $GLOBALS['TYPO3_DB']->admin_get_keys($tableName);
			foreach($keyInformation as $kN => $keyRow)	{
				$tempKeys[$tableName][$keyRow['Key_name']][$keyRow['Seq_in_index']] = $keyRow['Column_name'];
				if ($keyRow['Sub_part'])	{
					$tempKeys[$tableName][$keyRow['Key_name']][$keyRow['Seq_in_index']].= '('.$keyRow['Sub_part'].')';
				}
				if ($keyRow['Key_name']=='PRIMARY')	{
					$tempKeysPrefix[$tableName][$keyRow['Key_name']] = 'PRIMARY KEY';
				} else {
					if ($keyRow['Non_unique'])	{
						$tempKeysPrefix[$tableName][$keyRow['Key_name']] = 'KEY';
					} else {
						$tempKeysPrefix[$tableName][$keyRow['Key_name']] = 'UNIQUE';
					}
					$tempKeysPrefix[$tableName][$keyRow['Key_name']].= ' '.$keyRow['Key_name'];
				}
			}
		}

			// Compile information:
		if (is_array($tempKeys))	{
			foreach($tempKeys as $table => $keyInf) {
				foreach($keyInf as $kName => $index) {
					ksort($index);
					$total[$table]['keys'][$kName] = $tempKeysPrefix[$table][$kName].' ('.implode(',',$index).')';
				}
			}
		}

		return $total;
	}

	/**
	 * Compares two arrays with field information and returns information about fields that are MISSING and fields that have CHANGED.
	 * FDsrc and FDcomp can be switched if you want the list of stuff to remove rather than update.
	 *
	 * @param	array		Field definitions, source (from getFieldDefinitions_sqlContent())
	 * @param	array		Field definitions, comparison. (from getFieldDefinitions_database())
	 * @param	string		Table names (in list) which is the ONLY one observed.
	 * @param	boolean		If set, this function ignores NOT NULL statements of the sql file field definition when comparing current field definition from database with field definition from sql file. This way, NOT NULL statements will be executed when the field is initially created, but the sql parser will never complain about missing NOT NULL statements afterwards.
	 * @return	array		Returns an array with 1) all elements from $FSsrc that is not in $FDcomp (in key 'extra') and 2) all elements from $FSsrc that is difference from the ones in $FDcomp
	 */
	function getDatabaseExtra($FDsrc, $FDcomp, $onlyTableList='',$ignoreNotNullWhenComparing=true)	{
		$extraArr = array();
		$diffArr = array();

		if (is_array($FDsrc))	{
			foreach($FDsrc as $table => $info) {
				if (!strlen($onlyTableList) || t3lib_div::inList($onlyTableList, $table))	{
					if (!isset($FDcomp[$table]))	{
						$extraArr[$table] = $info;		// If the table was not in the FDcomp-array, the result array is loaded with that table.
						$extraArr[$table]['whole_table']=1;
					} else {
						$keyTypes = explode(',','fields,keys');
						foreach($keyTypes as $theKey)	{
							if (is_array($info[$theKey]))	{
								foreach($info[$theKey] as $fieldN => $fieldC)	{
									$fieldN = str_replace('`','',$fieldN);
									if (!isset($FDcomp[$table][$theKey][$fieldN]))	{
										$extraArr[$table][$theKey][$fieldN] = $fieldC;
									} elseif (strcmp($FDcomp[$table][$theKey][$fieldN], $ignoreNotNullWhenComparing?str_replace(' NOT NULL', '', $fieldC):$fieldC))	{
										$diffArr[$table][$theKey][$fieldN] = $fieldC;
										$diffArr_cur[$table][$theKey][$fieldN] = $FDcomp[$table][$theKey][$fieldN];
									}
								}
							}
						}
					}
				}
			}
		}

		$output = array(
			'extra' => $extraArr,
			'diff' => $diffArr,
			'diff_currentValues' => $diffArr_cur
		);

		return $output;
	}

	/**
	 * Returns an array with SQL-statements that is needed to update according to the diff-array
	 *
	 * @param	array		Array with differences of current and needed DB settings. (from getDatabaseExtra())
	 * @param	string		List of fields in diff array to take notice of.
	 * @return	array		Array of SQL statements (organized in keys depending on type)
	 */
	function getUpdateSuggestions($diffArr,$keyList='extra,diff')	{
		$statements = array();
		$deletedPrefixKey = $this->deletedPrefixKey;
		$remove = 0;
		if ($keyList == 'remove')	{
			$remove = 1;
			$keyList = 'extra';
		}
		$keyList = explode(',',$keyList);
		foreach($keyList as $theKey)	{
			if (is_array($diffArr[$theKey]))	{
				foreach($diffArr[$theKey] as $table => $info) {
					$whole_table = array();
					if (is_array($info['fields']))	{
						foreach($info['fields'] as $fN => $fV) {
							if ($info['whole_table'])	{
								$whole_table[]=$fN.' '.$fV;
							} else {
								if ($theKey=='extra')	{
									if ($remove)	{
										if (substr($fN,0,strlen($deletedPrefixKey))!=$deletedPrefixKey)	{
											$statement = 'ALTER TABLE '.$table.' CHANGE '.$fN.' '.$deletedPrefixKey.$fN.' '.$fV.';';
											$statements['change'][md5($statement)] = $statement;
										} else {
											$statement = 'ALTER TABLE '.$table.' DROP '.$fN.';';
											$statements['drop'][md5($statement)] = $statement;
										}
									} else {
										$statement = 'ALTER TABLE '.$table.' ADD '.$fN.' '.$fV.';';
										$statements['add'][md5($statement)] = $statement;
									}
								} elseif ($theKey=='diff')	{
									$statement = 'ALTER TABLE '.$table.' CHANGE '.$fN.' '.$fN.' '.$fV.';';
									$statements['change'][md5($statement)] = $statement;
									$statements['change_currentValue'][md5($statement)] = $diffArr['diff_currentValues'][$table]['fields'][$fN];
								}
							}
						}
					}
					if (is_array($info['keys']))	{
						foreach($info['keys'] as $fN => $fV) {
							if ($info['whole_table'])	{
								$whole_table[] = $fV;
							} else {
								if ($theKey=='extra')	{
									if ($remove)	{
										$statement = 'ALTER TABLE '.$table.($fN=='PRIMARY' ? ' DROP PRIMARY KEY' : ' DROP KEY '.$fN).';';
										$statements['drop'][md5($statement)] = $statement;
									} else {
										$statement = 'ALTER TABLE '.$table.' ADD '.$fV.';';
										$statements['add'][md5($statement)] = $statement;
									}
								} elseif ($theKey=='diff') {
									$statement = 'ALTER TABLE '.$table.($fN=='PRIMARY' ? ' DROP PRIMARY KEY' : ' DROP KEY '.$fN).';';
									$statements['change'][md5($statement)] = $statement;
									$statement = 'ALTER TABLE '.$table.' ADD '.$fV.';';
									$statements['change'][md5($statement)] = $statement;
								}
							}
						}
					}
					if ($info['whole_table'])	{
						if ($remove)	{
							if (substr($table,0,strlen($deletedPrefixKey))!=$deletedPrefixKey)	{
								$statement = 'ALTER TABLE '.$table.' RENAME '.$deletedPrefixKey.$table.';';
								$statements['change_table'][md5($statement)]=$statement;
							} else {
								$statement = 'DROP TABLE '.$table.';';
								$statements['drop_table'][md5($statement)]=$statement;
							}
							// count:
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', $table, '');
							list($count) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
							$statements['tables_count'][md5($statement)] = $count?'Records in table: '.$count:'';
						} else {
							$statement = 'CREATE TABLE '.$table." (\n".implode(",\n",$whole_table)."\n)";
							$statement .= ($info['extra']['ttype']) ? ' TYPE='.$info['extra']['ttype'].';' : ';';
							$statements['create_table'][md5($statement)]=$statement;
						}
					}
				}
			}
		}

		return $statements;
	}

	/**
	 * Converts a result row with field information into the SQL field definition string
	 *
	 * @param	array		MySQL result row.
	 * @return	string		Field definition
	 */
	function assembleFieldDefinition($row)	{
		$field[] = $row['Type'];
		// if (!$row['Null'])	{ $field[] = 'NOT NULL'; }
		if (!strstr($row['Type'],'blob') && !strstr($row['Type'],'text'))	{
				// Add a default value if the field is not auto-incremented (these fields never have a default definition).
			if (!stristr($row['Extra'],'auto_increment'))	{
				$field[] = 'default '."'".(addslashes($row['Default']))."'";
			}
		}
		if ($row['Extra'])	{ $field[] = $row['Extra']; }

		return implode(' ',$field);
	}

	/**
	 * Returns an array where every entry is a single sql-statement. Input must be formatted like an ordinary MySQL-dump files
	 *
	 * @param	string		$sqlcode	The sql-file content. Provided that 1) every query in the input is ended with ';' and that a line in the file contains only one query or a part of a query.
	 * @param	boolean		If set, non-sql (like comments and blank lines) are not included in the final product)
	 * @param	string		Regex to filter SQL lines to include.
	 * @return	array		Array of SQL statements.
	 */
	function getStatementArray($sqlcode,$removeNonSQL=0,$query_regex='')	{
		$sqlcodeArr = explode(chr(10),$sqlcode);

		// Based on the assumption that the sql-dump has
		$statementArray = array();
		$statementArrayPointer = 0;

		foreach($sqlcodeArr as $line => $linecontent)	{
			$is_set = 0;
			if(stristr($linecontent,'auto_increment')) {
				$linecontent = eregi_replace(' default \'0\'','',$linecontent);
			}

			if (!$removeNonSQL || (strcmp(trim($linecontent),'') && substr(trim($linecontent),0,1)!='#' && substr(trim($linecontent),0,2)!='--'))	{		// '--' is seen as mysqldump comments from server version 3.23.49
				$statementArray[$statementArrayPointer].= $linecontent;
				$is_set = 1;
			}
			if (substr(trim($linecontent),-1)==';')	{
				if (isset($statementArray[$statementArrayPointer]))	{
					if (!trim($statementArray[$statementArrayPointer]) || ($query_regex && !eregi($query_regex,trim($statementArray[$statementArrayPointer]))))	{
						unset($statementArray[$statementArrayPointer]);
					}
				}
				$statementArrayPointer++;
			} elseif ($is_set) {
				$statementArray[$statementArrayPointer].=chr(10);
			}
		}
		return $statementArray;
	}

	/**
	 * Returns tables to create and how many records in each
	 *
	 * @param	array		Array of SQL statements to analyse.
	 * @param	boolean		If set, will count number of INSERT INTO statements following that table definition
	 * @return	array		Array with table definitions in index 0 and count in index 1
	 */
	function getCreateTables($statements, $insertCountFlag=0)	{
		$crTables = array();
		$insertCount = array();
		foreach($statements as $line => $linecontent)	{
			$reg = array();
			if (eregi('^create[[:space:]]*table[[:space:]]*[`]?([[:alnum:]_]*)[`]?',substr($linecontent,0,100),$reg))	{
				$table = trim($reg[1]);
				if ($table)	{
					if (TYPO3_OS=='WIN')	{ $table=strtolower($table); }	// table names are always lowercase on Windows!
					$sqlLines = explode(chr(10), $linecontent);
					foreach($sqlLines as $k=>$v)	{
						if(stristr($v,'auto_increment')) {
							$sqlLines[$k] = eregi_replace(' default \'0\'','',$v);
						}
					}
					$linecontent = implode(chr(10), $sqlLines);
					$crTables[$table] = $linecontent;
				}
			} elseif ($insertCountFlag && eregi('^insert[[:space:]]*into[[:space:]]*[`]?([[:alnum:]_]*)[`]?',substr($linecontent,0,100),$reg))	{
				$nTable = trim($reg[1]);
				$insertCount[$nTable]++;
			}
		}

		return array($crTables,$insertCount);
	}

	/**
	 * Extracts all insert statements from $statement array where content is inserted into $table
	 *
	 * @param	array		Array of SQL statements
	 * @param	string		Table name
	 * @return	array		Array of INSERT INTO statements where table match $table
	 */
	function getTableInsertStatements($statements, $table)	{
		$outStatements=array();
		foreach($statements as $line => $linecontent)	{
			$reg = array();
			if (preg_match('/^insert[[:space:]]*into[[:space:]]*[`]?([[:alnum:]_]*)[`]?/i',substr($linecontent,0,100),$reg))	{
				$nTable = trim($reg[1]);
				if ($nTable && !strcmp($table,$nTable))	{
					$outStatements[]=$linecontent;
				}
			}
		}
		return $outStatements;
	}

	/**
	 * Performs the queries passed from the input array.
	 *
	 * @param	array		Array of SQL queries to execute.
	 * @param	array		Array with keys that must match keys in $arr. Only where a key in this array is set and true will the query be executed (meant to be passed from a form checkbox)
	 * @return	void
	 */
	function performUpdateQueries($arr,$keyArr)	{
		if (is_array($arr))	{
			foreach($arr as $key => $string)	{
				if (isset($keyArr[$key]) && $keyArr[$key])	{
					$GLOBALS['TYPO3_DB']->admin_query($string);
				}
			}
		}
	}

	/**
	 * Returns list of tables in the database
	 *
	 * @return	array		List of tables.
	 * @see t3lib_db::admin_get_tables()
	 */
	function getListOfTables()	{
		$whichTables = $GLOBALS['TYPO3_DB']->admin_get_tables(TYPO3_db);
		return $whichTables;
	}

	/**
	 * Creates a table which checkboxes for updating database.
	 *
	 * @param	array		Array of statements (key / value pairs where key is used for the checkboxes)
	 * @param	string		Label for the table.
	 * @param	boolean		If set, then checkboxes are set by default.
	 * @param	boolean		If set, then icons are shown.
	 * @param	array		Array of "current values" for each key/value pair in $arr. Shown if given.
	 * @param	boolean		If set, will show the prefix "Current value" if $currentValue is given.
	 * @return	string		HTML table with checkboxes for update. Must be wrapped in a form.
	 */
	function generateUpdateDatabaseForm_checkboxes($arr,$label,$checked=1,$iconDis=0,$currentValue=array(),$cVfullMsg=0)	{
		$out = array();
		if (is_array($arr))	{
			foreach($arr as $key => $string)	{
				$ico = '';
				if ($iconDis)	{
					if (stristr($string,' user_'))	{
						$ico.= '<img src="'.$this->backPath.'gfx/icon_warning.gif" width="18" height="16" align="top" alt="" /><strong>(USER) </strong>';
					}
					if (stristr($string,' app_'))	{
						$ico.= '<img src="'.$this->backPath.'gfx/icon_warning.gif" width="18" height="16" align="top" alt="" /><strong>(APP) </strong>';
					}
					if (stristr($string,' ttx_') || stristr($string,' tx_'))	{
						$ico.= '<img src="'.$this->backPath.'gfx/icon_warning.gif" width="18" height="16" align="top" alt="" /><strong>(EXT) </strong>';
					}
				}
				$out[]='
					<tr>
						<td valign="top"><input type="checkbox" id="db-'.$key.'" name="'.$this->dbUpdateCheckboxPrefix.'['.$key.']" value="1"'.($checked?' checked="checked"':'').' /></td>
						<td nowrap="nowrap"><label for="db-'.$key.'">'.nl2br($ico.htmlspecialchars($string)).'</label></td>
					</tr>';
				if (isset($currentValue[$key]))	{
					$out[]='
					<tr>
						<td valign="top"></td>
						<td nowrap="nowrap" style="color : #666666;">'.nl2br((!$cVfullMsg?"Current value: ":"").'<em>'.$currentValue[$key].'</em>').'</td>
					</tr>';
				}
			}

			// Compile rows:
			$content = '
				<!-- Update database fields / tables -->
				<h3>'.$label.'</h3>
				<table border="0" cellpadding="2" cellspacing="2" class="update-db-fields">'.implode('',$out).'
				</table>';
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_install.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_install.php']);
}
?>
