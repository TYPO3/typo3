<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   89: class t3lib_refindex
 *  107:     function updateRefIndexTable($table,$uid,$testOnly=FALSE)
 *  178:     function generateRefIndexData($table,$uid)
 *  255:     function createEntryData($table,$uid,$field,$flexpointer,$deleted,$ref_table,$ref_uid,$ref_string='',$sort=-1,$softref_key='',$softref_id='')
 *  282:     function createEntryData_dbRels($table,$uid,$fieldname,$flexpointer,$deleted,$items)
 *  299:     function createEntryData_fileRels($table,$uid,$fieldname,$flexpointer,$deleted,$items)
 *  320:     function createEntryData_softreferences($table,$uid,$fieldname,$flexpointer,$deleted,$keys)
 *
 *              SECTION: Get relations from table row
 *  376:     function getRelations($table,$row,$onlyField='')
 *  473:     function getRelations_flexFormCallBack($dsArr, $dataValue, $PA, $structurePath, &$pObj)
 *  523:     function getRelations_procFiles($value, $conf, $uid)
 *  573:     function getRelations_procDB($value, $conf, $uid)
 *
 *              SECTION: Setting values
 *  616:     function setReferenceValue($hash,$newValue,$returnDataArray=FALSE)
 *  699:     function setReferenceValue_dbRels($refRec,$itemArray,$newValue,&$dataArray,$flexpointer='')
 *  737:     function setReferenceValue_fileRels($refRec,$itemArray,$newValue,&$dataArray,$flexpointer='')
 *  775:     function setReferenceValue_softreferences($refRec,$softref,$newValue,&$dataArray,$flexpointer='')
 *
 *              SECTION: Helper functions
 *  822:     function isReferenceField($conf)
 *  832:     function destPathFromUploadFolder($folder)
 *  842:     function error($msg)
 *  853:     function updateIndex($testOnly,$cli_echo=FALSE)
 *
 * TOTAL FUNCTIONS: 18
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */




