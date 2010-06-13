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
 * T3D file Import/Export library (TYPO3 Record Document)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  198: class tx_impexp
 *
 *              SECTION: Initialize
 *  261:     function init($dontCompress=0,$mode='')
 *
 *              SECTION: Export / Init + Meta Data
 *  292:     function setHeaderBasics()
 *  316:     function setCharset($charset)
 *  331:     function setMetaData($title,$description,$notes,$packager_username,$packager_name,$packager_email)
 *  351:     function addThumbnail($imgFilepath)
 *
 *              SECTION: Export / Init Page tree
 *  389:     function setPageTree($idH)
 *  402:     function unsetExcludedSections($idH)
 *  424:     function flatInversePageTree($idH,$a=array())
 *  447:     function flatInversePageTree_pid($idH,$a=array(),$pid=-1)
 *
 *              SECTION: Export
 *  486:     function export_addRecord($table,$row,$relationLevel=0)
 *  544:     function export_addDBRelations($relationLevel=0)
 *  648:     function export_addDBRelations_registerRelation($fI, &$addR, $tokenID='')
 *  672:     function export_addFilesFromRelations()
 *  773:     function export_addFile($fI, $recordRef='', $fieldname='')
 *  898:     function flatDBrels($dbrels)
 *  924:     function flatSoftRefs($dbrels)
 *
 *              SECTION: File Output
 *  988:     function compileMemoryToFileContent($type='')
 * 1014:     function createXML()
 * 1106:     function doOutputCompress()
 * 1117:     function addFilePart($data, $compress=FALSE)
 *
 *              SECTION: Import
 * 1150:     function importData($pid)
 * 1191:     function writeRecords_pages($pid)
 * 1246:     function writeRecords_pages_order($pid)
 * 1284:     function writeRecords_records($pid)
 * 1334:     function writeRecords_records_order($mainPid)
 * 1383:     function addSingle($table,$uid,$pid)
 * 1457:     function addToMapId($substNEWwithIDs)
 * 1477:     function getNewTCE()
 * 1491:     function unlinkTempFiles()
 *
 *              SECTION: Import / Relations setting
 * 1529:     function setRelations()
 * 1584:     function setRelations_db($itemArray)
 * 1611:     function import_addFileNameToBeCopied($fI)
 * 1634:     function setFlexFormRelations()
 * 1718:     function remapListedDBRecords_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2, $path)
 *
 *              SECTION: Import / Soft References
 * 1760:     function processSoftReferences()
 * 1851:     function processSoftReferences_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2, $path)
 * 1890:     function processSoftReferences_substTokens($tokenizedContent, $softRefCfgs, $table, $uid)
 * 1954:     function processSoftReferences_saveFile($relFileName, $cfg, $table, $uid)
 * 2015:     function processSoftReferences_saveFile_createRelFile($origDirPrefix, $fileName, $fileID, $table, $uid)
 * 2104:     function writeFileVerify($fileName, $fileID, $bypassMountCheck=FALSE)
 * 2131:     function checkOrCreateDir($dirPrefix)
 * 2164:     function verifyFolderAccess($dirPrefix, $noAlternative=FALSE)
 *
 *              SECTION: File Input
 * 2214:     function loadFile($filename,$all=0)
 * 2257:     function getNextFilePart($fd,$unserialize=0,$name='')
 * 2284:     function loadContent($filecontent)
 * 2302:     function getNextContentPart($filecontent,&$pointer,$unserialize=0,$name='')
 * 2327:     function loadInit()
 * 2343:     function fixCharsets()
 *
 *              SECTION: Visual rendering of import/export memory, $this->dat
 * 2398:     function displayContentOverview()
 * 2506:     function traversePageTree($pT,&$lines,$preCode='')
 * 2541:     function traversePageRecords($pT,&$lines)
 * 2568:     function traverseAllRecords($pT,&$lines)
 * 2590:     function singleRecordLines($table,$uid,&$lines,$preCode,$checkImportInPidRecord=0)
 * 2748:     function addRelations($rels,&$lines,$preCode,$recurCheck=array(),$htmlColorClass='')
 * 2813:     function addFiles($rels,&$lines,$preCode,$htmlColorClass='',$tokenID='')
 * 2931:     function checkDokType($checkTable,$doktype)
 * 2947:     function renderControls($r)
 * 2975:     function softrefSelector($cfg)
 *
 *              SECTION: Helper functions of kinds
 * 3051:     function isTableStatic($table)
 * 3065:     function inclRelation($table)
 * 3080:     function isExcluded($table,$uid)
 * 3092:     function includeSoftref($tokenID)
 * 3102:     function checkPID($pid)
 * 3119:     function dontIgnorePid($table, $uid)
 * 3132:     function doesRecordExist($table,$uid,$fields='')
 * 3142:     function getRecordPath($pid)
 * 3159:     function renderSelectBox($prefix,$value,$optValues)
 * 3183:     function compareRecords($databaseRecord, $importRecord, $table, $inverseDiff=FALSE)
 * 3250:     function getRTEoriginalFilename($string)
 * 3267:     function &getFileProcObj()
 *
 *              SECTION: Error handling
 * 3299:     function error($msg)
 * 3308:     function printErrorLog()
 *
 * TOTAL FUNCTIONS: 72
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * EXAMPLE for using the impexp-class for exporting stuff:
 *
 * 		// Create and initialize:
 * 	$this->export = t3lib_div::makeInstance('tx_impexp');
 * 	$this->export->init();
 * 		// Set which tables relations we will allow:
 * 	$this->export->relOnlyTables[]="tt_news";	// exclusively includes. See comment in the class
 *
 * 		// Adding records:
 * 	$this->export->export_addRecord("pages",$this->pageinfo);
 * 	$this->export->export_addRecord("pages",t3lib_BEfunc::getRecord("pages",38));
 * 	$this->export->export_addRecord("pages",t3lib_BEfunc::getRecord("pages",39));
 * 	$this->export->export_addRecord("tt_content",t3lib_BEfunc::getRecord("tt_content",12));
 * 	$this->export->export_addRecord("tt_content",t3lib_BEfunc::getRecord("tt_content",74));
 * 	$this->export->export_addRecord("sys_template",t3lib_BEfunc::getRecord("sys_template",20));
 *
 * 		// Adding all the relations (recursively in 5 levels so relations has THEIR relations registered as well)
 * 	for($a=0;$a<5;$a++)	{
 * 		$addR = $this->export->export_addDBRelations($a);
 * 		if (!count($addR)) break;
 * 	}
 *
 * 		// Finally load all the files.
 * 	$this->export->export_addFilesFromRelations();	// MUST be after the DBrelations are set so that file from ALL added records are included!
 *
 * 		// Now the internal DAT array is ready to export:
 * 	#debug($this->export->dat);
 *
 * 		// Write export
 * 	$out = $this->export->compileMemoryToFileContent();
 * 	#t3lib_div::writeFile(PATH_site."fileadmin/relations.t3d",$out);
 * 	#debug(strlen($out));
 */

@ini_set('max_execution_time',600);
@ini_set('memory_limit','256m');







