<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class for getting and transforming data for display in backend forms (TCEforms)
 *
 * $Id$
 * Revised for TYPO3 3.6 September/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   99: class t3lib_transferData
 *
 *			  SECTION: Getting record content, ready for display in TCEforms
 *  138:	 function fetchRecord($table,$idList,$operation)
 *  225:	 function renderRecord($table, $id, $pid, $row)
 *  269:	 function renderRecordRaw($table, $id, $pid, $row, $TSconfig='', $tscPID=0)
 *  327:	 function renderRecord_SW($data,$fieldConfig,$TSconfig,$table,$row,$field)
 *  359:	 function renderRecord_groupProc($data,$fieldConfig,$TSconfig,$table,$row,$field)
 *  410:	 function renderRecord_selectProc($data,$fieldConfig,$TSconfig,$table,$row,$field)
 *  473:	 function renderRecord_flexProc($data,$fieldConfig,$TSconfig,$table,$row,$field)
 *  504:	 function renderRecord_typesProc($totalRecordContent,$types_fieldConfig,$tscPID,$table,$pid)
 *  545:	 function renderRecord_inlineProc($data,$fieldConfig,$TSconfig,$table,$row,$field)
 *
 *			  SECTION: FlexForm processing functions
 *  632:	 function renderRecord_flexProc_procInData($dataPart,$dataStructArray,$pParams)
 *  661:	 function renderRecord_flexProc_procInData_travDS(&$dataValues,$DSelements,$pParams)
 *
 *			  SECTION: Selector box processing functions
 *  738:	 function selectAddSpecial($dataAcc, $elements, $specialKey)
 *  863:	 function selectAddForeign($dataAcc, $elements, $fieldConfig, $field, $TSconfig, $row, $table)
 *  917:	 function getDataIdList($elements, $fieldConfig, $row, $table)
 *  946:	 function procesItemArray($selItems,$config,$fieldTSConfig,$table,$row,$field)
 *  961:	 function addItems($items,$iArray)
 *  983:	 function procItems($items,$itemsProcFuncTSconfig,$config,$table,$row,$field)
 *
 *			  SECTION: Helper functions
 * 1018:	 function lockRecord($table, $id, $pid=0)
 * 1035:	 function regItem($table, $id, $field, $content)
 * 1045:	 function sL($in)
 *
 * TOTAL FUNCTIONS: 20
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Class for getting and transforming data for display in backend forms (TCEforms)
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_transferData {
		// External, static:
	var $lockRecords = 0; // If set, the records requested are locked.
	var $disableRTE = 0; // Is set externally if RTE is disabled.
	var $prevPageID = ''; // If the pid in the command is 'prev' then $prevPageID is used as pid for the record. This is used to attach new records to other previous records eg. new pages.
	var $defVals = array(); // Can be set with an array of default values for tables. First key is table name, second level keys are field names. Originally this was a GLOBAL array used internally.
	var $addRawData = FALSE; // If set, the processed data is overlaid the raw record.

		// Internal, dynamic
	var $regTableItems = array(); // Used to register, which items are already loaded!!
	var $regTableItems_data = array(); // This stores the record data of the loaded records
	var $loadModules = ''; // Contains loadModules object, if used. (for reuse internally)


	/***********************************************
	 *
	 * Getting record content, ready for display in TCEforms
	 *
	 ***********************************************/

	/**
	 * A function which can be used for load a batch of records from $table into internal memory of this object.
	 * The function is also used to produce proper default data for new records
	 * Ultimately the function will call renderRecord()
	 *
	 * @param	string		Table name, must be found in $TCA
	 * @param	string		Comma list of id values. If $idList is "prev" then the value from $this->prevPageID is used. NOTICE: If $operation is "new", then negative ids are meant to point to a "previous" record and positive ids are PID values for new records. Otherwise (for existing records that is) it is straight forward table/id pairs.
	 * @param	string		If "new", then a record with default data is returned. Further, the $id values are meant to be PID values (or if negative, pointing to a previous record). If NOT new, then the table/ids are just pointing to an existing record!
	 * @return	void
	 * @see renderRecord()
	 */
	function fetchRecord($table, $idList, $operation) {
		global $TCA;

		if ((string) $idList == 'prev') {
			$idList = $this->prevPageID;
		}

		if ($TCA[$table]) {
			t3lib_div::loadTCA($table);

				// For each ID value (integer) we
			$ids = t3lib_div::trimExplode(',', $idList, 1);
			foreach ($ids as $id) {
				if (strcmp($id, '')) { // If ID is not blank:

						// For new records to be created, find default values:
					if ($operation == 'new') {

							// Default values:
						$newRow = array(); // Used to store default values as found here:

							// Default values as set in userTS:
						$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
						if (is_array($TCAdefaultOverride[$table . '.'])) {
							foreach ($TCAdefaultOverride[$table . '.'] as $theF => $theV) {
								if (isset($TCA[$table]['columns'][$theF])) {
									$newRow[$theF] = $theV;
								}
							}
						}

						if ($id < 0) {
							$record = t3lib_beFunc::getRecord($table, abs($id), 'pid');
							$pid = $record['pid'];
							unset($record);
						} else {
							$pid = intval($id);
						}

						$pageTS = t3lib_beFunc::getPagesTSconfig($pid);

						if (isset($pageTS['TCAdefaults.'])) {
							$TCAPageTSOverride = $pageTS['TCAdefaults.'];
							if (is_array($TCAPageTSOverride[$table . '.'])) {
								foreach ($TCAPageTSOverride[$table . '.'] as $theF => $theV) {
									if (isset($TCA[$table]['columns'][$theF])) {
										$newRow[$theF] = $theV;
									}
								}
							}
						}

							// Default values as submitted:
						if (is_array($this->defVals[$table])) {
							foreach ($this->defVals[$table] as $theF => $theV) {
								if (isset($TCA[$table]['columns'][$theF])) {
									$newRow[$theF] = $theV;
								}
							}
						}

							// Fetch default values if a previous record exists
						if ($id < 0 && $TCA[$table]['ctrl']['useColumnsForDefaultValues']) {
								// Fetches the previous record:
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . abs($id) . t3lib_BEfunc::deleteClause($table));
							if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
									// Gets the list of fields to copy from the previous record.
								$fArr = t3lib_div::trimExplode(',', $TCA[$table]['ctrl']['useColumnsForDefaultValues'], 1);
								foreach ($fArr as $theF) {
									if (isset($TCA[$table]['columns'][$theF])) {
										$newRow[$theF] = $row[$theF];
									}
								}
							}
							$GLOBALS['TYPO3_DB']->sql_free_result($res);
						}

							// Finally, call renderRecord:
						$this->renderRecord($table, uniqid('NEW'), $id, $newRow);
					} else {
						$id = intval($id);

							// Fetch database values
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . intval($id) . t3lib_BEfunc::deleteClause($table));
						if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							t3lib_BEfunc::fixVersioningPid($table, $row);
							$this->renderRecord($table, $id, $row['pid'], $row);
							$contentTable = $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'];
							$this->lockRecord($table, $id, $contentTable == $table ? $row['pid'] : 0); // Locking the pid if the table edited is the content table.
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($res);
					}
				}
			}
		}
	}

	/**
	 * This function performs processing on the input $row array and stores internally a corresponding array which contains processed values, ready to pass on to the TCEforms rendering in the frontend!
	 * The objective with this function is to prepare the content for handling in TCEforms.
	 * Default values from outside/TSconfig is added by fetchRecord(). In this function default values from TCA is used if a field is NOT defined in $row.
	 * The resulting, processed row is stored in $this->regTableItems_data[$uniqueItemRef], where $uniqueItemRef is "[tablename]_[id-value]"
	 *
	 * @param	string		The table name
	 * @param	string		The uid value of the record (integer). Can also be a string (NEW-something) if the record is a NEW record.
	 * @param	integer		The pid integer. For existing records this is of course the row's "pid" field. For new records it can be either a page id (positive) or a pointer to another record from the SAME table (negative) after which the record should be inserted (or on same page)
	 * @param	array		The row of the current record. If NEW record, then it may be loaded with default values (by eg. fetchRecord()).
	 * @return	void
	 * @see fetchRecord()
	 */
	function renderRecord($table, $id, $pid, $row) {
		global $TCA;

			// Init:
		$uniqueItemRef = $table . '_' . $id;
		t3lib_div::loadTCA($table);

			// Fetches the true PAGE TSconfig pid to use later, if needed. (Until now, only for the RTE, but later..., who knows?)
		list($tscPID) = t3lib_BEfunc::getTSCpid($table, $id, $pid);
		$TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig($table, array_merge($row, array('uid' => $id, 'pid' => $pid)));

			// If the record has not already been loaded (in which case we DON'T do it again)...
		if (!$this->regTableItems[$uniqueItemRef]) {
			$this->regTableItems[$uniqueItemRef] = 1; // set "loaded" flag.

				// If the table is pages, set the previous page id internally.
			if ($table == 'pages') {
				$this->prevPageID = $id;
			}

			$this->regTableItems_data[$uniqueItemRef] = $this->renderRecordRaw($table, $id, $pid, $row, $TSconfig, $tscPID);

				// Merges the processed array on-top of the raw one - this is done because some things in TCEforms may need access to other fields than those in the columns configuration!
			if ($this->addRawData && is_array($row) && is_array($this->regTableItems_data[$uniqueItemRef])) {
				$this->regTableItems_data[$uniqueItemRef] = array_merge($row, $this->regTableItems_data[$uniqueItemRef]);
			}
		}
	}


	/**
	 * This function performs processing on the input $row array and stores internally a corresponding array which contains processed values, ready to pass on to the TCEforms rendering in the frontend!
	 * The objective with this function is to prepare the content for handling in TCEforms.
	 * In opposite to renderRecord() this function do not prepare things like fetching TSconfig and others.
	 * The resulting, processed row will be returned.
	 *
	 * @param	string		The table name
	 * @param	string		The uid value of the record (integer). Can also be a string (NEW-something) if the record is a NEW record.
	 * @param	integer		The pid integer. For existing records this is of course the row's "pid" field. For new records it can be either a page id (positive) or a pointer to another record from the SAME table (negative) after which the record should be inserted (or on same page)
	 * @param	array		The row of the current record. If NEW record, then it may be loaded with default values (by eg. fetchRecord()).
	 * @param	array		Tsconfig array
	 * @param	integer		PAGE TSconfig pid
	 * @return	array		Processed record data
	 * @see renderRecord()
	 */
	function renderRecordRaw($table, $id, $pid, $row, $TSconfig = '', $tscPID = 0) {
		global $TCA;

		if (!is_array($TSconfig)) {
			$TSconfig = array();
		}

			// Create blank accumulation array:
		$totalRecordContent = array();

			// Traverse the configured columns for the table (TCA):
			// For each column configured, we will perform processing if needed based on the type (eg. for "group" and "select" types this is needed)
		t3lib_div::loadTCA($table);
		$copyOfColumns = $TCA[$table]['columns'];
		foreach ($copyOfColumns as $field => $fieldConfig) {
				// Set $data variable for the field, either inputted value from $row - or if not found, the default value as defined in the "config" array
			if (isset($row[$field])) {
				$data = $row[$field];
			} else {
				$data = $fieldConfig['config']['default'];
			}

			$data = $this->renderRecord_SW($data, $fieldConfig, $TSconfig, $table, $row, $field);

				// Set the field in the accumulation array IF the $data variabel is set:
			$totalRecordContent[$field] = isset($data) ? $data : '';
		}

			// Further processing may apply for each field in the record depending on the settings in the "types" configuration (the list of fields to currently display for a record in TCEforms).
			// For instance this could be processing instructions for the Rich Text Editor.
		$types_fieldConfig = t3lib_BEfunc::getTCAtypes($table, $totalRecordContent);
		if (is_array($types_fieldConfig)) {
			$totalRecordContent = $this->renderRecord_typesProc($totalRecordContent, $types_fieldConfig, $tscPID, $table, $pid);
		}

			// Register items, mostly for external use (overriding the regItem() function)
		foreach ($totalRecordContent as $field => $data) {
			$this->regItem($table, $id, $field, $data);
		}

			// Finally, store the result:
		reset($totalRecordContent);

		return $totalRecordContent;

	}

	/**
	 * Function with the switch() construct which triggers functions for processing of the data value depending on the TCA-config field type.
	 *
	 * @param	string		Value to process
	 * @param	array		TCA/columns array for field	(independant of TCA for flexforms - coming from XML then)
	 * @param	array		TSconfig	(blank for flexforms for now)
	 * @param	string		Table name
	 * @param	array		The row array, always of the real record (also for flexforms)
	 * @param	string		The field (empty for flexforms!)
	 * @return	string		Modified $value
	 */
	function renderRecord_SW($data, $fieldConfig, $TSconfig, $table, $row, $field) {
		switch ((string) $fieldConfig['config']['type']) {
			case 'group':
				$data = $this->renderRecord_groupProc($data, $fieldConfig, $TSconfig, $table, $row, $field);
			break;
			case 'select':
				$data = $this->renderRecord_selectProc($data, $fieldConfig, $TSconfig, $table, $row, $field);
			break;
			case 'flex':
				$data = $this->renderRecord_flexProc($data, $fieldConfig, $TSconfig, $table, $row, $field);
			break;
			case 'inline':
				$data = $this->renderRecord_inlineProc($data, $fieldConfig, $TSconfig, $table, $row, $field);
			break;
		}

		return $data;
	}

	/**
	 * Processing of the data value in case the field type is "group"
	 *
	 * @param	string		The field value
	 * @param	array		TCA field config
	 * @param	array		TCEform TSconfig for the record
	 * @param	string		Table name
	 * @param	array		The row
	 * @param	string		Field name
	 * @return	string		The processed input field value ($data)
	 * @access private
	 * @see renderRecord()
	 */
	function renderRecord_groupProc($data, $fieldConfig, $TSconfig, $table, $row, $field) {
		switch ($fieldConfig['config']['internal_type']) {
			case 'file':
					// Init array used to accumulate the files:
				$dataAcc = array();

					// Now, load the files into the $dataAcc array, whether stored by MM or as a list of filenames:
				if ($fieldConfig['config']['MM']) {
					$loadDB = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$loadDB->start('', 'files', $fieldConfig['config']['MM'], $row['uid']); // Setting dummy startup

					foreach ($loadDB->itemArray as $value) {
						if ($value['id']) {
							$dataAcc[] = rawurlencode($value['id']) . '|' . rawurlencode($value['id']);
						}
					}
				} else {
					$fileList = t3lib_div::trimExplode(',', $data, 1);
					foreach ($fileList as $value) {
						if ($value) {
							$dataAcc[] = rawurlencode($value) . '|' . rawurlencode($value);
						}
					}
				}
					// Implode the accumulation array to a comma separated string:
				$data = implode(',', $dataAcc);
			break;
			case 'db':
				$loadDB = t3lib_div::makeInstance('t3lib_loadDBGroup');
				/* @var $loadDB t3lib_loadDBGroup */
				$loadDB->start($data, $fieldConfig['config']['allowed'], $fieldConfig['config']['MM'], $row['uid'], $table, $fieldConfig['config']);
				$loadDB->getFromDB();
				$data = $loadDB->readyForInterface();
			break;
		}

		return $data;
	}

	/**
	 * Processing of the data value in case the field type is "select"
	 *
	 * @param	string		The field value
	 * @param	array		TCA field config
	 * @param	array		TCEform TSconfig for the record
	 * @param	string		Table name
	 * @param	array		The row
	 * @param	string		Field name
	 * @return	string		The processed input field value ($data)
	 * @access private
	 * @see renderRecord()
	 */
	function renderRecord_selectProc($data, $fieldConfig, $TSconfig, $table, $row, $field) {
		global $TCA;

			// Initialize:
		$elements = t3lib_div::trimExplode(',', $data, 1); // Current data set.
		$dataAcc = array(); // New data set, ready for interface (list of values, rawurlencoded)

			// For list selectors (multi-value):
		if (intval($fieldConfig['config']['maxitems']) > 1) {

				// Add regular elements:
			if (!is_array($fieldConfig['config']['items'])) {
				$fieldConfig['config']['items'] = array();
			}
			$fieldConfig['config']['items'] = $this->procesItemArray($fieldConfig['config']['items'], $fieldConfig['config'], $TSconfig[$field], $table, $row, $field);
			foreach ($fieldConfig['config']['items'] as $pvpv) {
				foreach ($elements as $eKey => $value) {
					if (!strcmp($value, $pvpv[1])) {
						$dataAcc[$eKey] = rawurlencode($pvpv[1]) . '|' . rawurlencode($this->sL($pvpv[0]));
					}
				}
			}

				// Add "special"
			if ($fieldConfig['config']['special']) {
				$dataAcc = $this->selectAddSpecial($dataAcc, $elements, $fieldConfig['config']['special']);
			}

				// Add "foreign table" stuff:
			if ($TCA[$fieldConfig['config']['foreign_table']]) {
				$dataAcc = $this->selectAddForeign($dataAcc, $elements, $fieldConfig, $field, $TSconfig, $row, $table);
			}

				// Always keep the native order for display in interface:
			ksort($dataAcc);
		} else { // Normal, <= 1 -> value without title on it
			if ($TCA[$fieldConfig['config']['foreign_table']]) {
					// Getting the data
				$dataIds = $this->getDataIdList($elements, $fieldConfig, $row, $table);

				if (!count($dataIds)) {
					$dataIds = array(0);
				}
				$dataAcc[] = $dataIds[0];
			} else {
				$dataAcc[] = $elements[0];
			}
		}

		return implode(',', $dataAcc);
	}

	/**
	 * Processing of the data value in case the field type is "flex"
	 * MUST NOT be called in case of already INSIDE a flexform!
	 *
	 * @param	string		The field value
	 * @param	array		TCA field config
	 * @param	array		TCEform TSconfig for the record
	 * @param	string		Table name
	 * @param	array		The row
	 * @param	string		Field name
	 * @return	string		The processed input field value ($data)
	 * @access private
	 * @see renderRecord()
	 */
	function renderRecord_flexProc($data, $fieldConfig, $TSconfig, $table, $row, $field) {
		global $TCA;

			// Convert the XML data to PHP array:
		$currentValueArray = t3lib_div::xml2array($data);
		if (is_array($currentValueArray)) {

				// Get current value array:
			$dataStructArray = t3lib_BEfunc::getFlexFormDS($fieldConfig['config'], $row, $table);

				// Manipulate Flexform DS via TSConfig and group access lists
			if (is_array($dataStructArray)) {
				$flexFormHelper = t3lib_div::makeInstance('t3lib_TCEforms_Flexforms');
				$dataStructArray = $flexFormHelper->modifyFlexFormDS($dataStructArray, $table, $field, $row, $fieldConfig);
				unset($flexFormHelper);
			}

			if (is_array($dataStructArray)) {
				$currentValueArray['data'] = $this->renderRecord_flexProc_procInData($currentValueArray['data'], $dataStructArray, array($data, $fieldConfig, $TSconfig, $table, $row, $field));

				$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
				$data = $flexObj->flexArray2Xml($currentValueArray, TRUE);
			}
		}

		return $data;
	}

	/**
	 * Processing of the content in $totalRecordcontent based on settings in the types-configuration
	 *
	 * @param	array		The array of values which has been processed according to their type (eg. "group" or "select")
	 * @param	array		The "types" configuration for the current display of fields.
	 * @param	integer		PAGE TSconfig PID
	 * @param	string		Table name
	 * @param	integer		PID value
	 * @return	array		The processed version of $totalRecordContent
	 * @access private
	 */
	function renderRecord_typesProc($totalRecordContent, $types_fieldConfig, $tscPID, $table, $pid) {
		foreach ($types_fieldConfig as $vconf) {

				// Find file to write to, if configured:
			$eFile = t3lib_parsehtml_proc::evalWriteFile($vconf['spec']['static_write'], $totalRecordContent);

				// Write file configuration:
			if (is_array($eFile)) {
				if ($eFile['loadFromFileField'] && $totalRecordContent[$eFile['loadFromFileField']]) {
						// Read the external file, and insert the content between the ###TYPO3_STATICFILE_EDIT### markers:
					$SW_fileContent = t3lib_div::getUrl($eFile['editFile']);
					$parseHTML = t3lib_div::makeInstance('t3lib_parsehtml_proc');
					$parseHTML->init('', '');

					$totalRecordContent[$vconf['field']] = $parseHTML->getSubpart(
						$SW_fileContent,
						$eFile['markerField'] && trim($totalRecordContent[$eFile['markerField']])
								? trim($totalRecordContent[$eFile['markerField']])
								: '###TYPO3_STATICFILE_EDIT###'
					);
				}
			}
		}

		return $totalRecordContent;
	}

	/**
	 * Processing of the data value in case the field type is "inline"
	 * In some parts nearly the same as type "select"
	 *
	 * @param	string		The field value
	 * @param	array		TCA field config
	 * @param	array		TCEform TSconfig for the record
	 * @param	string		Table name
	 * @param	array		The row
	 * @param	string		Field name
	 * @return	string		The processed input field value ($data)
	 * @access private
	 * @see renderRecord()
	 */
	function renderRecord_inlineProc($data, $fieldConfig, $TSconfig, $table, $row, $field) {
		global $TCA;

			// Initialize:
		$elements = t3lib_div::trimExplode(',', $data); // Current data set.
		$dataAcc = array(); // New data set, ready for interface (list of values, rawurlencoded)

			// At this point all records that CAN be selected is found in $recordList
			// Now, get the data from loadDBgroup based on the input list of values.
		$dataIds = $this->getDataIdList($elements, $fieldConfig, $row, $table);

			// After this we can traverse the loadDBgroup values and match values with the list of possible values in $recordList:
		foreach ($dataIds as $theId) {
			if ($fieldConfig['config']['MM'] || $fieldConfig['config']['foreign_field']) {
				$dataAcc[] = $theId;
			} else {
				foreach ($elements as $eKey => $value) {
					if (!strcmp($theId, $value)) {
						$dataAcc[$eKey] = $theId;
					}
				}
			}
		}

		return implode(',', $dataAcc);
	}


	/***********************************************
	 *
	 * FlexForm processing functions
	 *
	 ***********************************************/

	/**
	 * Function traversing sheets/languages for flex form data structures
	 *
	 * @param	array		Data array
	 * @param	array		Data Structure array
	 * @param	array		Various parameters to pass-through
	 * @return	array		Modified $dataPart array.
	 * @access private
	 * @see t3lib_TCEmain::checkValue_flex_procInData(), renderRecord_flexProc_procInData_travDS()
	 */
	function renderRecord_flexProc_procInData($dataPart, $dataStructArray, $pParams) {
		if (is_array($dataPart)) {
			foreach ($dataPart as $sKey => $sheetDef) {
				list ($dataStruct, $actualSheet) = t3lib_div::resolveSheetDefInDS($dataStructArray, $sKey);

				if (is_array($dataStruct) && $actualSheet == $sKey && is_array($sheetDef)) {
					foreach ($sheetDef as $lKey => $lData) {
						$this->renderRecord_flexProc_procInData_travDS(
							$dataPart[$sKey][$lKey],
							$dataStruct['ROOT']['el'],
							$pParams
						);
					}
				}
			}
		}

		return $dataPart;
	}

	/**
	 * Traverse data array / structure
	 *
	 * @param	array		Data array passed by reference.
	 * @param	array		Data structure
	 * @param	array		Various parameters pass-through.
	 * @return	void
	 * @see renderRecord_flexProc_procInData(), t3lib_TCEmain::checkValue_flex_procInData_travDS()
	 */
	function renderRecord_flexProc_procInData_travDS(&$dataValues, $DSelements, $pParams) {
		if (is_array($DSelements)) {

				// For each DS element:
			foreach ($DSelements as $key => $dsConf) {

					// Array/Section:
				if ($DSelements[$key]['type'] == 'array') {
					if (is_array($dataValues[$key]['el'])) {
						if ($DSelements[$key]['section']) {
							foreach ($dataValues[$key]['el'] as $ik => $el) {
								if (is_array($el)) {
									$theKey = key($el);
									if (is_array($dataValues[$key]['el'][$ik][$theKey]['el'])) {
										$this->renderRecord_flexProc_procInData_travDS(
											$dataValues[$key]['el'][$ik][$theKey]['el'],
											$DSelements[$key]['el'][$theKey]['el'],
											$pParams
										);
									}
								}
							}
						} else {
							if (!isset($dataValues[$key]['el'])) {
								$dataValues[$key]['el'] = array();
							}
							$this->renderRecord_flexProc_procInData_travDS(
								$dataValues[$key]['el'],
								$DSelements[$key]['el'],
								$pParams
							);
						}
					}
				} else {
					if (is_array($dsConf['TCEforms']['config']) && is_array($dataValues[$key])) {
						foreach ($dataValues[$key] as $vKey => $data) {

								// $data,$fieldConfig,$TSconfig,$table,$row,$field
							list(, , $CVTSconfig, $CVtable, $CVrow, $CVfield) = $pParams;

								// Set default value:
							if (!isset($dataValues[$key][$vKey])) {
								$dataValues[$key][$vKey] = $dsConf['TCEforms']['config']['default'];
							}

								// Process value:
							$dataValues[$key][$vKey] = $this->renderRecord_SW($dataValues[$key][$vKey], $dsConf['TCEforms'], $CVTSconfig, $CVtable, $CVrow, '');
						}
					}
				}
			}
		}
	}


	/***********************************************
	 *
	 * Selector box processing functions
	 *
	 ***********************************************/

	/**
	 * Adding "special" types to the $dataAcc array of selector items
	 *
	 * @param	array		Array with numeric keys, containing values for the selector box, prepared for interface. We are going to add elements to this array as needed.
	 * @param	array		The array of original elements - basically the field value exploded by ","
	 * @param	string		The "special" key from the TCA config of the field. Determines the type of processing in here.
	 * @return	array		Modified $dataAcc array
	 * @access private
	 * @see renderRecord_selectProc()
	 */
	function selectAddSpecial($dataAcc, $elements, $specialKey) {
		global $TCA;

			// Special select types:
		switch ((string) $specialKey) {
			case 'tables': // Listing all tables from $TCA:
				$tNames = array_keys($TCA);
				foreach ($tNames as $tableName) {
					foreach ($elements as $eKey => $value) {
						if (!strcmp($tableName, $value)) {
							$dataAcc[$eKey] = rawurlencode($value) . '|' . rawurlencode($this->sL($TCA[$value]['ctrl']['title']));
						}
					}
				}
			break;
			case 'pagetypes': // Listing all page types (doktype)
				$theTypes = $TCA['pages']['columns']['doktype']['config']['items'];
				if (is_array($theTypes)) {
					foreach ($theTypes as $theTypesArrays) {
						foreach ($elements as $eKey => $value) {
							if (!strcmp($theTypesArrays[1], $value)) {
								$dataAcc[$eKey] = rawurlencode($value) . '|' . rawurlencode($this->sL($theTypesArrays[0]));
							}
						}
					}
				}
			break;
			case 'exclude': // Listing exclude fields.
				$theExcludeFields = t3lib_BEfunc::getExcludeFields();

				if (is_array($theExcludeFields)) {
					foreach ($theExcludeFields as $theExcludeFieldsArrays) {
						foreach ($elements as $eKey => $value) {
							if (!strcmp($theExcludeFieldsArrays[1], $value)) {
								$dataAcc[$eKey] = rawurlencode($value) . '|' . rawurlencode(rtrim($theExcludeFieldsArrays[0], ':'));
							}
						}
					}
				}
			break;
			case 'explicitValues':
				$theTypes = t3lib_BEfunc::getExplicitAuthFieldValues();

				foreach ($theTypes as $tableFieldKey => $theTypeArrays) {
					if (is_array($theTypeArrays['items'])) {
						foreach ($theTypeArrays['items'] as $itemValue => $itemContent) {
							foreach ($elements as $eKey => $value) {
								if (!strcmp($tableFieldKey . ':' . $itemValue . ':' . $itemContent[0], $value)) {
									$dataAcc[$eKey] = rawurlencode($value) . '|' . rawurlencode('[' . $itemContent[2] . '] ' . $itemContent[1]);
								}
							}
						}
					}
				}
			break;
			case 'languages':
				$theLangs = t3lib_BEfunc::getSystemLanguages();
				foreach ($theLangs as $lCfg) {
					foreach ($elements as $eKey => $value) {
						if (!strcmp($lCfg[1], $value)) {
							$dataAcc[$eKey] = rawurlencode($value) . '|' . rawurlencode($lCfg[0]);
						}
					}
				}
			break;
			case 'custom':
				$customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];

				if (is_array($customOptions)) {
					foreach ($customOptions as $coKey => $coValue) {
						if (is_array($coValue['items'])) {
								// Traverse items:
							foreach ($coValue['items'] as $itemKey => $itemCfg) {
								foreach ($elements as $eKey => $value) {
									if (!strcmp($coKey . ':' . $itemKey, $value)) {
										$dataAcc[$eKey] = rawurlencode($value) . '|' . rawurlencode($this->sL($itemCfg[0]));
									}
								}
							}
						}
					}
				}
			break;
			case 'modListGroup': // Listing modules for GROUPS
			case 'modListUser': // Listing modules for USERS:
				if (!$this->loadModules) {
					$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
					$this->loadModules->load($GLOBALS['TBE_MODULES']);
				}
				$modList = ($specialKey == 'modListUser') ? $this->loadModules->modListUser : $this->loadModules->modListGroup;

				foreach ($modList as $theModName) {
					foreach ($elements as $eKey => $value) {
						$label = '';
							// Add label for main module:
						$pp = explode('_', $value);
						if (count($pp) > 1) {
							$label .= $GLOBALS['LANG']->moduleLabels['tabs'][$pp[0] . '_tab'] . '>';
						}
							// Add modules own label now:
						$label .= $GLOBALS['LANG']->moduleLabels['tabs'][$value . '_tab'];

						if (!strcmp($theModName, $value)) {
							$dataAcc[$eKey] = rawurlencode($value) . '|' . rawurlencode($label);
						}
					}
				}
			break;
		}

		return $dataAcc;
	}

	/**
	 * Adds the foreign record elements to $dataAcc, if any
	 *
	 * @param	array		Array with numeric keys, containing values for the selector box, prepared for interface. We are going to add elements to this array as needed.
	 * @param	array		The array of original elements - basically the field value exploded by ","
	 * @param	array		Field configuration from TCA
	 * @param	string		The field name
	 * @param	array		TSconfig for the record
	 * @param	array		The record
	 * @param	array		The current table
	 * @return	array		Modified $dataAcc array
	 * @access private
	 * @see renderRecord_selectProc()
	 */
	function selectAddForeign($dataAcc, $elements, $fieldConfig, $field, $TSconfig, $row, $table) {
		global $TCA;

			// Init:
		$recordList = array();

			// foreign_table
		$subres = t3lib_BEfunc::exec_foreign_table_where_query($fieldConfig, $field, $TSconfig);
		while ($subrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres)) {
			$recordList[$subrow['uid']] = t3lib_BEfunc::getRecordTitle($fieldConfig['config']['foreign_table'], $subrow);
		}

			// neg_foreign_table
		if (is_array($TCA[$fieldConfig['config']['neg_foreign_table']])) {
			$subres = t3lib_BEfunc::exec_foreign_table_where_query($fieldConfig, $field, $TSconfig, 'neg_');
			while ($subrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($subres)) {
				$recordList[-$subrow['uid']] = t3lib_BEfunc::getRecordTitle($fieldConfig['config']['neg_foreign_table'], $subrow);
			}
		}

			// At this point all records that CAN be selected is found in $recordList
			// Now, get the data from loadDBgroup based on the input list of values.
		$dataIds = $this->getDataIdList($elements, $fieldConfig, $row, $table);
		if ($fieldConfig['config']['MM']) {
			$dataAcc = array();
		} // Reset, if MM (which cannot bear anything but real relations!)

			// After this we can traverse the loadDBgroup values and match values with the list of possible values in $recordList:
		foreach ($dataIds as $theId) {
			if (isset($recordList[$theId])) {
				$lPrefix = $this->sL($fieldConfig['config'][($theId > 0 ? '' : 'neg_') . 'foreign_table_prefix']);
				if ($fieldConfig['config']['MM'] || $fieldConfig['config']['foreign_field']) {
					$dataAcc[] = rawurlencode($theId) . '|' . rawurlencode(t3lib_div::fixed_lgd_cs($lPrefix . strip_tags($recordList[$theId]), $GLOBALS['BE_USER']->uc['titleLen']));
				} else {
					foreach ($elements as $eKey => $value) {
						if (!strcmp($theId, $value)) {
							$dataAcc[$eKey] = rawurlencode($theId) . '|' . rawurlencode(t3lib_div::fixed_lgd_cs($lPrefix . strip_tags($recordList[$theId]), $GLOBALS['BE_USER']->uc['titleLen']));
						}
					}
				}
			}
		}

		return $dataAcc;
	}

	/**
	 * Returning the id-list processed by loadDBgroup for the foreign tables.
	 *
	 * @param	array		The array of original elements - basically the field value exploded by ","
	 * @param	array		Field configuration from TCA
	 * @param	array		The data array, currently. Used to set the "local_uid" for selecting MM relation records.
	 * @param	string		Current table name. passed on to t3lib_loadDBGroup
	 * @return	array		An array with ids of the records from the input elements array.
	 * @access private
	 */
	function getDataIdList($elements, $fieldConfig, $row, $table) {
		$loadDB = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$loadDB->registerNonTableValues = $fieldConfig['config']['allowNonIdValues'] ? 1 : 0;
		$loadDB->start(implode(',', $elements),
					   $fieldConfig['config']['foreign_table'] . ',' . $fieldConfig['config']['neg_foreign_table'],
					   $fieldConfig['config']['MM'],
					   $row['uid'],
					   $table,
					   $fieldConfig['config']
		);

		$idList = $loadDB->convertPosNeg($loadDB->getValueArray(), $fieldConfig['config']['foreign_table'], $fieldConfig['config']['neg_foreign_table']);

		return $idList;
	}

	/**
	 * Processing of selector box items. This includes the automated adding of elements plus user-function processing.
	 *
	 * @param	array		The elements to process
	 * @param	array		TCA/columns configuration
	 * @param	array		TSconfig for the field
	 * @param	string		The table name
	 * @param	array		The current row
	 * @param	string		The field name
	 * @return	array		The modified input $selItems array
	 * @access private
	 * @see renderRecord_selectProc()
	 */
	function procesItemArray($selItems, $config, $fieldTSConfig, $table, $row, $field) {
		$selItems = $this->addItems($selItems, $fieldTSConfig['addItems.']);
		if ($config['itemsProcFunc']) {
			$selItems = $this->procItems($selItems, $fieldTSConfig['itemsProcFunc.'], $config, $table, $row, $field);
		}
		return $selItems;
	}

	/**
	 * Adding items from $iArray to $items array
	 *
	 * @param	array		The array of selector box items to which key(value) / value(label) pairs from $iArray will be added.
	 * @param	array		The array of elements to add. The keys will become values. The value will become the label.
	 * @return	array		The modified input $items array
	 * @access private
	 * @see procesItemArray()
	 */
	function addItems($items, $iArray) {
		if (is_array($iArray)) {
			foreach ($iArray as $value => $label) {
				$items[] = array($label, $value);
			}
		}
		return $items;
	}

	/**
	 * User processing of a selector box array of values.
	 *
	 * @param	array		The array of selector box items
	 * @param	array		TSconfig for the fields itemProcFunc
	 * @param	array		TCA/columns configuration
	 * @param	string		The table name
	 * @param	array		The current row
	 * @param	string		The field name
	 * @return	array		The modified input $items array
	 * @access private
	 * @see procesItemArray()
	 */
	function procItems($items, $itemsProcFuncTSconfig, $config, $table, $row, $field) {
		$params = array();
		$params['items'] = &$items;
		$params['config'] = $config;
		$params['TSconfig'] = $itemsProcFuncTSconfig;
		$params['table'] = $table;
		$params['row'] = $row;
		$params['field'] = $field;

		t3lib_div::callUserFunction($config['itemsProcFunc'], $params, $this);
		return $items;
	}


	/***********************************************
	 *
	 * Helper functions
	 *
	 ***********************************************/

	/**
	 * Sets the lock for a record from table/id, IF $this->lockRecords is set!
	 *
	 * @param	string		The table name
	 * @param	integer		The id of the record
	 * @param	integer		The pid of the record
	 * @return	void
	 */
	function lockRecord($table, $id, $pid = 0) {
		if ($this->lockRecords) {
			t3lib_BEfunc::lockRecords($table, $id, $pid);
		}
	}

	/**
	 * Dummy function, can be used to "register" records. Used by eg. the "show_item" script.
	 *
	 * @param	string		Table name
	 * @param	integer		Record id
	 * @param	string		Field name
	 * @param	string		Field content.
	 * @return	void
	 * @access private
	 * @see renderRecord()
	 */
	function regItem($table, $id, $field, $content) {
	}

	/**
	 * Local wrapper function for LANG->sL (returning language labels)
	 *
	 * @param	string		Language label key
	 * @return	string		Localized label value.
	 * @access private
	 */
	function sL($in) {
		return $GLOBALS['LANG']->sL($in);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_transferdata.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_transferdata.php']);
}

?>