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
 * Cleaner module: Double Files
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   58: class tx_lowlevel_double_files extends tx_lowlevel_cleaner_core
 *   67:     function tx_lowlevel_double_files()
 *   99:     function main()
 *  182:     function main_autoFix($resultArray)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * Looking for double files
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class tx_lowlevel_double_files extends tx_lowlevel_cleaner_core {

	var $checkRefIndex = TRUE;

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function tx_lowlevel_double_files()	{
		parent::tx_lowlevel_cleaner_core();

			// Setting up help:
		$this->cli_help['name'] = 'double_files -- Looking for files from TYPO3 managed records which are referenced more than one time (only one time allowed)';
		$this->cli_help['description'] = trim('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- files found in deleted records are included (otherwise you would see a false list of lost files)

Files attached to records in TYPO3 using a "group" type configuration in TCA or FlexForm DataStructure are managed exclusively by the system and there must always exist a 1-1 reference between the file and the reference in the record.
This tool will expose when such files are referenced from multiple locations which is considered an integrity error.
If a multi-reference is found it was typically created because the record was copied or modified outside of TCEmain which will otherwise maintain the relations correctly.
Multi-references should be resolved to 1-1 references as soon as possible. The danger of keeping multi-references is that if the file is removed from one of the refering records it will actually be deleted in the file system, leaving missing files for the remaining referers!

Automatic Repair of Errors:
- The multi-referenced file is copied under a new name and references updated.

Manual repair suggestions:
- None that can not be handled by the automatic repair.
');

		$this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner double_files -s -r
This will check the system for double files relations.';
	}

	/**
	 * Find managed files which are referred to more than one time
	 * Fix methods: API in t3lib_refindex that allows to change the value of a reference (we could copy the file) or remove reference
	 *
	 * @return	array
	 */
	function main() {
		global $TYPO3_DB;

			// Initialize result array:
		$resultArray = array(
			'message' => $this->cli_help['name'].LF.LF.$this->cli_help['description'],
			'headers' => array(
				'multipleReferencesList_count' => array('Number of multi-reference files','(See below)',0),
				'singleReferencesList_count' => array('Number of files correctly referenced','The amount of correct 1-1 references',0),
				'multipleReferencesList' => array('Entries with files having multiple references','These are serious problems that should be resolved ASAP to prevent data loss! '.$this->label_infoString,3),
				'dirname_registry' => array('Registry of directories in which files are found.','Registry includes which table/field pairs store files in them plus how many files their store.',0),
				'missingFiles' => array('Tracking missing files','(Extra feature, not related to tracking of double references. Further, the list may include more files than found in the missing_files()-test because this list includes missing files from deleted records.)',0),
				'warnings' => array('Warnings picked up','',2)
			),
			'multipleReferencesList_count' => array('count' => 0),
			'singleReferencesList_count' => array('count' => 0),
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
				' AND softref_key='.$TYPO3_DB->fullQuoteStr('', 'sys_refindex'),
			'',
			'sorting DESC'
		);

			// Traverse the files and put into a large table:
		$tempCount = array();
		if (is_array($recs)) {
			foreach($recs as $rec)	{

					// Compile info string for location of reference:
				$infoString = $this->infoStr($rec);

					// Registering occurencies in directories:
				$resultArray['dirname_registry'][dirname($rec['ref_string'])][$rec['tablename'].':'.$rec['field']]++;

					// Handle missing file:
				if (!@is_file(PATH_site.$rec['ref_string']))	{
					$resultArray['missingFiles'][$rec['ref_string']][$rec['hash']] = $infoString;
					ksort($resultArray['missingFiles'][$rec['ref_string']]);	// Sort by array key
				}

					// Add entry if file has multiple references pointing to it:
				if (isset($tempCount[$rec['ref_string']]))	{
					if (!is_array($resultArray['multipleReferencesList'][$rec['ref_string']]))	{
						$resultArray['multipleReferencesList'][$rec['ref_string']] = array();
						$resultArray['multipleReferencesList'][$rec['ref_string']][$tempCount[$rec['ref_string']][1]] = $tempCount[$rec['ref_string']][0];
					}
					$resultArray['multipleReferencesList'][$rec['ref_string']][$rec['hash']] = $infoString;
					ksort($resultArray['multipleReferencesList'][$rec['ref_string']]);
				} else {
					$tempCount[$rec['ref_string']] = array($infoString,$rec['hash']);
				}
			}
		}

		ksort($resultArray['missingFiles']);
		ksort($resultArray['multipleReferencesList']);

			// Add count for multi-references:
		$resultArray['multipleReferencesList_count']['count'] = count($resultArray['multipleReferencesList']);
		$resultArray['singleReferencesList_count']['count'] = count($tempCount) - $resultArray['multipleReferencesList_count']['count'];

			// Sort dirname registry and add warnings for directories outside uploads/
		ksort($resultArray['dirname_registry']);
		foreach($resultArray['dirname_registry'] as $dir => $temp)	{
			ksort($resultArray['dirname_registry'][$dir]);
			if (!t3lib_div::isFirstPartOfStr($dir,'uploads/'))	{
				$resultArray['warnings'][t3lib_div::shortmd5($dir)] = 'Directory "'.$dir.'" was outside uploads/ which is unusual practice in TYPO3 although not forbidden. Directory used by the following table:field pairs: '.implode(',',array_keys($temp));
			}
		}

		return $resultArray;
	}

	/**
	 * Mandatory autofix function
	 * Will run auto-fix on the result array. Echos status during processing.
	 *
	 * @param	array		Result array from main() function
	 * @return	void
	 */
	function main_autoFix($resultArray)	{
		foreach($resultArray['multipleReferencesList'] as $key => $value)	{
			$absFileName = t3lib_div::getFileAbsFileName($key);
			if ($absFileName && @is_file($absFileName))	{
				echo 'Processing file: '.$key.LF;
				$c=0;
				foreach($value as $hash => $recReference)	{
					if ($c==0)	{
						echo '	Keeping '.$key.' for record "'.$recReference.'"'.LF;
					} else {
							// Create unique name for file:
						$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
						$newName = $fileFunc->getUniqueName(basename($key), dirname($absFileName));
						echo '	Copying '.$key.' to '.substr($newName,strlen(PATH_site)).' for record "'.$recReference.'": ';

						if ($bypass = $this->cli_noExecutionCheck($recReference))	{
							echo $bypass;
						} else {
							t3lib_div::upload_copy_move($absFileName,$newName);
							clearstatcache();

							if (@is_file($newName))	{
								$sysRefObj = t3lib_div::makeInstance('t3lib_refindex');
								$error = $sysRefObj->setReferenceValue($hash,basename($newName));
								if ($error)	{
									echo '	ERROR:	t3lib_refindex::setReferenceValue(): '.$error.LF;
									exit;
								} else echo "DONE";
							} else {
								echo '	ERROR: File "'.$newName.'" was not created!';
							}
						}
						echo LF;
					}
					$c++;
				}
			} else {
				echo '	ERROR: File "'.$absFileName.'" was not found!';
			}
		}
	}
}

?>