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
 * Cleaner module: Orphan records
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_lowlevel_orphan_records extends tx_lowlevel_cleaner_core
 *   63:     function tx_lowlevel_orphan_records()
 *   96:     function main()
 *  151:     function main_autoFix($resultArray)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Looking for Orphan Records
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class tx_lowlevel_orphan_records extends tx_lowlevel_cleaner_core {

	/**
	 * Constructor
	 *
	 * @return	[type]		...
	 */
	function tx_lowlevel_orphan_records()	{
		parent::tx_lowlevel_cleaner_core();

			// Setting up help:
		$this->cli_options[] = array('--echotree level', 'When "level" is set to 1 or higher you will see the page of the page tree outputted as it is traversed. A value of 2 for "level" will show even more information.');
		$this->cli_help['name'] = 'orphan_records -- To find records that has lost their connection with the page tree';
		$this->cli_help['description'] = trim('
Assumptions:
- That all actively used records on the website from TCA configured tables are located in the page tree exclusively.

All records managed by TYPO3 via the TCA array configuration has to belong to a page in the page tree, either directly or indirectly as a version of another record.
VERY TIME, CPU and MEMORY intensive operation since the full page tree is looked up!

Automatic Repair of Errors:
- Silently deleting the orphaned records. In theory they should not be used anywhere in the system, but there could be references. See below for more details on this matter.

Manual repair suggestions:
- Possibly re-connect orphaned records to page tree by setting their "pid" field to a valid page id. A lookup in the sys_refindex table can reveal if there are references to a orphaned record. If there are such references (from records that are not themselves orphans) you might consider to re-connect the record to the page tree, otherwise it should be safe to delete it.
');
		$this->cli_help['todo'] = trim('
- Implement a check for references to orphaned records and if a reference comes from a record that is not orphaned itself, we might rather like to re-connect the record to the page tree.
- Implement that orphans can be fixed by setting the PID to a certain page instead of deleting.');

		$this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner orphan_records -s -r
Will report orphan uids from TCA tables.';
	}

	/**
	 * Find orphan records
	 * VERY CPU and memory intensive since it will look up the whole page tree!
	 *
	 * @return	array
	 */
	function main() {
		global $TYPO3_DB;

			// Initialize result array:
		$resultArray = array(
			'message' => $this->cli_help['name'].LF.LF.$this->cli_help['description'],
			'headers' => array(
				'orphans' => array('Index of orphaned records','',3),
				'misplaced_at_rootlevel' => array('Records that should not be at root level but are.','Fix manually by moving record into page tree',2),
				'misplaced_inside_tree' => array('Records that should be at root level but are not.','Fix manually by moving record to tree root',2),
				'illegal_record_under_versioned_page' => array('Records that cannot be attached to a versioned page','(Listed under orphaned records so is fixed along with orphans.)',2),
			),
			'orphans' => array(),
			'misplaced_at_rootlevel' => array(),				// Subset of "all": Those that should not be at root level but are. [Warning: Fix by moving record into page tree]
			'misplaced_inside_tree' => array(),					// Subset of "all": Those that are inside page tree but should be at root level [Warning: Fix by setting PID to zero]
			'illegal_record_under_versioned_page' => array(),
		);

		$startingPoint = 0;	// zero = tree root, must use tree root if you wish to reverse selection to find orphans!
		$pt = t3lib_div::milliseconds();

		$this->genTree($startingPoint,1000,(int)$this->cli_argValue('--echotree'));

		$resultArray['misplaced_at_rootlevel'] = $this->recStats['misplaced_at_rootlevel'];
		$resultArray['misplaced_inside_tree'] = $this->recStats['misplaced_inside_tree'];
		$resultArray['illegal_record_under_versioned_page'] = $this->recStats['illegal_record_under_versioned_page'];

			// Find orphans:
		foreach($GLOBALS['TCA'] as $tableName => $cfg)	{

			$idList = is_array($this->recStats['all'][$tableName]) && count($this->recStats['all'][$tableName]) ? implode(',',$this->recStats['all'][$tableName]) : 0;

				// Select all records belonging to page:
			$orphanRecords = 	$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
									'uid',
									$tableName,
									'uid NOT IN ('.$idList.')',
									'','uid','','uid'
								);

			if (count($orphanRecords))	{
				$resultArray['orphans'][$tableName] = array();
				foreach($orphanRecords as $oR)	{
					$resultArray['orphans'][$tableName][$oR['uid']] = $oR['uid'];
				}
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

			// Putting "pages" table in the bottom:
		if (isset($resultArray['orphans']['pages']))	{
			$_pages = $resultArray['orphans']['pages'];
			unset($resultArray['orphans']['pages']);
			$resultArray['orphans']['pages'] = $_pages;
		}

			// Traversing records:
		foreach($resultArray['orphans'] as $table => $list)	{
			echo 'Removing orphans from table "'.$table.'":'.LF;
			foreach($list as $uid)	{
				echo '	Flushing orphan record "'.$table.':'.$uid.'": ';
				if ($bypass = $this->cli_noExecutionCheck($table.':'.$uid))	{
					echo $bypass;
				} else {

						// Execute CMD array:
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values = FALSE;
					$tce->start(array(),array());
					$tce->deleteRecord($table,$uid, TRUE, TRUE);	// Notice, we are deleting pages with no regard to subpages/subrecords - we do this since they should also be included in the set of orphans of course!

						// Return errors if any:
					if (count($tce->errorLog))	{
						echo '	ERROR from "TCEmain":'.LF.'TCEmain:'.implode(LF.'TCEmain:',$tce->errorLog);
					} else echo 'DONE';
				}
				echo LF;
			}
		}
	}
}

?>