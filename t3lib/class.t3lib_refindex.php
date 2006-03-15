<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Reference index processing
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
 *   74: class t3lib_refindex
 *   91:     function updateRefIndexTable($table,$uid,$testOnly=FALSE)
 *  162:     function generateRefIndexData($table,$uid)
 *  235:     function createEntryData($table,$uid,$field,$flexpointer,$ref_table,$ref_uid,$ref_string='',$sort=-1,$softref_key='',$softref_id='')
 *  260:     function createEntryData_dbRels($table,$uid,$fieldname,$flexpointer,$items)
 *  276:     function createEntryData_fileRels($table,$uid,$fieldname,$flexpointer,$items)
 *  296:     function createEntryData_softreferences($table,$uid,$fieldname,$flexpointer,$keys)
 *
 *              SECTION: Get relations from table row
 *  351:     function getRelations($table,$row)
 *  456:     function getRelations_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2, $structurePath)
 *  503:     function getRelations_procFiles($value, $conf, $uid)
 *  553:     function getRelations_procDB($value, $conf, $uid)
 *
 *              SECTION: Helper functions
 *  590:     function isReferenceField($conf)
 *  600:     function destPathFromUploadFolder($folder)
 *  610:     function error($msg)
 *
 * TOTAL FUNCTIONS: 13
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_flexformtools.php');



