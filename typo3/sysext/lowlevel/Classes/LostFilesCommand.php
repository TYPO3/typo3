<?php
namespace TYPO3\CMS\Lowlevel;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Cleaner module: Lost files
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Looking for Lost files
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class LostFilesCommand extends CleanerCommand {

	/**
	 * @todo Define visibility
	 */
	public $checkRefIndex = TRUE;

	/**
	 * Constructor
	 *
	 * @todo Define visibility
	 */
	public function __construct() {
		parent::__construct();
		$this->cli_options[] = array('--excludePath [path-list]', 'Comma separated list of paths to exclude. Example: "uploads/[path1],uploads/[path2],..."');
		// Setting up help:
		$this->cli_help['name'] = 'lost_files -- Looking for files in the uploads/ folder which does not have a reference in TYPO3 managed records.';
		$this->cli_help['description'] = trim('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- that all contents in the uploads folder are files attached to TCA records and exclusively managed by TCEmain through "group" type fields
- exceptions are: index.html and .htaccess files (ignored)
- exceptions are: RTEmagic* image files (ignored)
- files found in deleted records are included (otherwise you would see a false list of lost files)

The assumptions are not requirements by the TYPO3 API but reflects the de facto implementation of most TYPO3 installations and therefore a practical approach to cleaning up the uploads/ folder.
Therefore, if all "group" type fields in TCA and flexforms are positioned inside the uploads/ folder and if no files inside are managed manually it should be safe to clean out files with no relations found in the system.
Under such circumstances there should theoretically be no lost files in the uploads/ folder since TCEmain should have managed relations automatically including adding and deleting files.
However, there is at least one reason known to why files might be found lost and that is when FlexForms are used. In such a case a change of/in the Data Structure XML (or the ability of the system to find the Data Structure definition!) used for the flexform could leave lost files behind. This is not unlikely to happen when records are deleted. More details can be found in a note to the function TYPO3\\CMS\\Backend\\Utility\\BackendUtility::getFlexFormDS()
Another scenario could of course be de-installation of extensions which managed files in the uploads/ folders.

Automatic Repair of Errors:
- Simply delete lost files (Warning: First, make sure those files are not used somewhere TYPO3 does not know about! See the assumptions above).
');
		$this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner lost_files -s -r
Will report lost files.';
	}

	/**
	 * Find lost files in uploads/ folder
	 * FIX METHOD: Simply delete the file...
	 *
	 * TODO: Add parameter to exclude filepath
	 * TODO: Add parameter to list more file names/patterns to ignore
	 * TODO: Add parameter to include RTEmagic images
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function main() {
		global $TYPO3_DB;
		// Initialize result array:
		$resultArray = array(
			'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
			'headers' => array(
				'managedFiles' => array('Files related to TYPO3 records and managed by TCEmain', 'These files you definitely want to keep.', 0),
				'ignoredFiles' => array('Ignored files (index.html, .htaccess etc.)', 'These files are allowed in uploads/ folder', 0),
				'RTEmagicFiles' => array('RTE magic images - those found (and ignored)', 'These files are also allowed in some uploads/ folders as RTEmagic images.', 0),
				'lostFiles' => array('Lost files - those you can delete', 'You can delete these files!', 3),
				'warnings' => array('Warnings picked up', '', 2)
			),
			'managedFiles' => array(),
			'ignoredFiles' => array(),
			'RTEmagicFiles' => array(),
			'lostFiles' => array(),
			'warnings' => array()
		);
		// Get all files:
		$fileArr = array();
		$fileArr = \TYPO3\CMS\Core\Utility\GeneralUtility::getAllFilesAndFoldersInPath($fileArr, PATH_site . 'uploads/');
		$fileArr = \TYPO3\CMS\Core\Utility\GeneralUtility::removePrefixPathFromList($fileArr, PATH_site);
		$excludePaths = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->cli_argValue('--excludePath', 0), 1);
		// Traverse files and for each, look up if its found in the reference index.
		foreach ($fileArr as $key => $value) {
			$include = TRUE;
			foreach ($excludePaths as $exclPath) {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($value, $exclPath)) {
					$include = FALSE;
				}
			}
			$shortKey = \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5($value);
			if ($include) {
				// First, allow "index.html", ".htaccess" files since they are often used for good reasons
				if (substr($value, -11) == '/index.html' || substr($value, -10) == '/.htaccess') {
					unset($fileArr[$key]);
					$resultArray['ignoredFiles'][$shortKey] = $value;
				} else {
					// Looking for a reference from a field which is NOT a soft reference (thus, only fields with a proper TCA/Flexform configuration)
					$recs = $TYPO3_DB->exec_SELECTgetRows('*', 'sys_refindex', 'ref_table=' . $TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex') . ' AND ref_string=' . $TYPO3_DB->fullQuoteStr($value, 'sys_refindex') . ' AND softref_key=' . $TYPO3_DB->fullQuoteStr('', 'sys_refindex'), '', 'sorting DESC');
					// If found, unset entry:
					if (count($recs)) {
						unset($fileArr[$key]);
						$resultArray['managedFiles'][$shortKey] = $value;
						if (count($recs) > 1) {
							$resultArray['warnings'][$shortKey] = 'Warning: File "' . $value . '" had ' . count($recs) . ' references from group-fields, should have only one!';
						}
					} else {
						// When here it means the file was not found. So we test if it has a RTEmagic-image name and if so, we allow it:
						if (preg_match('/^RTEmagic[P|C]_/', basename($value))) {
							unset($fileArr[$key]);
							$resultArray['RTEmagicFiles'][$shortKey] = $value;
						} else {
							// We conclude that the file is lost...:
							unset($fileArr[$key]);
							$resultArray['lostFiles'][$shortKey] = $value;
						}
					}
				}
			}
		}
		asort($resultArray['ignoredFiles']);
		asort($resultArray['managedFiles']);
		asort($resultArray['RTEmagicFiles']);
		asort($resultArray['lostFiles']);
		asort($resultArray['warnings']);
		// $fileArr variable should now be empty with all contents transferred to the result array keys.
		return $resultArray;
	}

	/**
	 * Mandatory autofix function
	 * Will run auto-fix on the result array. Echos status during processing.
	 *
	 * @param array $resultArray Result array from main() function
	 * @return void
	 * @todo Define visibility
	 */
	public function main_autoFix($resultArray) {
		foreach ($resultArray['lostFiles'] as $key => $value) {
			$absFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($value);
			echo 'Deleting file: "' . $absFileName . '": ';
			if ($bypass = $this->cli_noExecutionCheck($absFileName)) {
				echo $bypass;
			} else {
				if ($absFileName && @is_file($absFileName)) {
					unlink($absFileName);
					echo 'DONE';
				} else {
					echo '	ERROR: File "' . $absFileName . '" was not found!';
				}
			}
			echo LF;
		}
	}

}


?>