/**
 * Reference index processing and relation extraction
 *
 * NOTICE: When the reference index is updated for an offline version the results may not be correct.
 * First, lets assumed that the reference update happens in LIVE workspace (ALWAYS update from Live workspace if you analyse whole database!)
 * Secondly, lets assume that in a Draft workspace you have changed the data structure of a parent page record - this is (in TemplaVoila) inherited by subpages.
 * When in the LIVE workspace the data structure for the records/pages in the offline workspace will not be evaluated to the right one simply because the data structure is taken from a rootline traversal and in the Live workspace that will NOT include the changed DataSTructure! Thus the evaluation will be based on the Data Structure set in the Live workspace!
 * Somehow this scenario is rarely going to happen. Yet, it is an inconsistency and I see now practical way to handle it - other than simply ignoring maintaining the index for workspace records. Or we can say that the index is precise for all Live elements while glitches might happen in an offline workspace?
 * Anyway, I just wanted to document this finding - I don't think we can find a solution for it. And its very TemplaVoila specific.
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

	var $words_strings = array();
	var $words = array();

	var $hashVersion = 1;	// Number which we can increase if a change in the code means we will have to force a re-generation of the index.


	/**
	 * Call this function to update the sys_refindex table for a record (even one just deleted)
	 * NOTICE: Currently, references updated for a deleted-flagged record will not include those from within flexform fields in some cases where the data structure is defined by another record since the resolving process ignores deleted records! This will also result in bad cleaning up in tcemain I think... Anyway, thats the story of flexforms; as long as the DS can change, lots of references can get lost in no time.
	 *
	 * @param	string		Table name
	 * @param	integer		UID of record
	 * @param	boolean		If set, nothing will be written to the index but the result value will still report statistics on what is added, deleted and kept. Can be used for mere analysis.
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

			// First, test to see if the record exists (including deleted-flagged)
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

				// Words:
			if (!$testOnly)	$this->wordIndexing($table,$uid);
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

					// Initialize:
				$this->words_strings = array();
				$this->words = array();

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
 						case 'file_reference':
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

					// Word indexing:
				t3lib_div::loadTCA($table);
				foreach($TCA[$table]['columns'] as $field => $conf)	{
					if (t3lib_div::inList('input,text',$conf['config']['type']) && strcmp($record[$field],'') && !t3lib_div::testInt($record[$field])) {
						$this->words_strings[$field] = $record[$field];
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
								 case 'file_reference':
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
	 * @param	string		Table name
	 * @param	array		Row from table
	 * @param	string		Specific field to fetch for.
	 * @return	array		Array with information about relations
	 * @see export_addRecord()
	 */
	function getRelations($table,$row,$onlyField='')	{
		global $TCA;

			// Load full table description
		t3lib_div::loadTCA($table);

			// Initialize:
		$uid = $row['uid'];
		$nonFields = explode(',','uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,pid');

		$outRow = array();
		foreach($row as $field => $value)	{
			if (!in_array($field,$nonFields) && is_array($TCA[$table]['columns'][$field]) && (!$onlyField || $onlyField===$field))	{
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
				if ($result = $this->getRelations_procDB($value, $conf, $uid, $table))	{
						// Create an entry for the field with all DB relations:
					$outRow[$field] = array(
						'type' => 'db',
						'itemArray' => $result,
					);
				}

					// For "flex" fieldtypes we need to traverse the structure looking for file and db references of course!
				if ($conf['type']=='flex')	{

						// Get current value array:
						// NOTICE: failure to resolve Data Structures can lead to integrity problems with the reference index. Please look up the note in the JavaDoc documentation for the function t3lib_BEfunc::getFlexFormDS()
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
						$softRefObj = t3lib_BEfunc::softRefParserObj($spKey);
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
	function getRelations_flexFormCallBack($dsArr, $dataValue, $PA, $structurePath, $pObj) {
		$structurePath = substr($structurePath,5).'/';	// removing "data/" in the beginning of path (which points to location in data array)

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
				$softRefObj = t3lib_BEfunc::softRefParserObj($spKey);
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
		if ($conf['type'] == 'group' && ($conf['internal_type'] == 'file' || $conf['internal_type'] == 'file_reference')) {

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
			$uploadFolder = $conf['internal_type'] == 'file' ? $conf['uploadfolder'] : '';
			$dest = $this->destPathFromUploadFolder($uploadFolder);
			$newValue = array();
			$newValueFiles = array();

			foreach($theFileValues as $file)	{
				if (trim($file))	{
					$realFile = $dest.'/'.trim($file);
#					if (@is_file($realFile))	{		// Now, the refernece index should NOT look if files exist - just faithfully include them if they are in the records!
						$newValueFiles[] = array(
							'filename' => basename($file),
							'ID' => md5($realFile),
							'ID_absFile' => $realFile
						);	// the order should be preserved here because.. (?)
#					} else $this->error('Missing file: '.$realFile);
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
	 * @param	string		Table name
	 * @return	array		If field type is OK it will return an array with the database relations. Else false
	 */
	function getRelations_procDB($value, $conf, $uid, $table = '')	{

			// DB record lists:
		if ($this->isReferenceField($conf))	{
			$allowedTables = $conf['type']=='group' ? $conf['allowed'] : $conf['foreign_table'].','.$conf['neg_foreign_table'];
			$prependName = $conf['type']=='group' ? $conf['prepend_tname'] : $conf['neg_foreign_table'];

			if($conf['MM_opposite_field']) {
				return array();
			}

			$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
			$dbAnalysis->start($value,$allowedTables,$conf['MM'],$uid,$table,$conf);

			return $dbAnalysis->itemArray;
		}
	}












	/*******************************
	 *
	 * Setting values
	 *
	 *******************************/

	/**
	 * Setting the value of a reference or removing it completely.
	 * Usage: For lowlevel clean up operations!
	 * WARNING: With this you can set values that are not allowed in the database since it will bypass all checks for validity! Hence it is targetted at clean-up operations. Please use TCEmain in the usual ways if you wish to manipulate references.
	 * Since this interface allows updates to soft reference values (which TCEmain does not directly) you may like to use it for that as an exception to the warning above.
	 * Notice; If you want to remove multiple references from the same field, you MUST start with the one having the highest sorting number. If you don't the removal of a reference with a lower number will recreate an index in which the remaining references in that field has new hash-keys due to new sorting numbers - and you will get errors for the remaining operations which cannot find the hash you feed it!
	 * To ensure proper working only admin-BE_USERS in live workspace should use this function
	 *
	 * @param	string		32-byte hash string identifying the record from sys_refindex which you wish to change the value for
	 * @param	mixed		Value you wish to set for reference. If NULL, the reference is removed (unless a soft-reference in which case it can only be set to a blank string). If you wish to set a database reference, use the format "[table]:[uid]". Any other case, the input value is set as-is
	 * @param	boolean		Return $dataArray only, do not submit it to database.
	 * @param	boolean		If set, it will bypass check for workspace-zero and admin user
	 * @return	string		If a return string, that carries an error message, otherwise false (=OK) (except if $returnDataArray is set!)
	 */
	function setReferenceValue($hash,$newValue,$returnDataArray=FALSE,$bypassWorkspaceAdminCheck=FALSE)	{

		if (($GLOBALS['BE_USER']->workspace===0 && $GLOBALS['BE_USER']->isAdmin()) || $bypassWorkspaceAdminCheck)	{

				// Get current index from Database:
			list($refRec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'sys_refindex',
				'hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr($hash,'sys_refindex')
			);

				// Check if reference existed.
			if (is_array($refRec))	{
				if ($GLOBALS['TCA'][$refRec['tablename']])	{

						// Get that record from database:
					list($record) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',$refRec['tablename'],'uid='.intval($refRec['recuid']));

					if (is_array($record))	{

							// Get all relations from record, filter with fieldname:
						$dbrels = $this->getRelations($refRec['tablename'],$record,$refRec['field']);
						if ($dat = $dbrels[$refRec['field']])	{

								// Initialize data array that is to be sent to TCEmain afterwards:
							$dataArray = array();

		 						// Based on type,
		 					switch((string)$dat['type'])	{
		 						case 'db':
		 							$error = $this->setReferenceValue_dbRels($refRec,$dat['itemArray'],$newValue,$dataArray);
									if ($error)	return $error;
		 						break;
		 						case 'file_reference':
		 						case 'file':
		 							$this->setReferenceValue_fileRels($refRec,$dat['newValueFiles'],$newValue,$dataArray);
									if ($error)	return $error;
		 						break;
		 						case 'flex':
										// DB references:
									if (is_array($dat['flexFormRels']['db'][$refRec['flexpointer']]))	{
			 							$error = $this->setReferenceValue_dbRels($refRec,$dat['flexFormRels']['db'][$refRec['flexpointer']],$newValue,$dataArray,$refRec['flexpointer']);
										if ($error)	return $error;
									}
										// File references
									if (is_array($dat['flexFormRels']['file'][$refRec['flexpointer']]))	{
			 							$this->setReferenceValue_fileRels($refRec,$dat['flexFormRels']['file'][$refRec['flexpointer']],$newValue,$dataArray,$refRec['flexpointer']);
										if ($error)	return $error;
									}
										// Soft references in flexforms
									if ($refRec['softref_key'] && is_array($dat['flexFormRels']['softrefs'][$refRec['flexpointer']]['keys'][$refRec['softref_key']]))	{
			 							$error = $this->setReferenceValue_softreferences($refRec,$dat['flexFormRels']['softrefs'][$refRec['flexpointer']],$newValue,$dataArray,$refRec['flexpointer']);
										if ($error)	return $error;
									}
		 						break;
		 					}

		 						// Softreferences in the field:
		 					if ($refRec['softref_key'] && is_array($dat['softrefs']['keys'][$refRec['softref_key']]))	{
	 							$error = $this->setReferenceValue_softreferences($refRec,$dat['softrefs'],$newValue,$dataArray);
								if ($error)	return $error;

		 					}

								// Data Array, now ready to sent to TCEmain
							if ($returnDataArray)	{
								return $dataArray;
							} else {

									// Execute CMD array:
								$tce = t3lib_div::makeInstance('t3lib_TCEmain');
								$tce->stripslashes_values = FALSE;
								$tce->dontProcessTransformations = TRUE;
								$tce->bypassWorkspaceRestrictions = TRUE;
								$tce->bypassFileHandling = TRUE;
								$tce->bypassAccessCheckForRecords = TRUE;	// Otherwise this cannot update things in deleted records...

								$tce->start($dataArray,array());	// check has been done previously that there is a backend user which is Admin and also in live workspace
								$tce->process_datamap();

									// Return errors if any:
								if (count($tce->errorLog))	{
									return LF.'TCEmain:'.implode(LF.'TCEmain:',$tce->errorLog);
								}
							}
						}
					}
				} else return 'ERROR: Tablename "'.$refRec['tablename'].'" was not in TCA!';
			} else return 'ERROR: No reference record with hash="'.$hash.'" was found!';
		} else return 'ERROR: BE_USER object is not admin OR not in workspace 0 (Live)';
	}

	/**
	 * Setting a value for a reference for a DB field:
	 *
	 * @param	array		sys_refindex record
	 * @param	array		Array of references from that field
	 * @param	string		Value to substitute current value with (or NULL to unset it)
	 * @param	array		data array in which the new value is set (passed by reference)
	 * @param	string		Flexform pointer, if in a flex form field.
	 * @return	string		Error message if any, otherwise false = OK
	 */
	function setReferenceValue_dbRels($refRec,$itemArray,$newValue,&$dataArray,$flexpointer='')	{
		if (!strcmp($itemArray[$refRec['sorting']]['id'],$refRec['ref_uid']) && !strcmp($itemArray[$refRec['sorting']]['table'],$refRec['ref_table']))	{

				// Setting or removing value:
			if ($newValue===NULL)	{	// Remove value:
				unset($itemArray[$refRec['sorting']]);
			} else {
				list($itemArray[$refRec['sorting']]['table'],$itemArray[$refRec['sorting']]['id']) = explode(':',$newValue);
			}

				// Traverse and compile new list of records:
			$saveValue = array();
			foreach($itemArray as $pair)	{
				$saveValue[] = $pair['table'].'_'.$pair['id'];
			}

				// Set in data array:
			if ($flexpointer)	{
				$flexToolObj = t3lib_div::makeInstance('t3lib_flexformtools');
				$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = array();
				$flexToolObj->setArrayValueByPath(substr($flexpointer,0,-1),$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'],implode(',',$saveValue));
			} else {
				$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = implode(',',$saveValue);
			}

		} else return 'ERROR: table:id pair "'.$refRec['ref_table'].':'.$refRec['ref_uid'].'" did not match that of the record ("'.$itemArray[$refRec['sorting']]['table'].':'.$itemArray[$refRec['sorting']]['id'].'") in sorting index "'.$refRec['sorting'].'"';
	}

	/**
	 * Setting a value for a reference for a FILE field:
	 *
	 * @param	array		sys_refindex record
	 * @param	array		Array of references from that field
	 * @param	string		Value to substitute current value with (or NULL to unset it)
	 * @param	array		data array in which the new value is set (passed by reference)
	 * @param	string		Flexform pointer, if in a flex form field.
	 * @return	string		Error message if any, otherwise false = OK
	 */
	function setReferenceValue_fileRels($refRec,$itemArray,$newValue,&$dataArray,$flexpointer='')	{
		if (!strcmp(substr($itemArray[$refRec['sorting']]['ID_absFile'],strlen(PATH_site)),$refRec['ref_string']) && !strcmp('_FILE',$refRec['ref_table']))	{

				// Setting or removing value:
			if ($newValue===NULL)	{	// Remove value:
				unset($itemArray[$refRec['sorting']]);
			} else {
				$itemArray[$refRec['sorting']]['filename'] = $newValue;
			}

				// Traverse and compile new list of records:
			$saveValue = array();
			foreach($itemArray as $fileInfo)	{
				$saveValue[] = $fileInfo['filename'];
			}

				// Set in data array:
			if ($flexpointer)	{
				$flexToolObj = t3lib_div::makeInstance('t3lib_flexformtools');
				$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = array();
				$flexToolObj->setArrayValueByPath(substr($flexpointer,0,-1),$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'],implode(',',$saveValue));
			} else {
				$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = implode(',',$saveValue);
			}

		} else return 'ERROR: either "'.$refRec['ref_table'].'" was not "_FILE" or file PATH_site+"'.$refRec['ref_string'].'" did not match that of the record ("'.$itemArray[$refRec['sorting']]['ID_absFile'].'") in sorting index "'.$refRec['sorting'].'"';
	}

	/**
	 * Setting a value for a soft reference token
	 *
	 * @param	array		sys_refindex record
	 * @param	array		Array of soft reference occurencies
	 * @param	string		Value to substitute current value with
	 * @param	array		data array in which the new value is set (passed by reference)
	 * @param	string		Flexform pointer, if in a flex form field.
	 * @return	string		Error message if any, otherwise false = OK
	 */
	function setReferenceValue_softreferences($refRec,$softref,$newValue,&$dataArray,$flexpointer='')	{
		if (is_array($softref['keys'][$refRec['softref_key']][$refRec['softref_id']]))	{

				// Set new value:
			$softref['keys'][$refRec['softref_key']][$refRec['softref_id']]['subst']['tokenValue'] = ''.$newValue;

				// Traverse softreferences and replace in tokenized content to rebuild it with new value inside:
			foreach($softref['keys'] as $sfIndexes)	{
				foreach($sfIndexes as $data)	{
					$softref['tokenizedContent'] = str_replace('{softref:'.$data['subst']['tokenID'].'}', $data['subst']['tokenValue'], $softref['tokenizedContent']);
				}
			}

				// Set in data array:
			if (!strstr($softref['tokenizedContent'],'{softref:'))	{
				if ($flexpointer)	{
					$flexToolObj = t3lib_div::makeInstance('t3lib_flexformtools');
					$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = array();
					$flexToolObj->setArrayValueByPath(substr($flexpointer,0,-1),$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'],$softref['tokenizedContent']);
				} else {
					$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = $softref['tokenizedContent'];
				}
			} else return 'ERROR: After substituting all found soft references there were still soft reference tokens in the text. (theoretically this does not have to be an error if the string "{softref:" happens to be in the field for another reason.)';
		} else return 'ERROR: Soft reference parser key "'.$refRec['softref_key'].'" or the index "'.$refRec['softref_id'].'" was not found.';
	}










	/*******************************
	 *
	 * Indexing words
	 *
	 *******************************/

	/**
	 * Indexing words from table records. Can be useful for quick backend look ups in records across the system.
	 */
	function wordIndexing($table,$uid)	{
		return; // Disabled until Kasper finishes this feature. If someone else needs it in the meantime you are welcome to complete it. Below my todo list.

		// TODO:
		// - Flag to disable indexing
		// - Clean-up to remove words not used anymore  and indexes for records not in the system anymore.
		// - UTF-8 compliant substr()
		$lexer = t3lib_div::makeInstance('tx_indexedsearch_lexer');
		$words = $lexer->split2Words(implode(' ',$this->words_strings));
		foreach($words as $w) {
			$words[]=substr($w,0,3);
		}
		$words = array_unique($words);
		$this->updateWordIndex($words,$table,$uid);
	}

	/**
	 * Update/Create word index for record
	 *
	 * @param	array		Word list array (words are values in array)
	 * @param	string		Table
	 * @param	integer		Rec uid
	 * @return	void
	 */
	function updateWordIndex($words,$table,$uid) {

			// Submit words to
		$this->submitWords($words);

			// Result id and remove relations:
		$rid = t3lib_div::md5int($table.':'.$uid);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_refindex_rel', 'rid='.intval($rid));

			// Add relations:
		foreach($words as $w)	{
			$insertFields = array(
				'rid' => $rid,
				'wid' => t3lib_div::md5int($w)
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_refindex_rel', $insertFields);
		}

			// Add result record:
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_refindex_res', 'rid='.intval($rid));
		$insertFields = array(
			'rid' => $rid,
			'tablename' => $table,
			'recuid' => $uid
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_refindex_res', $insertFields);
	}

	/**
	 * Adds new words to db
	 *
	 * @param	array		Word List array (where each word has information about position etc).
	 * @return	void
	 */
	function submitWords($wl) {

		$hashArr = array();
		foreach($wl as $w)	{
			$hashArr[] = t3lib_div::md5int($w);
		}
		$wl = array_flip($wl);

		if (count($hashArr))	{
			$cwl = implode(',',$hashArr);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('baseword', 'sys_refindex_words', 'wid IN ('.$cwl.')');

			if($GLOBALS['TYPO3_DB']->sql_num_rows($res)!=count($wl)) {
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					unset($wl[$row['baseword']]);
				}

				foreach ($wl as $key => $val) {
					$insertFields = array(
						'wid' => t3lib_div::md5int($key),
						'baseword' => $key
					);

						// A duplicate-key error will occur here if a word is NOT unset in the unset() line. However as long as the words in $wl are NOT longer as 60 chars (the baseword varchar is 60 characters...) this is not a problem.
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_refindex_words', $insertFields);
				}
			}
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
		return ($conf['type']=='group' && $conf['internal_type']=='db') || (($conf['type']=='select' || $conf['type']=='inline') && $conf['foreign_table']);
	}

	/**
	 * Returns destination path to an upload folder given by $folder
	 *
	 * @param	string		Folder relative to PATH_site
	 * @return	string		Input folder prefixed with PATH_site. No checking for existence is done. Output must be a folder without trailing slash.
	 */
	function destPathFromUploadFolder($folder)	{
		if (!$folder) {
			return substr(PATH_site, 0, -1);
		}
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

	/**
	 * Updating Index (External API)
	 *
	 * @param	boolean		If set, only a test
	 * @param	boolean		If set, output CLI status
	 * @return	array		Header and body status content
	 */
	function updateIndex($testOnly,$cli_echo=FALSE)	{
		global $TCA, $TYPO3_DB;

		$errors = array();
		$tableNames = array();
		$recCount=0;
		$tableCount=0;

		$headerContent = $testOnly ? 'Reference Index being TESTED (nothing written, use "-e" to update)' : 'Reference Index being Updated';
		if ($cli_echo) echo
						'*******************************************'.LF.
						$headerContent.LF.
						'*******************************************'.LF;

			// Traverse all tables:
		foreach ($TCA as $tableName => $cfg)	{
			$tableNames[] = $tableName;
			$tableCount++;

				// Traverse all records in tables, including deleted records:
			$allRecs = $TYPO3_DB->exec_SELECTgetRows('uid',$tableName,'1=1');	//.t3lib_BEfunc::deleteClause($tableName)
			$uidList = array(0);
			foreach ($allRecs as $recdat)	{
				$refIndexObj = t3lib_div::makeInstance('t3lib_refindex');
				$result = $refIndexObj->updateRefIndexTable($tableName,$recdat['uid'],$testOnly);
				$uidList[]= $recdat['uid'];
				$recCount++;

				if ($result['addedNodes'] || $result['deletedNodes'])	{
					$Err = 'Record '.$tableName.':'.$recdat['uid'].' had '.$result['addedNodes'].' added indexes and '.$result['deletedNodes'].' deleted indexes';
					$errors[]= $Err;
					if ($cli_echo) echo $Err.LF;
					#$errors[] = t3lib_div::view_array($result);
				}
			}

				// Searching lost indexes for this table:
			$where = 'tablename='.$TYPO3_DB->fullQuoteStr($tableName,'sys_refindex').' AND recuid NOT IN ('.implode(',',$uidList).')';
			$lostIndexes = $TYPO3_DB->exec_SELECTgetRows('hash','sys_refindex',$where);
			if (count($lostIndexes))	{
				$Err = 'Table '.$tableName.' has '.count($lostIndexes).' lost indexes which are now deleted';
				$errors[]= $Err;
				if ($cli_echo) echo $Err.LF;
				if (!$testOnly)	$TYPO3_DB->exec_DELETEquery('sys_refindex',$where);
			}
		}

			// Searching lost indexes for non-existing tables:
		$where = 'tablename NOT IN ('.implode(',',$TYPO3_DB->fullQuoteArray($tableNames,'sys_refindex')).')';
		$lostTables = $TYPO3_DB->exec_SELECTgetRows('hash','sys_refindex',$where);
		if (count($lostTables))	{
			$Err = 'Index table hosted '.count($lostTables).' indexes for non-existing tables, now removed';
			$errors[]= $Err;
			if ($cli_echo) echo $Err.LF;
			if (!$testOnly)	$TYPO3_DB->exec_DELETEquery('sys_refindex',$where);
		}

		$testedHowMuch = $recCount.' records from '.$tableCount.' tables were checked/updated.'.LF;

		$bodyContent = $testedHowMuch.(count($errors)?implode(LF,$errors):'Index Integrity was perfect!');
		if ($cli_echo) echo $testedHowMuch.(count($errors)?'Updates: '.count($errors):'Index Integrity was perfect!').LF;

		if(!$testOnly) {
			$registry = t3lib_div::makeInstance('t3lib_Registry');
			$registry->set('core', 'sys_refindex_lastUpdate', $GLOBALS['EXEC_TIME']);
		}

		return array($headerContent,$bodyContent,count($errors));
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_refindex.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_refindex.php']);
}

?>