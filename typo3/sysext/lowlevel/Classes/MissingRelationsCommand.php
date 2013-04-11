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
 * Cleaner module: Missing relations
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Looking for missing relations.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class MissingRelationsCommand extends CleanerCommand {

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
		// Setting up help:
		$this->cli_help['name'] = 'missing_relations -- Find all record references pointing to a non-existing record.';
		$this->cli_help['description'] = trim('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- all database references to check are integers greater than zero
- does not check if a referenced record is inside an offline branch, another workspace etc. which could make the reference useless in reality or otherwise question integrity
Records may be missing for these reasons (except software bugs):
- someone deleted the record which is technically not an error although it might be a mistake that someone did so.
- after flushing published versions and/or deleted-flagged records a number of new missing references might appear; those were pointing to records just flushed.

Automatic Repair of Errors:
- Only managed references are repaired (TCA-configured).
- Offline Version Records and Non Existing Records: Reference is removed

Manual repair suggestions:
- For soft references you should investigate each case and edit the content accordingly.
- References to deleted records can theoretically be removed since a deleted record cannot be selected and hence your website should not be affected by removal of the reference. On the other hand it does not hurt to ignore it for now. To have this automatically fixed you must first flush the deleted records after which remaining references will appear as pointing to Non Existing Records and can now be removed with the automatic fix.

NOTICE: Uses the Reference Index Table (sys_refindex) for analysis. Update it before use!
');
		$this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner missing_relations -s -r
Reports missing relations';
	}

	/**
	 * Find relations pointing to non-existing records
	 * Fix methods: API in \TYPO3\CMS\Core\Database\ReferenceIndex that allows to
	 * change the value of a reference (or remove it) [Only for managed relations!]
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function main() {
		global $TYPO3_DB;
		// Initialize result array:
		$listExplain = ' Shows the missing record as header and underneath a list of record fields in which the references are found. ' . $this->label_infoString;
		$resultArray = array(
			'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
			'headers' => array(
				'offlineVersionRecords_m' => array('Offline version records (managed)', 'These records are offline versions having a pid=-1 and references should never occur directly to their uids.' . $listExplain, 3),
				'deletedRecords_m' => array('Deleted-flagged records (managed)', 'These records are deleted with a flag but references are still pointing at them. Keeping the references is useful if you undelete the referenced records later, otherwise the references are lost completely when the deleted records are flushed at some point. Notice that if those records listed are themselves deleted (marked with "DELETED") it is not a problem.' . $listExplain, 2),
				'nonExistingRecords_m' => array('Non-existing records to which there are references (managed)', 'These references can safely be removed since there is no record found in the database at all.' . $listExplain, 3),
				// 3 = error
				'offlineVersionRecords_s' => array('Offline version records (softref)', 'See above.' . $listExplain, 2),
				'deletedRecords_s' => array('Deleted-flagged records (softref)', 'See above.' . $listExplain, 2),
				'nonExistingRecords_s' => array('Non-existing records to which there are references (softref)', 'See above.' . $listExplain, 2)
			),
			'offlineVersionRecords_m' => array(),
			'deletedRecords_m' => array(),
			'nonExistingRecords_m' => array(),
			'offlineVersionRecords_s' => array(),
			'deletedRecords_s' => array(),
			'nonExistingRecords_s' => array()
		);
		// Select DB relations from reference table
		$recs = $TYPO3_DB->exec_SELECTgetRows('*', 'sys_refindex', 'ref_table<>' . $TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex') . ' AND ref_uid>0' . $filterClause, '', 'sorting DESC');
		// Traverse the records
		$tempExists = array();
		if (is_array($recs)) {
			foreach ($recs as $rec) {
				$suffix = $rec['softref_key'] != '' ? '_s' : '_m';
				$idx = $rec['ref_table'] . ':' . $rec['ref_uid'];
				// Get referenced record:
				if (!isset($tempExists[$idx])) {
					$tempExists[$idx] = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw($rec['ref_table'], 'uid=' . intval($rec['ref_uid']), 'uid,pid' . ($GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] ? ',' . $GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] : ''));
				}
				// Compile info string for location of reference:
				$infoString = $this->infoStr($rec);
				// Handle missing file:
				if ($tempExists[$idx]['uid']) {
					if ($tempExists[$idx]['pid'] == -1) {
						$resultArray['offlineVersionRecords' . $suffix][$idx][$rec['hash']] = $infoString;
						ksort($resultArray['offlineVersionRecords' . $suffix][$idx]);
					} elseif ($GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete'] && $tempExists[$idx][$GLOBALS['TCA'][$rec['ref_table']]['ctrl']['delete']]) {
						$resultArray['deletedRecords' . $suffix][$idx][$rec['hash']] = $infoString;
						ksort($resultArray['deletedRecords' . $suffix][$idx]);
					}
				} else {
					$resultArray['nonExistingRecords' . $suffix][$idx][$rec['hash']] = $infoString;
					ksort($resultArray['nonExistingRecords' . $suffix][$idx]);
				}
			}
		}
		ksort($resultArray['offlineVersionRecords_m']);
		ksort($resultArray['deletedRecords_m']);
		ksort($resultArray['nonExistingRecords_m']);
		ksort($resultArray['offlineVersionRecords_s']);
		ksort($resultArray['deletedRecords_s']);
		ksort($resultArray['nonExistingRecords_s']);
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
		$trav = array('offlineVersionRecords_m', 'nonExistingRecords_m');
		foreach ($trav as $tk) {
			echo 'Processing managed "' . $tk . '"...' . LF;
			foreach ($resultArray[$tk] as $key => $value) {
				foreach ($value as $hash => $recReference) {
					echo '	Removing reference to ' . $key . ' in record "' . $recReference . '": ';
					if ($bypass = $this->cli_noExecutionCheck($recReference)) {
						echo $bypass;
					} else {
						$sysRefObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
						$error = $sysRefObj->setReferenceValue($hash, NULL);
						if ($error) {
							echo '		TYPO3\\CMS\\Core\\Database\\ReferenceIndex::setReferenceValue(): ' . $error . LF;
						} else {
							echo 'DONE';
						}
					}
					echo LF;
				}
			}
		}
	}

}


?>