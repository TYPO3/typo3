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
 * Core functions for cleaning and analysing
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   63: class tx_lowlevel_cleaner_core
 *   70:     function missing_files_analyze()
 *  133:     function missing_relations_analyze($filter='')
 *  221:     function double_files_analyze()
 *  305:     function RTEmagic_files_analyze()
 *  386:     function clean_lost_files_analyze()
 *
 *              SECTION: CLI functionality
 *  487:     function cli_main($argv)
 *  517:     function cli_printInfo($header,$res,$silent=FALSE)
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require_once(PATH_t3lib.'class.t3lib_admin.php');



/**
 * Core functions for cleaning and analysing
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class tx_lowlevel_cleaner_core {

	var $label_infoString = 'The list of records is organized as [table]:[uid]:[field]:[flexpointer]:[softref_key]';









	/**************************
	 *
	 * Analyse functions
	 *
	 *************************/

	/**
	 * Find missing files
	 *
	 * @return	array
	 */
	function missing_files_analyze() {
		global $TYPO3_DB;

		$listExplain = ' Shows the relative filename of missing file as header and under a list of record fields in which the references are found. '.$this->label_infoString;

			// Initialize result array:
		$resultArray = array(
			'message' => '
Objective: Find all file references from non-deleted records pointing to a missing (non-existing) file.

Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- relevant soft reference parsers applied everywhere file references are used inline

Files may be missing for these reasons (except software bugs):
- someone manually deleted the file inside fileadmin/ or another user maintained folder. If the reference was a soft reference (opposite to a TCEmain managed file relation from "group" type fields), technically it is not an error although it might be a mistake that someone did so.
- someone manually deleted the file inside the uploads/ folder (typically containing managed files) which is an error since no user interaction should take place there.

NOTICE: Uses the Reference Index Table (sys_refindex) for analysis. Update it before use!',
			'headers' => array(
				'managedFilesMissing' => array('List of missing files managed by TCEmain', $listExplain, 3),
				'softrefFilesMissing' => array('List of missing files registered as a soft reference', $listExplain, 3),
				'warnings' => array('Warnings, if any','',2)
			),
			'managedFilesMissing' => array(),
			'softrefFilesMissing' => array(),
			'warnings' => array()
		);

			// Select all files in the reference table
		$recs = $TYPO3_DB->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table='.$TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex').
				' AND deleted=0'	// Check only for records which are not deleted (we don't care about missing files in deleted-flagged records)
		);

			// Traverse the files and put into a large table:
		foreach($recs as $rec)	{

				// Compile info string for location of reference:
			$infoString = $rec['tablename'].':'.$rec['recuid'].':'.$rec['field'].':'.$rec['flexpointer'].':'.$rec['softref_key'];

				// Handle missing file:
			if (!@is_file(PATH_site.$rec['ref_string']))	{

				if ((string)$rec['softref_key']=='')	{
					$resultArrayIndex = 'managedFilesMissing';
				} else {
					$resultArrayIndex = 'softrefFilesMissing';
				}

				$resultArray[$resultArrayIndex][$rec['ref_string']][$rec['hash']] = $infoString;
			}
		}

		return $resultArray;
	}

	/**
	 * Missing relations to database records
	 *
	 * @param	string		Filter selection, options: "softref", "managed", "" (all)
	 * @return	array
	 */
	function missing_relations_analyze($filter='') {
		global $TYPO3_DB;

		$listExplain = ' Shows the missing record as header and underneath a list of record fields in which the references are found. '.$this->label_infoString;

			// Initialize result array:
		$resultArray = array(
			'message' => '
Objective: Find all record references pointing to a missing (non-existing or deleted-flagged) record.
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- all database references to check are integers greater than zero
- does not check if a referenced record is inside an offline branch, another workspace etc. which could make the reference useless in reality or otherwise question integrity
Records may be missing for these reasons (except software bugs):
- someone deleted the record which is technically not an error although it might be a mistake that someone did so.
- after flushing published versions and/or deleted-flagged records a number of new missing references might appear; those were pointing to records just flushed.
NOTICE: Uses the Reference Index Table (sys_refindex) for analysis. Update it before use!',
			'headers' => array(
				'offlineVersionRecords' => array('Offline version records','These records are offline versions having a pid=-1 and references should never occur directly to their uids.'.$listExplain,3),
				'deletedRecords' => array('Deleted-flagged records','These records are deleted with a flag but references are still pointing at them. Keeping the references is useful if you undelete the referenced records later, otherwise the references are lost completely when the deleted records are flushed at some point.'.$listExplain,2),
				'nonExistingRecords' => array('Non-existing records to which there are references','These references can safely be removed since there is no record found in the database at all.'.$listExplain,3),	// 3 = error
				'uniqueReferencesToTables' => array('Unique references to various tables','For each listed table, this shows how many different records had references pointing to them. More references to the same record counts only 1, hence it is the number of unique referenced records you see. The count includes both valid and invalid references.',1), // 1 = info
				'warnings' => array('Warnings picked up','',2)		// 2 = warning
			),
			'offlineVersionRecords' => array(),
			'deletedRecords' => array(),
			'nonExistingRecords' => array(),
			'uniqueReferencesToTables' => array(),
			'warnings' => array()	
		);

			// Create clause to filter by:
		$filterClause = '';
		if ($filter==='softref') {
			$filterClause = ' AND softref_key!='.$TYPO3_DB->fullQuoteStr('', 'sys_refindex');
		}
		if ($filter==='managed') {
			$filterClause = ' AND softref_key='.$TYPO3_DB->fullQuoteStr('', 'sys_refindex');
		}

			// Select all files in the reference table not found by a soft reference parser (thus TCA configured)
		$recs = $TYPO3_DB->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table!='.$TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex').	// Assuming that any other key will be a table name!
			' AND ref_uid>0'.
			$filterClause.
			' AND deleted=0'	// Check only for records which are not deleted (we don't care about missing relations in deleted-flagged records)
		);

			// Traverse the files and put into a large table:
		$tempExists = array();
		foreach($recs as $rec)	{
			$idx = $rec['ref_table'].':'.$rec['ref_uid'];

			if (!isset($tempExists[$idx]))	{
		
					// Select all files in the reference table not found by a soft reference parser (thus TCA configured)
				if (isset($GLOBALS['TCA'][$rec['ref_table']]))	{
					$recs = $TYPO3_DB->exec_SELECTgetRows(
						'uid,pid'.($GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] ? ','.$GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] : ''),
						$rec['ref_table'],
						'uid='.intval($rec['ref_uid'])
					);

					$tempExists[$idx] = count($recs) ? TRUE : FALSE;
				} else {
					$tempExists[$idx] = FALSE;
				}
				$resultArray['uniqueReferencesToTables'][$rec['ref_table']]++;
			}

				// Compile info string for location of reference:
			$infoString = $rec['tablename'].':'.$rec['recuid'].':'.$rec['field'].':'.$rec['flexpointer'].':'.$rec['softref_key'];

				// Handle missing file:
			if ($tempExists[$idx])	{
				if ($recs[0]['pid']==-1)	{
					$resultArray['offlineVersionRecords'][$idx][$rec['hash']] = $infoString;
				} elseif ($GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] && $recs[0][$GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete']])	{
					$resultArray['deletedRecords'][$idx][$rec['hash']] = $infoString;
				}
			} else {
				$resultArray['nonExistingRecords'][$idx][$rec['hash']] = $infoString;
			}
		}

		return $resultArray;
	}

	/**
	 * Find managed files which are referred to more than one time
	 *
	 * @return	array
	 */
	function double_files_analyze()	{
		global $TYPO3_DB;

			// Initialize result array:
		$resultArray = array(
			'message' => '
Objective: Looking for files from TYPO3 managed records which are referenced more than one time (only one time allowed)
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- files found in deleted records are included (otherwise you would see a false list of lost files)
' .
		'Files attached to records in TYPO3 using a "group" type configuration in TCA or FlexForm DataStructure are managed exclusively by the system and there must always exist a 1-1 reference between the file and the reference in the record.' .
		'This tool will expose when such files are referenced from multiple locations which is considered an integrity error. ' .
		'If a multi-reference is found it was typically created because the record was copied or modified outside of TCEmain which will otherwise maintain the relations correctly. ' .
		'Multi-references should be resolved to 1-1 references as soon as possible. The danger of keeping multi-references is that if the file is removed from one of the refering records it will actually be deleted in the file system, leaving missing files for the remaining referers!

NOTICE: Uses the Reference Index Table (sys_refindex) for analysis. Update it before use!',
			'headers' => array(
				'multipleReferencesList_count' => array('Number of multi-reference files','(See below)',1),
				'singleReferencesList_count' => array('Number of files correctly referenced','The amount of correct 1-1 references',1),
				'multipleReferencesList' => array('Entries with files having multiple references','These are serious problems that should be resolved ASAP to prevent data loss! '.$this->label_infoString,3),
				'dirname_registry' => array('Registry of directories in which files are found.','Registry includes which table/field pairs store files in them plus how many files their store.',1),
				'missingFiles' => array('Tracking missing files','(Extra feature, not related to tracking of double references. Further, the list may include more files than found in the missing_files()-test because this list includes missing files from deleted records.)',0),
				'warnings' => array('Warnings picked up','',2)
			),
			'multipleReferencesList_count' => 0,
			'singleReferencesList_count' => 0,	
			'multipleReferencesList' => array(),
			'dirname_registry' => array(),
			'missingFiles' => array(),
			'warnings' => array()	
		);

			// Select all files in the reference table not found by a soft reference parser (thus TCA configured)
		$recs = $TYPO3_DB->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table='.$TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex').
				' AND softref_key='.$TYPO3_DB->fullQuoteStr('', 'sys_refindex')
		);

			// Traverse the files and put into a large table:
		$tempCount = array();
		foreach($recs as $rec)	{

				// Compile info string for location of reference:
			$infoString = $rec['tablename'].':'.$rec['recuid'].':'.$rec['field'].':'.$rec['flexpointer'].':';

				// Registering occurencies in directories:
			$resultArray['dirname_registry'][dirname($rec['ref_string'])][$rec['tablename'].':'.$rec['field']]++;

				// Handle missing file:
			if (!@is_file(PATH_site.$rec['ref_string']))	{
				$resultArray['missingFiles'][$rec['ref_string']][$rec['hash']] = $infoString;
			}

				// Add entry if file has multiple references pointing to it:
			if (isset($tempCount[$rec['ref_string']]))	{
				if (!is_array($resultArray['multipleReferencesList'][$rec['ref_string']]))	{
					$resultArray['multipleReferencesList'][$rec['ref_string']] = array();
					$resultArray['multipleReferencesList'][$rec['ref_string']][$tempCount[$rec['ref_string']][1]] = $tempCount[$rec['ref_string']][0];
				}
				$resultArray['multipleReferencesList'][$rec['ref_string']][$rec['hash']] = $infoString;
			} else {
				$tempCount[$rec['ref_string']] = array($infoString,$rec['hash']);
			}
		}

			// Add count for multi-references:
		$resultArray['multipleReferencesList_count'] = count($resultArray['multipleReferencesList']);
		$resultArray['singleReferencesList_count'] = count($tempCount) - $resultArray['multipleReferencesList_count'];

			// Sort dirname registry and add warnings for directories outside uploads/
		ksort($resultArray['dirname_registry']);
		foreach($resultArray['dirname_registry'] as $dir => $temp)	{
			if (!t3lib_div::isFirstPartOfStr($dir,'uploads/'))	{
				$resultArray['warnings'][] = 'Directory "'.$dir.'" was outside uploads/ which is unusual practice in TYPO3 although not forbidden. Directory used by the following table:field pairs: '.implode(',',array_keys($temp));
			}
		}

		return $resultArray;
	}

	/**
	 * Analyse situation with RTE magic images.
	 *
	 * @return	array
	 */
	function RTEmagic_files_analyze()	{
		global $TYPO3_DB;

			// Initialize result array:
		$resultArray = array(
			'message' => '
Objective: Looking up all occurencies of RTEmagic images in the database and check existence of parent and copy files on the file system plus report possibly lost files of this type.
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- that all RTEmagic image files in the database are registered with the soft reference parser "images"
- images found in deleted records are included (means that you might find lost RTEmagic images after flushing deleted records)
The assumptions are not requirements by the TYPO3 API but reflects the de facto implementation of most TYPO3 installations. ' .
		'However, many custom fields using an RTE will probably not have the "images" soft reference parser registered and so the index will be incomplete and not listing all RTEmagic image files. ' .
		'The consequence of this limitation is that you should be careful if you wish to delete lost RTEmagic images - they could be referenced from a field not parsed by the "images" soft reference parser!' .
		'Another limitation: In theory a RTEmagic image should be used from only one record, however TCEmain does not support this (unfortunately!) so when a record is copied or versionized no new version will be produced. This leads to a usage count of more than one for many RTEmagic images which is also shown in the overview. At this point in time its not considered a bug and there is no fix for it.

NOTICE: Uses the Reference Index Table (sys_refindex) for analysis. Update it before use!',
			'headers' => array(
				'completeFileList' => array('Complete list of used RTEmagic files','Both parent and copy are listed here including usage count (which should in theory all be "1")',1),
				'RTEmagicFilePairs' => array('Statistical info about RTEmagic files','(copy used as index)',0),
				'missingFiles' => array('Missing RTEmagic image files','Have either their parent or copy missing (look that up in RTEmagicFilePairs)',3),
				'lostFiles' => array('Lost RTEmagic files from uploads/','These files you might be able to deleted but only if _all_ RTEmagic images are found by the soft reference parser. If you are using the RTE in third-party extensions it is likely that the soft reference parser is not applied correctly to their RTE and thus these "lost" files actually represent valid RTEmagic images, just not registered.',2),
				'warnings' => array('Warnings picked up','',2)
			),
			'RTEmagicFilePairs' => array(),
			'completeFileList' => array(),
			'missingFiles' => array(),
			'lostFiles' => array(),
			'warnings' => array()
		);

			// Select all RTEmagic files in the reference table (only from soft references of course)
		$recs = $TYPO3_DB->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table='.$TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex').
				' AND ref_string LIKE '.$TYPO3_DB->fullQuoteStr('%/RTEmagic%', 'sys_refindex').
				' AND softref_key='.$TYPO3_DB->fullQuoteStr('images', 'sys_refindex')
		);

			// Traverse the files and put into a large table:
		foreach($recs as $rec)	{
			$filename = basename($rec['ref_string']);
			if (t3lib_div::isFirstPartOfStr($filename,'RTEmagicC_'))	{
				$original = 'RTEmagicP_'.ereg_replace('\.[[:alnum:]]+$','',substr($filename,10));
				$infoString = $rec['tablename'].':'.$rec['recuid'].':'.$rec['field'].':'.$rec['flexpointer'].':'.$rec['softref_key'];
				
					// Build index:
				$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['exists'] = @is_file(PATH_site.$rec['ref_string']);
				$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original'] = substr($rec['ref_string'],0,-strlen($filename)).$original;
				$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original_exists'] = @is_file(PATH_site.$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original']);
				$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['count']++;
				$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['usedIn'][$rec['hash']] = $infoString;

				$resultArray['completeFileList'][$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original']]++;
				$resultArray['completeFileList'][$rec['ref_string']]++;

					// Missing files:
				if (!$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['exists'] || !$resultArray['RTEmagicFilePairs'][$rec['ref_string']]['original_exists'])	{
					$resultArray['missingFiles'][$rec['ref_string']] = $rec['ref_string'];
				}
			}
		}

			// Now, ask for RTEmagic files inside uploads/ folder:
		$resLostFiles = $this->clean_lost_files_analyze();

		if (is_array($resLostFiles['RTEmagicFiles']))	{
			foreach($resLostFiles['RTEmagicFiles'] as $fileName) {
				if (!isset($resultArray['completeFileList'][$fileName])) 	{
					$resultArray['lostFiles'][] = $fileName;
				}
			}
		}

		return $resultArray;
	}

	/**
	 * Find lost files in uploads/ folder
	 *
	 * TODO: Add parameter to exclude filepath
	 * TODO: Add parameter to list more file names/patterns to ignore
	 * TODO: Add parameter to include RTEmagic images
	 *
	 * @return	void
	 */
	function clean_lost_files_analyze()	{
		global $TYPO3_DB;

			// Initialize result array:
		$resultArray = array(
			'message' => '
Objective: Looking for files in the uploads/ folder which does not have a reference in TYPO3 managed records.
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- that all contents in the uploads folder are files attached to TCA records and exclusively managed by TCEmain through "group" type fields
- exceptions are: index.html and .htaccess files (ignored)
- exceptions are: RTEmagic* image files (ignored)
- files found in deleted records are included (otherwise you would see a false list of lost files)
The assumptions are not requirements by the TYPO3 API but reflects the de facto implementation of most TYPO3 installations and therefore a practical approach to cleaning up the uploads/ folder. ' .
		'Therefore, if all "group" type fields in TCA and flexforms are positioned inside the uploads/ folder and if no files inside are managed manually it should be safe to clean out files with no relations found in the system. ' .
		'Under such circumstances there should theoretically be no lost files in the uploads/ folder since TCEmain should have managed relations automatically including adding and deleting files. ' .
		'However, there is at least one reason known to why files might be found lost and that is when FlexForms are used. In such a case a change in the data structure used for the flexform could leave lost files behind. ' .
		'Another scenario could of course be de-installation of extensions which managed files in the uploads/ folders.

NOTICE: Uses the Reference Index Table (sys_refindex) for analysis. Update it before use!',
			'headers' => array(
				'managedFiles' => array('Files related to TYPO3 records and managed by TCEmain','These files you definitely want to keep.',0),
				'ignoredFiles' => array('Ignored files (index.html, .htaccess etc.)','These files are allowed in uploads/ folder',0),
				'RTEmagicFiles' => array('RTE magic images - those found (and ignored)','These files are also allowed in some uploads/ folders as RTEmagic images.',0),
				'lostFiles' => array('Lost files - those you can delete','You can delete these files!',3),
				'warnings' => array('Warnings picked up','',2)
			),
			'managedFiles' => array(),
			'ignoredFiles' => array(),
			'RTEmagicFiles' => array(),
			'lostFiles' => array(),
			'warnings' => array()	
		);

			// Get all files:
		$fileArr = array();
		$fileArr = t3lib_div::getAllFilesAndFoldersInPath($fileArr,PATH_site.'uploads/');
		$fileArr = t3lib_div::removePrefixPathFromList($fileArr,PATH_site);

			// Traverse files and for each, look up if its found in the reference index.
		foreach($fileArr as $key => $value) {

				// First, allow "index.html", ".htaccess" files since they are often used for good reasons
			if (substr($value,-11) == '/index.html' || substr($value,-10) == '/.htaccess')	{
				unset($fileArr[$key])	;
				$resultArray['ignoredFiles'][] = $value;
			} else {
					// Looking for a reference from a field which is NOT a soft reference (thus, only fields with a proper TCA/Flexform configuration)
				$recs = $TYPO3_DB->exec_SELECTgetRows(
					'*',
					'sys_refindex',
					'ref_table='.$TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex').
						' AND ref_string='.$TYPO3_DB->fullQuoteStr($value, 'sys_refindex').
						' AND softref_key='.$TYPO3_DB->fullQuoteStr('', 'sys_refindex')
				);

					// If found, unset entry:
				if (count($recs))		{
					unset($fileArr[$key])	;
					$resultArray['managedFiles'][] = $value;
					if (count($recs)>1)	{
						$resultArray['warnings'][]='Warning: File "'.$value.'" had '.count($recs).' references from group-fields, should have only one!';
					}
				} else {
						// When here it means the file was not found. So we test if it has a RTEmagic-image name and if so, we allow it:
					if (ereg('^RTEmagic[P|C]_',basename($value)))	{
						unset($fileArr[$key])	;
						$resultArray['RTEmagicFiles'][] = $value;
					} else {
							// We conclude that the file is lost...:
						unset($fileArr[$key])	;
						$resultArray['lostFiles'][] = $value;
					}
				}
			}
		}

		// $fileArr variable should now be empty with all contents transferred to the result array keys.

		return $resultArray;
	}

	/**
	 * Find orphan records
	 * VERY CPU and memory intensive since it will look up the whole page tree!
	 *
	 * @return	void
	 */
	function orphan_records_analyze()	{
		global $TYPO3_DB;

		$adminObj = t3lib_div::makeInstance('t3lib_admin');

		$adminObj->genTree_includeDeleted = TRUE;		// if set, genTree() includes deleted pages. This is default.
		$adminObj->genTree_includeVersions = TRUE;		// if set, genTree() includes verisonized pages/records. This is default.
		$adminObj->genTree_includeRecords = TRUE;		// if set, genTree() includes records from pages.
		$adminObj->perms_clause = '';					// extra where-clauses for the tree-selection
		$adminObj->genTree_makeHTML = 0;				// if set, genTree() generates HTML, that visualizes the tree.

$pt = t3lib_div::milliseconds();
		$adminObj->genTree(1,'');

print_r($adminObj->recStats);
		echo strlen(serialize($adminObj->recStats)).chr(10);
		echo (t3lib_div::milliseconds()-$pt).' milliseconds';
exit;

		return $resultArray;
	}











	/**************************
	 *
	 * Helper functions
	 *
	 *************************/

	/**
	 * Formats a result array from a test so it fits HTML output
	 *
	 * @param	string		name of the test (eg. function name)
	 * @param	array		Result array from an analyze function
	 * @param	boolean		Silent flag, if set, will only output when the result array contains data in arrays.
	 * @param	integer		Detail level: 0=all, 1=info and greater, 2=warnings and greater, 3=errors
	 * @return	string		HTML
	 */
	function html_printInfo($header,$res,$silent=FALSE,$detailLevel=0)	{

		if (!$silent) {
				// Name:
			$output.= '<h3>'.htmlspecialchars($header).'</h3>';

				// Message:
			$output.= nl2br(htmlspecialchars(trim($res['message']))).'<hr/>';
		}

			// Traverse headers for output:
		foreach($res['headers'] as $key => $value)	{

			if ($detailLevel <= intval($value[2]))	{
				if (!$silent || (is_array($res[$key]) && count($res[$key]))) {
						// Header and explanaion:
					$output.= '<b>'.
							($silent ? '<i>'.htmlspecialchars($header).'</i><br/>' : '').
							(is_array($res[$key]) && count($res[$key]) ? $GLOBALS['SOBE']->doc->icons($value[2]) : '').
							htmlspecialchars($value[0]).
							'</b><br/>';
					if (trim($value[1]))	{
						$output.= '<em>'.htmlspecialchars(trim($value[1])).'</em><br/>';
					}
					$output.='<br/>';
				}
	
					// Content:
				if (is_array($res[$key]))	{
					if (count($res[$key]))	{
						$output.= t3lib_div::view_array($res[$key]).'<br/><br/>';
					} else {
						if (!$silent) $output.= '(None)'.'<br/><br/>';
					}
				} else {
					if (!$silent) $output.= htmlspecialchars($res[$key]).'<br/><br/>';
				}
			}
		}
		
		return $output;
	}














	/**************************
	 *
	 * CLI functionality
	 *
	 *************************/

	/**
	 * CLI engine
	 *
	 * @param	array		Command line arguments
	 * @return	string
	 */
	function cli_main($argv) {

		if (in_array('-h',$argv))	{
			echo "
		Options:
		-h = This help screen.
		";
			exit;
		}


#		$silentFlag = TRUE;
		$filter = 1;

			// Missing files:
#		$res = $this->missing_files_analyze();
#		$this->cli_printInfo('missing_files_analyze()', $res, $silentFlag, $filter);

			// Missing relations:
#		$res = $this->missing_relations_analyze();
#		$this->cli_printInfo('missing_relations_analyze()', $res, $silentFlag, $filter);

			// Double references
#		$res = $this->double_files_analyze();
#		$this->cli_printInfo('double_files_analyze()', $res, $silentFlag, $filter);

			// RTE images
#		$res = $this->RTEmagic_files_analyze();
#		$this->cli_printInfo('RTEmagic_files_analyze()', $res, $silentFlag, $filter);

			// Lost files:
#		$res = $this->clean_lost_files_analyze();
#		$this->cli_printInfo('clean_lost_files_analyze()', $res, $silentFlag, $filter);

		$res = $this->orphan_records_analyze();
		$this->cli_printInfo('orphan_records_analyze()', $res, $silentFlag, $filter);
		
#			ob_start();
#			$output.= ob_get_contents().chr(10);
#			ob_end_clean();
	}

	/**
	 * Formats a result array from a test so it fits output in the shell
	 *
	 * @param	string		name of the test (eg. function name)
	 * @param	array		Result array from an analyze function
	 * @param	boolean		Silent flag, if set, will only output when the result array contains data in arrays.
	 * @param	integer		Detail level: 0=all, 1=info and greater, 2=warnings and greater, 3=errors
	 * @return	void			Outputs with echo - capture content with output buffer if needed.
	 */
	function cli_printInfo($header,$res,$silent=FALSE,$detailLevel=0)	{

		if (!$silent) {
				// Name:
			echo chr(10).'*********************************************'.chr(10).$header.chr(10).'*********************************************'.chr(10);

				// Message:
			echo trim($res['message']).chr(10).chr(10);
		}

			// Traverse headers for output:
		foreach($res['headers'] as $key => $value)	{

			if ($detailLevel <= intval($value[2]))	{
				if (!$silent || (is_array($res[$key]) && count($res[$key]))) {
						// Header and explanaion:
					echo '---------------------------------------------'.chr(10).
							($silent ? '['.$header.']'.chr(10) : '').
							$value[0].' ['.$value[2].']'.chr(10).
							'---------------------------------------------'.chr(10);
					if (trim($value[1]))	{
						echo '[Explanation: '.trim($value[1]).']'.chr(10);
					}
				}
	
					// Content:
				if (is_array($res[$key]))	{
					if (count($res[$key]))	{
						print_r($res[$key]);
					} else {
						if (!$silent) echo '(None)'.chr(10).chr(10);
					}
				} else {
					if (!$silent) echo $res[$key].chr(10).chr(10);
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/class.tx_lowlevel_cleaner.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/class.tx_lowlevel_cleaner.php']);
}
?>