/**
 * T3D file Import/Export library (TYPO3 Record Document)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class tx_impexp {

		// Configuration, general
	var $showStaticRelations = FALSE;		// If set, static relations (not exported) will be shown in overview as well
	var $fileadminFolderName = 'fileadmin';	// Name of the "fileadmin" folder where files for export/import should be located

	var $mode = '';							// Whether "import" or "export" mode of object. Set through init() function
	var $update = FALSE;					// Updates all records that has same UID instead of creating new!
	var $doesImport = FALSE;				// Is set by importData() when an import has been done.

		// Configuration, import
	var $display_import_pid_record = '';		// If set to a page-record, then the preview display of the content will expect this page-record to be the target for the import and accordingly display validation information. This triggers the visual view of the import/export memory to validate if import is possible
	var $suggestedInsertUids = array();		// Used to register the forged UID values for imported records that we want to create with the same UIDs as in the import file. Admin-only feature.
	var $import_mode = array();				// Setting import modes during update state: as_new, exclude, force_uid
	var $global_ignore_pid = FALSE;			// If set, PID correct is ignored globally
	var $force_all_UIDS = FALSE;			// If set, all UID values are forced! (update or import)
	var $showDiff = FALSE;					// If set, a diff-view column is added to the overview.
	var $allowPHPScripts = FALSE;			// If set, and if the user is admin, allow the writing of PHP scripts to fileadmin/ area.
	var $enableLogging = FALSE;				// Disable logging when importing
	var $softrefInputValues = array();		// Array of values to substitute in editable softreferences.
	var $fileIDMap = array();				// Mapping between the fileID from import memory and the final filenames they are written to.

		// Configuration, export
	var $maxFileSize = 1000000;		// 1MB max file size
	var $maxRecordSize = 1000000;	// 1MB max record size
	var $maxExportSize = 10000000;	// 10MB max export size
	var $relOnlyTables = array();	// add table names here which are THE ONLY ones which will be included into export if found as relations. '_ALL' will allow all tables.
	var $relStaticTables = array();	// add tables names here which should not be exported with the file. (Where relations should be mapped to same UIDs in target system).
	var $excludeMap = array();		// Exclude map. Keys are table:uid  pairs and if set, records are not added to the export.
	var $softrefCfg = array();		// Soft Reference Token ID modes.
	var $extensionDependencies = array();		// Listing extension dependencies.
	var $dontCompress = 0;			// Set  by user: If set, compression in t3d files is disabled
	var $includeExtFileResources = 0;	// Boolean, if set, HTML file resources are included.
	var $extFileResourceExtensions = 'html,htm,css';	// Files with external media (HTML/css style references inside)

		// Internal, dynamic:
	var $import_mapId = array();		// After records are written this array is filled with [table][original_uid] = [new_uid]
	var $import_newId = array();		// Keys are [tablename]:[new NEWxxx ids (or when updating it is uids)] while values are arrays with table/uid of the original record it is based on. By the array keys the new ids can be looked up inside tcemain
	var $import_newId_pids = array();	// Page id map for page tree (import)
	var $import_data = array();			// Internal data accumulation for writing records during import
	var $errorLog = array();			// Error log.
	var $cache_getRecordPath = array();	// Cache for record paths
	var $checkPID_cache = array();		// Cache of checkPID values.

	var $compress = 0;					// Set internally if the gzcompress function exists
	var $dat = array();					// Internal import/export memory

	/**
	 * File processing object
	 *
	 * @var t3lib_extFileFunctions
	 */
	var $fileProcObj = '';



	/**************************
	 *
	 * Initialize
	 *
	 *************************/

	/**
	 * Init the object, both import and export
	 *
	 * @param	boolean		If set, compression in t3d files is disabled
	 * @param	string		Mode of usage, either "import" or "export"
	 * @return	void
	 */
	function init($dontCompress=0,$mode='')	{
		$this->compress = function_exists('gzcompress');
		$this->dontCompress = $dontCompress;

		$this->mode = $mode;
	}














	/**************************
	 *
	 * Export / Init + Meta Data
	 *
	 *************************/

	/**
	 * Set header basics
	 *
	 * @return	void
	 */
	function setHeaderBasics()	{

			// Initializing:
		if (is_array($this->softrefCfg))	{
			foreach($this->softrefCfg as $key => $value)	{
				if (!strlen($value['mode']))	unset($this->softrefCfg[$key]);
			}
		}

			// Setting in header memory:
		$this->dat['header']['XMLversion'] = '1.0';		// Version of file format
		$this->dat['header']['meta'] = array();			// Initialize meta data array (to put it in top of file)
		$this->dat['header']['relStaticTables'] = $this->relStaticTables;	// Add list of tables to consider static
		$this->dat['header']['excludeMap'] = $this->excludeMap;				// The list of excluded records
		$this->dat['header']['softrefCfg'] = $this->softrefCfg;			// Soft Reference mode for elements
		$this->dat['header']['extensionDependencies'] = $this->extensionDependencies;		// List of extensions the import depends on.
	}

	/**
	 * Set charset
	 *
	 * @param	string		Charset for the content in the export. During import the character set will be converted if the target system uses another charset.
	 * @return	void
	 */
	function setCharset($charset)	{
		$this->dat['header']['charset'] = $charset;
	}

	/**
	 * Sets meta data
	 *
	 * @param	string		Title of the export
	 * @param	string		Description of the export
	 * @param	string		Notes about the contents
	 * @param	string		Backend Username of the packager (the guy making the export)
	 * @param	string		Real name of the packager
	 * @param	string		Email of the packager
	 * @return	void
	 */
	function setMetaData($title,$description,$notes,$packager_username,$packager_name,$packager_email)	{
		$this->dat['header']['meta'] = array(
			'title' => $title,
			'description' => $description,
			'notes' => $notes,
			'packager_username' => $packager_username,
			'packager_name' => $packager_name,
			'packager_email' => $packager_email,
			'TYPO3_version' => TYPO3_version,
			'created' => strftime('%A %e. %B %Y', $GLOBALS['EXEC_TIME']),
		);
	}

	/**
	 * Sets a thumbnail image to the exported file
	 *
	 * @param	string		Filename reference, gif, jpg, png. Absolute path.
	 * @return	void
	 */
	function addThumbnail($imgFilepath)	{
		if (@is_file($imgFilepath))	{
			$imgInfo = @getimagesize($imgFilepath);
			if (is_array($imgInfo))	{
				$fileContent = t3lib_div::getUrl($imgFilepath);
				$this->dat['header']['thumbnail'] = array(
					'imgInfo' => $imgInfo,
					'content' => $fileContent,
					'filesize' => strlen($fileContent),
					'filemtime' => filemtime($imgFilepath),
					'filename' => basename($imgFilepath)
				);
			}
		}
	}











	/**************************
	 *
	 * Export / Init Page tree
	 *
	 *************************/

	/**
	 * Sets the page-tree array in the export header and returns the array in a flattened version
	 *
	 * @param	array		Hierarchy of ids, the page tree: array([uid] => array("uid" => [uid], "subrow" => array(.....)), [uid] => ....)
	 * @return	array		The hierarchical page tree converted to a one-dimensional list of pages
	 */
	function setPageTree($idH)	{
		$this->dat['header']['pagetree'] = $this->unsetExcludedSections($idH);
		return $this->flatInversePageTree($this->dat['header']['pagetree']);
	}

	/**
	 * Removes entries in the page tree which are found in ->excludeMap[]
	 *
	 * @param	array		Page uid hierarchy
	 * @return	array		Modified input array
	 * @access private
	 * @see setPageTree()
	 */
	function unsetExcludedSections($idH)	{
		if (is_array($idH))	{
			foreach ($idH as $k => $v) {
				if ($this->excludeMap['pages:'.$idH[$k]['uid']])	{
					unset($idH[$k]);
				} elseif (is_array($idH[$k]['subrow']))	{
					$idH[$k]['subrow'] = $this->unsetExcludedSections($idH[$k]['subrow']);
				}
			}
		}
		return $idH;
	}

	/**
	 * Recursively flattening the idH array (for setPageTree() function)
	 *
	 * @param	array		Page uid hierarchy
	 * @param	array		Accumulation array of pages (internal, don't set from outside)
	 * @return	array		Array with uid-uid pairs for all pages in the page tree.
	 * @see flatInversePageTree_pid()
	 */
	function flatInversePageTree($idH,$a=array())	{
		if (is_array($idH))	{
			$idH = array_reverse($idH);
			foreach ($idH as $k => $v) {
				$a[$v['uid']] = $v['uid'];
				if (is_array($v['subrow']))	{
					$a = $this->flatInversePageTree($v['subrow'],$a);
				}
			}
		}
		return $a;
	}

	/**
	 * Recursively flattening the idH array (for setPageTree() function), setting PIDs as values
	 *
	 * @param	array		Page uid hierarchy
	 * @param	array		Accumulation array of pages (internal, don't set from outside)
	 * @param	integer		PID value (internal)
	 * @return	array		Array with uid-pid pairs for all pages in the page tree.
	 * @see flatInversePageTree()
	 */
	function flatInversePageTree_pid($idH,$a=array(),$pid=-1)	{
		if (is_array($idH))	{
			$idH = array_reverse($idH);
			foreach ($idH as $k => $v) {
				$a[$v['uid']] = $pid;
				if (is_array($v['subrow']))	{
					$a = $this->flatInversePageTree_pid($v['subrow'],$a,$v['uid']);
				}
			}
		}
		return $a;
	}











	/**************************
	 *
	 * Export
	 *
	 *************************/

	/**
	 * Adds the record $row from $table.
	 * No checking for relations done here. Pure data.
	 *
	 * @param	string		Table name
	 * @param	array		Record row.
	 * @param	integer		(Internal) if the record is added as a relation, this is set to the "level" it was on.
	 * @return	void
	 */
	function export_addRecord($table,$row,$relationLevel=0)	{

		t3lib_BEfunc::workspaceOL($table,$row);

		if (strcmp($table,'') && is_array($row) && $row['uid']>0 && !$this->excludeMap[$table.':'.$row['uid']])	{
			if ($this->checkPID($table==='pages' ? $row['uid'] : $row['pid']))	{
				if (!isset($this->dat['records'][$table.':'.$row['uid']]))	{

						// Prepare header info:
					$headerInfo = array();
					$headerInfo['uid'] = $row['uid'];
					$headerInfo['pid'] = $row['pid'];
					$headerInfo['title'] = t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table,$row),40);
					$headerInfo['size'] = strlen(serialize($row));
					if ($relationLevel)	{
						$headerInfo['relationLevel'] = $relationLevel;
					}

						// If record content is not too large in size, set the header content and add the rest:
					if ($headerInfo['size']<$this->maxRecordSize)	{

							// Set the header summary:
						$this->dat['header']['records'][$table][$row['uid']] = $headerInfo;

							// Create entry in the PID lookup:
						$this->dat['header']['pid_lookup'][$row['pid']][$table][$row['uid']]=1;

							// Initialize reference index object:
						$refIndexObj = t3lib_div::makeInstance('t3lib_refindex');
						$refIndexObj->WSOL = TRUE;	// Yes to workspace overlays for exporting....

							// Data:
						$this->dat['records'][$table.':'.$row['uid']] = array();
						$this->dat['records'][$table.':'.$row['uid']]['data'] = $row;
						$this->dat['records'][$table.':'.$row['uid']]['rels'] = $refIndexObj->getRelations($table,$row);
						$this->errorLog = array_merge($this->errorLog,$refIndexObj->errorLog);	// Merge error logs.

							// Add information about the relations in the record in the header:
						$this->dat['header']['records'][$table][$row['uid']]['rels'] = $this->flatDBrels($this->dat['records'][$table.':'.$row['uid']]['rels']);

							// Add information about the softrefs to header:
						$this->dat['header']['records'][$table][$row['uid']]['softrefs'] = $this->flatSoftRefs($this->dat['records'][$table.':'.$row['uid']]['rels']);

					} else $this->error('Record '.$table.':'.$row['uid'].' was larger than maxRecordSize ('.t3lib_div::formatSize($this->maxRecordSize).')');
				} else $this->error('Record '.$table.':'.$row['uid'].' already added.');
			} else $this->error('Record '.$table.':'.$row['uid'].' was outside your DB mounts!');
		}
	}

	/**
	 * This analyses the existing added records, finds all database relations to records and adds these records to the export file.
	 * This function can be called repeatedly until it returns an empty array. In principle it should not allow to infinite recursivity, but you better set a limit...
	 * Call this BEFORE the ext_addFilesFromRelations (so files from added relations are also included of course)
	 *
	 * @param	integer		Recursion level
	 * @return	array		overview of relations found and added: Keys [table]:[uid], values array with table and id
	 * @see export_addFilesFromRelations()
	 */
	function export_addDBRelations($relationLevel=0)	{
		global $TCA;

			// Initialize:
		$addR = array();

			// Traverse all "rels" registered for "records"
		if (is_array($this->dat['records']))	{
			foreach ($this->dat['records'] as $k => $value) {
				if (is_array($this->dat['records'][$k]))	{
					foreach ($this->dat['records'][$k]['rels'] as $fieldname => $vR) {
#debug($vR);
							// For all DB types of relations:
						if ($vR['type']=='db')	{
							foreach($vR['itemArray'] as $fI)	{
								$this->export_addDBRelations_registerRelation($fI, $addR);
							}
						}

							// For all flex/db types of relations:
						if ($vR['type']=='flex')	{
								// DB relations in flex form fields:
							if (is_array($vR['flexFormRels']['db']))	{
								foreach($vR['flexFormRels']['db'] as $subList)	{
									foreach($subList as $fI)	{
										$this->export_addDBRelations_registerRelation($fI, $addR);
									}
								}
							}
								// DB oriented soft references in flex form fields:
							if (is_array($vR['flexFormRels']['softrefs']))	{
								foreach($vR['flexFormRels']['softrefs'] as $subList)	{
									foreach($subList['keys'] as $spKey => $elements)	{
										foreach($elements as $el)	{
											if ($el['subst']['type'] === 'db' && $this->includeSoftref($el['subst']['tokenID']))	{
												list($tempTable, $tempUid) = explode(':', $el['subst']['recordRef']);
												$fI = array(
													'table' => $tempTable,
													'id' => $tempUid
												);
												$this->export_addDBRelations_registerRelation($fI, $addR, $el['subst']['tokenID']);
											}
										}
									}
								}
							}
						}

							// In any case, if there are soft refs:
						if (is_array($vR['softrefs']['keys']))	{
							foreach($vR['softrefs']['keys'] as $spKey => $elements)	{
								foreach($elements as $el)	{
									if ($el['subst']['type'] === 'db' && $this->includeSoftref($el['subst']['tokenID']))	{
										list($tempTable, $tempUid) = explode(':', $el['subst']['recordRef']);
										$fI = array(
											'table' => $tempTable,
											'id' => $tempUid
										);
										$this->export_addDBRelations_registerRelation($fI, $addR, $el['subst']['tokenID']);
									}
								}
							}
						}
					}
				}
			}
		} else $this->error('There were no records available.');

			// Now, if there were new records to add, do so:
		if (count($addR))	{
			foreach($addR as $fI)	{

					// Get and set record:
				$row = t3lib_BEfunc::getRecord($fI['table'],$fI['id']);
				if (is_array($row))	{
					$this->export_addRecord($fI['table'],$row,$relationLevel+1);
				}

					// Set status message
				if ($fI['id']>0)	{	// Relation pointers always larger than zero except certain "select" types with negative values pointing to uids - but that is not supported here.
					$rId = $fI['table'].':'.$fI['id'];
					if (!isset($this->dat['records'][$rId]))	{
						$this->dat['records'][$rId] = 'NOT_FOUND';
						$this->error('Relation record '.$rId.' was not found!');
					}
				}
			}
		}

			// Return overview of relations found and added
		return $addR;
	}

	/**
	 * Helper function for export_addDBRelations()
	 *
	 * @param	array		Array with table/id keys to add
	 * @param	array		Add array, passed by reference to be modified
	 * @param	string		Softref Token ID, if applicable.
	 * @return	void
	 * @see export_addDBRelations()
	 */
	function export_addDBRelations_registerRelation($fI, &$addR, $tokenID='')	{
		global $TCA;

		$rId = $fI['table'].':'.$fI['id'];
		if (isset($TCA[$fI['table']])
				&& !$this->isTableStatic($fI['table'])
				&& !$this->isExcluded($fI['table'],$fI['id'])
				&& (!$tokenID || $this->includeSoftref($tokenID))
				&& $this->inclRelation($fI['table'])
				)	{
			if (!isset($this->dat['records'][$rId]))	{
					// Set this record to be included since it is not already.
				$addR[$rId] = $fI;
			}
		}
	}

	/**
	 * This adds all files in relations.
	 * Call this method AFTER adding all records including relations.
	 *
	 * @return	void
	 * @see export_addDBRelations()
	 */
	function export_addFilesFromRelations()	{

			// Traverse all "rels" registered for "records"
		if (is_array($this->dat['records']))	{
			foreach ($this->dat['records'] as $k => $value) {
				if (is_array($this->dat['records'][$k]['rels']))	{
					foreach ($this->dat['records'][$k]['rels'] as $fieldname => $vR) {

							// For all file type relations:
						if ($vR['type']=='file')	{
							foreach($vR['newValueFiles'] as $key => $fI)	{
								$this->export_addFile($fI, $k, $fieldname);
									// Remove the absolute reference to the file so it doesn't expose absolute paths from source server:
								unset($this->dat['records'][$k]['rels'][$fieldname]['newValueFiles'][$key]['ID_absFile']);
							}
						}

							// For all flex type relations:
						if ($vR['type']=='flex')	{
							if (is_array($vR['flexFormRels']['file']))	{
								foreach($vR['flexFormRels']['file'] as $key => $subList)	{
									foreach($subList as $subKey => $fI)	{
										$this->export_addFile($fI, $k, $fieldname);
											// Remove the absolute reference to the file so it doesn't expose absolute paths from source server:
										unset($this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['file'][$key][$subKey]['ID_absFile']);
									}
								}
							}

								// DB oriented soft references in flex form fields:
							if (is_array($vR['flexFormRels']['softrefs']))	{
								foreach($vR['flexFormRels']['softrefs'] as $key => $subList)	{
									foreach($subList['keys'] as $spKey => $elements)	{
										foreach($elements as $subKey => $el)	{
											if ($el['subst']['type'] === 'file' && $this->includeSoftref($el['subst']['tokenID']))	{

													// Create abs path and ID for file:
												$ID_absFile = t3lib_div::getFileAbsFileName(PATH_site.$el['subst']['relFileName']);
												$ID = md5($ID_absFile);

												if ($ID_absFile)	{
													if (!$this->dat['files'][$ID])	{
														$fI = array(
															'filename' => basename($ID_absFile),
															'ID_absFile' => $ID_absFile,
															'ID' => $ID,
															'relFileName' => $el['subst']['relFileName']
														);
														$this->export_addFile($fI, '_SOFTREF_');
													}
													$this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['softrefs'][$key]['keys'][$spKey][$subKey]['file_ID'] = $ID;
												}
											}
										}
									}
								}
							}
						}

							// In any case, if there are soft refs:
						if (is_array($vR['softrefs']['keys']))	{
							foreach($vR['softrefs']['keys'] as $spKey => $elements)	{
								foreach($elements as $subKey => $el)	{
									if ($el['subst']['type'] === 'file' && $this->includeSoftref($el['subst']['tokenID']))	{

											// Create abs path and ID for file:
										$ID_absFile = t3lib_div::getFileAbsFileName(PATH_site.$el['subst']['relFileName']);
										$ID = md5($ID_absFile);

										if ($ID_absFile)	{
											if (!$this->dat['files'][$ID])	{
												$fI = array(
													'filename' => basename($ID_absFile),
													'ID_absFile' => $ID_absFile,
													'ID' => $ID,
													'relFileName' => $el['subst']['relFileName']
												);
												$this->export_addFile($fI, '_SOFTREF_');
											}
											$this->dat['records'][$k]['rels'][$fieldname]['softrefs']['keys'][$spKey][$subKey]['file_ID'] = $ID;
										}
									}
								}
							}
						}
					}
				}
			}
		} else $this->error('There were no records available.');
	}

	/**
	 * Adds a files content to the export memory
	 *
	 * @param	array		File information with three keys: "filename" = filename without path, "ID_absFile" = absolute filepath to the file (including the filename), "ID" = md5 hash of "ID_absFile". "relFileName" is optional for files attached to records, but mandatory for soft referenced files (since the relFileName determines where such a file should be stored!)
	 * @param	string		If the file is related to a record, this is the id on the form [table]:[id]. Information purposes only.
	 * @param	string		If the file is related to a record, this is the field name it was related to. Information purposes only.
	 * @return	void
	 */
	function export_addFile($fI, $recordRef='', $fieldname='')	{
		if (@is_file($fI['ID_absFile']))	{
			if (filesize($fI['ID_absFile']) < $this->maxFileSize)	{
				$fileRec = array();
				$fileRec['filesize'] = filesize($fI['ID_absFile']);
				$fileRec['filename'] = basename($fI['ID_absFile']);
				$fileRec['filemtime'] = filemtime($fI['ID_absFile']);
					//for internal type file_reference
				$fileRec['relFileRef'] = substr($fI['ID_absFile'], strlen(PATH_site));
				if ($recordRef)	{
					$fileRec['record_ref'] = $recordRef.'/'.$fieldname;
				}
				if ($fI['relFileName'])	{
					$fileRec['relFileName'] = $fI['relFileName'];
				}

					// Setting this data in the header
				$this->dat['header']['files'][$fI['ID']] = $fileRec;

					// ... and for the recordlisting, why not let us know WHICH relations there was...
				if ($recordRef && $recordRef!=='_SOFTREF_')	{
					$refParts = explode(':',$recordRef,2);
					if (!is_array($this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs']))	{
						$this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs'] = array();
					}
					$this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs'][] = $fI['ID'];
				}

					// ... and finally add the heavy stuff:
				$fileRec['content'] = t3lib_div::getUrl($fI['ID_absFile']);
				$fileRec['content_md5'] = md5($fileRec['content']);
				$this->dat['files'][$fI['ID']] = $fileRec;


					// For soft references, do further processing:
				if ($recordRef === '_SOFTREF_')	{

						// RTE files?
					if ($RTEoriginal = $this->getRTEoriginalFilename(basename($fI['ID_absFile'])))	{
						$RTEoriginal_absPath = dirname($fI['ID_absFile']).'/'.$RTEoriginal;
						if (@is_file($RTEoriginal_absPath))	{

							$RTEoriginal_ID = md5($RTEoriginal_absPath);

							$fileRec = array();
							$fileRec['filesize'] = filesize($RTEoriginal_absPath);
							$fileRec['filename'] = basename($RTEoriginal_absPath);
							$fileRec['filemtime'] = filemtime($RTEoriginal_absPath);
							$fileRec['record_ref'] = '_RTE_COPY_ID:'.$fI['ID'];
							$this->dat['header']['files'][$fI['ID']]['RTE_ORIG_ID'] = $RTEoriginal_ID;

								// Setting this data in the header
							$this->dat['header']['files'][$RTEoriginal_ID] = $fileRec;

								// ... and finally add the heavy stuff:
							$fileRec['content'] = t3lib_div::getUrl($RTEoriginal_absPath);
							$fileRec['content_md5'] = md5($fileRec['content']);
							$this->dat['files'][$RTEoriginal_ID] = $fileRec;
						} else {
							$this->error('RTE original file "'.substr($RTEoriginal_absPath,strlen(PATH_site)).'" was not found!');
						}
					}

						// Files with external media?
						// This is only done with files grabbed by a softreference parser since it is deemed improbable that hard-referenced files should undergo this treatment.
					$html_fI = pathinfo(basename($fI['ID_absFile']));
					if ($this->includeExtFileResources && t3lib_div::inList($this->extFileResourceExtensions,strtolower($html_fI['extension'])))	{
						$uniquePrefix = '###' . md5($GLOBALS['EXEC_TIME']) . '###';

						if (strtolower($html_fI['extension'])==='css')	{
							$prefixedMedias = explode($uniquePrefix, preg_replace('/(url[[:space:]]*\([[:space:]]*["\']?)([^"\')]*)(["\']?[[:space:]]*\))/i', '\1'.$uniquePrefix.'\2'.$uniquePrefix.'\3', $fileRec['content']));
						} else {	// html, htm:
							$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
							$prefixedMedias = explode($uniquePrefix, $htmlParser->prefixResourcePath($uniquePrefix,$fileRec['content'],array(),$uniquePrefix));
						}

						$htmlResourceCaptured = FALSE;
						foreach($prefixedMedias as $k => $v)	{
							if ($k%2)	{
								$EXTres_absPath = t3lib_div::resolveBackPath(dirname($fI['ID_absFile']).'/'.$v);
								$EXTres_absPath = t3lib_div::getFileAbsFileName($EXTres_absPath);
								if ($EXTres_absPath && t3lib_div::isFirstPartOfStr($EXTres_absPath,PATH_site.$this->fileadminFolderName.'/') && @is_file($EXTres_absPath))	{

									$htmlResourceCaptured = TRUE;
									$EXTres_ID = md5($EXTres_absPath);
									$this->dat['header']['files'][$fI['ID']]['EXT_RES_ID'][] = $EXTres_ID;
									$prefixedMedias[$k] = '{EXT_RES_ID:'.$EXTres_ID.'}';

										// Add file to memory if it is not set already:
									if (!isset($this->dat['header']['files'][$EXTres_ID]))		{
										$fileRec = array();
										$fileRec['filesize'] = filesize($EXTres_absPath);
										$fileRec['filename'] = basename($EXTres_absPath);
										$fileRec['filemtime'] = filemtime($EXTres_absPath);
										$fileRec['record_ref'] = '_EXT_PARENT_:'.$fI['ID'];

										$fileRec['parentRelFileName'] = $v;		// Media relative to the HTML file.

											// Setting this data in the header
										$this->dat['header']['files'][$EXTres_ID] = $fileRec;

											// ... and finally add the heavy stuff:
										$fileRec['content'] = t3lib_div::getUrl($EXTres_absPath);
										$fileRec['content_md5'] = md5($fileRec['content']);
										$this->dat['files'][$EXTres_ID] = $fileRec;
									}
								}
							}
						}

						if ($htmlResourceCaptured)	{
							$this->dat['files'][$fI['ID']]['tokenizedContent'] = implode('', $prefixedMedias);
						}
					}
				}

			} else  $this->error($fI['ID_absFile'].' was larger ('.t3lib_div::formatSize(filesize($fI['ID_absFile'])).') than the maxFileSize ('.t3lib_div::formatSize($this->maxFileSize).')! Skipping.');
		} else $this->error($fI['ID_absFile'].' was not a file! Skipping.');
	}

	/**
	 * DB relations flattend to 1-dim array.
	 * The list will be unique, no table/uid combination will appear twice.
	 *
	 * @param	array		2-dim Array of database relations organized by table key
	 * @return	array		1-dim array where entries are table:uid and keys are array with table/id
	 */
	function flatDBrels($dbrels)	{
		$list = array();

		foreach($dbrels as $dat)	{
			if ($dat['type']=='db')	{
				foreach($dat['itemArray'] as $i)	{
					$list[$i['table'].':'.$i['id']] = $i;
				}
			}
			if ($dat['type']=='flex' && is_array($dat['flexFormRels']['db']))	{
				foreach($dat['flexFormRels']['db'] as $subList)	{
					foreach($subList as $i)	{
						$list[$i['table'].':'.$i['id']] = $i;
					}
				}
			}
		}
		return $list;
	}

	/**
	 * Soft References flattend to 1-dim array.
	 *
	 * @param	array		2-dim Array of database relations organized by table key
	 * @return	array		1-dim array where entries are arrays with properties of the soft link found and keys are a unique combination of field, spKey, structure path if applicable and token ID
	 */
	function flatSoftRefs($dbrels)	{
		$list = array();
#debug($dbrels);
		foreach($dbrels as $field => $dat)	{
			if (is_array($dat['softrefs']['keys']))	{
				foreach($dat['softrefs']['keys'] as $spKey => $elements)	{
					if (is_array($elements))	{
						foreach($elements as $subKey => $el)	{
							$lKey = $field.':'.$spKey.':'.$subKey;
							$list[$lKey] = array_merge(array('field' => $field, 'spKey' => $spKey),$el);

								// Add file_ID key to header - slightly "risky" way of doing this because if the calculation changes for the same value in $this->records[...] this will not work anymore!
							if ($el['subst'] && $el['subst']['relFileName'])	{
								$list[$lKey]['file_ID'] = md5(PATH_site.$el['subst']['relFileName']);
							}
						}
					}
				}
			}
			if ($dat['type']=='flex' && is_array($dat['flexFormRels']['softrefs']))	{
				foreach($dat['flexFormRels']['softrefs'] as $structurePath => $subSoftrefs)	{
					if (is_array($subSoftrefs['keys']))	{
						foreach($subSoftrefs['keys'] as $spKey => $elements)	{
							foreach($elements as $subKey => $el)	{
								$lKey = $field.':'.$structurePath.':'.$spKey.':'.$subKey;
								$list[$lKey] = array_merge(array('field' => $field, 'spKey' => $spKey, 'structurePath' => $structurePath),$el);

									// Add file_ID key to header - slightly "risky" way of doing this because if the calculation changes for the same value in $this->records[...] this will not work anymore!
								if ($el['subst'] && $el['subst']['relFileName'])	{
									$list[$lKey]['file_ID'] = md5(PATH_site.$el['subst']['relFileName']);
								}
							}
						}
					}
				}
			}
		}

#debug($list);
		return $list;
	}











	/**************************
	 *
	 * File Output
	 *
	 *************************/

	/**
	 * This compiles and returns the data content for an exported file
	 *
	 * @param	string		Type of output; "xml" gives xml, otherwise serialized array, possibly compressed.
	 * @return	string		The output file stream
	 */
	function compileMemoryToFileContent($type='')	{

		if ($type=='xml')	{
			$out = $this->createXML();
		} else {
			$compress = $this->doOutputCompress();
			$out = '';

			// adding header:
			$out.= $this->addFilePart(serialize($this->dat['header']),$compress);

			// adding records:
			$out.= $this->addFilePart(serialize($this->dat['records']),$compress);

			// adding files:
			$out.= $this->addFilePart(serialize($this->dat['files']),$compress);
		}

		return $out;
	}

	/**
	 * Creates XML string from input array
	 *
	 * @return	string		XML content
	 */
	function createXML()	{

			// Options:
		$options = array(
			'alt_options' => array(
				'/header' => array(
					'disableTypeAttrib' => TRUE,
					'clearStackPath' => TRUE,
					'parentTagMap' => array(
						'files' => 'file',
						'records' => 'table',
						'table' => 'rec',
						'rec:rels' => 'relations',
						'relations' => 'element',
						'filerefs' => 'file',
						'pid_lookup' => 'page_contents',
						'header:relStaticTables' => 'static_tables',
						'static_tables' => 'tablename',
						'excludeMap' => 'item',
						'softrefCfg' => 'softrefExportMode',
						'extensionDependencies' => 'extkey',
						'softrefs' => 'softref_element',
					),
					'alt_options' => array(
						'/pagetree' => array(
							'disableTypeAttrib' => TRUE,
							'useIndexTagForNum' => 'node',
							'parentTagMap' => array(
								'node:subrow' => 'node'
							)
						),
						'/pid_lookup/page_contents' => array(
							'disableTypeAttrib' => TRUE,
							'parentTagMap' => array(
								'page_contents' => 'table'
							),
							'grandParentTagMap' => array(
								'page_contents/table' => 'item'
							)
						)
					)
				),
				'/records' => array(
					'disableTypeAttrib' => TRUE,
					'parentTagMap' => array(
						'records' => 'tablerow',
						'tablerow:data' => 'fieldlist',
						'tablerow:rels' => 'related',
						'related' => 'field',
						'field:itemArray' => 'relations',
						'field:newValueFiles' => 'filerefs',
						'field:flexFormRels' => 'flexform',
						'relations' => 'element',
						'filerefs' => 'file',
						'flexform:db' => 'db_relations',
						'flexform:file' => 'file_relations',
						'flexform:softrefs' => 'softref_relations',
						'softref_relations' => 'structurePath',
						'db_relations' => 'path',
						'file_relations' => 'path',
						'path' => 'element',
						'keys' => 'softref_key',
						'softref_key' => 'softref_element',
					),
					'alt_options' => array(
						'/records/tablerow/fieldlist' => array(
							'useIndexTagForAssoc' => 'field',
						)
					)
				),
				'/files' => array(
					'disableTypeAttrib' => TRUE,
					'parentTagMap' => array(
						'files' => 'file',
					),
				),
			)
		);

			// Creating XML file from $outputArray:
		$charset = $this->dat['header']['charset'] ? $this->dat['header']['charset'] : 'iso-8859-1';
		$XML = '<?xml version="1.0" encoding="'.$charset.'" standalone="yes" ?>'.LF;
		$XML.= t3lib_div::array2xml($this->dat,'',0,'T3RecordDocument',0,$options);

		return $XML;
	}

	/**
	 * Returns true if the output should be compressed.
	 *
	 * @return	boolean		True if compression is possible AND requested.
	 */
	function doOutputCompress()	{
		return $this->compress && !$this->dontCompress;
	}

	/**
	 * Returns a content part for a filename being build.
	 *
	 * @param	array		Data to store in part
	 * @param	boolean		Compress file?
	 * @return	string		Content stream.
	 */
	function addFilePart($data, $compress=FALSE)	{
		if ($compress)	$data = gzcompress($data);
		return md5($data).':'.
				($compress?'1':'0').':'.
				str_pad(strlen($data),10,'0',STR_PAD_LEFT).':'.
				$data.':';
	}














	/***********************
	 *
	 * Import
	 *
	 ***********************/

	/**
	 * Imports the internal data array to $pid.
	 *
	 * @param	integer		Page ID in which to import the content
	 * @return	void		...
	 */
	function importData($pid)	{

			// Set this flag to indicate that an import is being/has been done.
		$this->doesImport = 1;

			// Initialize:
			// These vars MUST last for the whole section not being cleared. They are used by the method setRelations() which are called at the end of the import session.
		$this->import_mapId = array();
		$this->import_newId = array();
		$this->import_newId_pids = array();

			// Temporary files stack initialized:
		$this->unlinkFiles = array();
		$this->alternativeFileName = array();
		$this->alternativeFilePath = array();

			// Write records, first pages, then the rest
			// Fields with "hard" relations to database, files and flexform fields are kept empty during this run
		$this->writeRecords_pages($pid);
		$this->writeRecords_records($pid);

			// Finally all the file and DB record references must be fixed. This is done after all records have supposedly been written to database:
			// $this->import_mapId will indicate two things: 1) that a record WAS written to db and 2) that it has got a new id-number.
		$this->setRelations();

			// And when all DB relations are in place, we can fix file and DB relations in flexform fields (since data structures often depends on relations to a DS record):
		$this->setFlexFormRelations();

			// Unlink temporary files:
		$this->unlinkTempFiles();

			// Finally, traverse all records and process softreferences with substitution attributes.
		$this->processSoftReferences();
	}

	/**
	 * Writing pagetree/pages to database:
	 *
	 * @param	integer		PID in which to import. If the operation is an update operation, the root of the page tree inside will be moved to this PID unless it is the same as the root page from the import
	 * @return	void
	 * @see writeRecords_records()
	 */
	function writeRecords_pages($pid)	{

			// First, write page structure if any:
		if (is_array($this->dat['header']['records']['pages']))	{

				// $pageRecords is a copy of the pages array in the imported file. Records here are unset one by one when the addSingle function is called.
			$pageRecords = $this->dat['header']['records']['pages'];
			$this->import_data = array();

				// First add page tree if any
			if (is_array($this->dat['header']['pagetree']))	{
				$pagesFromTree = $this->flatInversePageTree($this->dat['header']['pagetree']);
				foreach($pagesFromTree as $uid)	{
					$thisRec = $this->dat['header']['records']['pages'][$uid];
						// PID: Set the main $pid, unless a NEW-id is found
					$setPid = isset($this->import_newId_pids[$thisRec['pid']])	? $this->import_newId_pids[$thisRec['pid']] : $pid;
					$this->addSingle('pages',$uid,$setPid);
					unset($pageRecords[$uid]);
				}
			}

				// Then add all remaining pages not in tree on root level:
			if (count($pageRecords))	{
				$remainingPageUids = array_keys($pageRecords);
				foreach($remainingPageUids as $pUid)	{
					$this->addSingle('pages',$pUid,$pid);
				}
			}

				// Now write to database:
			$tce = $this->getNewTCE();
			$this->callHook('before_writeRecordsPages', array(
				'tce' => &$tce,
				'data' => &$this->import_data,
			));
			$tce->suggestedInsertUids = $this->suggestedInsertUids;
			$tce->start($this->import_data,Array());
			$tce->process_datamap();
			$this->callHook('after_writeRecordsPages', array(
				'tce' => &$tce
			));

				// post-processing: Registering new ids (end all tcemain sessions with this)
			$this->addToMapId($tce->substNEWwithIDs);

				// In case of an update, order pages from the page tree correctly:
			if ($this->update && is_array($this->dat['header']['pagetree']))	{
				$this->writeRecords_pages_order($pid);
			}
		}
	}

	/**
	 * Organize all updated pages in page tree so they are related like in the import file
	 * Only used for updates and when $this->dat['header']['pagetree'] is an array.
	 *
	 * @param	integer		Page id in which to import
	 * @return	void
	 * @access private
	 * @see writeRecords_pages(), writeRecords_records_order()
	 */
	function writeRecords_pages_order($pid)	{
		$cmd_data = array();

			// Get uid-pid relations and traverse them in order to map to possible new IDs
		$pidsFromTree = $this->flatInversePageTree_pid($this->dat['header']['pagetree']);

		foreach($pidsFromTree as $origPid => $newPid)	{
			if ($newPid>=0 && $this->dontIgnorePid('pages', $origPid))	{
				if (substr($this->import_newId_pids[$origPid],0,3)==='NEW')	{	// If the page had a new id (because it was created) use that instead!

					if ($this->import_mapId['pages'][$origPid])	{
						$mappedPid = $this->import_mapId['pages'][$origPid];
						$cmd_data['pages'][$mappedPid]['move'] = $newPid;
					}
				} else {
					$cmd_data['pages'][$origPid]['move'] = $newPid;
				}
			}
		}

			// Execute the move commands if any:
		if (count($cmd_data))	{
			$tce = $this->getNewTCE();
			$this->callHook('before_writeRecordsPagesOrder', array(
				'tce' => &$tce,
				'data' => &$cmd_data,
			));
			$tce->start(Array(),$cmd_data);
			$tce->process_cmdmap();
			$this->callHook('after_writeRecordsPagesOrder', array(
				'tce' => &$tce,
			));
		}

	}

	/**
	 * Write all database records except pages (writtein in writeRecords_pages())
	 *
	 * @param	integer		Page id in which to import
	 * @return	void
	 * @see writeRecords_pages()
	 */
	function writeRecords_records($pid)	{
		global $TCA;

			// Write the rest of the records
		$this->import_data = array();
		if (is_array($this->dat['header']['records']))	{
			foreach ($this->dat['header']['records'] as $table => $recs) {
				if ($table!='pages')	{
					foreach ($recs as $uid => $thisRec) {
							// PID: Set the main $pid, unless a NEW-id is found
						$setPid = isset($this->import_mapId['pages'][$thisRec['pid']]) ? $this->import_mapId['pages'][$thisRec['pid']] : $pid;
						if (is_array($TCA[$table]) && $TCA[$table]['ctrl']['rootLevel'])	{
							$setPid = 0;
						}

							// Add record:
						$this->addSingle($table,$uid,$setPid);
					}
				}
			}
		} else $this->error('Error: No records defined in internal data array.');

			// Now write to database:
		$tce = $this->getNewTCE();
		$this->callHook('before_writeRecordsRecords', array(
			'tce' => &$tce,
			'data' => &$this->import_data,
		));
		$tce->suggestedInsertUids = $this->suggestedInsertUids;
		$tce->reverseOrder=1;	// Because all records are being submitted in their correct order with positive pid numbers - and so we should reverse submission order internally.
		$tce->start($this->import_data,Array());
		$tce->process_datamap();
		$this->callHook('after_writeRecordsRecords', array(
			'tce' => &$tce,
		));

			// post-processing: Removing files and registering new ids (end all tcemain sessions with this)
		$this->addToMapId($tce->substNEWwithIDs);

			// In case of an update, order pages from the page tree correctly:
		if ($this->update)	{
			$this->writeRecords_records_order($pid);
		}
	}

	/**
	 * Organize all updated record to their new positions.
	 * Only used for updates
	 *
	 * @param	integer		Main PID into which we import.
	 * @return	void
	 * @access private
	 * @see writeRecords_records(), writeRecords_pages_order()
	 */
	function writeRecords_records_order($mainPid)	{
		$cmd_data = array();

		if (is_array($this->dat['header']['pagetree']))	{
			$pagesFromTree = $this->flatInversePageTree($this->dat['header']['pagetree']);
		} else $pagesFromTree = array();

		if (is_array($this->dat['header']['pid_lookup']))	{
			foreach($this->dat['header']['pid_lookup'] as $pid => $recList)	{
				$newPid = isset($this->import_mapId['pages'][$pid]) ? $this->import_mapId['pages'][$pid] : $mainPid;

				if (t3lib_div::testInt($newPid))	{
					foreach($recList as $tableName => $uidList)	{
						if (($tableName!='pages' || !$pagesFromTree[$pid]) && is_array($uidList))	{		// If $mainPid===$newPid then we are on root level and we can consider to move pages as well! (they will not be in the page tree!)
							$uidList = array_reverse(array_keys($uidList));
							foreach($uidList as $uid)	{
								if ($this->dontIgnorePid($tableName, $uid))	{
									$cmd_data[$tableName][$uid]['move'] = $newPid;
								} else {
									// nothing
								}
							}
						}
					}
				}
			}
		}

			// Execute the move commands if any:
		if (count($cmd_data))	{
			$tce = $this->getNewTCE();
			$this->callHook('before_writeRecordsRecordsOrder', array(
				'tce' => &$tce,
				'data' => &$cmd_data,
			));
			$tce->start(Array(),$cmd_data);
			$tce->process_cmdmap();
			$this->callHook('after_writeRecordsRecordsOrder', array(
				'tce' => &$tce,
			));
		}
	}

	/**
	 * Adds a single record to the $importData array. Also copies files to tempfolder.
	 * However all File/DB-references and flexform field contents are set to blank for now! That is done with setRelations() later
	 *
	 * @param	string		Table name (from import memory)
	 * @param	integer		Record UID (from import memory)
	 * @param	integer		Page id
	 * @return	void
	 * @see writeRecords()
	 */
	function addSingle($table,$uid,$pid)	{
		if ($this->import_mode[$table.':'.$uid]!=='exclude')	{
			$record = $this->dat['records'][$table.':'.$uid]['data'];
			if (is_array($record))	{

				if ($this->update && $this->doesRecordExist($table,$uid) && $this->import_mode[$table.':'.$uid]!=='as_new')	{
					$ID = $uid;
				} else {
#debug($this->import_mode[$table.':'.$uid],$table.':'.$uid);
					$ID = uniqid('NEW');
				}
				$this->import_newId[$table.':'.$ID] = array('table' => $table, 'uid' => $uid);
				if ($table=='pages')	$this->import_newId_pids[$uid] = $ID;

					// Set main record data:
				$this->import_data[$table][$ID] = $record;
				$this->import_data[$table][$ID]['tx_impexp_origuid'] = $this->import_data[$table][$ID]['uid'];

					// Reset permission data:
				if ($table==='pages')	{
						// Have to reset the user/group IDs so pages are owned by importing user. Otherwise strange things may happen for non-admins!
					unset($this->import_data[$table][$ID]['perms_userid']);
					unset($this->import_data[$table][$ID]['perms_groupid']);

						// user/group/everybody settings is kept - but these might still conflict with possibilities for writing the content!"
					#unset($this->import_data[$table][$ID]['perms_user']);
					#unset($this->import_data[$table][$ID]['perms_group']);
					#unset($this->import_data[$table][$ID]['perms_everybody']);
				}

					// PID and UID:
				unset($this->import_data[$table][$ID]['uid']);
				if (t3lib_div::testInt($ID))	{	// Updates:
					unset($this->import_data[$table][$ID]['pid']);
				} else {	// Inserts:
					$this->import_data[$table][$ID]['pid'] = $pid;

					if ((($this->import_mode[$table.':'.$uid]==='force_uid' && $this->update) || $this->force_all_UIDS) && $GLOBALS['BE_USER']->isAdmin())	{
#debug($this->import_mode[$table.':'.$uid],$table.':'.$uid);
						$this->import_data[$table][$ID]['uid'] = $uid;
						$this->suggestedInsertUids[$table.':'.$uid] = 'DELETE';
					}
				}

					// Setting db/file blank:
				foreach ($this->dat['records'][$table.':'.$uid]['rels'] as $field => $config) {
					switch((string)$config['type'])	{
						case 'db':
						case 'file':
								// Fixed later in ->setRelations() [because we need to know ALL newly created IDs before we can map relations!]
								// In the meantime we set NO values for relations:
							$this->import_data[$table][$ID][$field] = '';
						break;
						case 'flex':
								// Fixed later in setFlexFormRelations()
								// In the meantime we set NO value for flexforms - this is mainly because file references inside will not be processed properly; In fact references will point to no file or existing files (in which case there will be double-references which is a big problem of course!)
							$this->import_data[$table][$ID][$field] = '';
						break;
					}
				}
			} elseif ($table.':'.$uid != 'pages:0') {	// On root level we don't want this error message.
				$this->error('Error: no record was found in data array!',1);
			}
		}
	}

	/**
	 * Registers the substNEWids in memory.
	 *
	 * @param	array		$substNEWwithIDs from tcemain to be merged into internal mapping variable in this object
	 * @return	void
	 * @see writeRecords()
	 */
	function addToMapId($substNEWwithIDs)	{
		foreach ($this->import_data as $table => $recs) {
			foreach ($recs as $id => $value) {
				$old_uid = $this->import_newId[$table.':'.$id]['uid'];
				if (isset($substNEWwithIDs[$id]))	{
					$this->import_mapId[$table][$old_uid] = $substNEWwithIDs[$id];
				} elseif ($this->update) {
					$this->import_mapId[$table][$old_uid] = $id;	// Map same ID to same ID....
				} else $this->error('Possible error: '.$table.':'.$old_uid.' had no new id assigned to it. This indicates that the record was not added to database during import. Please check changelog!',1);
			}
		}
	}

	/**
	 * Returns a new $TCE object
	 *
	 * @return	object		$TCE object
	 */
	function getNewTCE()	{
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->dontProcessTransformations = 1;
		$tce->enableLogging = $this->enableLogging;
		$tce->alternativeFileName = $this->alternativeFileName;
		$tce->alternativeFilePath = $this->alternativeFilePath;
		return $tce;
	}

	/**
	 * Cleaning up all the temporary files stored in typo3temp/ folder
	 *
	 * @return	void
	 */
	function unlinkTempFiles()	{
		foreach($this->unlinkFiles as $fileName)	{
			if (t3lib_div::isFirstPartOfStr($fileName, PATH_site.'typo3temp/'))	{
				t3lib_div::unlink_tempfile($fileName);
				clearstatcache();
				if (is_file($fileName))	{
					$this->error('Error: '.$fileName.' was NOT unlinked as it should have been!',1);
				}
			} else $this->error('Error: '.$fileName.' was not in temp-path. Not removed!',1);
		}
		$this->unlinkFiles = array();
	}













	/***************************
	 *
	 * Import / Relations setting
	 *
	 ***************************/

	/**
	 * At the end of the import process all file and DB relations should be set properly (that is relations to imported records are all re-created so imported records are correctly related again)
	 * Relations in flexform fields are processed in setFlexFormRelations() after this function
	 *
	 * @return	void
	 * @see setFlexFormRelations()
	 */
	function setRelations()	{
		global $TCA;

		$updateData = array();

			// import_newId contains a register of all records that was in the import memorys "records" key
		foreach ($this->import_newId as $nId => $dat) {
			$table = $dat['table'];
			$uid = $dat['uid'];	// original UID - NOT the new one!

				// If the record has been written and received a new id, then proceed:
			if (is_array($this->import_mapId[$table]) && isset($this->import_mapId[$table][$uid]))	{
				$thisNewUid = t3lib_BEfunc::wsMapId($table,$this->import_mapId[$table][$uid]);

				if (is_array($this->dat['records'][$table.':'.$uid]['rels']))	{

						// Traverse relation fields of each record
					foreach ($this->dat['records'][$table.':'.$uid]['rels'] as $field => $config) {
						switch((string)$config['type'])	{
							case 'db':
								if (is_array($config['itemArray']) && count($config['itemArray']))	{
									$valArray = $this->setRelations_db($config['itemArray']);
									$updateData[$table][$thisNewUid][$field] = implode(',',$valArray);	// List of [table]_[uid]
								}
							break;
							case 'file':
								if (is_array($config['newValueFiles']) && count($config['newValueFiles']))	{
									$valArr = array();
									foreach($config['newValueFiles'] as $fI)	{
										$valArr[] = $this->import_addFileNameToBeCopied($fI);
									}
									$updateData[$table][$thisNewUid][$field] = implode(',',$valArr);	// List of absolute files
								}
							break;
						}
					}
				} else $this->error('Error: no record was found in data array!',1);
			} else $this->error('Error: this records is NOT created it seems! ('.$table.':'.$uid.')',1);
		}
		if (count($updateData))	{
			$tce = $this->getNewTCE();
			$this->callHook('before_setRelation', array(
				'tce' => &$tce,
				'data' => &$updateData,
			));
			$tce->start($updateData,Array());
			$tce->process_datamap();
			$this->callHook('after_setRelations', array(
				'tce' => &$tce,
			));
		}
	}

	/**
	 * Maps relations for database
	 *
	 * @param	array		Array of item sets (table/uid) from a dbAnalysis object
	 * @return	array		Array with values [table]_[uid]. These values have the regular tcemain-input group/select type which means they will automatically be processed into a uid-list or MM relations.
	 */
	function setRelations_db($itemArray)	{
		$valArray = array();

		foreach($itemArray as $relDat)	{
			if (is_array($this->import_mapId[$relDat['table']]) && isset($this->import_mapId[$relDat['table']][$relDat['id']]))	{

				#debug('FOUND: '.$relDat['table'].':'.$relDat['id']);
				$valArray[] = $relDat['table'].'_'.$this->import_mapId[$relDat['table']][$relDat['id']];
			} elseif ($this->isTableStatic($relDat['table']) || $this->isExcluded($relDat['table'], $relDat['id']) || $relDat['id']<0) {	// Checking for less than zero because some select types could contain negative values, eg. fe_groups (-1, -2) and sys_language (-1 = ALL languages). This must be handled on both export and import.

				#debug('STATIC: '.$relDat['table'].':'.$relDat['id']);
				$valArray[] = $relDat['table'].'_'.$relDat['id'];
			} else {

				$this->error('Lost relation: '.$relDat['table'].':'.$relDat['id'],1);
			}
		}

		return $valArray;
	}

	/**
	 * Writes the file from import array to temp dir and returns the filename of it.
	 *
	 * @param	array		File information with three keys: "filename" = filename without path, "ID_absFile" = absolute filepath to the file (including the filename), "ID" = md5 hash of "ID_absFile"
	 * @return	string		Absolute filename of the temporary filename of the file. In ->alternativeFileName the original name is set.
	 */
	function import_addFileNameToBeCopied($fI)	{
		if (is_array($this->dat['files'][$fI['ID']]))	{
			$tmpFile = t3lib_div::tempnam('import_temp_');
			t3lib_div::writeFile($tmpFile,$this->dat['files'][$fI['ID']]['content']);
			clearstatcache();
			if (@is_file($tmpFile))	{
				$this->unlinkFiles[] = $tmpFile;
				if (filesize($tmpFile)==$this->dat['files'][$fI['ID']]['filesize'])	{
					$this->alternativeFileName[$tmpFile] = $fI['filename'];
					$this->alternativeFilePath[$tmpFile] = $this->dat['files'][$fI['ID']]['relFileRef'];

					return $tmpFile;
				} else $this->error('Error: temporary file '.$tmpFile.' had a size ('.filesize($tmpFile).') different from the original ('.$this->dat['files'][$fI['ID']]['filesize'].')',1);
			} else $this->error('Error: temporary file '.$tmpFile.' was not written as it should have been!',1);
		} else $this->error('Error: No file found for ID '.$fI['ID'],1);
	}

	/**
	 * After all DB relations has been set in the end of the import (see setRelations()) then it is time to correct all relations inside of FlexForm fields.
	 * The reason for doing this after is that the setting of relations may affect (quite often!) which data structure is used for the flexforms field!
	 *
	 * @return	void
	 * @see setRelations()
	 */
	function setFlexFormRelations()	{
		global $TCA;

		$updateData = array();
			// import_newId contains a register of all records that was in the import memorys "records" key
		foreach ($this->import_newId as $nId => $dat) {
			$table = $dat['table'];
			$uid = $dat['uid'];	// original UID - NOT the new one!

				// If the record has been written and received a new id, then proceed:
			if (is_array($this->import_mapId[$table]) && isset($this->import_mapId[$table][$uid]))	{
				$thisNewUid = t3lib_BEfunc::wsMapId($table,$this->import_mapId[$table][$uid]);
				if (is_array($this->dat['records'][$table.':'.$uid]['rels']))	{
					t3lib_div::loadTCA($table);

						// Traverse relation fields of each record
					foreach ($this->dat['records'][$table.':'.$uid]['rels'] as $field => $config) {
						switch((string)$config['type'])	{
							case 'flex':
									// Get XML content and set as default value (string, non-processed):
								$updateData[$table][$thisNewUid][$field] = $this->dat['records'][$table.':'.$uid]['data'][$field];

									// If there has been registered relations inside the flex form field, run processing on the content:
								if (count($config['flexFormRels']['db']) || count($config['flexFormRels']['file']))	{
									$origRecordRow = t3lib_BEfunc::getRecord($table,$thisNewUid,'*');	// This will fetch the new row for the element (which should be updated with any references to data structures etc.)
									$conf = $TCA[$table]['columns'][$field]['config'];
									if (is_array($origRecordRow) && is_array($conf) && $conf['type']==='flex')	{
											// Get current data structure and value array:
										$dataStructArray = t3lib_BEfunc::getFlexFormDS($conf, $origRecordRow, $table);
										$currentValueArray = t3lib_div::xml2array($updateData[$table][$thisNewUid][$field]);
											// Do recursive processing of the XML data:
										$iteratorObj = t3lib_div::makeInstance('t3lib_TCEmain');
										$iteratorObj->callBackObj = $this;
										$currentValueArray['data'] = $iteratorObj->checkValue_flex_procInData(
													$currentValueArray['data'],
													array(),	// Not used.
													array(),	// Not used.
													$dataStructArray,
													array($table,$thisNewUid,$field,$config),	// Parameters.
													'remapListedDBRecords_flexFormCallBack'
												);
											// The return value is set as an array which means it will be processed by tcemain for file and DB references!
										if (is_array($currentValueArray['data']))	{
											$updateData[$table][$thisNewUid][$field] = $currentValueArray;
										}
									}
								}
							break;
						}
					}
				} else $this->error('Error: no record was found in data array!',1);
			} else $this->error('Error: this records is NOT created it seems! ('.$table.':'.$uid.')',1);
		}
		if (count($updateData))	{
			$tce = $this->getNewTCE();
			$this->callHook('before_setFlexFormRelations', array(
				'tce' => &$tce,
				'data' => &$updateData,
			));
			$tce->start($updateData,Array());
			$tce->process_datamap();
			$this->callHook('after_setFlexFormRelations', array(
				'tce' => &$tce,
			));
		}
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to remapping database relations
	 *
	 * @param	array		Set of parameters in numeric array: table, uid, field
	 * @param	array		TCA config for field (from Data Structure of course)
	 * @param	string		Field value (from FlexForm XML)
	 * @param	string		Not used
	 * @param	string		Not used
	 * @param	string		Path of where the data structure of the element is found
	 * @return	array		Array where the "value" key carries the value.
	 * @see setFlexFormRelations()
	 */
	function remapListedDBRecords_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2, $path)	{

			// Extract parameters:
		list($table,$uid,$field,$config) = $pParams;

			// In case the $path is used as index without a trailing slash we will remove that
		if (!is_array($config['flexFormRels']['db'][$path]) && is_array($config['flexFormRels']['db'][rtrim($path, '/')]))	{
			$path = rtrim($path, '/');
		}
		if (is_array($config['flexFormRels']['db'][$path]))	{
			$valArray = $this->setRelations_db($config['flexFormRels']['db'][$path]);
			$dataValue = implode(',',$valArray);
		}

		if (is_array($config['flexFormRels']['file'][$path]))	{
			foreach($config['flexFormRels']['file'][$path] as $fI)	{
				$valArr[] = $this->import_addFileNameToBeCopied($fI);
			}
			$dataValue = implode(',',$valArr);
		}

		return array('value' => $dataValue);
	}











	/**************************
	 *
	 * Import / Soft References
	 *
	 *************************/

	/**
	 * Processing of soft references
	 *
	 * @return	void
	 */
	function processSoftReferences()	{
		global $TCA;

			// Initialize:
		$inData = array();

			// Traverse records:
		if (is_array($this->dat['header']['records']))	{
			foreach($this->dat['header']['records'] as $table => $recs)	{
				foreach($recs as $uid => $thisRec)	{

						// If there are soft references defined, traverse those:
					if (isset($TCA[$table]) && is_array($thisRec['softrefs']))	{
						t3lib_div::loadTCA($table);

							// First traversal is to collect softref configuration and split them up based on fields. This could probably also have been done with the "records" key instead of the header.
						$fieldsIndex = array();
						foreach($thisRec['softrefs'] as $softrefDef)	{

								// If a substitution token is set:
							if ($softrefDef['field'] && is_array($softrefDef['subst']) && $softrefDef['subst']['tokenID'])	{
								$fieldsIndex[$softrefDef['field']][$softrefDef['subst']['tokenID']] = $softrefDef;
							}
						}

							// The new id:
						$thisNewUid = t3lib_BEfunc::wsMapId($table,$this->import_mapId[$table][$uid]);

							// Now, if there are any fields that require substitution to be done, lets go for that:
						foreach($fieldsIndex as $field => $softRefCfgs)	{
							if (is_array($TCA[$table]['columns'][$field]))	{
								$conf = $TCA[$table]['columns'][$field]['config'];
								if ($conf['type']==='flex')	{

									$origRecordRow = t3lib_BEfunc::getRecord($table,$thisNewUid,'*');	// This will fetch the new row for the element (which should be updated with any references to data structures etc.)
									if (is_array($origRecordRow))	{

											// Get current data structure and value array:
										$dataStructArray = t3lib_BEfunc::getFlexFormDS($conf, $origRecordRow, $table);
										$currentValueArray = t3lib_div::xml2array($origRecordRow[$field]);

											// Do recursive processing of the XML data:
										$iteratorObj = t3lib_div::makeInstance('t3lib_TCEmain');
										$iteratorObj->callBackObj = $this;
										$currentValueArray['data'] = $iteratorObj->checkValue_flex_procInData(
													$currentValueArray['data'],
													array(),	// Not used.
													array(),	// Not used.
													$dataStructArray,
													array($table,$uid,$field,$softRefCfgs),	// Parameters (using old UID on purpose!)
													'processSoftReferences_flexFormCallBack'
												);

											// The return value is set as an array which means it will be processed by tcemain for file and DB references!
										if (is_array($currentValueArray['data']))	{
											$inData[$table][$thisNewUid][$field] = $currentValueArray;
										}
									}
								} else {
										// Get tokenizedContent string and proceed only if that is not blank:
									$tokenizedContent = $this->dat['records'][$table.':'.$uid]['rels'][$field]['softrefs']['tokenizedContent'];
									if (strlen($tokenizedContent) && is_array($softRefCfgs))	{
										$inData[$table][$thisNewUid][$field] = $this->processSoftReferences_substTokens($tokenizedContent, $softRefCfgs, $table, $uid);
									}
								}
							}
						}
					}
				}
			}
		}

			// Now write to database:
		$tce = $this->getNewTCE();
		$this->callHook('before_processSoftReferences', array(
				'tce' => &$tce,
				'data' => &$inData,
			));
		$tce->enableLogging = TRUE;
		$tce->start($inData, Array());
		$tce->process_datamap();
		$this->callHook('after_processSoftReferences', array(
			'tce' => &$tce,
		));
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to remapping softreference relations
	 *
	 * @param	array		Set of parameters in numeric array: table, uid, field
	 * @param	array		TCA config for field (from Data Structure of course)
	 * @param	string		Field value (from FlexForm XML)
	 * @param	string		Not used
	 * @param	string		Not used
	 * @param	string		Path of where the data structure where the element is found
	 * @return	array		Array where the "value" key carries the value.
	 * @see setFlexFormRelations()
	 */
	function processSoftReferences_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2, $path)	{

			// Extract parameters:
		list($table,$origUid,$field,$softRefCfgs)	= $pParams;

		if (is_array($softRefCfgs))	{

				// First, find all soft reference configurations for this structure path (they are listed flat in the header):
			$thisSoftRefCfgList = array();
			foreach($softRefCfgs as $sK => $sV)	{
				if ($sV['structurePath']===$path)	{
					$thisSoftRefCfgList[$sK] = $sV;
				}
			}

				// If any was found, do processing:
			if (count($thisSoftRefCfgList))	{

					// Get tokenizedContent string and proceed only if that is not blank:
				$tokenizedContent = $this->dat['records'][$table.':'.$origUid]['rels'][$field]['flexFormRels']['softrefs'][$path]['tokenizedContent'];
				if (strlen($tokenizedContent))	{
					$dataValue = $this->processSoftReferences_substTokens($tokenizedContent, $thisSoftRefCfgList, $table, $origUid);
				}
			}
		}

			// Return
		return array('value' => $dataValue);
	}

	/**
	 * Substition of softreference tokens
	 *
	 * @param	string		Content of field with soft reference tokens in.
	 * @param	array		Soft reference configurations
	 * @param	string		Table for which the processing occurs
	 * @param	string		UID of record from table
	 * @return	string		The input content with tokens substituted according to entries in softRefCfgs
	 */
	function processSoftReferences_substTokens($tokenizedContent, $softRefCfgs, $table, $uid)	{

			// traverse each softref type for this field:
		foreach($softRefCfgs as $cfg)	{

				// Get token ID:
			$tokenID = $cfg['subst']['tokenID'];

				// Default is current token value:
			$insertValue = $cfg['subst']['tokenValue'];

				// Based on mode:
			switch((string)$this->softrefCfg[$tokenID]['mode'])	{
				case 'exclude':
					// Exclude is a simple passthrough of the value
				break;
				case 'editable':
						// Editable always picks up the value from this input array:
					$insertValue = $this->softrefInputValues[$tokenID];
				break;
				default:
						// Mapping IDs/creating files: Based on type, look up new value:
					switch((string)$cfg['subst']['type'])	{
						case 'db':
							default:
									// Trying to map database element if found in the mapID array:
								list($tempTable,$tempUid) = explode(':',$cfg['subst']['recordRef']);
								if (isset($this->import_mapId[$tempTable][$tempUid]))	{
									$insertValue = t3lib_BEfunc::wsMapId($tempTable,$this->import_mapId[$tempTable][$tempUid]);

										// Look if reference is to a page and the original token value was NOT an integer - then we assume is was an alias and try to look up the new one!
									if ($tempTable==='pages' && !t3lib_div::testInt($cfg['subst']['tokenValue']))	{
										$recWithUniqueValue = t3lib_BEfunc::getRecord($tempTable,$insertValue, 'alias');
										if ($recWithUniqueValue['alias'])	{
											$insertValue = $recWithUniqueValue['alias'];
										}
									}
								}
							break;
						break;
						case 'file':
								// Create / Overwrite file:
							$insertValue = $this->processSoftReferences_saveFile($cfg['subst']['relFileName'], $cfg, $table, $uid);
						break;
					}
				break;
			}

				// Finally, swap the soft reference token in tokenized content with the insert value:
			$tokenizedContent = str_replace('{softref:'.$tokenID.'}', $insertValue, $tokenizedContent);
		}

		return $tokenizedContent;
	}

	/**
	 * Process a soft reference file
	 *
	 * @param	string		Old Relative filename
	 * @param	array		soft reference configuration array
	 * @param	string		Table for which the processing occurs
	 * @param	string		UID of record from table
	 * @return	string		New relative filename (value to insert instead of the softref token)
	 */
	function processSoftReferences_saveFile($relFileName, $cfg, $table, $uid)	{

		if ($fileHeaderInfo = $this->dat['header']['files'][$cfg['file_ID']])	{
				// Initialize; Get directory prefix for file and find possible RTE filename
			$dirPrefix = dirname($relFileName).'/';
			$rteOrigName = $this->getRTEoriginalFilename(basename($relFileName));

				// If filename looks like an RTE file, and the directory is in "uploads/", then process as a RTE file!
			if ($rteOrigName && t3lib_div::isFirstPartOfStr($dirPrefix,'uploads/'))	{	// RTE:

					// First, find unique RTE file name:
				if (@is_dir(PATH_site.$dirPrefix))	{

						// From the "original" RTE filename, produce a new "original" destination filename which is unused. Even if updated, the image should be unique. Currently the problem with this is that it leaves a lot of unused RTE images...
					$fileProcObj = $this->getFileProcObj();
					$origDestName = $fileProcObj->getUniqueName($rteOrigName, PATH_site.$dirPrefix);

						// Create copy file name:
					$pI = pathinfo($relFileName);
					$copyDestName = dirname($origDestName).'/RTEmagicC_'.substr(basename($origDestName),10).'.'.$pI['extension'];
					if (!@is_file($copyDestName) && !@is_file($origDestName)
						&& $origDestName===t3lib_div::getFileAbsFileName($origDestName) && $copyDestName===t3lib_div::getFileAbsFileName($copyDestName))	{

						if ($this->dat['header']['files'][$fileHeaderInfo['RTE_ORIG_ID']])	{

								// Write the copy and original RTE file to the respective filenames:
							$this->writeFileVerify($copyDestName, $cfg['file_ID'], TRUE);
							$this->writeFileVerify($origDestName, $fileHeaderInfo['RTE_ORIG_ID'], TRUE);

								// Return the relative path of the copy file name:
							return substr($copyDestName, strlen(PATH_site));
						} else $this->error('ERROR: Could not find original file ID');
					} else $this->error('ERROR: The destination filenames "'.$copyDestName.'" and "'.$origDestName.'" either existed or have non-valid names');
				} else $this->error('ERROR: "'.PATH_site.$dirPrefix.'" was not a directory, so could not process file "'.$relFileName.'"');


			} elseif (t3lib_div::isFirstPartOfStr($dirPrefix, $this->fileadminFolderName.'/'))	{	// File in fileadmin/ folder:

					// Create file (and possible resources)
				$newFileName = $this->processSoftReferences_saveFile_createRelFile($dirPrefix,basename($relFileName),$cfg['file_ID'], $table, $uid);

				if (strlen($newFileName))	{
					$relFileName = $newFileName;
				} else $this->error('ERROR: No new file created for "'.$relFileName.'"');
			} else $this->error('ERROR: Sorry, cannot operate on non-RTE files which are outside the fileadmin folder.');
		} else $this->error('ERROR: Could not find file ID in header.');

			// Return (new) filename relative to PATH_site:
		return $relFileName;
	}

	/**
	 * Create file in directory and return the new (unique) filename
	 *
	 * @param	string		Directory prefix, relative, with trailing slash
	 * @param	string		Filename (without path)
	 * @param	string		File ID from import memory
	 * @param	string		Table for which the processing occurs
	 * @param	string		UID of record from table
	 * @return	string		New relative filename, if any
	 */
	function processSoftReferences_saveFile_createRelFile($origDirPrefix, $fileName, $fileID, $table, $uid)	{

			// If the fileID map contains an entry for this fileID then just return the relative filename of that entry; we don't want to write another unique filename for this one!
		if ($this->fileIDMap[$fileID])	{
			return substr($this->fileIDMap[$fileID],strlen(PATH_site));
		}

			// Verify FileMount access to dir-prefix. Returns the best alternative relative path if any
		$dirPrefix = $this->verifyFolderAccess($origDirPrefix);

		if ($dirPrefix && (!$this->update || $origDirPrefix===$dirPrefix) && $this->checkOrCreateDir($dirPrefix))	{
			$fileHeaderInfo = $this->dat['header']['files'][$fileID];
			$updMode = $this->update && $this->import_mapId[$table][$uid]===$uid && $this->import_mode[$table.':'.$uid]!=='as_new';
				// Create new name for file:
			if ($updMode)	{	// Must have same ID in map array (just for security, is not really needed) and NOT be set "as_new".
				$newName = PATH_site.$dirPrefix.$fileName;
			} else {
					// Create unique filename:
				$fileProcObj = $this->getFileProcObj();
				$newName = $fileProcObj->getUniqueName($fileName, PATH_site.$dirPrefix);
			}
#debug($newName,'$newName');

				// Write main file:
			if ($this->writeFileVerify($newName, $fileID))	{

					// If the resource was an HTML/CSS file with resources attached, we will write those as well!
				if (is_array($fileHeaderInfo['EXT_RES_ID']))	{
#debug($fileHeaderInfo['EXT_RES_ID']);
					$tokenizedContent = $this->dat['files'][$fileID]['tokenizedContent'];
					$tokenSubstituted = FALSE;

					$fileProcObj = $this->getFileProcObj();

					if ($updMode)	{
						foreach($fileHeaderInfo['EXT_RES_ID'] as $res_fileID)	{
							if ($this->dat['files'][$res_fileID]['filename'])	{

									// Resolve original filename:
								$relResourceFileName = $this->dat['files'][$res_fileID]['parentRelFileName'];
								$absResourceFileName = t3lib_div::resolveBackPath(PATH_site.$origDirPrefix.$relResourceFileName);
								$absResourceFileName = t3lib_div::getFileAbsFileName($absResourceFileName);
								if ($absResourceFileName && t3lib_div::isFirstPartOfStr($absResourceFileName,PATH_site.$this->fileadminFolderName.'/'))	{
									$destDir = substr(dirname($absResourceFileName).'/',strlen(PATH_site));
									if ($this->verifyFolderAccess($destDir, TRUE) && $this->checkOrCreateDir($destDir))	{
										$this->writeFileVerify($absResourceFileName, $res_fileID);
									} else $this->error('ERROR: Could not create file in directory "'.$destDir.'"');
								} else $this->error('ERROR: Could not resolve path for "'.$relResourceFileName.'"');

								$tokenizedContent = str_replace('{EXT_RES_ID:'.$res_fileID.'}', $relResourceFileName, $tokenizedContent);
								$tokenSubstituted = TRUE;
							}
						}
					} else {
							// Create the resouces directory name (filename without extension, suffixed "_FILES")
						$resourceDir = dirname($newName).'/'.preg_replace('/\.[^.]*$/','',basename($newName)).'_FILES';
						if (t3lib_div::mkdir($resourceDir))	{
							foreach($fileHeaderInfo['EXT_RES_ID'] as $res_fileID)	{
								if ($this->dat['files'][$res_fileID]['filename'])	{
									$absResourceFileName = $fileProcObj->getUniqueName($this->dat['files'][$res_fileID]['filename'], $resourceDir);
									$relResourceFileName = substr($absResourceFileName, strlen(dirname($resourceDir))+1);
									$this->writeFileVerify($absResourceFileName, $res_fileID);

									$tokenizedContent = str_replace('{EXT_RES_ID:'.$res_fileID.'}', $relResourceFileName, $tokenizedContent);
									$tokenSubstituted = TRUE;
								}
							}
						}
					}

						// If substitutions has been made, write the content to the file again:
					if ($tokenSubstituted)	{
						t3lib_div::writeFile($newName, $tokenizedContent);
					}
				}

				return substr($newName, strlen(PATH_site));
			}
		}
	}

	/**
	 * Writes a file from the import memory having $fileID to file name $fileName which must be an absolute path inside PATH_site
	 *
	 * @param	string		Absolute filename inside PATH_site to write to
	 * @param	string		File ID from import memory
	 * @param	boolean		Bypasses the checking against filemounts - only for RTE files!
	 * @return	boolean		Returns true if it went well. Notice that the content of the file is read again, and md5 from import memory is validated.
	 */
	function writeFileVerify($fileName, $fileID, $bypassMountCheck=FALSE)	{
		$fileProcObj = $this->getFileProcObj();

		if ($fileProcObj->actionPerms['newFile'])	{
			if ($fileProcObj->checkPathAgainstMounts($fileName) || $bypassMountCheck)	{	// Just for security, check again. Should actually not be necessary.
				$fI = t3lib_div::split_fileref($fileName);
				if ($fileProcObj->checkIfAllowed($fI['fileext'], $fI['path'], $fI['file']) || ($this->allowPHPScripts && $GLOBALS['BE_USER']->isAdmin())) {
					if (t3lib_div::getFileAbsFileName($fileName))	{
						if ($this->dat['files'][$fileID])	{
							t3lib_div::writeFile($fileName,$this->dat['files'][$fileID]['content']);
							$this->fileIDMap[$fileID] = $fileName;
							if (md5(t3lib_div::getUrl($fileName))==$this->dat['files'][$fileID]['content_md5'])	{
								return TRUE;
							} else $this->error('ERROR: File content "'.$fileName.'" was corrupted');
						} else $this->error('ERROR: File ID "'.$fileID.'" could not be found');
					} else $this->error('ERROR: Filename "'.$fileName.'" was not a valid relative file path!');
				} else $this->error('ERROR: Filename "'.$fileName.'" failed against extension check or deny-pattern!');
			} else $this->error('ERROR: Filename "'.$fileName.'" was not allowed in destination path!');
		} else $this->error('ERROR: You did not have sufficient permissions to write the file "'.$fileName.'"');
	}

	/**
	 * Returns true if directory exists  and if it doesn't it will create directory and return true if that succeeded.
	 *
	 * @param	string		Directory to create. Having a trailing slash. Must be in fileadmin/. Relative to PATH_site
	 * @return	boolean		True, if directory exists (was created)
	 */
	function checkOrCreateDir($dirPrefix)	{

			// Split dir path and remove first directory (which should be "fileadmin")
		$filePathParts = explode('/', $dirPrefix);
		$firstDir = array_shift($filePathParts);

		if ($firstDir===$this->fileadminFolderName && t3lib_div::getFileAbsFileName($dirPrefix))	{

			$pathAcc = '';
			foreach($filePathParts as $dirname)	{
				$pathAcc.='/'.$dirname;
				if (strlen($dirname))	{
					if (!@is_dir(PATH_site.$this->fileadminFolderName.$pathAcc))	{
						if (!t3lib_div::mkdir(PATH_site.$this->fileadminFolderName.$pathAcc))	{
							$this->error('ERROR: Directory could not be created....B');
							return FALSE;
						}
					}
				} elseif ($dirPrefix===$this->fileadminFolderName.$pathAcc) {
					return TRUE;
				} else $this->error('ERROR: Directory could not be created....A');
			}
		}
	}

	/**
	 * Verifies that the input path (relative to PATH_site) is found in the backend users filemounts.
	 * If it doesn't it will try to find another relative filemount for the user and return an alternative path prefix for the file.
	 *
	 * @param	string		Path relative to PATH_site
	 * @param	boolean		If set, Do not look for alternative path! Just return false
	 * @return	string		If a path is available that will be returned, otherwise false.
	 */
	function verifyFolderAccess($dirPrefix, $noAlternative=FALSE)	{
		$fileProcObj = $this->getFileProcObj();

#$fileProcObj->mounts['1f390e42e1dc46f125310ead30c7bd9d']['path'] = '/var/www/typo3/dev/testsite-3.6.0/fileadmin/user_upload/';

			// Check, if dirPrefix is inside a valid Filemount for user:
		$result = $fileProcObj->checkPathAgainstMounts(PATH_site.$dirPrefix);

			// If not, try to find another relative filemount and use that instead:
		if (!$result)	{
			if ($noAlternative)	return FALSE;

				// Find first web folder:
			$result = $fileProcObj->findFirstWebFolder();

				// If that succeeded, return the path to it:
			if ($result)	{
					// Remove the "fileadmin/" prefix of input path - and append the rest to the return value:
				if (t3lib_div::isFirstPartOfStr($dirPrefix,$this->fileadminFolderName.'/'))	{
					$dirPrefix = substr($dirPrefix,strlen($this->fileadminFolderName.'/'));
				}
				return substr($fileProcObj->mounts[$result]['path'].$dirPrefix,strlen(PATH_site));
			}
		} else {
			return $dirPrefix;
		}
	}










	/**************************
	 *
	 * File Input
	 *
	 *************************/

	/**
	 * Loads the header section/all of the $filename into memory
	 *
	 * @param	string		Filename, absolute
	 * @param	boolean		If set, all information is loaded (header, records and files). Otherwise the default is to read only the header information
	 * @return	boolean		True if the operation went well
	 */
	function loadFile($filename,$all=0)	{
		if (@is_file($filename))	{
			$fI = pathinfo($filename);
			if (strtolower($fI['extension'])=='xml')	{
					// XML:
				$xmlContent = t3lib_div::getUrl($filename);
				if (strlen($xmlContent))	{
					$this->dat = t3lib_div::xml2array($xmlContent,'',TRUE);
					if (is_array($this->dat))	{
						if ($this->dat['_DOCUMENT_TAG']==='T3RecordDocument' && is_array($this->dat['header']) && is_array($this->dat['records']))	{
							$this->loadInit();
							return TRUE;
						} else $this->error('XML file did not contain proper XML for TYPO3 Import');
					} else $this->error('XML could not be parsed: '.$this->dat);
				} else $this->error('Error opening file: '.$filename);
			} else {
					// T3D
				if($fd = fopen($filename,'rb'))	{
					$this->dat['header'] = $this->getNextFilePart($fd,1,'header');
					if ($all)	{
						$this->dat['records'] = $this->getNextFilePart($fd,1,'records');
						$this->dat['files'] = $this->getNextFilePart($fd,1,'files');
					}
					$this->loadInit();
					return TRUE;
				} else $this->error('Error opening file: '.$filename);
				fclose($fd);
			}
		} else $this->error('Filename not found: '.$filename);

		return FALSE;
	}

	/**
	 * Returns the next content part form the fileresource (t3d), $fd
	 *
	 * @param	pointer		File pointer
	 * @param	boolean		If set, the returned content is unserialized into an array, otherwise you get the raw string
	 * @param	string		For error messages this indicates the section of the problem.
	 * @return	string		Data string
	 * @access private
	 * @see loadFile()
	 */
	function getNextFilePart($fd,$unserialize=0,$name='')	{
		$initStrLen = 32+1+1+1+10+1;

			// getting header data
		$initStr = fread($fd, $initStrLen);
		$initStrDat = explode(':',$initStr);
		if (strstr($initStrDat[0],'Warning') == FALSE)	{
			if (!strcmp($initStrDat[3],''))	{
				$datString = fread($fd,intval($initStrDat[2]));
				fread($fd,1);
				if (!strcmp(md5($datString), $initStrDat[0]))	{
					if ($initStrDat[1])	{
						if ($this->compress)	{
							$datString = gzuncompress($datString);
						} else $this->error('Content read error: This file requires decompression, but this server does not offer gzcompress()/gzuncompress() functions.',1);
					}
					return $unserialize ? unserialize($datString) : $datString;
				} else $this->error('MD5 check failed ('.$name.')');
			} else $this->error('File read error: InitString had a wrong length. ('.$name.')');
		} else $this->error('File read error: Warning message in file. ('.$initStr.fgets($fd).')');
	}

	/**
	 * Loads T3D file content into the $this->dat array
	 * (This function can be used to test the output strings from ->compileMemoryToFileContent())
	 *
	 * @param	string		File content
	 * @return	void
	 */
	function loadContent($filecontent)	{
		$pointer = 0;

		$this->dat['header'] = $this->getNextContentPart($filecontent,$pointer,1,'header');
		$this->dat['records'] = $this->getNextContentPart($filecontent,$pointer,1,'records');
		$this->dat['files'] = $this->getNextContentPart($filecontent,$pointer,1,'files');
		$this->loadInit();
	}

	/**
	 * Returns the next content part from the $filecontent
	 *
	 * @param	string		File content string
	 * @param	integer		File pointer (where to read from)
	 * @param	boolean		If set, the returned content is unserialized into an array, otherwise you get the raw string
	 * @param	string		For error messages this indicates the section of the problem.
	 * @return	string		Data string
	 */
	function getNextContentPart($filecontent,&$pointer,$unserialize=0,$name='')	{
		$initStrLen = 32+1+1+1+10+1;
			// getting header data
		$initStr = substr($filecontent,$pointer,$initStrLen);
		$pointer+= $initStrLen;
		$initStrDat = explode(':',$initStr);
		if (!strcmp($initStrDat[3],''))	{
			$datString = substr($filecontent,$pointer,intval($initStrDat[2]));
			$pointer+= intval($initStrDat[2])+1;
			if (!strcmp(md5($datString),$initStrDat[0]))	{
				if ($initStrDat[1])	{
					if ($this->compress)	{
						$datString = gzuncompress($datString);
					} else $this->error('Content read error: This file requires decompression, but this server does not offer gzcompress()/gzuncompress() functions.',1);
				}
				return $unserialize ? unserialize($datString) : $datString;
			} else $this->error('MD5 check failed ('.$name.')');
		} else $this->error('Content read error: InitString had a wrong length. ('.$name.')');
	}

	/**
	 * Setting up the object based on the recently loaded ->dat array
	 *
	 * @return	void
	 */
	function loadInit()	{
		$this->relStaticTables = (array)$this->dat['header']['relStaticTables'];
		$this->excludeMap = (array)$this->dat['header']['excludeMap'];

		$this->softrefCfg = (array)$this->dat['header']['softrefCfg'];
		$this->extensionDependencies = (array)$this->dat['header']['extensionDependencies'];

		$this->fixCharsets();
	}

	/**
	 * Fix charset of import memory if different from system charset
	 *
	 * @return	void
	 * @see loadInit()
	 */
	function fixCharsets()	{
		global $LANG;

		$importCharset = $this->dat['header']['charset'];
		if ($importCharset)	{
			if ($importCharset!==$LANG->charSet)	{
				$this->error('CHARSET: Converting charset of input file ('.$importCharset.') to the system charset ('.$LANG->charSet.')');

					// convert meta data:
				if (is_array($this->dat['header']['meta']))	{
					$LANG->csConvObj->convArray($this->dat['header']['meta'],$importCharset,$LANG->charSet);
				}
					// convert record headers:
				if (is_array($this->dat['header']['records']))	{
					$LANG->csConvObj->convArray($this->dat['header']['records'],$importCharset,$LANG->charSet);
				}
					// convert records themselves:
				if (is_array($this->dat['records']))	{
					$LANG->csConvObj->convArray($this->dat['records'],$importCharset,$LANG->charSet);
				}
			}
		} else {
			$this->error('CHARSET: No charset found in import file!');
		}
	}



















	/********************************************************
	 *
	 * Visual rendering of import/export memory, $this->dat
	 *
	 ********************************************************/

	/**
	 * Displays an overview of the header-content.
	 *
	 * @return	string		HTML content
	 */
	function displayContentOverview()	{
		global $LANG;

			// Check extension dependencies:
		if (is_array($this->dat['header']['extensionDependencies']))	{
			foreach($this->dat['header']['extensionDependencies'] as $extKey)	{
				if (!t3lib_extMgm::isLoaded($extKey))	{
					$this->error('DEPENDENCY: The extension with key "'.$extKey.'" must be installed!');
				}
			}
		}

			// Probably this is done to save memory space?
		unset($this->dat['files']);

			// Traverse header:
		$this->remainHeader = $this->dat['header'];
		if (is_array($this->remainHeader))	{

				// If there is a page tree set, show that:
			if (is_array($this->dat['header']['pagetree']))	{
				reset($this->dat['header']['pagetree']);
				$lines = array();
				$this->traversePageTree($this->dat['header']['pagetree'],$lines);

				$rows = array();
				$rows[] = '
				<tr class="bgColor5 tableheader">
					<td>'.$LANG->getLL('impexpcore_displaycon_controls',1).'</td>
					<td>'.$LANG->getLL('impexpcore_displaycon_title',1).'</td>
					<td>'.$LANG->getLL('impexpcore_displaycon_size',1).'</td>
					<td>'.$LANG->getLL('impexpcore_displaycon_message',1).'</td>
					'.($this->update ? '<td>'.$LANG->getLL('impexpcore_displaycon_updateMode',1).'</td>' : '').'
					'.($this->update ? '<td>'.$LANG->getLL('impexpcore_displaycon_currentPath',1).'</td>' : '').'
					'.($this->showDiff ? '<td>'.$LANG->getLL('impexpcore_displaycon_result',1).'</td>' : '').'
				</tr>';

				foreach($lines as $r)	{
					$rows[] = '
					<tr class="'.$r['class'].'">
						<td>'.$this->renderControls($r).'</td>
						<td nowrap="nowrap">'.$r['preCode'].$r['title'].'</td>
						<td nowrap="nowrap">'.t3lib_div::formatSize($r['size']).'</td>
						<td nowrap="nowrap">'.($r['msg'] && !$this->doesImport ? '<span class="typo3-red">'.htmlspecialchars($r['msg']).'</span>' : '').'</td>
						'.($this->update ? '<td nowrap="nowrap">'.$r['updateMode'].'</td>' : '').'
						'.($this->update ? '<td nowrap="nowrap">'.$r['updatePath'].'</td>' : '').'
						'.($this->showDiff ? '<td>'.$r['showDiffContent'].'</td>' : '').'
					</tr>';
				}

				$out = '
					<strong>'.$LANG->getLL('impexpcore_displaycon_insidePagetree',1).'</strong>
					<br /><br />
					<table border="0" cellpadding="0" cellspacing="1">'.implode('',$rows).'</table>
					<br /><br />';
			}

				// Print remaining records that were not contained inside the page tree:
			$lines = array();
			if (is_array($this->remainHeader['records']))	{
				if (is_array($this->remainHeader['records']['pages']))	{
					$this->traversePageRecords($this->remainHeader['records']['pages'], $lines);
				}
				$this->traverseAllRecords($this->remainHeader['records'], $lines);

				if (count($lines))	{
					$rows = array();
					$rows[] = '
					<tr class="bgColor5 tableheader">
						<td>'.$LANG->getLL('impexpcore_displaycon_controls',1).'</td>
						<td>'.$LANG->getLL('impexpcore_displaycon_title',1).'</td>
						<td>'.$LANG->getLL('impexpcore_displaycon_size',1).'</td>
						<td>'.$LANG->getLL('impexpcore_displaycon_message',1).'</td>
						'.($this->update ? '<td>'.$LANG->getLL('impexpcore_displaycon_updateMode',1).'</td>' : '').'
						'.($this->update ? '<td>'.$LANG->getLL('impexpcore_displaycon_currentPath',1).'</td>' : '').'
						'.($this->showDiff ? '<td>'.$LANG->getLL('impexpcore_displaycon_result',1).'</td>' : '').'
					</tr>';

					foreach($lines as $r)	{
						$rows[] = '<tr class="'.$r['class'].'">
							<td>'.$this->renderControls($r).'</td>
							<td nowrap="nowrap">'.$r['preCode'].$r['title'].'</td>
							<td nowrap="nowrap">'.t3lib_div::formatSize($r['size']).'</td>
							<td nowrap="nowrap">'.($r['msg'] && !$this->doesImport ? '<span class="typo3-red">'.htmlspecialchars($r['msg']).'</span>' : '').'</td>
							'.($this->update ? '<td nowrap="nowrap">'.$r['updateMode'].'</td>' : '').'
							'.($this->update ? '<td nowrap="nowrap">'.$r['updatePath'].'</td>' : '').'
							'.($this->showDiff ? '<td>'.$r['showDiffContent'].'</td>' : '').'
						</tr>';
					}

					$out.= '
						<strong>'.$LANG->getLL('impexpcore_singlereco_outsidePagetree',1).'</strong>
						<br /><br />
						<table border="0" cellpadding="0" cellspacing="1">'.implode('',$rows).'</table>';
				}
			}
		}
		return $out;
	}

	/**
	 * Go through page tree for display
	 *
	 * @param	array		Page tree array with uid/subrow (from ->dat[header][pagetree]
	 * @param	array		Output lines array (is passed by reference and modified)
	 * @param	string		Pre-HTML code
	 * @return	void
	 */
	function traversePageTree($pT,&$lines,$preCode='')	{
		foreach ($pT as $k => $v) {

				// Add this page:
			$this->singleRecordLines('pages',$k,$lines,$preCode);

				// Subrecords:
			if (is_array($this->dat['header']['pid_lookup'][$k]))	{
				foreach ($this->dat['header']['pid_lookup'][$k] as $t => $recUidArr) {
					if ($t!='pages')	{
						foreach ($recUidArr as $ruid => $value) {
							$this->singleRecordLines($t,$ruid,$lines,$preCode.'&nbsp;&nbsp;&nbsp;&nbsp;');
						}
					}
				}
				unset($this->remainHeader['pid_lookup'][$k]);
			}

				// Subpages, called recursively:
			if (is_array($v['subrow']))		{
				$this->traversePageTree($v['subrow'],$lines,$preCode.'&nbsp;&nbsp;&nbsp;&nbsp;');
			}
		}
	}

	/**
	 * Go through remaining pages (not in tree)
	 *
	 * @param	array		Page tree array with uid/subrow (from ->dat[header][pagetree]
	 * @param	array		Output lines array (is passed by reference and modified)
	 * @return	void
	 */
	function traversePageRecords($pT,&$lines)	{
		foreach ($pT as $k => $rHeader) {
			$this->singleRecordLines('pages',$k,$lines,'',1);
				// Subrecords:
			if (is_array($this->dat['header']['pid_lookup'][$k]))	{
				foreach ($this->dat['header']['pid_lookup'][$k] as $t => $recUidArr) {
					if ($t!='pages')	{
						foreach ($recUidArr as $ruid => $value) {
							$this->singleRecordLines($t,$ruid,$lines,'&nbsp;&nbsp;&nbsp;&nbsp;');
						}
					}
				}
				unset($this->remainHeader['pid_lookup'][$k]);
			}
		}
	}

	/**
	 * Go through ALL records (if the pages are displayed first, those will not be amoung these!)
	 *
	 * @param	array		Page tree array with uid/subrow (from ->dat[header][pagetree]
	 * @param	array		Output lines array (is passed by reference and modified)
	 * @return	void
	 */
	function traverseAllRecords($pT,&$lines)	{
		foreach ($pT as $t => $recUidArr) {
			if ($t!='pages')	{
				foreach ($recUidArr as $ruid => $value) {
					$this->singleRecordLines($t,$ruid,$lines,$preCode,1);
				}
			}
		}
	}

	/**
	 * Add entries for a single record
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	array		Output lines array (is passed by reference and modified)
	 * @param	string		Pre-HTML code
	 * @param	boolean		If you want import validation, you can set this so it checks if the import can take place on the specified page.
	 * @return	void
	 */
	function singleRecordLines($table,$uid,&$lines,$preCode,$checkImportInPidRecord=0)	{
		global $TCA,$BE_USER,$LANG;

			// Get record:
		$record = $this->dat['header']['records'][$table][$uid];
		unset($this->remainHeader['records'][$table][$uid]);
		if (!is_array($record) && !($table==='pages' && !$uid))	$this->error('MISSING RECORD: '.$table.':'.$uid,1);

			// Begin to create the line arrays information record, pInfo:
		$pInfo = array();
		$pInfo['ref'] = $table.':'.$uid;
		if ($table==='_SOFTREF_')	{	// Unknown table name:
			$pInfo['preCode'] = $preCode;
			$pInfo['title'] = '<em>'.$LANG->getLL('impexpcore_singlereco_softReferencesFiles',1).'</em>';
		} elseif (!isset($TCA[$table]))	{	// Unknown table name:
			$pInfo['preCode'] = $preCode;
			$pInfo['msg'] = "UNKNOWN TABLE '".$pInfo['ref']."'";
			$pInfo['title'] = '<em>'.htmlspecialchars($record['title']).'</em>';
		} else {	// Otherwise, set table icon and title.

				// Import Validation (triggered by $this->display_import_pid_record) will show messages if import is not possible of various items.
			if (is_array($this->display_import_pid_record))	{
				if ($checkImportInPidRecord)	{
					if (!$BE_USER->doesUserHaveAccess($this->display_import_pid_record, $table=='pages'?8:16))	{
						$pInfo['msg'].="'".$pInfo['ref']."' cannot be INSERTED on this page! ";
					}
					if (!$this->checkDokType($table, $this->display_import_pid_record['doktype']) && !$TCA[$table]['ctrl']['rootLevel'])	{
						$pInfo['msg'].="'".$table."' cannot be INSERTED on this page type (change to 'sysFolder'!) ";
					}
				}
				if (!$BE_USER->check('tables_modify',$table))	{$pInfo['msg'].="You are not allowed to CREATE '".$table."' tables! ";}

				if ($TCA[$table]['ctrl']['readOnly'])	{$pInfo['msg'].="TABLE '".$table."' is READ ONLY! ";}
				if ($TCA[$table]['ctrl']['adminOnly'] && !$BE_USER->isAdmin())	{$pInfo['msg'].="TABLE '".$table."' is ADMIN ONLY! ";}
				if ($TCA[$table]['ctrl']['is_static'])	{$pInfo['msg'].="TABLE '".$table."' is a STATIC TABLE! ";}
				if ($TCA[$table]['ctrl']['rootLevel'])	{$pInfo['msg'].="TABLE '".$table."' will be inserted on ROOT LEVEL! ";}

				$diffInverse = FALSE;
				if ($this->update)	{
					$diffInverse = TRUE;	// In case of update-PREVIEW we swap the diff-sources.
					$recInf = $this->doesRecordExist($table, $uid, $this->showDiff ? '*' : '');
					$pInfo['updatePath']= $recInf ? htmlspecialchars($this->getRecordPath($recInf['pid'])) : '<strong>NEW!</strong>';

						// Mode selector:
					$optValues = array();
					$optValues[] = $recInf ? $LANG->getLL('impexpcore_singlereco_update') : $LANG->getLL('impexpcore_singlereco_insert');
					if ($recInf) $optValues['as_new'] = $LANG->getLL('impexpcore_singlereco_importAsNew');
					if ($recInf) {
						if (!$this->global_ignore_pid)	{
							$optValues['ignore_pid'] = $LANG->getLL('impexpcore_singlereco_ignorePid');
						} else {
							$optValues['respect_pid'] = $LANG->getLL('impexpcore_singlereco_respectPid');
						}
					}
					if (!$recInf && $GLOBALS['BE_USER']->isAdmin()) $optValues['force_uid'] = sprintf($LANG->getLL('impexpcore_singlereco_forceUidSAdmin'),$uid);
					$optValues['exclude'] = $LANG->getLL('impexpcore_singlereco_exclude');

					$pInfo['updateMode'] = $this->renderSelectBox('tx_impexp[import_mode]['.$table.':'.$uid.']',$this->import_mode[$table.':'.$uid],$optValues);
				}

					// Diff vieiw:
				if ($this->showDiff)	{
						// For IMPORTS, get new id:
					if ($newUid = $this->import_mapId[$table][$uid])	{
						$diffInverse = FALSE;
						$recInf = $this->doesRecordExist($table, $newUid, '*');
						t3lib_BEfunc::workspaceOL($table,$recInf);
					}
					if (is_array($recInf))	{
						$pInfo['showDiffContent'] = $this->compareRecords($recInf, $this->dat['records'][$table.':'.$uid]['data'], $table, $diffInverse);
					}
				}
			}

			$pInfo['preCode'] = $preCode.t3lib_iconworks::getIconImage($table,$this->dat['records'][$table.':'.$uid]['data'],$GLOBALS['BACK_PATH'],'align="top" title="'.htmlspecialchars($table.':'.$uid).'"');
			$pInfo['title'] = htmlspecialchars($record['title']);

				// View page:
			if ($table==='pages')	{
				$viewID = $this->mode === 'export' ? $uid : ($this->doesImport ? $this->import_mapId['pages'][$uid] : 0);
				if ($viewID)	{
					$pInfo['title'] = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($viewID, $GLOBALS['BACK_PATH'])).'return false;">'.$pInfo['title'].'</a>';
				}
			}
		}
		$pInfo['class'] = $table=='pages' ? 'bgColor4-20' : 'bgColor4';
		$pInfo['type'] = 'record';
		$pInfo['size'] = $record['size'];
		$lines[] = $pInfo;

			// File relations:
		if (is_array($record['filerefs']))	{
			$this->addFiles($record['filerefs'],$lines,$preCode);
		}

			// DB relations
		if (is_array($record['rels']))	{
			$this->addRelations($record['rels'],$lines,$preCode);
		}

			// Soft ref
		if (count($record['softrefs']))		{
			$preCode_A = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;';
			$preCode_B = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			foreach($record['softrefs'] as $info)	{
				$pInfo = array();
				$pInfo['preCode'] = $preCode_A. t3lib_iconWorks::getSpriteIcon('status-reference-soft');
				$pInfo['title'] = '<em>'.$info['field'].', "'.$info['spKey'].'" </em>: <span title="'.htmlspecialchars($info['matchString']).'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($info['matchString'],60)).'</span>';
				if ($info['subst']['type'])	{
					if (strlen($info['subst']['title']))	{
						$pInfo['title'].= '<br/>'.$preCode_B.'<strong>'.$LANG->getLL('impexpcore_singlereco_title',1).'</strong> '.htmlspecialchars(t3lib_div::fixed_lgd_cs($info['subst']['title'],60));
					}
					if (strlen($info['subst']['description']))	{
						$pInfo['title'].= '<br/>'.$preCode_B.'<strong>'.$LANG->getLL('impexpcore_singlereco_descr',1).'</strong> '.htmlspecialchars(t3lib_div::fixed_lgd_cs($info['subst']['description'],60));
					}
					$pInfo['title'].= '<br/>'.$preCode_B.
											($info['subst']['type'] == 'file' ? $LANG->getLL('impexpcore_singlereco_filename',1).' <strong>'.$info['subst']['relFileName'].'</strong>' : '').
											($info['subst']['type'] == 'string' ? $LANG->getLL('impexpcore_singlereco_value',1).' <strong>'.$info['subst']['tokenValue'].'</strong>' : '').
											($info['subst']['type'] == 'db' ? $LANG->getLL('impexpcore_softrefsel_record',1).' <strong>'.$info['subst']['recordRef'].'</strong>' : '');
				}
				$pInfo['ref'] = 'SOFTREF';
				$pInfo['size'] = '';
				$pInfo['class'] = 'bgColor3';
				$pInfo['type'] = 'softref';
				$pInfo['_softRefInfo'] = $info;
				$pInfo['type'] = 'softref';
				if ($info['error'] && !t3lib_div::inList('editable,exclude',$this->softrefCfg[$info['subst']['tokenID']]['mode']))	{
					$pInfo['msg'].= $info['error'];
				}
				$lines[] = $pInfo;

					// Add relations:
				if ($info['subst']['type'] == 'db')	{
					list($tempTable, $tempUid) = explode(':', $info['subst']['recordRef']);
					$this->addRelations(array(array('table' => $tempTable, 'id' => $tempUid, 'tokenID' => $info['subst']['tokenID'])),$lines,$preCode_B,array(), '');
				}

					// Add files:
				if ($info['subst']['type'] == 'file')	{
#debug($info);
					$this->addFiles(array($info['file_ID']),$lines,$preCode_B, '', $info['subst']['tokenID']);
				}
			}
		}
	}

	/**
	 * Add DB relations entries for a record's rels-array
	 *
	 * @param	array		Array of relations
	 * @param	array		Output lines array (is passed by reference and modified)
	 * @param	string		Pre-HTML code
	 * @param	array		Recursivity check stack
	 * @param	string		Alternative HTML color class to use.
	 * @return	void
	 * @access private
	 * @see singleRecordLines()
	 */
	function addRelations($rels,&$lines,$preCode,$recurCheck=array(),$htmlColorClass='')	{

		foreach($rels as $dat)	{
			$table = $dat['table'];
			$uid = $dat['id'];
			$pInfo = array();
			$Iprepend = '';
			$staticFixed = FALSE;
			$pInfo['ref'] = $table.':'.$uid;
			if (!in_array($pInfo['ref'],$recurCheck))	{
				if ($uid > 0)	{
					$record = $this->dat['header']['records'][$table][$uid];
					if (!is_array($record))	{
						if ($this->isTableStatic($table) || $this->isExcluded($table, $uid) || ($dat['tokenID'] && !$this->includeSoftref($dat['tokenID'])))	{
							$pInfo['title'] = htmlspecialchars('STATIC: '.$pInfo['ref']);
							$Iprepend = '_static';
							$staticFixed = TRUE;
						} else {
							$doesRE = $this->doesRecordExist($table,$uid);
							$lostPath = $this->getRecordPath($table==='pages' ? $doesRE['uid'] : $doesRE['pid']);

							$pInfo['title'] = htmlspecialchars($pInfo['ref']);
							$pInfo['title'] = '<span title="'.htmlspecialchars($lostPath).'">'.$pInfo['title'].'</span>';

							$pInfo['msg'] = 'LOST RELATION'.(!$doesRE ? ' (Record not found!)' : ' (Path: '.$lostPath.')');
							$Iprepend = '_lost';
	#						debug('MISSING relation: '.$table.':'.$uid);
						}
					} else {
						$pInfo['title'] = htmlspecialchars($record['title']);
						$pInfo['title'] = '<span title="'.htmlspecialchars($this->getRecordPath($table==='pages' ? $record['uid'] : $record['pid'])).'">'.$pInfo['title'].'</span>';

					#	$pInfo['size'] = $record['size'];
					}
				} else {	// Negative values in relation fields. This is typically sys_language fields, fe_users fields etc. They are static values. They CAN theoretically be negative pointers to uids in other tables but this is so rarely used that it is not supported
					$pInfo['title'] = htmlspecialchars('FIXED: '.$pInfo['ref']);
					$staticFixed = TRUE;
				}

				$pInfo['preCode'] = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/rel_db'.$Iprepend.'.gif','width="13" height="12"').' align="top" title="'.htmlspecialchars($pInfo['ref']).'" alt="" />';
				$pInfo['class'] = $htmlColorClass ? $htmlColorClass : 'bgColor3';
				$pInfo['type'] = 'rel';

				if (!$staticFixed || $this->showStaticRelations)	{
					$lines[] = $pInfo;
					if (is_array($record) && is_array($record['rels']))	{
						$this->addRelations($record['rels'], $lines, $preCode.'&nbsp;&nbsp;', array_merge($recurCheck,array($pInfo['ref'])), $htmlColorClass);
					}
				}
			} else $this->error($pInfo['ref'].' was recursive...');
		}
	}

	/**
	 * Add file relation entries for a record's rels-array
	 *
	 * @param	array		Array of file IDs
	 * @param	array		Output lines array (is passed by reference and modified)
	 * @param	string		Pre-HTML code
	 * @param	string		Alternative HTML color class to use.
	 * @param	string		Token ID if this is a softreference (in which case it only makes sense with a single element in the $rels array!)
	 * @return	void
	 * @access private
	 * @see singleRecordLines()
	 */
	function addFiles($rels,&$lines,$preCode,$htmlColorClass='',$tokenID='')	{

		foreach($rels as $ID)	{

				// Process file:
			$pInfo = array();
			$fI = $this->dat['header']['files'][$ID];
			if (!is_array($fI))	{
				if (!$tokenID || $this->includeSoftref($tokenID))	{
					$pInfo['msg'] = 'MISSING FILE: '.$ID;
					$this->error('MISSING FILE: '.$ID,1);
				} else {
					return;
				}
			}
			$pInfo['preCode'] = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;'.t3lib_iconWorks::getSpriteIcon('status-reference-file');
			$pInfo['title'] = htmlspecialchars($fI['filename']);
			$pInfo['ref'] = 'FILE';
			$pInfo['size'] = $fI['filesize'];
			$pInfo['class'] = $htmlColorClass ? $htmlColorClass : 'bgColor3';
			$pInfo['type'] = 'file';

				// If import mode and there is a non-RTE softreference, check the destination directory:
			if ($this->mode==='import' && $tokenID && !$fI['RTE_ORIG_ID'])	{
				if (isset($fI['parentRelFileName']))	{
					$pInfo['msg'] = 'Seems like this file is already referenced from within an HTML/CSS file. That takes precedence. ';
				} else {
					$testDirPrefix = dirname($fI['relFileName']).'/';
					$testDirPrefix2 = $this->verifyFolderAccess($testDirPrefix);

					if (!$testDirPrefix2)	{
						$pInfo['msg'] = 'ERROR: There are no available filemounts to write file in! ';
					} elseif (strcmp($testDirPrefix,$testDirPrefix2))	{
						$pInfo['msg'] = 'File will be attempted written to "'.$testDirPrefix2.'". ';
					}
				}


					// Check if file exists:
				if (file_exists(PATH_site.$fI['relFileName']))	{
					if ($this->update)	{
						$pInfo['updatePath'].= 'File exists.';
					} else {
						$pInfo['msg'].= 'File already exists! ';
					}
				}

					// Check extension:
				$fileProcObj = $this->getFileProcObj();
				if ($fileProcObj->actionPerms['newFile'])	{
					$testFI = t3lib_div::split_fileref(PATH_site.$fI['relFileName']);
					if (!$this->allowPHPScripts && !$fileProcObj->checkIfAllowed($testFI['fileext'], $testFI['path'], $testFI['file']))	{
						$pInfo['msg'].= 'File extension was not allowed!';
					}
				} else $pInfo['msg'] = 'You user profile does not allow you to create files on the server!';
			}

			$pInfo['showDiffContent'] = substr($this->fileIDMap[$ID],strlen(PATH_site));

			$lines[] = $pInfo;
			unset($this->remainHeader['files'][$ID]);

				// RTE originals:
			if ($fI['RTE_ORIG_ID'])	{
				$ID = $fI['RTE_ORIG_ID'];
				$pInfo = array();
				$fI = $this->dat['header']['files'][$ID];
				if (!is_array($fI))	{
					$pInfo['msg'] = 'MISSING RTE original FILE: '.$ID;
					$this->error('MISSING RTE original FILE: '.$ID,1);
				}

				$pInfo['showDiffContent'] = substr($this->fileIDMap[$ID],strlen(PATH_site));

				$pInfo['preCode'] = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.t3lib_iconWorks::getSpriteIcon('actions-reference-file');
				$pInfo['title'] = htmlspecialchars($fI['filename']).' <em>(Original)</em>';
				$pInfo['ref'] = 'FILE';
				$pInfo['size'] = $fI['filesize'];
				$pInfo['class'] = $htmlColorClass ? $htmlColorClass : 'bgColor3';
				$pInfo['type'] = 'file';
				$lines[] = $pInfo;
				unset($this->remainHeader['files'][$ID]);
			}

				// External resources:
			if (is_array($fI['EXT_RES_ID']))	{
				foreach($fI['EXT_RES_ID'] as $ID)	{
					$pInfo = array();
					$fI = $this->dat['header']['files'][$ID];
					if (!is_array($fI))	{
						$pInfo['msg'] = 'MISSING External Resource FILE: '.$ID;
						$this->error('MISSING External Resource FILE: '.$ID,1);
					} else {
						$pInfo['updatePath'] = $fI['parentRelFileName'];
					}

					$pInfo['showDiffContent'] = substr($this->fileIDMap[$ID],strlen(PATH_site));

					$pInfo['preCode'] = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.t3lib_iconWorks::getSpriteIcon('actions-reference-file');
					$pInfo['title'] = htmlspecialchars($fI['filename']).' <em>(Resource)</em>';
					$pInfo['ref'] = 'FILE';
					$pInfo['size'] = $fI['filesize'];
					$pInfo['class'] = $htmlColorClass ? $htmlColorClass : 'bgColor3';
					$pInfo['type'] = 'file';
					$lines[] = $pInfo;
					unset($this->remainHeader['files'][$ID]);
				}
			}
		}
	}

	/**
	 * Verifies that a table is allowed on a certain doktype of a page
	 *
	 * @param	string		Table name to check
	 * @param	integer		doktype value.
	 * @return	boolean		True if OK
	 */
	function checkDokType($checkTable,$doktype)	{
		global $PAGES_TYPES;
		$allowedTableList = isset($PAGES_TYPES[$doktype]['allowedTables']) ? $PAGES_TYPES[$doktype]['allowedTables'] : $PAGES_TYPES['default']['allowedTables'];
		$allowedArray = t3lib_div::trimExplode(',',$allowedTableList,1);
		if (strstr($allowedTableList,'*') || in_array($checkTable,$allowedArray))	{		// If all tables or the table is listed as a allowed type, return true
			return true;
		}
	}

	/**
	 * Render input controls for import or export
	 *
	 * @param	array		Configuration for element
	 * @param	boolean		Set if export situation
	 * @return	string		HTML
	 */
	function renderControls($r)	{
		global $LANG;

		if ($this->mode==='export')	{
			return ($r['type']=='record' ? '<input type="checkbox" name="tx_impexp[exclude]['.$r['ref'].']" id="checkExclude'.$r['ref'].'" value="1" /> <label for="checkExclude'.$r['ref'].'">'.$LANG->getLL('impexpcore_singlereco_exclude',1).'</label>' :
								($r['type']=='softref' ? $this->softrefSelector($r['_softRefInfo']) : ''));
		} else {	// During import

				// For softreferences with editable fields:
			if ($r['type']=='softref' && is_array($r['_softRefInfo']['subst']) && $r['_softRefInfo']['subst']['tokenID'])	{
				$tokenID = $r['_softRefInfo']['subst']['tokenID'];
				$cfg = $this->softrefCfg[$tokenID];
				if ($cfg['mode'] === 'editable')	{
					return
						(strlen($cfg['title']) ? '<strong>'.htmlspecialchars($cfg['title']).'</strong><br/>' : '').
						htmlspecialchars($cfg['description']).'<br/>
						<input type="text" name="tx_impexp[softrefInputValues]['.$tokenID.']" value="'.htmlspecialchars(isset($this->softrefInputValues[$tokenID]) ? $this->softrefInputValues[$tokenID] : $cfg['defValue']).'" />';
				}
			}
		}
	}

	/**
	 * Selectorbox with export options for soft references
	 *
	 * @param	array		softref configuration array. An export box is shown only if a substitution scheme is found for the soft reference.
	 * @return	string		Selector box HTML
	 */
	function softrefSelector($cfg) {
		global $LANG;

			// Looking for file ID if any:
		$fI = $cfg['file_ID'] ? $this->dat['header']['files'][$cfg['file_ID']] : array();

			// Substitution scheme has to be around and RTE images MUST be exported.
		if (is_array($cfg['subst']) && $cfg['subst']['tokenID'] && !$fI['RTE_ORIG_ID'])	{

				// Create options:
			$optValues = array();
			$optValues[''] = '';
			$optValues['editable'] = $LANG->getLL('impexpcore_softrefsel_editable');
			$optValues['exclude'] = $LANG->getLL('impexpcore_softrefsel_exclude');

				// Get current value:
			$value = $this->softrefCfg[$cfg['subst']['tokenID']]['mode'];

				// Render options selector:
			$selectorbox = $this->renderSelectBox('tx_impexp[softrefCfg]['.$cfg['subst']['tokenID'].'][mode]',$value,$optValues).'<br/>';

			if ($value === 'editable')	{

				$descriptionField = '';

					// Title:
				if (strlen($cfg['subst']['title']))	{
					$descriptionField.= '
					<input type="hidden" name="tx_impexp[softrefCfg]['.$cfg['subst']['tokenID'].'][title]" value="'.htmlspecialchars($cfg['subst']['title']).'" />
					<strong>'.htmlspecialchars($cfg['subst']['title']).'</strong><br/>';
				}

					// Description:
				if (!strlen($cfg['subst']['description']))	{
					$descriptionField.= '
					'.$LANG->getLL('impexpcore_printerror_description',1).'<br/>
					<input type="text" name="tx_impexp[softrefCfg]['.$cfg['subst']['tokenID'].'][description]" value="'.htmlspecialchars($this->softrefCfg[$cfg['subst']['tokenID']]['description']).'" />';
				} else {
					$descriptionField.= '

					<input type="hidden" name="tx_impexp[softrefCfg]['.$cfg['subst']['tokenID'].'][description]" value="'.htmlspecialchars($cfg['subst']['description']).'" />'.
					htmlspecialchars($cfg['subst']['description']);
				}

					// Default Value:
				$descriptionField.= '<input type="hidden" name="tx_impexp[softrefCfg]['.$cfg['subst']['tokenID'].'][defValue]" value="'.htmlspecialchars($cfg['subst']['tokenValue']).'" />';

			} else $descriptionField = '';

			return $selectorbox.$descriptionField;
		}
	}












	/*****************************
	 *
	 * Helper functions of kinds
	 *
	 *****************************/

	/**
	 * Returns true if the input table name is to be regarded as a static relation (that is, not exported etc).
	 *
	 * @param	string		Table name
	 * @return	boolean		True, if table is marked static
	 */
	function isTableStatic($table)	{
		global $TCA;

		if (is_array($TCA[$table]))	{
			return $TCA[$table]['ctrl']['is_static'] || in_array($table, $this->relStaticTables) || in_array('_ALL', $this->relStaticTables);
		}
	}

	/**
	 * Returns true if the input table name is to be included as relation
	 *
	 * @param	string		Table name
	 * @return	boolean		True, if table is marked static
	 */
	function inclRelation($table)	{
		global $TCA;

		if (is_array($TCA[$table]))	{
			return (in_array($table, $this->relOnlyTables) || in_array('_ALL', $this->relOnlyTables)) && $GLOBALS['BE_USER']->check('tables_select',$table);
		}
	}

	/**
	 * Returns true if the element should be excluded as static record.
	 *
	 * @param	string		Table name
	 * @param	integer		UID value
	 * @return	boolean		True, if table is marked static
	 */
	function isExcluded($table,$uid)	{
		global $TCA;

		return $this->excludeMap[$table.':'.$uid] ? TRUE : FALSE;
	}

	/**
	 * Returns true if soft reference should be included in exported file.
	 *
	 * @param	string		Token ID for soft reference
	 * @return	boolean		True if softreference media should be included
	 */
	function includeSoftref($tokenID)	{
		return $tokenID && !t3lib_div::inList('exclude,editable', $this->softrefCfg[$tokenID]['mode']);
	}

	/**
	 * Checking if a PID is in the webmounts of the user
	 *
	 * @param	integer		Page ID to check
	 * @return	boolean		True if OK
	 */
	function checkPID($pid)	{
		global $BE_USER;

		if (!isset($this->checkPID_cache[$pid]))	{
			$this->checkPID_cache[$pid] = (boolean)$BE_USER->isInWebMount($pid);
		}

		return $this->checkPID_cache[$pid];
	}

	/**
	 * Checks if the position of an updated record is configured to be corrected. This can be disabled globally and changed for elements individually.
	 *
	 * @param	string		Table name
	 * @param	integer		Uid or record
	 * @return	boolean		True if the position of the record should be updated to match the one in the import structure
	 */
	function dontIgnorePid($table, $uid)	{
		return $this->import_mode[$table.':'.$uid]!=='ignore_pid' &&
				(!$this->global_ignore_pid || $this->import_mode[$table.':'.$uid]==='respect_pid');
	}

	/**
	 * Checks if the record exists
	 *
	 * @param	string		Table name
	 * @param	integer		UID of record
	 * @param	string		Field list to select. Default is "uid,pid"
	 * @return	array		Result of t3lib_BEfunc::getRecord() which means the record if found, otherwise false
	 */
	function doesRecordExist($table,$uid,$fields='')	{
		return t3lib_BEfunc::getRecord($table, $uid, $fields ? $fields : 'uid,pid');
	}

	/**
	 * Returns the page title path of a PID value. Results are cached internally
	 *
	 * @param	integer		Record PID to check
	 * @return	string		The path for the input PID
	 */
	function getRecordPath($pid)	{
		if (!isset($this->cache_getRecordPath[$pid]))	{
			$clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$this->cache_getRecordPath[$pid] = (string)t3lib_BEfunc::getRecordPath($pid, $clause, 20);
		}

		return $this->cache_getRecordPath[$pid];
	}

	/**
	 * Makes a selector-box from optValues
	 *
	 * @param	string		Form element name
	 * @param	string		Current value
	 * @param	array		Options to display (key/value pairs)
	 * @return	string		HTML select element
	 */
	function renderSelectBox($prefix,$value,$optValues)	{
		$opt = array();
		$isSelFlag = 0;
		foreach ($optValues as $k => $v) {
			$sel = (!strcmp($k,$value) ? ' selected="selected"' : '');
			if ($sel)	$isSelFlag++;
			$opt[] = '<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
		}
		if (!$isSelFlag && strcmp('',$value))	{
			$opt[] = '<option value="'.htmlspecialchars($value).'" selected="selected">'.htmlspecialchars("['".$value."']").'</option>';
		}
		return '<select name="'.$prefix.'">'.implode('',$opt).'</select>';
	}

	/**
	 * Compares two records, the current database record and the one from the import memory. Will return HTML code to show any differences between them!
	 *
	 * @param	array		Database record, all fields (new values)
	 * @param	array		Import memorys record for the same table/uid, all fields (old values)
	 * @param	string		The table name of the record
	 * @param	boolean		Inverse the diff view (switch red/green, needed for pre-update difference view)
	 * @return	string		HTML
	 */
	function compareRecords($databaseRecord, $importRecord, $table, $inverseDiff=FALSE)	{
		global $TCA, $LANG;

			// Initialize:
		$output = array();
		$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');

			// Check if both inputs are records:
		if (is_array($databaseRecord) && is_array($importRecord))	{

				// Traverse based on database record
			foreach($databaseRecord as $fN => $value)	{
				if (is_array($TCA[$table]['columns'][$fN]) && $TCA[$table]['columns'][$fN]['config']['type']!='passthrough')	{
					if (isset($importRecord[$fN]))	{
						if (strcmp(trim($databaseRecord[$fN]), trim($importRecord[$fN])))	{

								// Create diff-result:
							$output[$fN] = $t3lib_diff_Obj->makeDiffDisplay(
								t3lib_BEfunc::getProcessedValue($table,$fN,!$inverseDiff ? $importRecord[$fN] : $databaseRecord[$fN] ,0,1,1),
								t3lib_BEfunc::getProcessedValue($table,$fN,!$inverseDiff ? $databaseRecord[$fN] : $importRecord[$fN] ,0,1,1)
							);
						}
						unset($importRecord[$fN]);
					} else {
							// This will tell us if the field is not in the import file, but who cares? It is totally ok that the database contains fields that are not in the import, isn't it (extensions could be installed that added these fields!)?
						#$output[$fN] = '<strong>Field missing</strong> in import file';
					}
				}
			}

				// Traverse remaining in import record:
			foreach($importRecord as $fN => $value)	{
				if (is_array($TCA[$table]['columns'][$fN]) && $TCA[$table]['columns'][$fN]['config']['type']!='passthrough')	{
					$output[$fN] = '<strong>Field missing</strong> in database';
				}
			}

				// Create output:
			if (count($output))	{
				$tRows = array();
				foreach($output as $fN => $state)	{
					$tRows[] = '
						<tr>
							<td class="bgColor5">'.$LANG->sL($TCA[$table]['columns'][$fN]['label'],1).' ('.htmlspecialchars($fN).')</td>
							<td class="bgColor4">'.$state.'</td>
						</tr>
					';
				}

				$output = '<table border="0" cellpadding="0" cellspacing="1">'.implode('',$tRows).'</table>';
			} else {
				$output = 'Match';
			}

			return '<strong class="nobr">['.htmlspecialchars($table.':'.$importRecord['uid'].' => '.$databaseRecord['uid']).']:</strong> '.$output;
		}


		return 'ERROR: One of the inputs were not an array!';
	}

	/**
	 * Creates the original file name for a copy-RTE image (magic type)
	 *
	 * @param	string		RTE copy filename, eg. "RTEmagicC_user_pm_icon_01.gif.gif"
	 * @return	string		RTE original filename, eg. "RTEmagicP_user_pm_icon_01.gif". IF the input filename was NOT prefixed RTEmagicC_ as RTE images would be, nothing is returned!
	 */
	function getRTEoriginalFilename($string)	{
			// If "magic image":
		if (t3lib_div::isFirstPartOfStr($string,'RTEmagicC_'))	{
				// Find original file:
			$pI = pathinfo(substr($string,strlen('RTEmagicC_')));
			$filename = substr($pI['basename'],0,-strlen('.'.$pI['extension']));
			$origFilePath = 'RTEmagicP_'.$filename;

			return $origFilePath;
		}
	}

	/**
	 * Returns file processing object, initialized only once.
	 *
	 * @return	object		File processor object
	 */
	function getFileProcObj() {
		if (!is_object($this->fileProcObj))	{
			$this->fileProcObj = t3lib_div::makeInstance('t3lib_extFileFunctions');
			$this->fileProcObj->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
			$this->fileProcObj->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());
		}
		return $this->fileProcObj;
	}

	/**
	 * Call Hook
	 *
	 * @param string $name name of the hook
	 * @param array $params array with params
	 * @return void
	 */
	public function callHook($name, $params) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/impexp/class.tx_impexp.php'][$name])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/impexp/class.tx_impexp.php'][$name] as $hook) {
				t3lib_div::callUserFunction($hook, $params, $this);
			}
		}
	}







	/*****************************
	 *
	 * Error handling
	 *
	 *****************************/

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
	 * Returns a table with the error-messages.
	 *
	 * @return	string		HTML print of error log
	 */
	function printErrorLog()	{
		return count($this->errorLog) ? t3lib_div::view_array($this->errorLog) : '';
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/class.tx_impexp.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/class.tx_impexp.php']);
}
?>