/**
 * Reference index processing and relation extraction
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_refindex {

	var $temp_flexRelations = array();
	var $errorLog = array();
	var $WSOL = FALSE;
	var $relations = array();
	var $hashVersion = 1;	// Number which we can increase if a change in the code means we will have to force a re-generation of the index.


	/**
	 * Call this function to update the sys_refindex table for a record.
	 * NOTICE: Currently, references updated for a deleted-flagged record will not include those from within flexform fields in some cases where the data structure is defined by another record since the resolving process ignores deleted records! This will also result in bad cleaning up in tcemain I think... Anyway, thats the story of flexforms; as long as the DS can change, lots of references can get lost in no time.
	 *
	 * @param	string		Table name
	 * @param	uid		UID of record
	 * @param	boolean		If set, nothing will be written to the index but the result value will still report statistics on what as added, deleted and kept. Can be used for mere analysis.
	 * @return	array		Array with statistics about how many index records were added, deleted and not altered plus the complete reference set for the record.
	 */
	function updateRefIndexTable($table,$uid,$testOnly=FALSE)	{

			// First, secure that the index table is not updated with workspace tainted relations:
		$this->WSOL = FALSE;

			// Init:
		$result = array(
			'keptNodes' => 0,
			'deletedNodes' => 0,
			'addedNodes' => 0
		);

			// Get current index from Database:
		$currentRels = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table,'sys_refindex').
				' AND recuid='.intval($uid),
			'','','','hash'
		);

			// First, test to see if the record exists (being deleted also means it doesn't exist!)
		if (t3lib_BEfunc::getRecordRaw($table,'uid='.intval($uid),'uid'))	{

				// Then, get relations:
			$relations = $this->generateRefIndexData($table,$uid);

			if (is_array($relations))	{

					// Traverse the generated index:
				foreach($relations as $k => $datRec)	{
					$relations[$k]['hash'] = md5(implode('///',$relations[$k]).'///'.$this->hashVersion);

						// First, check if already indexed and if so, unset that row (so in the end we know which rows to remove!)
					if (isset($currentRels[$relations[$k]['hash']]))	{
						unset($currentRels[$relations[$k]['hash']]);
						$result['keptNodes']++;
						$relations[$k]['_ACTION'] = 'KEPT';
					} else {
							// If new, add it:
						if (!$testOnly)	$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_refindex',$relations[$k]);
						$result['addedNodes']++;
						$relations[$k]['_ACTION'] = 'ADDED';
					}
				}

				$result['relations'] = $relations;
			} else return FALSE;	// Weird mistake I would say...
		}

			// If any old are left, remove them:
		if (count($currentRels))	{
			$hashList = array_keys($currentRels);
			if (count($hashList))	{
				$result['deletedNodes'] = count($hashList);
				$result['deletedNodes_hashList'] = implode(',',$hashList);
				if (!$testOnly)	$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_refindex','hash IN ('.implode(',',$GLOBALS['TYPO3_DB']->fullQuoteArray($hashList,'sys_refindex')).')');
			}
		}

		return $result;
	}

	/**
	 * Returns array of arrays with an index of all references found in record from table/uid
	 * If the result is used to update the sys_refindex table then ->WSOL must NOT be true (no workspace overlay anywhere!)
	 *
	 * @param	string		Table name from $TCA
	 * @param	integer		Record UID
	 * @return	array		Index Rows
	 */
	function generateRefIndexData($table,$uid)	{
		global $TCA;

		if (isset($TCA[$table]))	{
				// Get raw record from DB:
			list($record) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',$table,'uid='.intval($uid));

			if (is_array($record))	{

					// Deleted:
				$deleted = $TCA[$table]['ctrl']['delete'] ? ($record[$TCA[$table]['ctrl']['delete']]?1:0) : 0;

					// Get all relations from record:
				$dbrels = $this->getRelations($table,$record);

					// Traverse those relations, compile records to insert in table:
				$this->relations = array();
				foreach($dbrels as $fieldname => $dat)	{

 						// Based on type,
 					switch((string)$dat['type'])	{
 						case 'db':
 							$this->createEntryData_dbRels($table,$uid,$fieldname,'',$deleted,$dat['itemArray']);
 						break;
 						case 'file':
 							$this->createEntryData_fileRels($table,$uid,$fieldname,'',$deleted,$dat['newValueFiles']);
 						break;
 						case 'flex':
								// DB references:
							if (is_array($dat['flexFormRels']['db']))	{
								foreach($dat['flexFormRels']['db'] as $flexpointer => $subList)	{
									$this->createEntryData_dbRels($table,$uid,$fieldname,$flexpointer,$deleted,$subList);
								}
							}
								// File references (NOT TESTED!)
							if (is_array($dat['flexFormRels']['file']))	{	// Not tested
								foreach($dat['flexFormRels']['file'] as $flexpointer => $subList)	{
									$this->createEntryData_fileRels($table,$uid,$fieldname,$flexpointer,$deleted,$subList);
								}
							}
								// Soft references in flexforms (NOT TESTED!)
							if (is_array($dat['flexFormRels']['softrefs']))	{
								foreach($dat['flexFormRels']['softrefs'] as $flexpointer => $subList)	{
									$this->createEntryData_softreferences($table,$uid,$fieldname,$flexpointer,$deleted,$subList['keys']);
								}
							}
 						break;
 					}

 						// Softreferences in the field:
 					if (is_array($dat['softrefs']))	{
 						$this->createEntryData_softreferences($table,$uid,$fieldname,'',$deleted,$dat['softrefs']['keys']);
 					}
				}

				return $this->relations;
			}
		}
	}

	/**
	 * Create array with field/value pairs ready to insert in database.
	 * The "hash" field is a fingerprint value across this table.
	 *
	 * @param	string		Tablename of source record (where reference is located)
	 * @param	integer		UID of source record (where reference is located)
	 * @param	string		Fieldname of source record (where reference is located)
	 * @param	string		Pointer to location inside flexform structure where reference is located in [field]
	 * @param	integer		Whether record is deleted-flagged or not
	 * @param	string		For database references; the tablename the reference points to. Special keyword "_FILE" indicates that "ref_string" is a file reference either absolute or relative to PATH_site. Special keyword "_STRING" indicates some special usage (typ. softreference) where "ref_string" is used for the value.
	 * @param	integer		For database references; The UID of the record (zero "ref_table" is "_FILE" or "_STRING")
	 * @param	string		For "_FILE" or "_STRING" references: The filepath (relative to PATH_site or absolute) or other string.
	 * @param	integer		The sorting order of references if many (the "group" or "select" TCA types). -1 if no sorting order is specified.
	 * @param	string		If the reference is a soft reference, this is the soft reference parser key. Otherwise empty.
	 * @param	string		Soft reference ID for key. Might be useful for replace operations.
	 * @return	array		Array record to insert into table.
	 */
	function createEntryData($table,$uid,$field,$flexpointer,$deleted,$ref_table,$ref_uid,$ref_string='',$sort=-1,$softref_key='',$softref_id='')	{
		return array(
			'tablename' => $table,
			'recuid' => $uid,
			'field' => $field,
			'flexpointer' => $flexpointer,
			'softref_key' => $softref_key,
			'softref_id' => $softref_id,
			'sorting' => $sort,
			'deleted' => $deleted,
			'ref_table' => $ref_table,
			'ref_uid' => $ref_uid,
			'ref_string' => $ref_string,
		);
	}

	/**
	 * Enter database references to ->relations array
	 *
	 * @param	string		[See createEntryData, arg 1]
	 * @param	integer		[See createEntryData, arg 2]
	 * @param	string		[See createEntryData, arg 3]
	 * @param	string		[See createEntryData, arg 4]
	 * @param	string		[See createEntryData, arg 5]
	 * @param	array		Data array with databaes relations (table/id)
	 * @return	void
	 */
	function createEntryData_dbRels($table,$uid,$fieldname,$flexpointer,$deleted,$items)	{
		foreach($items as $sort => $i)	{
			$this->relations[] = $this->createEntryData($table,$uid,$fieldname,$flexpointer,$deleted,$i['table'],$i['id'],'',$sort);
		}
	}

	/**
	 * Enter file references to ->relations array
	 *
	 * @param	string		[See createEntryData, arg 1]
	 * @param	integer		[See createEntryData, arg 2]
	 * @param	string		[See createEntryData, arg 3]
	 * @param	string		[See createEntryData, arg 4]
	 * @param	string		[See createEntryData, arg 5]
	 * @param	array		Data array with file relations
	 * @return	void
	 */
	function createEntryData_fileRels($table,$uid,$fieldname,$flexpointer,$deleted,$items)	{
		foreach($items as $sort => $i)	{
			$filePath = $i['ID_absFile'];
			if (t3lib_div::isFirstPartOfStr($filePath,PATH_site))	{
				$filePath = substr($filePath,strlen(PATH_site));
			}
			$this->relations[] = $this->createEntryData($table,$uid,$fieldname,$flexpointer,$deleted,'_FILE',0,$filePath,$sort);
		}
	}

	/**
	 * Enter softref references to ->relations array
	 *
	 * @param	string		[See createEntryData, arg 1]
	 * @param	integer		[See createEntryData, arg 2]
	 * @param	string		[See createEntryData, arg 3]
	 * @param	string		[See createEntryData, arg 4]
	 * @param	string		[See createEntryData, arg 5]
	 * @param	array		Data array with soft reference keys
	 * @return	void
	 */
	function createEntryData_softreferences($table,$uid,$fieldname,$flexpointer,$deleted,$keys)	{
		if (is_array($keys))	{
			foreach($keys as $spKey => $elements)	{
				if (is_array($elements))	{
					foreach($elements as $subKey => $el)	{
						if (is_array($el['subst']))	{
							switch((string)$el['subst']['type'])	{
								 case 'db':
								 	list($tableName,$recordId) = explode(':',$el['subst']['recordRef']);
								 	$this->relations[] = $this->createEntryData($table,$uid,$fieldname,$flexpointer,$deleted,$tableName,$recordId,'',-1,$spKey,$subKey);
								 break;
								 case 'file':
								 	$this->relations[] = $this->createEntryData($table,$uid,$fieldname,$flexpointer,$deleted,'_FILE',0,$el['subst']['relFileName'],-1,$spKey,$subKey);
								 break;
								 case 'string':
								 	$this->relations[] = $this->createEntryData($table,$uid,$fieldname,$flexpointer,$deleted,'_STRING',0,$el['subst']['tokenValue'],-1,$spKey,$subKey);
								 break;
							}
						}
					}
				}
			}
		}
	}















	/*******************************
	 *
	 * Get relations from table row
	 *
	 *******************************/

	/**
	 * Returns relation information for a $table/$row-array
	 * Traverses all fields in input row which are configured in TCA/columns
	 * It looks for hard relations to files and records in the TCA types "select" and "group"
	 *
	 * @param	string		Table
	 * @param	array		Row from table
	 * @return	array		Array with information about relations
	 * @see export_addRecord()
	 */
	function getRelations($table,$row)	{
		global $TCA;

			// Load full table description
		t3lib_div::loadTCA($table);

			// Initialize:
		$uid = $row['uid'];
		$nonFields = explode(',','uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,pid');

		$outRow = array();
		foreach($row as $field => $value)	{
			if (!in_array($field,$nonFields) && is_array($TCA[$table]['columns'][$field]))	{
				$conf = $TCA[$table]['columns'][$field]['config'];

					// Add files
				if ($result = $this->getRelations_procFiles($value, $conf, $uid))	{
						// Creates an entry for the field with all the files:
					$outRow[$field] = array(
						'type' => 'file',
						'newValueFiles' => $result,
					);
				}

					// Add DB:
				if ($result = $this->getRelations_procDB($value, $conf, $uid))	{
						// Create an entry for the field with all DB relations:
					$outRow[$field] = array(
						'type' => 'db',
						'itemArray' => $result,
					);
				}

					// For "flex" fieldtypes we need to traverse the structure looking for file and db references of course!
				if ($conf['type']=='flex')	{

						// Get current value array:
					$dataStructArray = t3lib_BEfunc::getFlexFormDS($conf, $row, $table,'',$this->WSOL);
					$currentValueArray = t3lib_div::xml2array($value);

						// Traversing the XML structure, processing files:
					if (is_array($currentValueArray))	{
						$this->temp_flexRelations = array(
							'db' => array(),
							'file' => array(),
							'softrefs' => array()
						);

							// Create and call iterator object:
						$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
						$flexObj->traverseFlexFormXMLData($table,$field,$row,$this,'getRelations_flexFormCallBack');

							// Create an entry for the field:
						$outRow[$field] = array(
							'type' => 'flex',
							'flexFormRels' => $this->temp_flexRelations,
						);
					}
				}

					// Soft References:
				if (strlen($value) && $softRefs = t3lib_BEfunc::explodeSoftRefParserList($conf['softref']))	{
					$softRefValue = $value;
					foreach($softRefs as $spKey => $spParams)	{
						$softRefObj = &t3lib_BEfunc::softRefParserObj($spKey);
						if (is_object($softRefObj))	{
							$resultArray = $softRefObj->findRef($table, $field, $uid, $softRefValue, $spKey, $spParams);
							if (is_array($resultArray))	{
								$outRow[$field]['softrefs']['keys'][$spKey] = $resultArray['elements'];
								if (strlen($resultArray['content'])) {
									$softRefValue = $resultArray['content'];
								}
							}
						}
					}

					if (is_array($outRow[$field]['softrefs']) && count($outRow[$field]['softrefs']) && strcmp($value,$softRefValue) && strstr($softRefValue,'{softref:'))	{
						$outRow[$field]['softrefs']['tokenizedContent'] = $softRefValue;
					}
				}
			}
		}

		return $outRow;
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to finding file and DB references!
	 *
	 * @param	array		Data structure for the current value
	 * @param	mixed		Current value
	 * @param	array		Additional configuration used in calling function
	 * @param	string		Path of value in DS structure
	 * @param	object		Object reference to caller
	 * @return	void
	 * @see t3lib_TCEmain::checkValue_flex_procInData_travDS()
	 */
	function getRelations_flexFormCallBack($dsArr, $dataValue, $PA, $structurePath, &$pObj)	{
		$structurePath = substr($structurePath,5);	// removing "data/" in the beginning of path (which points to location in data array)
		
		$dsConf = $dsArr['TCEforms']['config'];

			// Implode parameter values:
		list($table, $uid, $field) = array($PA['table'],$PA['uid'],$PA['field']);

			// Add files
		if ($result = $this->getRelations_procFiles($dataValue, $dsConf, $uid))	{

				// Creates an entry for the field with all the files:
			$this->temp_flexRelations['file'][$structurePath] = $result;
		}

			// Add DB:
		if ($result = $this->getRelations_procDB($dataValue, $dsConf, $uid))	{

				// Create an entry for the field with all DB relations:
			$this->temp_flexRelations['db'][$structurePath] = $result;
		}

			// Soft References:
		if (strlen($dataValue) && $softRefs = t3lib_BEfunc::explodeSoftRefParserList($dsConf['softref']))	{
			$softRefValue = $dataValue;
			foreach($softRefs as $spKey => $spParams)	{
				$softRefObj = &t3lib_BEfunc::softRefParserObj($spKey);
				if (is_object($softRefObj))	{
					$resultArray = $softRefObj->findRef($table, $field, $uid, $softRefValue, $spKey, $spParams, $structurePath);
					if (is_array($resultArray) && is_array($resultArray['elements']))	{
						$this->temp_flexRelations['softrefs'][$structurePath]['keys'][$spKey] = $resultArray['elements'];
						if (strlen($resultArray['content'])) $softRefValue = $resultArray['content'];
					}
				}
			}

			if (count($this->temp_flexRelations['softrefs']) && strcmp($dataValue,$softRefValue))	{
				$this->temp_flexRelations['softrefs'][$structurePath]['tokenizedContent'] = $softRefValue;
			}
		}
	}

	/**
	 * Check field configuration if it is a file relation field and extract file relations if any
	 *
	 * @param	string		Field value
	 * @param	array		Field configuration array of type "TCA/columns"
	 * @param	integer		Field uid
	 * @return	array		If field type is OK it will return an array with the files inside. Else false
	 */
	function getRelations_procFiles($value, $conf, $uid)	{
			// Take care of files...
		if ($conf['type']=='group' && $conf['internal_type']=='file')	{

				// Collect file values in array:
			if ($conf['MM'])	{
				$theFileValues = array();
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->start('', 'files', $conf['MM'], $uid);

				foreach($dbAnalysis->itemArray as $somekey => $someval)	{
					if ($someval['id'])	{
						$theFileValues[] = $someval['id'];
					}
				}
			} else {
				$theFileValues = explode(',',$value);
			}

				// Traverse the files and add them:
			$uploadFolder = $conf['uploadfolder'];
			$dest = $this->destPathFromUploadFolder($uploadFolder);
			$newValue = array();
			$newValueFiles = array();

			foreach($theFileValues as $file)	{
				if (trim($file))	{
					$realFile = $dest.'/'.trim($file);
					if (@is_file($realFile))	{
						$newValueFiles[] = array(
							'filename' => $file,
							'ID' => md5($realFile),
							'ID_absFile' => $realFile
						);	// the order should be preserved here because.. (?)
					} else $this->error('Missing file: '.$realFile);
				}
			}

			return $newValueFiles;
		}
	}

	/**
	 * Check field configuration if it is a DB relation field and extract DB relations if any
	 *
	 * @param	string		Field value
	 * @param	array		Field configuration array of type "TCA/columns"
	 * @param	integer		Field uid
	 * @return	array		If field type is OK it will return an array with the database relations. Else false
	 */
	function getRelations_procDB($value, $conf, $uid)	{

			// DB record lists:
		if ($this->isReferenceField($conf))	{
			$allowedTables = $conf['type']=='group' ? $conf['allowed'] : $conf['foreign_table'].','.$conf['neg_foreign_table'];
			$prependName = $conf['type']=='group' ? $conf['prepend_tname'] : $conf['neg_foreign_table'];

			$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
			$dbAnalysis->start($value,$allowedTables,$conf['MM'],$uid);

			return $dbAnalysis->itemArray;
		}
	}












	/*******************************
	 *
	 * Helper functions
	 *
	 *******************************/

	/**
	 * Returns true if the TCA/columns field type is a DB reference field
	 *
	 * @param	array		config array for TCA/columns field
	 * @return	boolean		True if DB reference field (group/db or select with foreign-table)
	 */
	function isReferenceField($conf)	{
		return ($conf['type']=='group' && $conf['internal_type']=='db') ||	($conf['type']=='select' && $conf['foreign_table']);
	}

	/**
	 * Returns destination path to an upload folder given by $folder
	 *
	 * @param	string		Folder relative to PATH_site
	 * @return	string		Input folder prefixed with PATH_site. No checking for existence is done. Output must be a folder without trailing slash.
	 */
	function destPathFromUploadFolder($folder)	{
		return PATH_site.$folder;
	}

	/**
	 * Sets error message in the internal error log
	 *
	 * @param	string		Error message
	 * @return	void
	 */
	function error($msg)	{
		$this->errorLog[]=$msg;
	}
	
	function updateIndex($testOnly,$cli_echo=FALSE)	{
		global $TCA, $TYPO3_DB;
		
		$errors = array();
		$tableNames = array();
		$recCount=0;
		$tableCount=0;

		$headerContent = $testOnly ? 'Reference Index TESTED (nothing written)' : 'Reference Index Updated';
		if ($cli_echo) echo 
						'*******************************************'.chr(10).
						$headerContent.chr(10).
						'*******************************************'.chr(10); 
		
			// Traverse all tables:
		foreach($TCA as $tableName => $cfg)	{
			$tableNames[] = $tableName;
			$tableCount++;

				// Traverse all non-deleted records in tables:
			$allRecs = $TYPO3_DB->exec_SELECTgetRows('uid',$tableName,'1=1');	//.t3lib_BEfunc::deleteClause($tableName)
			$uidList = array(0);
			foreach($allRecs as $recdat)	{
				$refIndexObj = t3lib_div::makeInstance('t3lib_refindex');
				$result = $refIndexObj->updateRefIndexTable($tableName,$recdat['uid'],$testOnly);
				$uidList[]= $recdat['uid'];
				$recCount++;

				if ($result['addedNodes'] || $result['deletedNodes'])	{
					$Err = 'Record '.$tableName.':'.$recdat['uid'].' had '.$result['addedNodes'].' added indexes and '.$result['deletedNodes'].' deleted indexes';
					$errors[]= $Err; 
					if ($cli_echo) echo $Err.chr(10);
					#$errors[] = t3lib_div::view_array($result);
				}
			}

				// Searching lost indexes for this table:
			$where = 'tablename='.$TYPO3_DB->fullQuoteStr($tableName,'sys_refindex').' AND recuid NOT IN ('.implode(',',$uidList).')';
			$lostIndexes = $TYPO3_DB->exec_SELECTgetRows('hash','sys_refindex',$where);
			if (count($lostIndexes))	{
				$Err = 'Table '.$tableName.' has '.count($lostIndexes).' lost indexes which are now deleted';
				$errors[]= $Err;
				if ($cli_echo) echo $Err.chr(10);
				if (!$testOnly)	$TYPO3_DB->exec_DELETEquery('sys_refindex',$where);
			}
		}

			// Searching lost indexes for non-existing tables:
		$where = 'tablename NOT IN ('.implode(',',$TYPO3_DB->fullQuoteArray($tableNames,'sys_refindex')).')';
		$lostTables = $TYPO3_DB->exec_SELECTgetRows('hash','sys_refindex',$where);
		if (count($lostTables))	{
			$Err = 'Index table hosted '.count($lostTables).' indexes for non-existing tables, now removed';
			$errors[]= $Err;
			if ($cli_echo) echo $Err.chr(10);
			if (!$testOnly)	$TYPO3_DB->exec_DELETEquery('sys_refindex',$where);
		}

		$testedHowMuch = $recCount.' records from '.$tableCount.' tables were checked/updated.'.chr(10);
		
		$bodyContent = $testedHowMuch.(count($errors)?implode(chr(10),$errors):'Index Integrity was perfect!');
		if ($cli_echo) echo $testedHowMuch.(count($errors)?'Updates: '.count($errors):'Index Integrity was perfect!').chr(10);
				
		return array($headerContent,$bodyContent);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_refindex.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_refindex.php']);
}
?>
